<?php
// --- config/modules/pages/constructor.php (Конструктор страниц) ---
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$pageId = $_GET['id'] ?? ($constructorPage['id'] ?? '');
$templateId = $_GET['template'] ?? ($constructorPage['template'] ?? 'full-width');
$modeCreate = $action === 'create';

// Получаем состояние чекбоксов из GET (для AJAX-обновления)
$showHeader = isset($_GET['show_header']) ? (bool)$_GET['show_header'] : ($constructorPage['show_header'] ?? true);
$showFooter = isset($_GET['show_footer']) ? (bool)$_GET['show_footer'] : ($constructorPage['show_footer'] ?? true);

if (empty($pageId) && !$modeCreate) {
    echo '<div class="p-8 text-center text-rose-500 bg-rose-50 rounded-2xl border border-rose-100">
            <span class="icon-alert-triangle text-2xl block mb-2"></span>
            <p class="font-semibold">Страница не выбрана</p>
            <p class="text-sm text-slate-500 mt-1">Для работы с конструктором перейдите в режим редактирования страницы.</p>
          </div>';
    return;
}

$page = $modeCreate ? [] : loadPageById($pageId);

if (!$page && !$modeCreate) {
    echo '<div class="p-8 text-center text-rose-500 bg-rose-50 rounded-2xl border border-rose-100">
            <span class="icon-alert-triangle text-2xl block mb-2"></span>
            <p class="font-semibold">Страница не найдена</p>
            <p class="text-sm text-slate-500 mt-1">Страница с ID "' . e($pageId) . '" не существует.</p>
          </div>';
    return;
}

// Принудительно используем переданные значения
$page['template'] = $templateId;
$page['show_header'] = $showHeader;
$page['show_footer'] = $showFooter;

$template = loadTemplate($page['template'] ?? 'full-width');
$zones = $template['zones'] ?? ['main' => ['class' => '']];

$modules = getModulesData();
$rows = $page['rows'] ?? [];

$rowsByZone = [];
foreach ($rows as $row) {
    $zone = $row['zone'] ?? 'main';
    if (!isset($rowsByZone[$zone])) {
        $rowsByZone[$zone] = [];
    }
    $rowsByZone[$zone][] = $row;
}

$token = $_SESSION['csrf_token'] ?? '';

// Получаем список шаблонов для выбора
$templatesData = getTemplateData();
$templates = $templatesData['templates'] ?? [];

// Полный вывод с обёрткой
?>
<div id="constructor-app" 
     data-page-id="<?php echo e($pageId); ?>" 
     data-token="<?php echo e($token); ?>"
     data-modules='<?php echo htmlspecialchars(json_encode($modules, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>'>
    
    <!-- Функция рендеринга должна вызываться СТРОГО внутри открытого контейнера div -->
    <?php renderConstructor($pageId, $token, $modules, $rows, $rowsByZone, $zones, $template, $templates, $showHeader, $showFooter, $templateId); ?>
    
    <!-- Скрытый инпут состояния, который читает JS -->
    <input type="hidden" id="constructor-data" value='<?php echo htmlspecialchars(json_encode($rows, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>'>
</div>

<!-- ПОДКЛЮЧЕНИЕ ПРАВИЛЬНЫХ СКРИПТОВ ДВИЖКА (СТРОГО ПОСЛЕ ЗАКРЫТИЯ CONSTRUCTOR-APP) -->
<script src="js/constructor.js?v=<?php echo filemtime('js/constructor.js'); ?>" type="text/javascript"></script>
<script src="js/constructor-template.js?v=<?php echo filemtime('js/constructor-template.js'); ?>" type="text/javascript"></script>

<?php
// --- Функция рендеринга конструктора (Начало объявления) ---
function renderConstructor($pageId, $token, $modules, $rows, $rowsByZone, $zones, $template, $templates, $showHeader, $showFooter, $currentTemplate) {
    $hasHeader = isset($zones['header']) && !$showHeader;
    $hasFooter = isset($zones['footer']) && !$showFooter;
    $hasSidebarLeft = isset($zones['sidebar-left']);
    $hasSidebarRight = isset($zones['sidebar-right']);
    $hasHero = isset($zones['hero']);
    $hasMain = isset($zones['main']);
    
    // Определяем порядок зон из templates.json
    $zoneOrder = array_keys($zones);
    
    // Зоны, которые должны быть в flex-контейнере
    $flexZones = ['main', 'sidebar-left', 'sidebar-right'];
    $hasFlexZones = false;
    foreach ($flexZones as $fz) {
        if (isset($zones[$fz])) {
            $hasFlexZones = true;
            break;
        }
    }
    
    // Цвета для разных зон
    $zoneColors = [
        'header' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-700', 'label' => 'HEADER (кастомный)'],
        'hero' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-700', 'label' => 'HERO'],
        'main' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-600', 'label' => 'MAIN'],
        'sidebar-left' => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'text' => 'text-purple-600', 'label' => 'САЙДБАР ЛЕВЫЙ'],
        'sidebar-right' => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'text' => 'text-purple-600', 'label' => 'САЙДБАР ПРАВЫЙ'],
        'footer' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-700', 'label' => 'FOOTER (кастомный)']
    ];

    // Синхронизируем имя переменной справочника манифестов для вложенного файла row.php
    $modulesList = $modules;
?>
    <!-- ВЫБОР ШАБЛОНА -->
    <div class="mb-6 p-4 bg-slate-50 rounded-2xl border border-slate-200">
        <div class="flex flex-wrap items-center gap-4">
            <label class="text-sm font-semibold text-slate-700">Шаблон:</label>
            <select id="constructor-template-select" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-[var(--primary-color)]">
                <?php foreach ($templates as $tmpl): ?>
                    <option value="<?php echo e($tmpl['id']); ?>" <?php echo ($currentTemplate === $tmpl['id']) ? 'selected' : ''; ?>>
                        <?php echo e($tmpl['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="window.changeTemplate()" class="px-4 py-2 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all text-sm">
                Применить шаблон
            </button>
            <input type="hidden" name="template" id="template-hidden" value="<?php echo e($currentTemplate); ?>">
            <button type="button" onclick="window.previewPage()" class="px-4 py-2 border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all text-sm flex items-center gap-2">
                <span class="icon-eye text-base"></span> Предпросмотр
            </button>
        </div>
    </div>

    <!-- ХЕДЕР И ФУТЕР -->
    <div class="mb-6 p-4 bg-slate-50 rounded-2xl border border-slate-200">
        <div class="flex flex-wrap gap-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="show_header" value="1" <?php echo $showHeader ? 'checked' : ''; ?> 
                       class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]"
                       onchange="window.toggleHeaderFooter()">
                <span class="text-sm font-medium text-slate-700">Показывать глобальный хедер</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="show_footer" value="1" <?php echo $showFooter ? 'checked' : ''; ?> 
                       class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]"
                       onchange="window.toggleHeaderFooter()">
                <span class="text-sm font-medium text-slate-700">Показывать глобальный футер</span>
            </label>
            <span class="text-xs text-slate-400">При отключении хедера/футера вы можете добавить свои блоки в соответствующие зоны.</span>
        </div>
        <input type="hidden" name="show_header_hidden" id="show-header-hidden" value="<?php echo $showHeader ? 1 : 0; ?>">
        <input type="hidden" name="show_footer_hidden" id="show-footer-hidden" value="<?php echo $showFooter ? 1 : 0; ?>">
    </div>

    <!-- ЗОНЫ КОНСТРУКТОРА -->
    <div id="constructor-zones" class="space-y-4">
        
        <?php 
        $flexOpen = false;
        $flexClosed = true;
        
        foreach ($zoneOrder as $zoneName): 
            $zoneConfig = $zones[$zoneName] ?? null;
            if (!$zoneConfig) continue;
            
            $isGlobal = !empty($zoneConfig['global']);
            
            // Пропускаем глобальные зоны, если они включены в настройках
            if ($isGlobal) {
                if ($zoneName === 'header' && $showHeader) continue;
                if ($zoneName === 'footer' && $showFooter) continue;
            }
            
            // Проверяем, есть ли ряды в этой зоне
            $zoneRows = $rowsByZone[$zoneName] ?? [];
            $isEmpty = empty($zoneRows);
            
            // Проверяем, является ли зона flex-зоной (main, sidebar-left, sidebar-right)
            $isFlexZone = in_array($zoneName, $flexZones);
            $zoneClass = $zoneConfig['class'] ?? '';
            
            // Если flex-зона и ещё не открыли flex-контейнер — открываем
            if ($isFlexZone && $hasFlexZones && !$flexOpen) {
                echo '<div class="flex flex-wrap">';
                $flexOpen = true;
                $flexClosed = false;
            }
            
            $colors = $zoneColors[$zoneName] ?? ['bg' => 'bg-slate-50', 'border' => 'border-slate-200', 'text' => 'text-slate-600', 'label' => strtoupper($zoneName)];
            ?>
            
            <div class="border border-slate-200 rounded-2xl overflow-hidden constructor-zone <?php echo $zoneClass; ?>" data-zone="<?php echo e($zoneName); ?>">
                <div class="<?php echo $colors['bg']; ?> px-4 py-2 border-b <?php echo $colors['border']; ?> flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold <?php echo $colors['text']; ?> uppercase tracking-wider"><?php echo $colors['label']; ?></span>
                        <span class="text-[10px] text-slate-500 font-mono"><?php echo e($zoneConfig['class'] ?? ''); ?></span>
                        <span class="text-[10px] text-slate-400">(<?php echo count($zoneRows); ?> рядов)</span>
                    </div>
                    <button type="button" onclick="window.addRow('<?php echo e($zoneName); ?>')" class="px-2.5 py-1 text-xs bg-white border <?php echo $colors['border']; ?> <?php echo $colors['text']; ?> font-bold rounded-lg hover:bg-white/80 transition-all flex items-center gap-1">
                        <span class="icon-plus text-xs"></span> Добавить ряд
                    </button>
                </div>
                <div class="p-3 space-y-3 constructor-rows" data-zone="<?php echo e($zoneName); ?>">
                    <?php if ($isEmpty): ?>
                        <div class="text-center text-slate-400 text-sm py-6 border-2 border-dashed border-slate-200 rounded-xl">Нет рядов.</div>
                    <?php else: ?>
                        <?php foreach ($zoneRows as $row): ?>
                            <?php include __DIR__ . '/row.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php 
            // Если это последняя flex-зона — закрываем flex-контейнер
            if ($isFlexZone && $flexOpen) {
                // Проверяем, есть ли ещё flex-зоны дальше
                $currentIndex = array_search($zoneName, $zoneOrder);
                $hasMoreFlex = false;
                for ($i = $currentIndex + 1; $i < count($zoneOrder); $i++) {
                    if (in_array($zoneOrder[$i], $flexZones)) {
                        $hasMoreFlex = true;
                        break;
                    }
                }
                if (!$hasMoreFlex) {
                    echo '</div>';
                    $flexOpen = false;
                    $flexClosed = true;
                }
            }
        endforeach; 
        ?>
        
    </div>
<?php
}
?>
<?php //<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.36.2/ace.min.js"></script> ?>
<script src="js/ace/ace.js"></script>
