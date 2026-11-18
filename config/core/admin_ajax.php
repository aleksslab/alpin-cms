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
    // --- ПРОВОДНИК (GET/POST) ---
    // Обрабатывается плагином filemanager на admin_init.

    // --- СКАЧИВАНИЕ БЭКАПА ---
    // Обрабатывается плагином backups на admin_init.

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
        // dirname(__DIR__) — это config/: путь не зависит от DOCUMENT_ROOT.
        require_once dirname(__DIR__) . '/modules/pages/constructor.php';
        exit;
    }
    
    // --- ПРОВЕРКА РАСПАКОВКИ МОДУЛЯ ---
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_module') {
        $moduleId = $_GET['module'] ?? '';        
    
        if (!isValidId($moduleId)) {
            echo json_encode(['installed' => false, 'error' => 'Невалидный ID модуля']);
            exit;
        }
        
        echo json_encode(['installed' => isModuleInstalled($moduleId)]);
        exit;
    }
    
    // --- СТАТИСТИКА МИНИФИКАЦИИ (AJAX) ---
    if (isset($_GET['action']) && $_GET['action'] === 'get_minify_stats' && isset($_GET['tab']) && $_GET['tab'] === 'config_vars') {
        require_once __DIR__ . '/admin_controller.php';
        handleGetMinifyStats();
        exit;
    }
    
    // --- ПЛАГИНЫ (AJAX-ДЕЙСТВИЯ: УСТАНОВКА ИЗ ZIP) ---
    if (isset($_POST['plugin_action']) && isset($_GET['ajax']) && $_GET['ajax'] === 'upload' && isset($_GET['tab']) && $_GET['tab'] === 'plugins') {
        header('Content-Type: application/json; charset=utf-8');
        
        // Проверка CSRF
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
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
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            echo json_encode(['success' => false, 'error' => 'Ошибка безопасности: CSRF-токен невалиден.']);
            exit;
        }

        $url = $_POST['url'] ?? '';
        $id = $_POST['id'] ?? '';
        $sha256 = is_string($_POST['sha256'] ?? null) ? strtolower(trim($_POST['sha256'])) : '';
        
        if (!filter_var($url, FILTER_VALIDATE_URL) || !isValidId($id)) {
            echo json_encode(['success' => false, 'error' => 'Неверные параметры запроса.']);
            exit;
        }

        // Хеш необязателен, но если пришёл — формат должен быть строгим.
        if ($sha256 !== '' && preg_match('/^[a-f0-9]{64}$/', $sha256) !== 1) {
            echo json_encode(['success' => false, 'error' => 'Неверный формат контрольной суммы.']);
            exit;
        }
        
        require_once __DIR__ . '/plugins.php';
        
        if (!function_exists('downloadPluginZip') || !function_exists('installPluginFromZip')) {
            echo json_encode(['success' => false, 'error' => 'Функции установки не найдены в ядре.']);
            exit;
        }

        $downloadError = null;
        $zipPath = downloadPluginZip($url, $id, $sha256, $downloadError);
        
        if (!$zipPath) {
            echo json_encode(['success' => false, 'error' => $downloadError ?: 'Не удалось скачать архив.']);
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

            // Без HTTPS и без хеша целостность архива проверить нечем.
            $note = '';
            if ($sha256 === '' && strtolower((string)parse_url($url, PHP_URL_SCHEME)) === 'http') {
                $note = ' Архив получен по http без контрольной суммы — целостность не проверена.';
            }
            
            // Ставим флеш
            setFlash($result['message'] . $note);
            
            echo json_encode(['success' => true, 'message' => $result['message'] . $note]);
        } else {
            setFlash($result['message'], 'error');
            echo json_encode(['success' => false, 'error' => $result['message']]);
        }
        exit;
    }
    
    // --- МОДУЛИ (AJAX-ДЕЙСТВИЯ: УСТАНОВКА ИЗ КАТАЛОГА) ---
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'module_install_from_url' && isset($_GET['tab']) && $_GET['tab'] === 'modules') {
        header('Content-Type: application/json; charset=utf-8');

        // Проверка CSRF
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            echo json_encode(['success' => false, 'error' => 'Ошибка безопасности: CSRF-токен невалиден.']);
            exit;
        }

        $url = $_POST['url'] ?? '';
        $id = $_POST['id'] ?? '';
        $sha256 = is_string($_POST['sha256'] ?? null) ? strtolower(trim($_POST['sha256'])) : '';

        if (!filter_var($url, FILTER_VALIDATE_URL) || !isValidId($id)) {
            echo json_encode(['success' => false, 'error' => 'Неверные параметры запроса.']);
            exit;
        }

        // Хеш необязателен, но если пришёл — формат должен быть строгим.
        if ($sha256 !== '' && preg_match('/^[a-f0-9]{64}$/', $sha256) !== 1) {
            echo json_encode(['success' => false, 'error' => 'Неверный формат контрольной суммы.']);
            exit;
        }

        if (!function_exists('downloadModuleZip') || !function_exists('installModuleFromZip')) {
            echo json_encode(['success' => false, 'error' => 'Функции установки модулей не найдены в ядре.']);
            exit;
        }

        $downloadError = null;
        $zipPath = downloadModuleZip($url, $id, $sha256, $downloadError);
        if (!$zipPath) {
            echo json_encode(['success' => false, 'error' => $downloadError ?: 'Не удалось скачать архив модуля.']);
            exit;
        }

        // Новый модуль ещё нигде не используется — только кладём архив в библиотеку.
        // Установленный обновляем: если он есть на страницах — распаковываем сразу,
        // иначе тоже только архив, код распакуется при добавлении.
        $isInstalled = isModuleInstalled($id);
        $inUse = function_exists('findModuleUsage') ? !empty(findModuleUsage($id)) : false;
        $unpack = $isInstalled && $inUse;

        $result = installModuleFromZip($zipPath, $id, true, $unpack);
        @unlink($zipPath);

        if ($result['success']) {
            // Удаляем из кеша "новых" и "обновлений"
            $cacheFile = DATA_DIR . 'updates_cache.json';
            if (file_exists($cacheFile) && is_writable($cacheFile)) {
                $cache = json_decode(file_get_contents($cacheFile), true);
                if (is_array($cache)) {
                    unset($cache['data']['new'][$id], $cache['data']['updates'][$id]);
                    safeFileWrite($cacheFile, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                }
            }

            // Без HTTPS и без хеша целостность архива проверить нечем.
            $note = '';
            if ($sha256 === '' && strtolower((string)parse_url($url, PHP_URL_SCHEME)) === 'http') {
                $note = ' Архив получен по http без контрольной суммы — целостность не проверена.';
            }

            setFlash($result['message'] . $note);
            echo json_encode(['success' => true, 'message' => $result['message'] . $note]);
        } else {
            setFlash($result['message'], 'error');
            echo json_encode(['success' => false, 'error' => $result['message']]);
        }
        exit;
    }

    // --- ОБНОВЛЕНИЕ КАТАЛОГА (AJAX, только POST + CSRF) ---
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'refresh_catalog') {
        header('Content-Type: application/json; charset=utf-8');

        // Действие ходит в сеть и пишет updates_cache.json — GET не принимаем.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo json_encode(['success' => false, 'error' => 'Требуется POST-запрос.']);
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!is_string($csrfToken)) {
            $csrfToken = '';
        }

        if (!isset($_GET['tab']) || !in_array($_GET['tab'], ['plugins', 'modules'], true)
            || !verifyCsrfToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Ошибка безопасности: CSRF-токен невалиден.']);
            exit;
        }
        
        $settings = getSettingsData();
        $catalogUrl = !empty($settings['updates_catalog_url']) ? $settings['updates_catalog_url'] : UPDATES_DEFAULT_CATALOG_URL;
        
        require_once __DIR__ . '/plugins.php';
        $error = null;
        $result = refresh_updates_cache($catalogUrl, $error);
        
        if ($result !== false) {
            echo json_encode(['success' => true]);
        } else {
            $message = 'Не удалось скачать каталог';
            $message .= ($error !== null && $error !== '') ? ': ' . $error : '.';
            echo json_encode(['success' => false, 'error' => $message]);
        }
        exit;
    }
}