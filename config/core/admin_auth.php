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

require_once 'core/admin_controller.php';

/**
 * 0. СИСТЕМНЫЙ СЛОВАРЬ И РЕГИСТРАТОР ОШИБОК (Паттерн Единого Реестра)
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
 * 1. ИНИЦИАЛИЗАЦИЯ И СБОР ДАННЫХ ИЗ ФАЙЛОВОЙ СИСТЕМЫ
 */
function getLoginAttemptsData(): array {
    if (!file_exists(CMS_ATTEMPTS_FILE)) { return []; }
    return json_decode(file_get_contents(CMS_ATTEMPTS_FILE), true) ?? [];
}

/**
 * 2. РУБЕЖ ОБОРОНЫ А: ПРОВЕРКА НА ТЕКУЩИЕ БЛОКИРОВКИ КЛИЕНТА (IP-Бан)
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
 * 3. РУБЕЖ ОБОРОНЫ Б: ПРОВЕРКА ПАРОЛЯ, ЛОГИНА И СЕКРЕТНОГО ПЕРЦА
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
 * 4. ОБРАБОТКА УСПЕШНОГО ВХОДА (Очистка логов и старт сессии)
 */
function handleSuccessfulLogin(string $userIp, array $bfData): void {
    session_regenerate_id(true); 
    $_SESSION['last_activity'] = time();
    $_SESSION['admin_auth'] = true;
    
    if (isset($bfData['attempts'][$userIp])) {
        unset($bfData['attempts'][$userIp]);
        safeFileWrite(CMS_ATTEMPTS_FILE, json_encode($bfData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    logAction('auth_login', 'Успешный вход пользователя с IP ' . $userIp, 'INFO');
    header('Location: index.php');
    exit;
}

/**
 * 5. РУБЕЖ ОБОРОНЫ В: НАКАЗАНИЕ — ИНКРЕМЕНТ ОШИБОК И КАРУСЕЛЬ БАНОВ БРУТФОРСА
 */
function handleFailedLogin(string $userIp, array $bfData): void {
    $maxAttempts = intval($bfData['settings']['max_attempts'] ?? 5);
    $lockoutTime = intval($bfData['settings']['lockout_time'] ?? 15);
    $permCount   = intval($bfData['settings']['permanent_trigger_count'] ?? 3);
    $permPeriod  = intval($bfData['settings']['permanent_trigger_period'] ?? 24);
    
    if (!isset($bfData['attempts'])) { $bfData['attempts'] = []; }
    if (!isset($bfData['attempts'][$userIp])) {
        $bfData['attempts'][$userIp] = ['count' => 0, 'last_time' => 0, 'bans_count' => 0, 'bans_history' => [], 'permanent' => false];
    }
    
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
        $timeDiff = time() - $bfData['attempts'][$userIp]['last_time'];
        if ($timeDiff >= ($lockoutTime * 60)) { $bfData['attempts'][$userIp]['count'] = 1; }
    }
    
    safeFileWrite(CMS_ATTEMPTS_FILE, json_encode($bfData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    logAction('auth_failed', 'Неудачная попытка входа с IP ' . $userIp . ' (попытка #' . $bfData['attempts'][$userIp]['count'] . ')', 'WARNING');
}

/**
 * 6. ГЕНЕРАТОР ХЭШЕЙ ПАРОЛЕЙ НА ЛЕТУ
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
function checkAdminSessionTimeout(array $bfData): void {
    if (!isset($_SESSION['last_activity'])) { return; }
    
    $timeoutMinutes = intval($bfData['settings']['session_timeout'] ?? 20);
    $inactiveTime = time() - $_SESSION['last_activity'];
    
    if ($inactiveTime > ($timeoutMinutes * 60)) {
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
