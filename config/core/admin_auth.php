<?php
// Изолированное ядро безопасности и авторизации
// 
// 1. ЦЕНТРАЛИЗОВАННЫЙ БЛОК НАСТРОЙКИ ПУТЕЙ БЕЗОПАСНОСТИ
$secureDir = APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'data';

// Автоматически создаем папку безопасности и закрываем её от браузеров через .htaccess
// Создаём папку безопасности, если её нет
if (!is_dir($secureDir)) {
    @mkdir($secureDir, 0755, true);
}

// Всегда проверяем .htaccess — создаём/обновляем при необходимости
$htaccessFile = $secureDir . DIRECTORY_SEPARATOR . '.htaccess';
$htaccessData = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order Deny,Allow\n    Deny from all\n</IfModule>\n";

$needWrite = false;
if (!file_exists($htaccessFile)) {
    $needWrite = true;
} else {
    $currentContent = @file_get_contents($htaccessFile);
    if ($currentContent === false || strpos($currentContent, 'Require all denied') === false) {
        $needWrite = true;
    }
}

if ($needWrite) {
    @file_put_contents($htaccessFile, $htaccessData);
}

// Константы путей к файлам — доступны в любой функции по всему файлу
define('CMS_PEPPER_FILE',   $secureDir . DIRECTORY_SEPARATOR . '.pepper');
define('CMS_CREDS_FILE',    $secureDir . DIRECTORY_SEPARATOR . 'credentials.json');
define('CMS_ATTEMPTS_FILE', $secureDir . DIRECTORY_SEPARATOR . 'login_attempts.json');

// Лимит подтверждения текущим паролем. Жёсткий, а не из настроек: украденная
// сессия может поднять max_attempts и перебирать пароль без предела.
define('SECURITY_FORM_MAX_ATTEMPTS', 3);
define('SECURITY_FORM_LOCKOUT_MINUTES', 15);

// Сколько секунд действует одноразовое подтверждение опасного действия.
define('SECURITY_CONFIRM_TTL', 300);

require_once __DIR__ . '/admin_controller.php';

/**
 * Единый реестр ошибок авторизации.
 *
 * Вызов с кодом фиксирует ошибку, вызов без кода возвращает её текст.
 * Так обработчики не разбрасывают строки по коду и не раскрывают детали.
 *
 * @param int|null $code Код ошибки (null — получить текст последней)
 * @param array $params Дополнительные параметры сообщения
 * @return string Текст ошибки или пустая строка
 */
function admin_error(int $code = null, array $params = []): string {
    static $currentErrorCode = 0; 
    static $errorParams = [];

    // Если передан код — фиксируем и запоминаем ошибку внутри системы
    if ($code !== null) {
        $currentErrorCode = $code;
        $errorParams = $params;
        return '';
    }

    // Если вызвали пустой функцию (например, $error = admin_error()) — отдаем текст
    if ($currentErrorCode === 0) { return ''; }

    $messages = [
        101 => 'Неверный логин или пароль!',
        102 => 'Системная ошибка авторизации. Обратитесь к администратору.',
        103 => 'Ваш IP-адрес занесен в постоянный черный список.',
        104 => 'Слишком много неудачных попыток. Доступ заблокирован на ' . ($errorParams['minutes'] ?? 15) . ' мин.',
        105 => 'Системная ошибка: невозможно сгенерировать хэш.'
    ];

    return $messages[$currentErrorCode] ?? 'Произошла неизвестная ошибка (Код: ' . $currentErrorCode . ').';
}

/**
 * Читает журнал попыток входа и настроек брутфорса.
 *
 * @return array Данные `login_attempts.json` или пустой массив
 */
function getLoginAttemptsData(): array {
    if (!file_exists(CMS_ATTEMPTS_FILE)) { return []; }
    return json_decode(file_get_contents(CMS_ATTEMPTS_FILE), true) ?? [];
}

/**
 * Выполняет чтение-изменение-запись журнала попыток под эксклюзивной блокировкой.
 *
 * `getLoginAttemptsData()` и `safeFileWrite()` по отдельности не атомарны: два
 * параллельных POST читали одинаковый `count` и оба писали `count + 1`, из-за
 * чего лимит попыток обходился пачкой запросов. Блокировка сериализует весь
 * цикл; сам JSON остаётся обычным файлом — его читают и другие модули.
 *
 * @param callable $mutator function(array &$bfData): bool — меняет данные
 *                          и возвращает true, если их нужно записать
 * @return bool true — данные записаны (или менять было нечего)
 */
function updateLoginAttempts(callable $mutator): bool {
    $lockPath = CMS_ATTEMPTS_FILE . '.lock';

    // Чтение и запись одним шагом: вызывается под блокировкой или без неё,
    // если файл блокировки открыть не удалось.
    $apply = function () use ($mutator): bool {
        clearstatcache(true, CMS_ATTEMPTS_FILE);
        $bfData = getLoginAttemptsData();

        if (!$mutator($bfData)) {
            return true;
        }

        return safeFileWrite(
            CMS_ATTEMPTS_FILE,
            json_encode($bfData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    };

    // Нет блокировки — работаем как раньше: потерять точность счётчика на
    // редкой гонке лучше, чем заблокировать вход в панель.
    $lock = @fopen($lockPath, 'c');
    if ($lock === false) {
        return $apply();
    }

    if (!flock($lock, LOCK_EX)) {
        fclose($lock);
        return $apply();
    }

    $written = $apply();

    flock($lock, LOCK_UN);
    fclose($lock);

    return $written;
}

/**
 * Проверяет, заблокирован ли IP — постоянный бан или активный локаут.
 *
 * При блокировке фиксирует код ошибки через `admin_error()`.
 *
 * @param string $userIp IP-адрес клиента
 * @param array $bfData Данные попыток и настроек брутфорса
 * @return bool true — доступ разрешён, false — заблокирован
 */
function checkIpBlockStatus(string $userIp, array $bfData): bool {
    if (!isset($bfData['attempts'][$userIp])) { return true; } // Логов нет — хост чист
    
    $ipData = $bfData['attempts'][$userIp];
    
    // 1. Проверяем жесткий пожизненный черный список
    if (!empty($ipData['permanent'])) {
        admin_error(103);
        return false;
    }
    
    // 2. Проверяем мягкий временный бан по таймеру локаута
    $maxAttempts = intval($bfData['settings']['max_attempts'] ?? 5);
    $lockoutTime = intval($bfData['settings']['lockout_time'] ?? 15);
    $timePassed = time() - $ipData['last_time'];
    
    if ($ipData['count'] >= $maxAttempts && $timePassed < ($lockoutTime * 60)) {
        $minutesLeft = ceil((($lockoutTime * 60) - $timePassed) / 60);
        admin_error(104, ['minutes' => $minutesLeft]);
        return false;
    }
    
    return true;
}

/**
 * Проверяет логин и пароль с учётом секретной «перчины» (pepper).
 *
 * Пароль сравнивается через `password_verify()` от HMAC, поэтому перебор
 * по времени невозможен. Ошибка фиксируется кодом 101 или 102.
 *
 * @param string $login Введённый логин
 * @param string $password Введённый пароль
 * @return bool true при совпадении
 */
function verifyAdminCredentials(string $login, string $password): bool {
    if (!file_exists(CMS_PEPPER_FILE)) {
        $randomPepper = bin2hex(random_bytes(32));
        file_put_contents(CMS_PEPPER_FILE, $randomPepper);
    }

    $pepper = trim(file_get_contents(CMS_PEPPER_FILE));
    $creds = file_exists(CMS_CREDS_FILE) ? (json_decode(file_get_contents(CMS_CREDS_FILE), true) ?? []) : [];

    if (empty($pepper) || empty($creds['login']) || empty($creds['password_hash'])) {
        admin_error(102);
        return false;
    }
    
    // Всегда выполняем дорогую операцию — защита от user enumeration по времени
    $loginMatch = hash_equals($creds['login'], $login);

    $secureMix = hash_hmac('sha256', $password, $creds['login'] . $pepper);
    $passwordMatch = password_verify($secureMix, $creds['password_hash']);

    if ($loginMatch && $passwordMatch) {
        return true;
    }

    admin_error(101);
    return false;
}

/**
 * Завершает успешный вход: обновляет сессию, чистит попытки и редиректит.
 *
 * @param string $userIp IP-адрес клиента
 * @param array  $bfData Снимок журнала (не используется: под блокировкой
 *                       данные перечитываются заново)
 * @return void
 */
function handleSuccessfulLogin(string $userIp, array $bfData): void {
    session_regenerate_id(true); 
    $_SESSION['last_activity'] = time();
    $_SESSION['admin_auth'] = true;    

    // Плагины: успешный вход (например, очистка временных preview-файлов).
    do_action('admin_login_success', $userIp);
    
    // Пароль из формы входа подтверждён — снимаем и серию ошибок формы
    // безопасности: владелец не должен ждать паузу после разлогина.
    // Снимок перечитывается под блокировкой: параллельный запрос мог успеть
    // записать новую попытку между чтением в index.php и этим местом.
    updateLoginAttempts(function (array &$bfData) use ($userIp): bool {
        $changed = false;
        if (isset($bfData['attempts'][$userIp])) {
            unset($bfData['attempts'][$userIp]);
            $changed = true;
        }
        if (isset($bfData['security_attempts'][$userIp])) {
            unset($bfData['security_attempts'][$userIp]);
            $changed = true;
        }
        return $changed;
    });
    
    logAction('auth_login', 'Успешный вход пользователя с IP ' . $userIp, 'INFO');
    header('Location: index.php');
    exit;
}

/**
 * Учитывает неудачную попытку входа и обновляет баны.
 *
 * Счётчик растёт до `max_attempts`; после локаута серия начинается заново.
 * Каждый бан пишется в историю, по её частоте включается постоянный бан.
 *
 * @param string $userIp IP-адрес клиента
 * @param array  $bfData Снимок журнала (не используется: под блокировкой
 *                       данные перечитываются заново)
 * @return void
 */
function handleFailedLogin(string $userIp, array $bfData): void {
    $attemptCount = 0;

    updateLoginAttempts(function (array &$bfData) use ($userIp, &$attemptCount): bool {
        $maxAttempts = intval($bfData['settings']['max_attempts'] ?? 5);
        $lockoutTime = intval($bfData['settings']['lockout_time'] ?? 15);
        $permCount   = intval($bfData['settings']['permanent_trigger_count'] ?? 3);
        $permPeriod  = intval($bfData['settings']['permanent_trigger_period'] ?? 24);

        if (!isset($bfData['attempts'])) { $bfData['attempts'] = []; }
        if (!isset($bfData['attempts'][$userIp])) {
            $bfData['attempts'][$userIp] = ['count' => 0, 'last_time' => 0, 'bans_count' => 0, 'bans_history' => [], 'permanent' => false];
        }

        // Запоминаем время прошлой попытки до того, как перезапишем last_time:
        // без этого в ветке сброса timeDiff всегда равен ~0 и счётчик не обнуляется.
        $lastTime = (int)($bfData['attempts'][$userIp]['last_time'] ?? 0);

        $bfData['attempts'][$userIp]['count']++;
        $bfData['attempts'][$userIp]['last_time'] = time();

        if ($bfData['attempts'][$userIp]['count'] == $maxAttempts) {
            $bfData['attempts'][$userIp]['bans_count']++;
            $bfData['attempts'][$userIp]['bans_history'][] = time();

            $freshBansCount = 0;
            $windowSeconds = $permPeriod * 3600;
            foreach ($bfData['attempts'][$userIp]['bans_history'] as $banTime) {
                if (time() - $banTime <= $windowSeconds) { $freshBansCount++; }
            }

            if ($freshBansCount >= $permCount) { $bfData['attempts'][$userIp]['permanent'] = true; }
        }
        elseif ($bfData['attempts'][$userIp]['count'] > $maxAttempts) {
            $timeDiff = time() - $lastTime;
            if ($timeDiff >= ($lockoutTime * 60)) { $bfData['attempts'][$userIp]['count'] = 1; }
        }

        $attemptCount = (int)$bfData['attempts'][$userIp]['count'];
        return true;
    });

    logAction('auth_failed', 'Неудачная попытка входа с IP ' . $userIp . ' (попытка #' . $attemptCount . ')', 'WARNING');
}

/**
 * Сколько минут осталось до конца паузы для формы настроек безопасности.
 *
 * Счётчик `security_attempts` отдельный от `attempts`: неверный текущий пароль
 * не приближает бан IP и не мешает войти в панель. Лимит берётся из констант,
 * а не из настроек: иначе украденная сессия поднимает `max_attempts`
 * и перебирает текущий пароль без ограничений.
 *
 * @param string $userIp IP-адрес клиента
 * @param array $bfData Данные попыток и настроек брутфорса
 * @return int 0 — можно пробовать; иначе сколько минут ждать
 */
function getSecurityFormLockoutMinutes(string $userIp, array $bfData): int {
    $entry = $bfData['security_attempts'][$userIp] ?? null;
    if (!is_array($entry)) { return 0; }

    $lockoutTime = SECURITY_FORM_LOCKOUT_MINUTES * 60;
    $timePassed  = time() - intval($entry['last_time'] ?? 0);

    if (intval($entry['count'] ?? 0) >= SECURITY_FORM_MAX_ATTEMPTS && $timePassed < $lockoutTime) {
        return (int)ceil(($lockoutTime - $timePassed) / 60);
    }
    return 0;
}

/**
 * Учитывает неверный текущий пароль при сохранении настроек безопасности.
 *
 * Пишет только `security_attempts` (по IP), не трогая `attempts` входа,
 * историю банов и флаг `permanent`. Постоянного бана у формы нет. После
 * паузы серия начинается заново.
 *
 * @param string $userIp IP-адрес клиента
 * @param array  $bfData Снимок журнала (не используется: под блокировкой
 *                       данные перечитываются заново)
 * @return int Номер попытки в текущей серии
 */
function registerSecurityFormFailure(string $userIp, array $bfData): int {
    $attemptCount = 0;

    updateLoginAttempts(function (array &$bfData) use ($userIp, &$attemptCount): bool {
        $lockoutTime = SECURITY_FORM_LOCKOUT_MINUTES * 60;

        if (!isset($bfData['security_attempts'])) { $bfData['security_attempts'] = []; }
        $entry = $bfData['security_attempts'][$userIp] ?? ['count' => 0, 'last_time' => 0];

        // Пауза прошла — начинаем серию заново, иначе копим счётчик.
        if (time() - intval($entry['last_time'] ?? 0) >= $lockoutTime) {
            $entry['count'] = 0;
        }

        $entry['count'] = intval($entry['count'] ?? 0) + 1;
        $entry['last_time'] = time();

        $bfData['security_attempts'][$userIp] = $entry;
        $attemptCount = (int)$entry['count'];
        return true;
    });

    logAction(
        'security_save',
        'Неверный текущий пароль при сохранении настроек безопасности с IP ' . $userIp . ' (попытка #' . $attemptCount . ')',
        'WARNING'
    );

    return $attemptCount;
}

/**
 * Полностью завершает сессию администратора.
 *
 * Используется при исчерпании лимита неверных паролей и по idle timeout.
 *
 * @return void
 */
function endAdminSession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Проверяет текущий пароль для опасного действия.
 *
 * Не завершает запрос сам: вызывающий код решает, отдать ошибку JSON-ом
 * (AJAX-модалка) или редиректом формы. Счётчик и правила те же, что у формы
 * безопасности: пауза, три ошибки, затем разлогин.
 *
 * @param string $password Введённый текущий пароль
 * @return array ['ok' => bool, 'error' => string, 'logout' => bool]
 */
function verifySecurityPassword(string $password): array {
    $userIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $bfData = getLoginAttemptsData();

    $waitMinutes = getSecurityFormLockoutMinutes($userIp, $bfData);
    if ($waitMinutes > 0) {
        return [
            'ok' => false,
            'error' => 'Слишком много неверных текущих паролей. Повторите через ' . $waitMinutes . ' мин.',
            'logout' => false
        ];
    }

    if ($password === '') {
        logAction('security_save', 'Попытка подтвердить действие без текущего пароля', 'WARNING');
        return ['ok' => false, 'error' => 'Введите текущий пароль.', 'logout' => false];
    }

    $currentCreds = file_exists(CMS_CREDS_FILE) ? (json_decode(file_get_contents(CMS_CREDS_FILE), true) ?? []) : [];
    $currentLogin = (string)($currentCreds['login'] ?? '');
    $passwordHash = (string)($currentCreds['password_hash'] ?? '');
    $pepper = file_exists(CMS_PEPPER_FILE) ? trim(file_get_contents(CMS_PEPPER_FILE)) : '';

    if ($currentLogin === '' || $passwordHash === '' || $pepper === '') {
        return ['ok' => false, 'error' => 'Системная ошибка безопасности. Действие невозможно.', 'logout' => false];
    }

    $currentMix = hash_hmac('sha256', $password, $currentLogin . $pepper);
    if (!password_verify($currentMix, $passwordHash)) {
        $attempt = registerSecurityFormFailure($userIp, $bfData);

        if ($attempt >= SECURITY_FORM_MAX_ATTEMPTS) {
            logAction('security_save', 'Сессия завершена: исчерпан лимит неверных текущих паролей', 'WARNING');
            return [
                'ok' => false,
                'error' => 'Неверный текущий пароль. Сессия завершена из соображений безопасности.',
                'logout' => true
            ];
        }

        return ['ok' => false, 'error' => 'Неверный текущий пароль.', 'logout' => false];
    }

    // Пароль подтверждён — снимаем серию ошибок формы. Под блокировкой:
    // параллельный запрос мог успеть записать новую попытку после чтения выше.
    updateLoginAttempts(function (array &$bfData) use ($userIp): bool {
        if (!isset($bfData['security_attempts'][$userIp])) {
            return false;
        }
        unset($bfData['security_attempts'][$userIp]);
        return true;
    });

    return ['ok' => true, 'error' => '', 'logout' => false];
}

/**
 * Выдаёт одноразовое подтверждение опасного действия.
 *
 * @param string $action Тип действия: save, unban или ban
 * @return void
 */
function issueSecurityConfirmation(string $action): void {
    $_SESSION['security_confirmation'] = [
        'action' => $action,
        'at'     => time()
    ];
}

/**
 * Проверяет, есть ли действующее подтверждение для действия.
 *
 * Подтверждение привязано к типу действия и живёт не дольше
 * `SECURITY_CONFIRM_TTL`. Просроченное удаляется.
 *
 * @param string $action Тип действия: save, unban или ban
 * @return bool
 */
function hasSecurityConfirmation(string $action): bool {
    $confirm = $_SESSION['security_confirmation'] ?? null;
    if (!is_array($confirm)) { return false; }
    if ((string)($confirm['action'] ?? '') !== $action) { return false; }

    if (time() - intval($confirm['at'] ?? 0) > SECURITY_CONFIRM_TTL) {
        unset($_SESSION['security_confirmation']);
        return false;
    }
    return true;
}

/**
 * Снимает подтверждение после успешного действия.
 *
 * @param string $action Тип действия: save, unban или ban
 * @return void
 */
function consumeSecurityConfirmation(string $action): void {
    if (hasSecurityConfirmation($action)) {
        unset($_SESSION['security_confirmation']);
    }
}

/**
 * Требует подтверждение опасного действия для обычной POST-формы.
 *
 * Принимает действующий токен из модалки либо пароль из аварийного поля
 * (когда JavaScript отключён). При третьей ошибке пароля завершает сессию
 * и уводит на страницу входа.
 *
 * @param string $action Тип действия: save, unban или ban
 * @param string $password Пароль из аварийного поля формы
 * @return array|null Ошибка для флеша либо null, если действие разрешено
 */
function requireSecurityConfirmation(string $action, string $password = ''): ?array {
    if (hasSecurityConfirmation($action)) {
        return null;
    }

    if ($password !== '') {
        $check = verifySecurityPassword($password);
        if (!empty($check['ok'])) {
            return null;
        }
        if (!empty($check['logout'])) {
            endAdminSession();
            header('Location: index.php?msg=security_failed');
            exit;
        }
        return ['success' => '', 'error' => $check['error']];
    }

    return ['success' => '', 'error' => 'Подтвердите действие текущим паролем.'];
}

/**
 * Генерирует хэш нового пароля тем же способом, что и при входе.
 *
 * Используется при смене пароля в админке: смешивает пароль с логином
 * и «перчиной», затем хэширует `password_hash()`.
 *
 * @param string $newPassword Новый пароль
 * @return string Хэш или пустая строка при ошибке
 */
function handleLiveHashGeneration(string $newPassword): string {
    if (!file_exists(CMS_PEPPER_FILE)) {
        $randomPepper = bin2hex(random_bytes(32));
        file_put_contents(CMS_PEPPER_FILE, $randomPepper);
    }

    $pepper = trim(file_get_contents(CMS_PEPPER_FILE));
    $creds = file_exists(CMS_CREDS_FILE) ? (json_decode(file_get_contents(CMS_CREDS_FILE), true) ?? []) : [];
    $currentLogin = $creds['login'] ?? 'admin';
    
    if (!empty($pepper)) {
        $secureMix = hash_hmac('sha256', $newPassword, $currentLogin . $pepper);
        return password_hash($secureMix, PASSWORD_DEFAULT);
    }
    
    admin_error(105);
    return '';
}

/**
 * 7. КОНТРОЛЬ СЕССИИ ПО ТАЙМАУТУ НЕАКТИВНОСТИ (Idle Timeout)
 */

/**
 * Возвращает время жизни админ-сессии в секундах по настройкам безопасности.
 *
 * @param array $bfData Данные настроек защиты от брутфорса.
 * @return int Секунды (минимум 60).
 */
function getAdminSessionTimeoutSeconds(array $bfData): int {
    $minutes = intval($bfData['settings']['session_timeout'] ?? 20);
    if ($minutes < 1) {
        $minutes = 1;
    }
    return $minutes * 60;
}

/**
 * Возвращает остаток времени текущей админ-сессии в секундах.
 *
 * @param array $bfData Данные настроек защиты от брутфорса.
 * @return int Секунды (0 — сессия истекла).
 */
function getAdminSessionRemaining(array $bfData): int {
    $lifetime = getAdminSessionTimeoutSeconds($bfData);

    if (!isset($_SESSION['last_activity'])) {
        return $lifetime;
    }

    $remaining = $lifetime - (time() - (int)$_SESSION['last_activity']);
    return max(0, min($lifetime, $remaining));
}

/**
 * Возвращает порог предупреждения об истечении сессии в секундах.
 * Обычно 2 минуты, но не больше половины времени жизни и не меньше 10 секунд.
 *
 * @param array $bfData Данные настроек защиты от брутфорса.
 * @return int Секунды.
 */
function getAdminSessionWarnSeconds(array $bfData): int {
    $lifetime = getAdminSessionTimeoutSeconds($bfData);
    return min(120, max(10, intdiv($lifetime, 2)));
}

/**
 * Проверяет бездействие админа и завершает сессию при превышении таймаута.
 * При каждом вызове обновляет время последней активности.
 *
 * @param array $bfData Данные настроек защиты от брутфорса.
 * @return void
 */
function checkAdminSessionTimeout(array $bfData): void {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return;
    }

    $timeoutSeconds = getAdminSessionTimeoutSeconds($bfData);
    $inactiveTime = time() - (int)$_SESSION['last_activity'];

    if ($inactiveTime > $timeoutSeconds) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        header("Location: index.php?msg=timeout");
        exit;
    }

    $_SESSION['last_activity'] = time(); // Свежий клик — продлеваем серверное время активности
}
