<?php
require_once 'config.php';
require_once 'core/functions.php';
require_once 'core/admin_auth.php';

// Синхронизируем манифесты плагинов с JSON (подхватываем изменения из plugin.json)
if (function_exists('syncPluginsData')) {
    syncPluginsData();
}

// Загружаем активные плагины
if (function_exists('loadActivePlugins')) {
    loadActivePlugins();
}

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
        $_SESSION['admin_login'] = preg_replace('/[\r\n\t]+/', '', $login);
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
    
    // === PREVIEW CLEANUP TRIGGERS ===
    
    // Успешный логин — очистка ВСЕГО
    // Флаг устанавливается в handleSuccessfulLogin, обрабатывается здесь
    if (!empty($_SESSION['preview_cleanup_all_pending'])) {
        cleanupAllPreviews();
        unset($_SESSION['preview_cleanup_all_pending']);
    }
    
    // --- ВИРТУАЛЬНЫЕ КРОНЫ (БЭКАП И ПУБЛИКАЦИЯ) ---
    require_once __DIR__ . '/core/virtual_crons.php';
    run_virtual_crons();
    
    // =========================================================================
    // ДИСПЕТЧЕРИЗАЦИЯ И ХЕНДЛИНГ ПОСТ-ЗАПРОСОВ (ЯДРО АДМИНИСТРИРОВАНИЯ)
    // =========================================================================
    
    // --- ХУК ДЛЯ ПЛАГИНОВ (ДО ОБРАБОТКИ AJAX) ---
    do_action('admin_init');

    // --- ОБРАБОТКА GET/AJAX ЗАПРОСОВ ---
    require_once __DIR__ . '/core/admin_ajax.php';
    handle_admin_ajax_actions();

    // --- Единый барьер для ВСЕХ POST-запросов в системе (и формы, и AJAX Проводника) ---
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

        // 3. ОБРАБОТКА POST-ЗАПРОСОВ (ФОРМЫ И AJAX)
        require_once __DIR__ . '/core/admin_actions.php';
        handle_admin_post_actions();
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
            // --- ФОРМИРОВАНИЕ ВКЛАДОК ---
            require_once __DIR__ . '/core/admin_menu.php';
            $tabsData = get_admin_tabs();
            
            $modulesTabs = $tabsData['modules'];
            $systemTabs  = $tabsData['system'];
            $allTabs     = $tabsData['all'];

            // Валидация текущей вкладки
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
                <!-- Уведомление об обновлениях (хук для плагинов) -->
                <?php do_action('admin_header_notifications'); ?>
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
                        <span>Контент и дизайн</span>
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
                    // 1. Ищем в стандартных модулях админки
                    $adminModuleFile = __DIR__ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . "{$currentTab}.php";
                    $adminModuleExists = file_exists($adminModuleFile);
                    
                    // 2. Если нет в модулях — ищем в плагинах
                    $pluginPageFile = null;
                    if (!$adminModuleExists) {
                        $pluginPageFile = PLUGINS_DIR . DIRECTORY_SEPARATOR . $currentTab . DIRECTORY_SEPARATOR . 'admin-page.php';
                    }
                    
                    $isSystemTab = in_array($currentTab, ['sections', 'config_vars', 'security', 'filemanager']);
                    ?>

                    <?php if ($adminModuleExists || $pluginPageFile || $isSystemTab): ?>
                        <?php 
                        if ($adminModuleExists) { 
                            include $adminModuleFile; 
                        } elseif ($pluginPageFile && file_exists($pluginPageFile)) {
                            include $pluginPageFile;
                        }
                        ?>
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
