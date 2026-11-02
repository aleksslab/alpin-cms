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

/**
 * Регистрирует фоновую задачу.
 *
 * @param string   $id      Уникальный ID задачи (латиница, цифры, дефис, подчёркивание)
 * @param callable $callback Функция-обработчик. Должна вернуть true при успехе.
 * @param array    $options  Опции: interval (секунды), virtual (участие в виртуальном кроне), label
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
 * Сохраняет время последнего запуска задачи.
 *
 * @param string $id        ID задачи
 * @param int    $timestamp Unix timestamp
 * @return void
 */
function set_cron_last_run(string $id, int $timestamp): void {
    if (!_is_valid_hook_name($id)) {
        return;
    }

    $file = get_cron_state_file();
    $dir = dirname($file);

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $state = get_cron_state();
    $state[$id] = ['last_run' => $timestamp];

    safeFileWrite($file, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
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
