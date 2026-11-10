<?php
/**
 * Реестр фоновых задач (кронов)
 *
 * Ядро хранит только зарегистрированные задачи и время их последнего запуска.
 * Оно не знает, какой плагин какую задачу добавил и что эта задача делает.
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

/** Сколько живёт заявка виртуального крона, если HTTP-запрос не дошёл. */
if (!defined('CRON_PENDING_TTL')) {
    define('CRON_PENDING_TTL', 900);
}

/** Пауза после неудачного запуска, чтобы не повторять задачу на каждом запросе. */
if (!defined('CRON_RETRY_COOLDOWN')) {
    define('CRON_RETRY_COOLDOWN', 300);
}

/**
 * Регистрирует фоновую задачу.
 *
 * @param string   $id      Уникальный ID задачи (латиница, цифры, дефис, подчёркивание)
 * @param callable $callback Функция-обработчик. Должна вернуть true при успехе.
 * @param array    $options  Опции: interval (секунды), virtual (участие в виртуальном кроне),
 *                           enabled (задача активна), log (писать cron_run при успехе), label
 * @return void
 */
function register_cron_task(string $id, callable $callback, array $options = []): void {
    if (!_is_valid_hook_name($id)) {
        return;
    }

    if (!isset($GLOBALS['cms_cron_tasks'])) {
        $GLOBALS['cms_cron_tasks'] = [];
    }

    $GLOBALS['cms_cron_tasks'][$id] = [
        'id'       => $id,
        'callback' => $callback,
        'interval' => max(0, (int)($options['interval'] ?? 0)),
        'virtual'  => !empty($options['virtual']),
        'enabled'  => !array_key_exists('enabled', $options) || !empty($options['enabled']),
        'log'      => !array_key_exists('log', $options) || !empty($options['log']),
        'label'    => (string)($options['label'] ?? $id),
    ];
}

/**
 * Возвращает все зарегистрированные задачи.
 *
 * @return array
 */
function get_cron_tasks(): array {
    return $GLOBALS['cms_cron_tasks'] ?? [];
}

/**
 * Выполняет зарегистрированную задачу по её ID.
 *
 * @param string $id ID задачи
 * @return bool true, если задача найдена и выполнилась успешно
 */
function run_cron_task(string $id): bool {
    $tasks = get_cron_tasks();

    if (!isset($tasks[$id])) {
        return false;
    }

    try {
        $result = call_user_func($tasks[$id]['callback']);
    } catch (Throwable $e) {
        return false;
    }

    return $result !== false;
}

/**
 * Возвращает путь к файлу состояния кронов.
 *
 * @return string
 */
function get_cron_state_file(): string {
    $configDir = defined('CONFIG_DIR') ? CONFIG_DIR : APP_ROOT . DIRECTORY_SEPARATOR . 'config';
    return $configDir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cron_state.json';
}

/**
 * Читает состояние кронов.
 *
 * @return array
 */
function get_cron_state(): array {
    $file = get_cron_state_file();

    if (!file_exists($file)) {
        return [];
    }

    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

/**
 * Атомарно читает и изменяет состояние кронов.
 *
 * Чтение и запись выполняются под одной эксклюзивной блокировкой: два
 * одновременных запроса не потеряют изменения друг друга.
 *
 * @param callable $mutator function (array &$state): mixed — меняет состояние
 * @return mixed Результат $mutator или null, если файл недоступен
 */
function _cron_update_state(callable $mutator) {
    $file = get_cron_state_file();
    $dir = dirname($file);

    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return null;
    }

    $handle = @fopen($file, 'c+');
    if (!$handle) {
        return null;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return null;
    }

    $raw = stream_get_contents($handle);
    $state = json_decode((string)$raw, true);
    if (!is_array($state)) {
        $state = [];
    }

    $result = $mutator($state);

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return $result;
}

/**
 * Сохраняет время последнего запуска задачи.
 *
 * Остальные поля записи (pending, retry_after) сохраняются.
 *
 * @param string $id        ID задачи
 * @param int    $timestamp Unix timestamp
 * @return void
 */
function set_cron_last_run(string $id, int $timestamp): void {
    if (!_is_valid_hook_name($id)) {
        return;
    }

    _cron_update_state(function (array &$state) use ($id, $timestamp) {
        if (!isset($state[$id]) || !is_array($state[$id])) {
            $state[$id] = [];
        }
        $state[$id]['last_run'] = $timestamp;
    });
}

/**
 * Заявляет запуск задачи из виртуального крона.
 *
 * Возвращает одноразовый токен, если интервал подошёл и никто другой уже
 * не отправил запрос. Токен передаётся в cron/run.php и подтверждает,
 * что запуск инициирован именно этой заявкой.
 *
 * @param string $id ID задачи
 * @return string Токен запуска или '' если запускать не нужно
 */
function mark_cron_pending(string $id): string {
    if (!_is_valid_hook_name($id)) {
        return '';
    }

    $tasks = get_cron_tasks();
    if (!isset($tasks[$id])) {
        return '';
    }

    $interval = (int)$tasks[$id]['interval'];
    $now = time();

    $token = _cron_update_state(function (array &$state) use ($id, $interval, $now) {
        $entry = (isset($state[$id]) && is_array($state[$id])) ? $state[$id] : [];

        if ((int)($entry['retry_after'] ?? 0) > $now) {
            return '';
        }

        $pendingAt = (int)($entry['pending'] ?? 0);
        if ($pendingAt > 0 && ($pendingAt + CRON_PENDING_TTL) > $now) {
            return '';
        }

        if ($interval > 0 && $now < (int)($entry['last_run'] ?? 0) + $interval) {
            return '';
        }

        $runToken = bin2hex(random_bytes(8));
        $state[$id] = $entry;
        $state[$id]['pending'] = $now;
        $state[$id]['pending_token'] = $runToken;

        return $runToken;
    });

    return is_string($token) ? $token : '';
}

/**
 * Захватывает запуск задачи в cron/run.php.
 *
 * С токеном виртуального крона запуск разрешён, пока заявка жива.
 * Без токена (внешний планировщик) — только когда подошёл интервал
 * и нет живой заявки от виртуального крона.
 *
 * @param string $id    ID задачи
 * @param string $token Токен виртуального крона ('' для внешнего вызова)
 * @return bool true, если запуск разрешён и отмечен
 */
function claim_cron_task_run(string $id, string $token = ''): bool {
    if (!_is_valid_hook_name($id)) {
        return false;
    }

    $tasks = get_cron_tasks();
    if (!isset($tasks[$id])) {
        return false;
    }

    $interval = (int)$tasks[$id]['interval'];
    $now = time();

    $claimed = _cron_update_state(function (array &$state) use ($id, $interval, $now, $token) {
        $entry = (isset($state[$id]) && is_array($state[$id])) ? $state[$id] : [];

        if ((int)($entry['retry_after'] ?? 0) > $now) {
            return false;
        }

        $allowed = false;

        $pendingToken = (string)($entry['pending_token'] ?? '');
        $pendingAt = (int)($entry['pending'] ?? 0);
        $pendingAlive = $pendingToken !== '' && ($pendingAt + CRON_PENDING_TTL) > $now;

        if ($token !== '') {
            $allowed = $pendingAlive && hash_equals($pendingToken, $token);
        } else {
            // Пока жива заявка виртуального крона, внешний планировщик ждёт:
            // иначе оба запустят задачу одновременно.
            $allowed = !$pendingAlive
                && ($interval <= 0 || $now >= ((int)($entry['last_run'] ?? 0) + $interval));
        }

        if (!$allowed) {
            return false;
        }

        $entry['claimed_prev'] = (int)($entry['last_run'] ?? 0);
        $entry['last_run'] = $now;
        unset($entry['pending'], $entry['pending_token'], $entry['retry_after']);

        $state[$id] = $entry;

        return true;
    });

    return $claimed === true;
}

/**
 * Подтверждает успешный запуск и убирает служебные поля захвата.
 *
 * @param string $id ID задачи
 * @return void
 */
function confirm_cron_task_run(string $id): void {
    if (!_is_valid_hook_name($id)) {
        return;
    }

    _cron_update_state(function (array &$state) use ($id) {
        if (!isset($state[$id]) || !is_array($state[$id])) {
            return;
        }

        unset($state[$id]['claimed_prev'], $state[$id]['pending'], $state[$id]['pending_token']);
    });
}

/**
 * Освобождает захват после неудачного запуска.
 *
 * Возвращает прежнее время запуска и ставит задачу на паузу, чтобы она
 * не повторялась на каждом запросе админки.
 *
 * @param string $id ID задачи
 * @return void
 */
function release_cron_task_run(string $id): void {
    if (!_is_valid_hook_name($id)) {
        return;
    }

    $now = time();

    _cron_update_state(function (array &$state) use ($id, $now) {
        if (!isset($state[$id]) || !is_array($state[$id])) {
            return;
        }

        $state[$id]['last_run'] = (int)($state[$id]['claimed_prev'] ?? 0);
        $state[$id]['retry_after'] = $now + CRON_RETRY_COOLDOWN;

        unset($state[$id]['claimed_prev'], $state[$id]['pending'], $state[$id]['pending_token']);
    });
}

/**
 * Возвращает время последнего запуска задачи.
 *
 * @param string $id ID задачи
 * @return int Unix timestamp или 0
 */
function get_cron_last_run(string $id): int {
    $state = get_cron_state();
    return (int)($state[$id]['last_run'] ?? 0);
}
