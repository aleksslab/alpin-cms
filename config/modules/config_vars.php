<?php
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$settingsData = getSettingsData();

// Бургер
$burgerEnabled = $settingsData['burger_enabled'] ?? false;
$burgerBreakpoint = $settingsData['burger_breakpoint'] ?? 'md';

// Визуальные элементы
$logo = $settingsData['logo'] ?? '';
$favicon = $settingsData['favicon'] ?? '';

// SEO
$metaTitle = $settingsData['meta_title'] ?? '';
$metaDesc = $settingsData['meta_description'] ?? '';
$metaKeywords = $settingsData['meta_keywords'] ?? '';

// Open Graph
$ogTitle = $settingsData['og_title'] ?? '';
$ogDesc = $settingsData['og_description'] ?? '';
$ogImage = $settingsData['og_image'] ?? '';
$ogUrl = $settingsData['og_url'] ?? '';

// Twitter
$twitterTitle = $settingsData['twitter_title'] ?? '';
$twitterDesc = $settingsData['twitter_description'] ?? '';
$twitterImage = $settingsData['twitter_image'] ?? '';

// Custom CSS/JS
$customCss = $settingsData['custom_css'] ?? '';
$customJs = $settingsData['custom_js'] ?? '';

// Копирайт
$footerCopyright = $settingsData['footer_copyright'] ?? '';

// Хедер/футер варианты
$headerVariants = getHeaderVariants();
$footerVariants = getFooterVariants();
$currentHeader = $settingsData['header_variant'] ?? 'default';
$currentFooter = $settingsData['footer_variant'] ?? 'default';

// Минификация
$assetsMinify = $settingsData['assets_minify'] ?? false;
$minifyExceptions = $settingsData['minify_exceptions'] ?? [];
$modules = getModulesData();
$minifyStats = getMinifyStats();

$token = $_SESSION['csrf_token'] ?? '';
?>

<h1 class="text-3xl font-bold text-slate-800 mb-2">Глобальные переменные</h1>
<p class="text-slate-500 text-sm mb-8">Управление контактными данными, визуальными элементами, SEO и социальными сетями.</p>

<form method="POST" action="index.php?tab=config_vars" class="space-y-6">
    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
    
    <!-- ===== АККОРДЕОН ===== -->
    <div id="config-sections" class="space-y-3">
        
        <?php 
        $sections = [
            'contacts',
            'visual', 
            'colors',
            'header',
            'footer',
            'seo',
            'og',
            'social',
            'assets',
            'cache', 
            'custom'
        ];
        
        foreach ($sections as $section) {
            $sectionFile = __DIR__ . '/config_vars/' . $section . '.php';
            if (file_exists($sectionFile)) {
                include $sectionFile;
            }
        }
        ?>
        
    </div>
    <!-- ===== КОНЕЦ АККОРДЕОНА ===== -->
    <?php
// Преобразуем $minifyExceptions в плоский формат для скрытого поля
$hiddenExceptions = [];
foreach ($minifyExceptions as $id => $data) {
    $hiddenExceptions['minify_exclude_css[' . $id . ']'] = $data['css'] ? '1' : '0';
    $hiddenExceptions['minify_exclude_js[' . $id . ']'] = $data['js'] ? '1' : '0';
}
?>
<input type="hidden" name="minify_exceptions_hidden" id="minify-exceptions-hidden" 
       value='<?php echo e(json_encode($hiddenExceptions, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>'>

    <!-- Кнопка сохранения -->
    <div class="pt-6 border-t border-slate-100 flex justify-end">
        <button type="submit" name="save_settings" class="w-full sm:w-auto bg-[var(--primary-color)] text-white font-bold px-8 py-4 rounded-xl hover:bg-[var(--primary-dark)] transition-all cursor-pointer shadow-lg shadow-[var(--primary-color)]/20 text-base">
            Сохранить настройки
        </button>
    </div>
</form>

<!-- ===== МОДАЛКИ ===== -->
<?php include __DIR__ . '/config_vars/modals.php'; ?>

<!-- Подключаем медиа-модалку -->
<?php $scanRoot = true; ?>
<?php include APP_ROOT . '/config/core/media_modal.php'; ?>

<!-- ===== СКРИПТ ===== -->
<script>
    window.socialNetworks = <?php echo json_encode(getSocialNetworks(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="js/config_vars.js?v=<?php echo filemtime('js/config_vars.js'); ?>"></script>