<?php
/**
 * Обработчик GET/AJAX-запросов админки
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

/**
 * Обрабатывает GET/AJAX-запросы в админке.
 * Вызывается из config/index.php после проверки авторизации.
 *
 * @return void
 */
function handle_admin_ajax_actions(): void {
    // --- ПРОВОДНИК (GET) ---
    if (isset($_GET['fm_api_action']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        require_once __DIR__ . '/filemanager_api.php';
        exit;
    }
    
    // --- СКАЧИВАНИЕ БЭКАПА ---
    if (isset($_GET['download_file']) && isset($_GET['tab']) && $_GET['tab'] === 'backups') { 
        handleDownloadBackup();
    }
    
    // --- НАСТРОЙКИ МОДУЛЯ ---
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'module_settings') {
        require_once __DIR__ . '/admin_controller.php';
        handleGetModuleSettings();
        exit;
    }
    
    // --- КОНСТРУКТОР ---
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['tab_edit']) && $_GET['tab_edit'] === 'constructor') {
        $pageId = $_GET['id'] ?? '';
        $action = empty($pageId) ? 'create' : ($_GET['action'] ?? 'edit');
        $templateId = $_GET['template'] ?? 'full-width';
        require_once __DIR__ . '/modules/pages/constructor.php';
        exit;
    }
    
    // --- ПРОВЕРКА РАСПАКОВКИ МОДУЛЯ ---
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_module') {
        $moduleId = $_GET['module'] ?? '';        
    
        if (!preg_match('/^[a-z0-9\-_]+$/i', $moduleId)) {
            echo json_encode(['installed' => false, 'error' => 'Невалидный ID модуля']);
            exit;
        }
        
        echo json_encode(['installed' => isModuleInstalled($moduleId)]);
        exit;
    }
    
    // --- ЛОГИ (AJAX) ---
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_logs' && isset($_GET['tab']) && $_GET['tab'] === 'logs') {
        require_once __DIR__ . '/admin_controller.php';
        handleGetLogs();
        exit;
    }
    
    // --- СТАТИСТИКА МИНИФИКАЦИИ (AJAX) ---
    if (isset($_GET['action']) && $_GET['action'] === 'get_minify_stats' && isset($_GET['tab']) && $_GET['tab'] === 'config_vars') {
        require_once __DIR__ . '/admin_controller.php';
        handleGetMinifyStats();
        exit;
    }
    
    // --- ГЕНЕРАЦИЯ ТОКЕНОВ (AJAX) ---
    if (isset($_GET['ajax']) && in_array($_GET['ajax'], ['generate_cron_token', 'generate_preview_token', 'generate_maintenance_token'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'token' => bin2hex(random_bytes(16))]);
        exit;
    }
    
    // --- ПЛАГИНЫ (AJAX-ДЕЙСТВИЯ: УСТАНОВКА ИЗ ZIP) ---
    if (isset($_POST['plugin_action']) && isset($_GET['ajax']) && $_GET['ajax'] === 'upload' && isset($_GET['tab']) && $_GET['tab'] === 'plugins') {
        header('Content-Type: application/json; charset=utf-8');
        
        // Проверка CSRF
        $incomingToken = $_POST['csrf_token'] ?? '';
        $sessionToken  = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken) || !hash_equals($sessionToken, $incomingToken)) {
            echo json_encode(['success' => false, 'error' => 'Ошибка безопасности: CSRF-токен невалиден.']);
            exit;
        }
        
        require_once __DIR__ . '/admin_controller.php';
        $result = handlePluginAction(); // Вызываем общий обработчик
        
        if (!empty($result['success'])) {
            // Ставим флеш, чтобы он показался ПОСЛЕ перезагрузки страницы
            setFlash($result['success']);
            echo json_encode(['success' => true, 'message' => $result['success']]);
        } else {
            setFlash($result['error'], 'error');
            echo json_encode(['success' => false, 'error' => $result['error']]);
        }
        exit;
    }
    
    // --- ПЛАГИНЫ (AJAX-ДЕЙСТВИЯ: УСТАНОВКА ИЗ КАТАЛОГА) ---
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'plugin_install_from_url' && isset($_GET['tab']) && $_GET['tab'] === 'plugins') {
        header('Content-Type: application/json; charset=utf-8');
        
        // Проверка CSRF
        $incomingToken = $_POST['csrf_token'] ?? '';
        $sessionToken  = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken) || !hash_equals($sessionToken, $incomingToken)) {
            echo json_encode(['success' => false, 'error' => 'Ошибка безопасности: CSRF-токен невалиден.']);
            exit;
        }

        $url = $_POST['url'] ?? '';
        $id = $_POST['id'] ?? '';
        
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^[a-z0-9\-_]+$/i', $id)) {
            echo json_encode(['success' => false, 'error' => 'Неверные параметры запроса.']);
            exit;
        }
        
        require_once __DIR__ . '/plugins.php';
        
        if (!function_exists('downloadPluginZip') || !function_exists('installPluginFromZip')) {
            echo json_encode(['success' => false, 'error' => 'Функции установки не найдены в ядре.']);
            exit;
        }

        $zipPath = downloadPluginZip($url, $id);
        
        if (!$zipPath) {
            echo json_encode(['success' => false, 'error' => 'Не удалось скачать архив.']);
            exit;
        }
        
        $result = installPluginFromZip($zipPath, $id);
        @unlink($zipPath);
        
        if ($result['success']) {
            // Удаляем из кеша "новых"
            $cacheFile = DATA_DIR . 'updates_cache.json';
            if (file_exists($cacheFile) && is_writable($cacheFile)) {
                $cache = json_decode(file_get_contents($cacheFile), true);
                if (is_array($cache) && isset($cache['data']['new'][$id])) {
                    unset($cache['data']['new'][$id]);
                    safeFileWrite($cacheFile, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                }
            }
            
            // Ставим флеш
            setFlash($result['message']);
            
            echo json_encode(['success' => true, 'message' => $result['message']]);
        } else {
            setFlash($result['message'], 'error');
            echo json_encode(['success' => false, 'error' => $result['message']]);
        }
        exit;
    }
    
    // --- ОБНОВЛЕНИЕ КАТАЛОГА ПЛАГИНОВ (AJAX) ---
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'refresh_catalog' && isset($_GET['tab']) && $_GET['tab'] === 'plugins') {
        header('Content-Type: application/json; charset=utf-8');
        
        $settings = getSettingsData();
        $catalogUrl = !empty($settings['updates_catalog_url']) ? $settings['updates_catalog_url'] : UPDATES_DEFAULT_CATALOG_URL;
        
        require_once __DIR__ . '/plugins.php';
        $result = refresh_updates_cache($catalogUrl);
        
        if ($result !== false) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Не удалось скачать каталог.']);
        }
        exit;
    }
}