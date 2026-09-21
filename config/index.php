<?php
require_once 'config.php';
require_once 'core/functions.php';
require_once 'core/admin_auth.php';

$error = '';
$generatedHash = '';
$successMessage = '';

// =========================================================================
// ЗОНА А: ОТКРЫТЫЕ СЛУЖЕБНЫЕ ДЕЙСТВИЯ (ДОСТУПНЫ ДО АВТОРИЗАЦИИ)
// =========================================================================

// 1. ОБРАБОТКА ВЫХОДА ИЗ ПАНЕЛИ
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (isset($_SESSION['admin_auth'])) { 
        require_once 'core/admin_controller.php';
        logAction('auth_logout', 'Выход из админки', 'INFO');
        $_SESSION['admin_auth'] = false; 
    }
    header('Location: index.php');
    exit;
}

// 2. ОБРАБОТКА ВХОДА (ИНТЕЛЛЕКТУАЛЬНАЯ ЗАЩИТА ANTI-BRUTE FORCE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $login    = trim($_POST['admin_login'] ?? '');
    $password = $_POST['admin_password'] ?? '';
    $userIp   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Функция сама знает путь к файлу логов
    $bfData = getLoginAttemptsData();
    
    // Компактный и плоский сценарий вызова
    if (checkIpBlockStatus($userIp, $bfData) && verifyAdminCredentials($login, $password)) {
        handleSuccessfulLogin($userIp, $bfData);
    } else {
        handleFailedLogin($userIp, $bfData);
        $error = admin_error(); // Забираем текст ошибки из центрального реестра
    }
}

// 3. ОБРАБОТКА ГЕНЕРАЦИИ КЛЮЧА НА ЛЕТУ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_submit'])) {    
    // Эндпоинт работает ТОЛЬКО пока нет credentials.json
    if (file_exists(CMS_CREDS_FILE)) {
        header('HTTP/1.1 403 Forbidden');
        exit('Доступ запрещён: система уже настроена.');
    }
    
    $newPassword = $_POST['new_password'] ?? '';
    if (!empty($newPassword)) {
        $generatedHash = handleLiveHashGeneration($newPassword);
        if ($generatedHash === '') {
            $error = admin_error();
        }
    }
}

// =========================================================================
// КРИТИЧЕСКИЙ БАРЬЕР АВТОРИЗАЦИИ
// =========================================================================
$isAuth = !empty($_SESSION['admin_auth']);

if (!$isAuth) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login_submit']) && !isset($_POST['generate_submit'])) {
        header('HTTP/1.1 403 Forbidden');
        exit('Доступ запрещен: требуется авторизация.');
    }
}

// =========================================================================
// ЗОНА Б: ЗАЩИЩЕННЫЕ ДЕЙСТВИЯ (СЮДА ПОПАДАЕТ ТОЛЬКО АВТОРИЗОВАННЫЙ АДМИН)
// =========================================================================
if ($isAuth) {
    
    // --- ЗАЩИТА ОТ CSRF-АТАК ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $incomingToken = $_POST['csrf_token'] ?? '';
        $sessionToken  = $_SESSION['csrf_token'] ?? '';
        
        if (empty($sessionToken) || !hash_equals($sessionToken, $incomingToken)) {
            header('HTTP/1.1 403 Forbidden');
            exit('Критическая ошибка безопасности: CSRF-токен невалиден или устарел. Обновите страницу.');
        }
    }
    
    // --- КОНТРОЛЬ НЕАКТИВНОСТИ СЕССИИ (Idle Timeout) ---
    $bfData = getLoginAttemptsData();
    checkAdminSessionTimeout($bfData);
    
    require_once 'core/admin_controller.php';
    
    $cronSettings = getBackupSettingsData();
    // Проверяем, включен ли Виртуальный Крон в сохраненном JSON-конфиге
    if (!empty($cronSettings['cron_virtual'])) {
        $lastCronBackup = intval($cronSettings['last_backup_time'] ?? 0);
        $cronPeriodHours = intval($cronSettings['cron_period'] ?? 24);

        // Если текущее время перешагнуло за установленный интервал периодичности
        if (time() >= ($lastCronBackup + ($cronPeriodHours * 3600))) {

            // Тихо и асинхронно вызываем наш готовый файл Крона через внутренний поток PHP
            $cronProtocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $safeHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
            if (!preg_match('#^[a-z0-9\-\.]+(:\d+)?$#i', $safeHost)) {
                $safeHost = 'localhost';
            }

            $cronUrl = $cronProtocol . $safeHost . dirname($_SERVER['SCRIPT_NAME']) . '/cron_backup.php?token=' . urlencode($cronSettings['cron_token'] ?? '');

            // Выставляем таймаут ровно в 1 секунду.
            // Страница админки откроется мгновенно, а сам авто-бэкап продолжит собираться в фоне сервера!
            $cronContext = stream_context_create([
                'http' => [
                    'timeout' => 1.0, 
                    'ignore_errors' => true
                ]
            ]);
            @file_get_contents($cronUrl, false, $cronContext);
        }
    }
    
    // =========================================================================
    // ДИСПЕТЧЕРИЗАЦИЯ И ХЕНДЛИНГ ПОСТ-ЗАПРОСОВ (ЯДРО АДМИНИСТРИРОВАНИЯ)
    // =========================================================================
    
    // Блок А: Обычные GET-запросы
    // Им токен не нужен, они просто читают данные
    if (isset($_GET['fm_api_action']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        require_once __DIR__ . '/core/filemanager_api.php';
        exit;
    }
    if (isset($_GET['download_file']) && isset($_GET['tab']) && $_GET['tab'] === 'backups') { 
        handleDownloadBackup();
    }
    
    // --- ОБРАБОТКА ПОЛУЧЕНИЯ НАСТРОЕК МОДУЛЯ ---
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'module_settings') {
        require_once __DIR__ . '/core/admin_controller.php';
        handleGetModuleSettings();
        exit;
    }
    
    // --- ОБРАБОТКА AJAX-ЗАПРОСОВ КОНСТРУКТОРА ---
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['tab_edit']) && $_GET['tab_edit'] === 'constructor') {
        $pageId = $_GET['id'] ?? '';

        // Железное правило: если ID пустой, принудительно переключаем бэкенд в режим создания
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
        
        $installed = isModuleInstalled($moduleId);
        echo json_encode(['installed' => $installed]);
        exit;
    }
    
    // --- ОБРАБОТКА AJAX-ЗАПРОСОВ ЛОГОВ ---
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_logs' && isset($_GET['tab']) && $_GET['tab'] === 'logs') {
        require_once 'core/admin_controller.php';
        handleGetLogs();
        exit;
    }
    
    // --- ПОЛУЧЕНИЕ СТАТИСТИКИ МИНИФИКАЦИИ (AJAX) ---
    if (isset($_GET['action']) && $_GET['action'] === 'get_minify_stats' && isset($_GET['tab']) && $_GET['tab'] === 'config_vars') {
        require_once 'core/admin_controller.php';
        handleGetMinifyStats();
        exit;
    }

    // Блок Б: Единый барьер для ВСЕХ POST-запросов в системе (и формы, и AJAX Проводника)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // 1. ЖЕЛЕЗНАЯ ПРОВЕРКА CSRF ТОКЕНА ДЛЯ ЛЮБОГО POST-ДЕЙСТВИЯ
        if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            header('HTTP/1.1 403 Forbidden');
            exit('Ошибка безопасности: невалидный CSRF-токен сессии.');
        }
        
        // 2. ПЕРЕХВАТ POST-ЗАПРОСОВ ПРОВОДНИКА (Удаление action=delete, сохранение кода и т.д.)
        // Токен уже проверен строкой выше, операция на 100% легитимна!
        if (isset($_GET['fm_api_action'])) {
            require_once __DIR__ . '/core/filemanager_api.php';
            exit;
        }

        // 3. СТАНДАРТНЫЙ ОБРАБОТЧИК ФОРМ АДМИНКИ (Бренды, Карусели)
        $res = ['success' => '', 'error' => ''];

        if (isset($_POST['save_settings']))       { $res = handleSaveSettings(); }
        elseif (isset($_POST['save_menu']))           { $res = handleSaveMenu(); }
        elseif (isset($_POST['save_page']))           { $res = handleSavePage(); }
        
        elseif (isset($_POST['page_delete']))   { handleDeletePage(); exit; }
        elseif (isset($_POST['page_clone']))    { handleClonePage(); exit; }
        elseif (isset($_POST['page_set_home'])) { handleSetHomePage(); exit; }
        
        elseif (isset($_POST['menu_delete']))    { handleDeleteMenu(); exit; }
        elseif (isset($_POST['menu_set_main']))  { handleSetMainMenu(); exit; }
        
        elseif (isset($_POST['unpack_module']))    { echo json_encode(unpackModule($_POST['module_id'] ?? '')); exit; }
        elseif (isset($_POST['rebuild_min']))      { handleRebuildModuleMin($_POST['module_id'] ?? ''); exit; }
        elseif (isset($_POST['rebuild_min_all']))  { handleRebuildAllMin($_POST['minify_exclude_css'] ?? [], $_POST['minify_exclude_js'] ?? []); exit; }
        elseif (isset($_POST['delete_min_all']))   { handleDeleteAllMin(); exit; }
        elseif (isset($_POST['clear_cache']))      { clearPageCache(); echo json_encode(['success' => true, 'message' => 'Кеш очищен']); exit; }
        
        elseif (isset($_POST['action_unban']))        { $res = handleActionUnban(); }
        elseif (isset($_POST['action_manual_ban']))   { $res = handleActionManualBan(); }
        elseif (isset($_POST['save_security']))       { $res = handleSaveSecurity(); }
        elseif (isset($_POST['ajax_upload_carousel_img'])) { $res = handleAjaxImageUpload(); }
        elseif (isset($_POST['save_backup_settings'])) { $res = handleSaveBackupSettings(); }
        elseif (isset($_POST['init_backup_process']))   { 
            echo json_encode(handleInitBackupProcess());
            exit;
        }
        elseif (isset($_POST['process_backup_step']))  { 
            echo json_encode(handleProcessBackupStep()); 
            exit;
        }
        elseif (isset($_POST['delete_backup_action'])) {
            handleDeleteBackup();
            header('Location: index.php?tab=backups');
            exit;
        }
        elseif (isset($_POST['restore_backup_action'])) {
            handleRestoreBackup();
            header('Location: index.php?tab=backups');
            exit;
        }
        elseif (isset($_POST['module_action'])) {
            $res = handleModuleAction();
            header('Location: index.php?tab=modules');
            exit;
        }
        elseif (isset($_POST['save_log_settings'])) { $res = handleSaveLogSettings(); }
        
        if (!empty($res['success'])) {
            setFlash($res['success'], 'success');
        } elseif (!empty($res['error'])) {
            setFlash($res['error'], 'error');
        }
    }

    $currentTab = $_GET['tab'] ?? 'config_vars';
    $activeBrandIdx = intval($_GET['brand'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конфигурация системы | <?php echo e(strip_tags(SITE_NAME)); ?></title>
    <link href="css/config.css?v=<?php echo filemtime('css/config.css'); ?>" rel="stylesheet" type="text/css"/>
    <link href="/fonts/lucide.css" rel="stylesheet" type="text/css"/>
    <script src="js/tailwindcss.js" type="text/javascript"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

    <?php if (!$isAuth): ?>
        <?php include 'login.php'; ?>
    <?php else: ?>
        <?php 
            // --- ЛОГИКА АВТОМАТИЧЕСКОГО РАЗДЕЛЕНИЯ ТАБОВ ПО ГРУППАМ ---            
            $modulesTabs = [];
            $systemTabs  = [];

            // Группа 1: Модули сайта
            $modulesTabs[] = ['id' => 'config_vars', 'title' => 'Настройки сайта', 'icon' => 'icon-settings', 'visible' => true];
            $modulesTabs[] = ['id' => 'pages', 'title' => 'Страницы', 'icon' => 'icon-file-text', 'visible' => true];
            $modulesTabs[] = ['id' => 'modules', 'title' => 'Управление модулями', 'icon' => 'icon-box', 'visible' => true];
            $modulesTabs[] = ['id' => 'menu', 'title' => 'Меню сайта', 'icon' => 'icon-menu', 'visible' => true];
            
            // Группа 2: Управление системой
            $systemTabs[] = ['id' => 'filemanager', 'title' => 'Проводник (Файлы)', 'icon' => 'icon-folder'];
            $systemTabs[] = ['id' => 'security', 'title' => 'Безопасность', 'icon' => 'icon-shield'];
            $systemTabs[] = ['id' => 'backups', 'title' => 'Резервное копирование', 'icon' => 'icon-database-backup'];
            $systemTabs[] = ['id' => 'logs', 'title' => 'Логи системы', 'icon' => 'icon-clipboard-clock'];

            // Сливаем массивы для общей валидации роутинга в CMS
            $allTabs = array_merge($modulesTabs, $systemTabs);
            $allowedModules = array_column($allTabs, 'id');
            if (!in_array($currentTab, $allowedModules)) {
                $currentTab = 'config_vars';
            }
        ?>

        <!-- ГЛОБАЛЬНЫЙ ХЕДЕР АДМИНКИ -->
        <header class="w-full bg-white border-b border-slate-100 shadow-sm sticky top-0 z-50 h-20 flex items-center justify-between px-6">
            <div class="flex items-center gap-4">
                <!-- МОБИЛЬНАЯ КНОПКА БУРГЕРА -->
                <button type="button" id="js-burger-trigger" class="md:hidden w-12 h-12 border border-slate-200 rounded-xl flex items-center justify-center text-slate-500 cursor-pointer bg-slate-50 hover:bg-slate-100 transition-all" title="Открыть меню">
                    <span class="icon-menu text-xl" id="js-burger-icon"></span>
                </button>
                <div class="flex items-center gap-3">
                    <span class="text-lg font-black tracking-tight text-slate-800">AlPin<span class="text-[var(--primary-color)]">CMS</span></span>
                    <span class="hidden sm:inline-block text-xs font-bold px-2.5 py-1 bg-emerald-50 text-[var(--primary-color)] rounded-full border border-emerald-100">CMS Mode</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <!-- Переключатель темы -->
                <button type="button" id="js-theme-toggle" class="w-10 h-10 border border-slate-200 rounded-xl flex items-center justify-center text-slate-500 hover:bg-slate-100 transition-all cursor-pointer" title="Переключить тему">
                    <span id="js-theme-icon" class="icon-moon text-lg"></span>
                </button>

                <!-- Выход -->
                <a href="index.php?action=logout" class="text-sm font-bold text-slate-500 hover:text-rose-600 transition-colors flex items-center gap-2">
                    Выйти <span class="icon-log-out text-base"></span>
                </a>
            </div>
        </header>

        <!-- МОБИЛЬНЫЙ ТЕМНЫЙ ФОН ПРИ ОТКРЫТИИ МЕНЮ -->
        <div id="js-sidebar-backdrop" class="sidebar-backdrop-overlay"></div>

        <!-- ОСНОВНОЙ ДВУХКОЛОНОЧНЫЙ КАРКАС -->
        <div class="flex flex-row min-h-[calc(100vh-80px)] relative">
            
            <!-- ЛЕВЫЙ САЙДБАР (ПАНЕЛЬ НАВИГАЦИИ С ПОДДЕРЖКОЙ СВОРАЧИВАНИЯ) -->
            <aside id="js-admin-sidebar" class="admin-sidebar-panel">
                
                <!-- БЛОК 1: МОДУЛИ САЙТА -->
                <?php 
                $isModulesActive = false;
                foreach ($modulesTabs as $t) { if ($currentTab === $t['id']) { $isModulesActive = true; break; } }
                ?>
                <div class="sidebar-menu-group <?php echo $isModulesActive ? 'is-active-group' : ''; ?>">
                    <div class="sidebar-group-title js-group-trigger">
                        <span>Модули сайта</span>
                        <span class="icon-chevron-down sidebar-group-chevron"></span>
                    </div>
                    <div class="sidebar-group-content">
                        <?php foreach ($modulesTabs as $tabItem): ?>
                            <?php 
                            $isActive = ($currentTab === $tabItem['id']);
                            $activeClass = $isActive ? 'active' : ''; 
                            ?>
                            <a href="index.php?tab=<?php echo urlencode($tabItem['id']); ?>" class="sidebar-menu-link <?php echo $activeClass; ?> flex items-center justify-between w-full">
                                <div class="flex items-center gap-3">
                                    <span class="<?php echo $tabItem['icon']; ?> text-base"></span>
                                    <span><?php echo e($tabItem['title']); ?></span>
                                </div>
                                <!-- Если модуль отключен, выводим иконку скрытия справа -->
                                <?php if (!$tabItem['visible']): ?>
                                    <span class="icon-eye-off text-xs text-slate-400/80 ml-auto" title="Модуль скрыт на сайте"></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- РАЗДЕЛИТЕЛЬ -->
                <div class="border-t border-slate-100 my-4"></div>

                <!-- БЛОК 2: СИСТЕМА -->
                <?php 
                // Проверяем, есть ли активный пункт во второй группе
                $isSystemActive = false;
                foreach ($systemTabs as $t) { if ($currentTab === $t['id']) { $isSystemActive = true; break; } }
                ?>
                <div class="sidebar-menu-group <?php echo $isSystemActive ? 'is-active-group' : ''; ?>">
                    <div class="sidebar-group-title js-group-trigger">
                        <span>Система</span>
                        <span class="icon-chevron-down sidebar-group-chevron"></span>
                    </div>
                    <div class="sidebar-group-content">
                        <?php foreach ($systemTabs as $tabItem): ?>
                            <?php 
                            $isActive = ($currentTab === $tabItem['id']);
                            $activeClass = $isActive ? 'active' : ''; 
                            ?>
                            <a href="index.php?tab=<?php echo urlencode($tabItem['id']); ?>" class="sidebar-menu-link <?php echo $activeClass; ?>">
                                <span class="<?php echo $tabItem['icon']; ?> text-base"></span>
                                <span><?php echo e($tabItem['title']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

            </aside>

            <!-- ПРАВАЯ РАБОЧАЯ ОБЛАСТЬ КОНТЕНТА -->
            <main class="flex-1 p-6 md:p-10 max-w-full overflow-hidden">
                <div class="bg-white p-6 md:p-10 rounded-[2.5rem] shadow-[0_10px_40px_rgb(0,0,0,0.01)] border border-slate-100 min-h-[450px]">
                    
                    <?php echo renderFlash(); ?>

                    <?php 
                    $adminModuleFile = __DIR__ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . "{$currentTab}.php";
                    $adminModuleExists = file_exists($adminModuleFile);
                    $isSystemTab = in_array($currentTab, ['sections', 'config_vars', 'security', 'filemanager']);
                    ?>

                    <?php if ($adminModuleExists || $isSystemTab): ?>
                        <?php if ($adminModuleExists) { include $adminModuleFile; } ?>
                    <?php else: ?>
                        <div class="text-center py-16 max-w-md mx-auto space-y-6 animate-fade-in">
                            <div class="w-16 h-16 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center mx-auto text-slate-400">
                                <span class="icon-x text-2xl"></span>
                            </div>
                            <div class="space-y-2">
                                <h3 class="text-xl font-bold text-slate-800">Визуальные настройки отсутствуют</h3>
                                <p class="text-sm text-slate-400 leading-relaxed">
                                    Для секции <span class="font-mono bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100 text-slate-600"><?php echo e($currentTab); ?></span> не предусмотрена форма управления в админке. 
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>

        </div>
    <?php endif; ?>

    <!-- ПОДКЛЮЧЕНИЕ ЕДИНОГО СКРИПТА АДМИНКИ -->
    <script src="js/main.js?v=<?php echo filemtime('js/main.js'); ?>" type="text/javascript"></script>
</body>
</html>
