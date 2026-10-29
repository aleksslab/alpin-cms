<?php
/**
 * Плагин: Технические работы
 */

// 1. Фронтенд: перехват запроса до начала роутинга
add_action('index_start', function() {
    $settings = getSettingsData();
    
    // Проверка на автоснятие
    if (!empty($settings['maintenance_enabled']) && !empty($settings['maintenance_until']) && time() > (int)$settings['maintenance_until']) {
        $settings['maintenance_enabled'] = false;
        $settings['maintenance_until'] = null;
        $settingsPath = DATA_DIR . 'settings.json';
        safeFileWrite($settingsPath, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        clearDataCache('settings');
        $settings = getSettingsData(true);
    }

    if (!empty($settings['maintenance_enabled'])) {
        $maintenanceToken = (string)($settings['maintenance_bypass_token'] ?? '');
        $isAdmin = !empty($_SESSION['admin_auth']);

        // Bypass по токену
        $bypassParam = trim($_GET['bypass'] ?? '');
        if ($bypassParam !== '' && $maintenanceToken !== '' && hash_equals($maintenanceToken, $bypassParam)) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            setcookie('maintenance_bypass', '1', [
                'expires'  => time() + 86400,
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            header('Location: /', true, 302);
            exit;
        }

        // Проверка cookie или админа
        $hasBypassCookie = ($_COOKIE['maintenance_bypass'] ?? '') === '1';
        if (!$isAdmin && !$hasBypassCookie) {
            header('HTTP/1.1 503 Service Unavailable');
            header('Retry-After: 3600');
            header('X-Robots-Tag: noindex, nofollow, noarchive');
            header('Content-Type: text/html; charset=utf-8');

            $maintenanceMessage = (string)($settings['maintenance_message'] ?? '');
            $maintenanceUntil = !empty($settings['maintenance_until']) ? (int)$settings['maintenance_until'] : null;
            $maintenanceShowTimer = !empty($settings['maintenance_show_timer']);

            include APP_ROOT . '/templates/maintenance.php';
            exit;
        }
    }
});

// 2. Сохранение настроек (через фильтр)
add_filter('save_settings_data', function($newSettings) {
    $newSettings['maintenance_enabled'] = isset($_POST['maintenance_enabled']) && $_POST['maintenance_enabled'] == '1';
    $newSettings['maintenance_message'] = sanitizeHtml(trim($_POST['maintenance_message'] ?? ''));
    $newSettings['maintenance_show_timer'] = isset($_POST['maintenance_show_timer']) && $_POST['maintenance_show_timer'] == '1';
    
    // Токен
    $posted = trim($_POST['maintenance_bypass_token'] ?? '');
    if (preg_match('/^[a-f0-9]{32}$/i', $posted)) {
        $newSettings['maintenance_bypass_token'] = $posted;
    } else {
        $existing = getSettingsData()['maintenance_bypass_token'] ?? '';
        if (!empty($existing) && preg_match('/^[a-f0-9]{32}$/i', $existing)) {
            $newSettings['maintenance_bypass_token'] = $existing;
        } else if ($newSettings['maintenance_enabled']) {
            $newSettings['maintenance_bypass_token'] = bin2hex(random_bytes(16));
        } else {
            $newSettings['maintenance_bypass_token'] = '';
        }
    }

    // Дата автоснятия
    $raw = trim($_POST['maintenance_until'] ?? '');
    if ($raw === '') {
        $newSettings['maintenance_until'] = null;
    } else {
        $ts = strtotime($raw);
        $newSettings['maintenance_until'] = ($ts === false) ? null : $ts;
    }

    return $newSettings;
});