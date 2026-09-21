<?php
require_once 'config/core/functions.php';
require_once 'config/config.php';

// --- РОУТИНГ: Определяем запрошенную страницу ---

// Получаем путь из URL (например, /about, /contacts)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Убираем GET-параметры (всё после ?)
if (strpos($requestUri, '?') !== false) {
    $requestUri = substr($requestUri, 0, strpos($requestUri, '?'));
}

// Убираем ведущий и trailing слеши
$slug = trim($requestUri, '/');

// Пытаемся загрузить страницу (теперь из data/pages/{slug}.json)
$page = loadPage($slug);

// --- ЕСЛИ СТРАНИЦА НАЙДЕНА — РЕНДЕРИМ ---
$isNewPage = false;
$usedModules = [];
$template = null;

if ($page && $page['status'] === 'published') {
    $isNewPage = true;
    $template = loadTemplate($page['template'] ?? 'full-width');
    $usedModules = getUsedModules($page);
}

// --- ЕСЛИ СТРАНИЦА НЕ НАЙДЕНА — РЕДИРЕКТ НА ГЛАВНУЮ ---
if (!$isNewPage) {
    if ($slug !== '') {
        header('Location: /', true, 301);
        exit;
    }
    
    // Если главная страница не найдена — создаём заглушку
    $page = [
        'id' => 'home',
        'title' => 'Главная',
        'template' => 'full-width',
        'status' => 'published',
        'zones' => ['main' => ['rows' => []]]
    ];
    $isNewPage = true;
    $template = loadTemplate('full-width');
}

// --- Получаем настройки для хедера и футера ---
$settings = function_exists('getSettingsData') ? getSettingsData() : [];
$site_name = $settings['name'] ?? SITE_NAME;
$siteNamePlain = getPlainText($site_name);
$site_slogan = $settings['slogan'] ?? '';
$site_logo = $settings['logo'] ?? SITE_LOGO;
$site_favicon = $settings['favicon'] ?? SITE_FAVICON;
$phone = $settings['phone'] ?? SITE_PHONE;
$email = $settings['email'] ?? SITE_EMAIL;
$address = $settings['address'] ?? SITE_ADDRESS;
$copyrightText = !empty($settings['footer_copyright']) ? trim($settings['footer_copyright']) : 'Все права защищены.';
$customCss = $settings['custom_css'] ?? SITE_CUSTOM_CSS;
$customJs = $settings['custom_js'] ?? SITE_CUSTOM_JS;

// ===== КЕШИРОВАНИЕ СТРАНИЦ =====
$cacheEnabled = $settings['cache_enabled'] ?? false;
$cacheTTL = $settings['cache_ttl'] ?? 86400;
$cacheDir = APP_ROOT . '/cache/pages/';
$cacheFile = $cacheDir . ($slug ?: 'home') . '.html';

// Проверяем кеш
if ($cacheEnabled && file_exists($cacheFile)) {
    $cacheAge = time() - filemtime($cacheFile);
    if ($cacheTTL === 0 || $cacheAge < $cacheTTL) {
        if (defined('DEBUG_SITE') && DEBUG_SITE) {
            header('X-Cache: HIT');
        }
        readfile($cacheFile);
        exit;
    }
}

// Если кеша нет или он устарел — начинаем буферизацию
ob_start();

// Определяем текущий URL для OG
$currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . ($slug ? '/' . $slug : '');

// Мета-данные страницы с приоритетом на глобальные настройки
$pageTitle = !empty(SITE_META_TITLE) ? SITE_META_TITLE : ($page['title'] ?? 'Название компании');
$pageDesc = !empty(SITE_META_DESCRIPTION) ? SITE_META_DESCRIPTION : ($page['meta']['description'] ?? '');
$pageKeywords = !empty(SITE_META_KEYWORDS) ? SITE_META_KEYWORDS : ($page['meta']['keywords'] ?? '');

// OG данные с приоритетом на глобальные настройки
$ogTitle = !empty(OG_TITLE) ? OG_TITLE : $pageTitle;
$ogDesc = !empty(OG_DESCRIPTION) ? OG_DESCRIPTION : $pageDesc;
$ogImage = !empty(OG_IMAGE) ? OG_IMAGE : $site_logo;
$ogUrl = !empty(OG_URL) ? OG_URL : $currentUrl;

// Twitter данные
$twitterTitle = !empty(TWITTER_TITLE) ? TWITTER_TITLE : $ogTitle;
$twitterDesc = !empty(TWITTER_DESCRIPTION) ? TWITTER_DESCRIPTION : $ogDesc;
$twitterImage = !empty(TWITTER_IMAGE) ? TWITTER_IMAGE : $ogImage;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Основные SEO -->
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($pageDesc); ?>">
    <?php if (!empty($pageKeywords)): ?>
        <meta name="keywords" content="<?php echo e($pageKeywords); ?>">
    <?php endif; ?>
    
    <meta property="og:url" content="<?php echo e($ogUrl); ?>" />
    <meta property="og:title" content="<?php echo e($ogTitle); ?>">
    <meta property="og:description" content="<?php echo e($ogDesc); ?>">
    <meta property="og:type" content="website">
    <?php if (!empty($ogImage)): ?>
        <meta property="og:image" content="<?php echo e($ogImage); ?>">
    <?php endif; ?>
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo e($twitterTitle); ?>">
    <meta name="twitter:description" content="<?php echo e($twitterDesc); ?>">
    <?php if (!empty($twitterImage)): ?>
        <meta name="twitter:image" content="<?php echo e($twitterImage); ?>">
    <?php endif; ?>
    
    <?php if (!empty($site_favicon)): ?>
        <link rel="icon" type="image/png" href="/<?php echo e($site_favicon); ?>">
        <link rel="icon" type="image/svg+xml" sizes="any" href="/<?php echo e($site_favicon); ?>">
        <link rel="apple-touch-icon" href="/<?php echo e($site_favicon); ?>">
    <?php else: ?>
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
        <link rel="icon" type="image/svg+xml" sizes="any" href="/favicon.svg">
        <link rel="apple-touch-icon" type="image/png" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/favicon-192.png">    
        <link rel="icon" href="/favicon.ico">
    <?php endif; ?>
    
    <?php if (!empty($site_logo)): ?>
        <link rel="image_src" href="/<?php echo e($site_logo); ?>">
    <?php endif; ?>
    
    <link href="css/style.css" rel="stylesheet" type="text/css"/>
    <link href="fonts/lucide.css" rel="stylesheet" type="text/css"/>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --primary-color: <?php echo e($settings['theme']['primary_color'] ?? '#10B981'); ?>;
            --primary-dark: <?php echo e($settings['theme']['primary_dark'] ?? '#059669'); ?>;
            --bg-main: <?php echo e($settings['theme']['bg_main'] ?? '#FFFFFF'); ?>;
            --bg-card: <?php echo e($settings['theme']['bg_card'] ?? '#FFFFFF'); ?>;
            --bg-section: <?php echo e($settings['theme']['bg_section'] ?? '#F8FAFC'); ?>;
            --text-main: <?php echo e($settings['theme']['text_main'] ?? '#334155'); ?>;
            --text-muted: <?php echo e($settings['theme']['text_muted'] ?? '#64748B'); ?>;
            --border-color: <?php echo e($settings['theme']['border_color'] ?? '#F1F5F9'); ?>;
        }        
        body {
            background-color: var(--bg-main);
            color: var(--text-main);
        }
        body::selection {
            background-color: var(--primary-color);
            color: #ffffff;
        }
        body *::selection {
            background-color: var(--primary-color);
            color: #ffffff;
        }
        <?php echo $customCss; ?>
    </style>    
    
    <?php 
        $assets = getModuleAssets($usedModules, $settings, $page['id'] ?? 'home');
    ?>
    <?php foreach ($assets['css'] as $cssUrl): ?>
        <link rel="stylesheet" href="<?php echo $cssUrl; ?>">
    <?php endforeach; ?>

</head>
<body>
    <?php 
    // --- Рендерим страницу через шаблон ---
    $templateFile = APP_ROOT . '/templates/' . ($template['id'] ?? 'full-width') . '.php';
    if (file_exists($templateFile)) {
        include $templateFile;
    } else {
        echo '<div style="padding: 2rem; text-align: center; color: red;">Шаблон ' . ($template['id'] ?? 'full-width') . ' не найден!</div>';
    }
    ?>

    <?php // COOKIE BANNER ?>
    <div id="cookie-banner" class="fixed bottom-6 left-6 z-50 max-w-xs sm:max-w-sm bg-slate-100 p-5 rounded-xl shadow-lg border border-slate-200">
        <button id="close-cookie-banner" class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 cursor-pointer">
            <span class="icon-x text-lg"></span></button>
        <p class="text-sm text-slate-600 pr-4 font-medium">Мы используем cookies. Продолжая использовать сайт, вы соглашаетесь с нашей <button onclick="openPolicy('cookie')" class="underline cursor-pointer">Политикой cookie</button>.</p>
    </div>

    <?php // BACK TO TOP ?>
    <button id="back-to-top" onclick="window.scrollTo({top:0, behavior:'smooth'})" class="fixed bottom-6 right-6 z-50 w-12 h-12 bg-[var(--primary-color)] text-white rounded-full shadow-lg hover:bg-[var(--primary-dark)] hover:-translate-y-1 transition-all hidden items-center justify-center group cursor-pointer">
        <span class="icon-chevron-up text-2xl group-hover:-translate-y-0.5 transition-transform"></span>
    </button>
    
    <?php // POLICY MODAL ?>
    <div id="policy-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] w-full max-w-2xl overflow-hidden shadow-2xl relative flex flex-col max-h-[90vh]">
            <div class="p-6 md:p-8 border-b border-slate-100 flex items-center justify-between">
                <h2 id="modal-title" class="text-xl md:text-2xl font-bold text-slate-800"></h2>
                <button onclick="closePolicy()" class="text-slate-400 hover:text-slate-600 w-10 h-10 flex items-center justify-center rounded-full hover:bg-slate-100 cursor-pointer">
                    <span class="icon-x text-xl"></span>
                </button>
            </div>
            <div id="modal-body" class="p-6 md:p-8 overflow-y-auto text-slate-600 space-y-4"></div>
            <div class="p-6 md:p-8 border-t border-slate-100 bg-slate-50 flex justify-end">
                <button onclick="closePolicy()" class="btn-primary py-3 px-8">Понятно</button>
            </div>
        </div>
    </div>
    
    <?php if (!empty($customJs)): ?>
        <script><?php echo $customJs; ?></script>
    <?php endif; ?>
    
    <script src="js/script.js" type="text/javascript"></script>
    <?php foreach ($assets['js'] as $jsUrl): ?>
        <script src="<?php echo $jsUrl; ?>"></script>
    <?php endforeach; ?>
</body>
</html>

<?php
// ===== СОХРАНЯЕМ КЕШ =====
$html = ob_get_clean();

if ($cacheEnabled) {
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    file_put_contents($cacheFile, $html);
}

echo $html;
?>