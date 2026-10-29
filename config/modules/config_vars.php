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

<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Глобальные переменные</h1>
        <p class="text-slate-500 text-sm">Управление контентом, дизайном, SEO и системными настройками сайта.</p>
    </div>
    <button type="button" onclick="openSectionsModal()" class="px-4 py-2.5 bg-slate-100 border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-all flex items-center gap-2 text-sm whitespace-nowrap">
        <span class="icon-sliders-horizontal text-base"></span>
        Настроить разделы
    </button>
</div>

<form method="POST" action="index.php?tab=config_vars" class="space-y-6">
    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
    <input type="hidden" name="config_sections_order_json" id="config_sections_order_json" value="<?php echo e(json_encode($settingsData['config_sections_order'] ?? [])); ?>">
    <input type="hidden" name="config_sections_hidden_json" id="config_sections_hidden_json" value="<?php echo e(json_encode($settingsData['config_sections_hidden'] ?? [])); ?>">
    
    <!-- ===== АККОРДЕОН ===== -->
    <div id="config-sections" class="space-y-3">
        
        <?php 
        // 1. Секции ядра
        $sectionsCore = [
            'contacts',
            'visual', 
            'colors',
            'header',
            'footer',
            'seo',
            'og',
            'social',
            'assets',
            'media',
            'cache',
            'history',
            'cron', 
            'custom'
        ];

        // 2. Секции плагинов (автоматически)
        $sectionsPlugins = get_plugin_sections();
        $pluginSectionIds = array_keys($sectionsPlugins);

        // 3. Объединяем
        $allSectionIds = array_merge($sectionsCore, $pluginSectionIds);

        // 4. Применяем порядок и скрытие
        $savedOrder = $settingsData['config_sections_order'] ?? [];
        $hiddenSections = $settingsData['config_sections_hidden'] ?? [];
        
        if (!empty($savedOrder)) {
            $ordered = [];
            foreach ($savedOrder as $id) {
                if (in_array($id, $allSectionIds)) {
                    $ordered[] = $id;
                }
            }
            foreach ($allSectionIds as $id) {
                if (!in_array($id, $ordered)) {
                    $ordered[] = $id;
                }
            }
            $allSectionIds = $ordered;
        }
        
        // 5. Выводим
        foreach ($allSectionIds as $sectionId) {
            if (in_array($sectionId, $hiddenSections)) {
                continue;
            }
            
            // Ядро
            $sectionFile = __DIR__ . '/config_vars/' . $sectionId . '.php';
            if (file_exists($sectionFile)) {
                include $sectionFile;
                continue;
            }
            
            // Плагины
            if (isset($sectionsPlugins[$sectionId])) {
                $plugin = $sectionsPlugins[$sectionId];
                $pluginFile = PLUGINS_DIR . '/' . $sectionId . '/admin-section.php';
                if (file_exists($pluginFile)) {
                    include $pluginFile;
                } elseif (is_callable($plugin['callback'])) {
                    call_user_func($plugin['callback']);
                }
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
<script>
(function() {
    'use strict';

    // Данные о секциях (передаются из PHP)
    const allSections = <?php 
        $list = [];
        $titlesCore = [
            'contacts' => 'Контактная информация',
            'visual' => 'Визуальные элементы',
            'colors' => 'Цветовая схема',
            'header' => 'Настройки хедера',
            'footer' => 'Настройки футера',
            'seo' => 'SEO настройки',
            'og' => 'Open Graph & Twitter',
            'social' => 'Социальные сети',
            'assets' => 'Минификация',
            'media' => 'Медиа',
            'cache' => 'Кеширование',
            'history' => 'История изменений',
            'cron' => 'Интеграция с планировщиком',
            'custom' => 'Произвольный код'
        ];
        
        foreach ($allSectionIds as $id) {
            if (isset($titlesCore[$id])) {
                $list[] = ['id' => $id, 'title' => $titlesCore[$id]];
            } elseif (isset($sectionsPlugins[$id])) {
                $list[] = ['id' => $id, 'title' => $sectionsPlugins[$id]['title']];
            }
        }
        echo json_encode($list, JSON_UNESCAPED_UNICODE);
    ?>;
    
    const hiddenSections = <?php echo json_encode($hiddenSections ?? [], JSON_UNESCAPED_UNICODE); ?>;

    window.openSectionsModal = function() {
        const modal = document.getElementById('sections-settings-modal');
        const container = document.getElementById('sections-list-container');
        if (!modal || !container) return;

        // Сортируем: сначала те, что в savedOrder, потом остальные
        const savedOrder = JSON.parse(document.getElementById('config_sections_order_json').value || '[]');
        let sortedSections = [];
        
        // Добавляем из savedOrder
        savedOrder.forEach(id => {
            const found = allSections.find(s => s.id === id);
            if (found) sortedSections.push(found);
        });
        
        // Добавляем остальные
        allSections.forEach(s => {
            if (!sortedSections.find(item => item.id === s.id)) {
                sortedSections.push(s);
            }
        });

        // Рендерим список
        container.innerHTML = sortedSections.map((section, index) => {
            const isHidden = hiddenSections.includes(section.id);
            return `
                <div class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl mb-2" data-id="${section.id}">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-slate-700">${section.title}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="moveSection(this, -1)" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 ${index === 0 ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer'}" ${index === 0 ? 'disabled' : ''}>
                            <span class="icon-chevron-up text-sm"></span>
                        </button>
                        <button type="button" onclick="moveSection(this, 1)" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 ${index === sortedSections.length - 1 ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer'}" ${index === sortedSections.length - 1 ? 'disabled' : ''}>
                            <span class="icon-chevron-down text-sm"></span>
                        </button>
                        <label class="flex items-center gap-2 cursor-pointer ml-2 pl-2 border-l border-slate-200">
                            <input type="checkbox" class="section-visibility-toggle w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]" ${isHidden ? '' : 'checked'} data-id="${section.id}">
                            <span class="text-xs text-slate-500">Показывать</span>
                        </label>
                    </div>
                </div>
            `;
        }).join('');

        modal.classList.add('active');
    };

    window.closeSectionsModal = function() {
        document.getElementById('sections-settings-modal').classList.remove('active');
    };

    // Глобальный массив с текущим порядком
    let currentSections = [];

    window.openSectionsModal = function() {
        const modal = document.getElementById('sections-settings-modal');
        const container = document.getElementById('sections-list-container');
        if (!modal || !container) return;

        // Сортируем: сначала те, что в savedOrder, потом остальные
        const savedOrder = JSON.parse(document.getElementById('config_sections_order_json').value || '[]');
        currentSections = [];
        
        // Добавляем из savedOrder
        savedOrder.forEach(id => {
            const found = allSections.find(s => s.id === id);
            if (found) currentSections.push(found);
        });
        
        // Добавляем остальные
        allSections.forEach(s => {
            if (!currentSections.find(item => item.id === s.id)) {
                currentSections.push(s);
            }
        });

        renderSectionsList();
        modal.classList.add('active');
    };

    function renderSectionsList() {
        const container = document.getElementById('sections-list-container');
        const hidden = JSON.parse(document.getElementById('config_sections_hidden_json').value || '[]');

        container.innerHTML = currentSections.map((section, index) => {
            const isHidden = hidden.includes(section.id);
            return `
                <div class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl mb-2" data-id="${section.id}">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-slate-700">${section.title}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="moveSection(${index}, -1)" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 ${index === 0 ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer'}" ${index === 0 ? 'disabled' : ''}>
                            <span class="icon-chevron-up text-sm"></span>
                        </button>
                        <button type="button" onclick="moveSection(${index}, 1)" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 ${index === currentSections.length - 1 ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer'}" ${index === currentSections.length - 1 ? 'disabled' : ''}>
                            <span class="icon-chevron-down text-sm"></span>
                        </button>
                        <label class="flex items-center gap-2 cursor-pointer ml-2 pl-2 border-l border-slate-200">
                            <input type="checkbox" class="section-visibility-toggle w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]" ${isHidden ? '' : 'checked'} data-id="${section.id}" onchange="toggleSectionVisibility(this)">
                            <span class="text-xs text-slate-500">Показывать</span>
                        </label>
                    </div>
                </div>
            `;
        }).join('');
    }

    window.moveSection = function(index, direction) {
        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= currentSections.length) return;
        
        // Меняем местами в массиве
        const temp = currentSections[index];
        currentSections[index] = currentSections[newIndex];
        currentSections[newIndex] = temp;
        
        // Перерисовываем
        renderSectionsList();
    };

    window.toggleSectionVisibility = function(checkbox) {
        // Просто перерисовывать не надо, чекбокс сам меняет состояние
        // Но можно добавить логику, если нужно
    };

    window.closeSectionsModal = function() {
        document.getElementById('sections-settings-modal').classList.remove('active');
    };

    window.saveSectionsSettings = function() {
        const order = currentSections.map(s => s.id);
        const hidden = [];
        
        // Собираем скрытые (по чекбоксам)
        document.querySelectorAll('.section-visibility-toggle').forEach(cb => {
            if (!cb.checked) {
                hidden.push(cb.getAttribute('data-id'));
            }
        });
        
        document.getElementById('config_sections_order_json').value = JSON.stringify(order);
        document.getElementById('config_sections_hidden_json').value = JSON.stringify(hidden);
        
        closeSectionsModal();
    };

    window.saveSectionsSettings = function() {
        const container = document.getElementById('sections-list-container');
        const rows = Array.from(container.children);
        
        const order = rows.map(row => row.getAttribute('data-id'));
        const hidden = [];
        
        rows.forEach(row => {
            const checkbox = row.querySelector('.section-visibility-toggle');
            if (checkbox && !checkbox.checked) {
                hidden.push(row.getAttribute('data-id'));
            }
        });
        
        document.getElementById('config_sections_order_json').value = JSON.stringify(order);
        document.getElementById('config_sections_hidden_json').value = JSON.stringify(hidden);
        
        closeSectionsModal();
    };
})();
</script>
<script src="js/config_vars.js?v=<?php echo filemtime('js/config_vars.js'); ?>"></script>