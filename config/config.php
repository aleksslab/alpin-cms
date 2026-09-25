<?php

// Убираем X-Powered-By (работает на любой конфигурации PHP)
if (function_exists('header_remove')) {
    header_remove('X-Powered-By');
}

// === ОТОБРАЖЕНИЕ ОШИБОК ===
define('DEBUG_SITE', false);
define('DEBUG_ADMIN', false);

if (!defined('DEBUG_SITE'))  define('DEBUG_SITE', false);
if (!defined('DEBUG_ADMIN')) define('DEBUG_ADMIN', false);

// Определяем, это админка или фронт
$isAdminRequest = (strpos($_SERVER['REQUEST_URI'] ?? '', '/config/') !== false)
               || (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/config/') !== false);

$debugEnabled = $isAdminRequest ? DEBUG_ADMIN : DEBUG_SITE;

if ($debugEnabled) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// 1. Абсолютный корень сайта
if (!defined('APP_ROOT')) {
    define('APP_ROOT', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));
}

// 2. Безопасность сессий (Вызываем СТРОГО до session_start)
ini_set('session.cookie_httponly', 1);           // Запрещает доступ к кукам через JS (защита от XSS-кражи)
ini_set('session.use_only_cookies', 1);           // Запрещает передавать ID сессии через URL
ini_set('session.use_strict_mode', 1);            // Отвергает неизвестные session_id (защита от fixation)
ini_set('session.cookie_samesite', 'Lax');        // Защита от CSRF (кука не уходит при subresource-запросах)

// cookie_secure — автоматически по HTTPS
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

session_start();

// 3. Generation CSRF-токена для форм сайта
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

// 4. Загружаем глобальные настройки из JSON на самом сайте
$settings = function_exists('getSettingsData') ? getSettingsData() : [];

// 5. Динамические константы (берут данные из админки, а если файла нет — подставляют дефолт)
define('SITE_NAME',    $settings['name']    ?? '');
define('SITE_SLOGAN',  $settings['slogan'] ?? '');
define('SITE_PHONE',   $settings['phone']   ?? '');
define('SITE_EMAIL',   $settings['email']   ?? '');
define('SITE_ADDRESS', $settings['address'] ?? '');
// SEO
define('SITE_META_TITLE', $settings['meta_title'] ?? '');
define('SITE_META_DESCRIPTION', $settings['meta_description'] ?? '');
define('SITE_META_KEYWORDS', $settings['meta_keywords'] ?? '');

// Open Graph
define('OG_TITLE', $settings['og_title'] ?? SITE_META_TITLE);
define('OG_DESCRIPTION', $settings['og_description'] ?? SITE_META_DESCRIPTION);
define('OG_IMAGE', $settings['og_image'] ?? '');
// Безопасный host
$safeHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!preg_match('#^[a-z0-9\-\.]+(:\d+)?$#i', $safeHost)) {
    $safeHost = 'localhost';
}
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
define('OG_URL', $settings['og_url'] ?? $protocol . '://' . $safeHost);

// Twitter Card
define('TWITTER_TITLE', $settings['twitter_title'] ?? OG_TITLE);
define('TWITTER_DESCRIPTION', $settings['twitter_description'] ?? OG_DESCRIPTION);
define('TWITTER_IMAGE', $settings['twitter_image'] ?? OG_IMAGE);

// Визуальные
define('SITE_LOGO', $settings['logo'] ?? '');
define('SITE_FAVICON', $settings['favicon'] ?? '');

// Социальные сети
define('SOCIAL_VK', $settings['social_vk'] ?? '');
define('SOCIAL_TELEGRAM', $settings['social_telegram'] ?? '');
define('SOCIAL_YOUTUBE', $settings['social_youtube'] ?? '');
define('SOCIAL_INSTAGRAM', $settings['social_instagram'] ?? '');
define('SOCIAL_TWITTER', $settings['social_twitter'] ?? '');
define('SOCIAL_FACEBOOK', $settings['social_facebook'] ?? '');
define('SOCIAL_WHATSAPP', $settings['social_whatsapp'] ?? '');
define('SOCIAL_TIKTOK', $settings['social_tiktok'] ?? '');
define('SOCIAL_GITHUB', $settings['social_github'] ?? '');
define('SOCIAL_LINKEDIN', $settings['social_linkedin'] ?? '');
define('SOCIAL_RSS', $settings['social_rss'] ?? '');
define('SOCIAL_OK', $settings['social_ok'] ?? '');
define('SOCIAL_TG_CHANNEL', $settings['social_tg_channel'] ?? '');

// Произвольный код
define('SITE_CUSTOM_CSS', $settings['custom_css'] ?? '');
define('SITE_CUSTOM_JS', $settings['custom_js'] ?? '');


// 6. Размер загружаемых файлов
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);
