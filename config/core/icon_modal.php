<?php
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) { die('Доступ запрещен'); }

/**
 * ЛОКАЛЬНАЯ АВТОНОМНАЯ ФУНКЦИЯ ПАРСИНГА CSS ШРИФТОВ
 */
function cms_get_parsed_css_icons_local(): array {
    $cssFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'lucide.css';
    
    if (!file_exists($cssFile)) {
        return [];
    }

    $cssContent = file_get_contents($cssFile);
    if (empty($cssContent)) {
        return [];
    }
    
    // Регулярное выражение ищет строго подстроку .icon-название, останавливаясь перед ::before или пробелом
    preg_match_all('/\.icon-([a-z0-9_-]+)/i', $cssContent, $matches);
    
    // В preg_match_all по индексу [0] всегда лежит массив полных совпадений (с точкой: .icon-activity)
    if (!empty($matches[0])) {
        $cleanIcons = [];
        
        foreach ($matches[0] as $rawClass) {
            $className = ltrim($rawClass, '.'); // Мягко срезаем ведущую точку
            if (!empty($className)) {
                $cleanIcons[] = $className;
            }
        }
        
        // Удаляем дубликаты и сортируем по алфавиту
        $uniqueIcons = array_unique($cleanIcons);
        sort($uniqueIcons);
        
        return $uniqueIcons;
    }

    return [];
}

// Вызываем нашу локальную гарантированную функцию
$cmsIconsList = cms_get_parsed_css_icons_local();
?>

<!-- КОНТЕНТ СТРУКТУРЫ МОДАЛКИ ВЫБОРА ИКОНОК -->
<div id="icon-picker-modal" class="modal-backdrop-fixed animate-fade-in">
    <div class="modal-content-card flex-col">
        
        <!-- 1. Шапка модального окна -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <div>
                <h3 class="text-base font-bold text-slate-800">Выбор системной иконки</h3>
                <p class="text-xs text-slate-400 my-1">Автоматически отсканировано из /fonts/lucide.css (Найдено: <?php echo count($cmsIconsList); ?>)</p>
            </div>
            <button type="button" onclick="closeIconModal()" class="w-12 h-12 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all cursor-pointer">
                <span class="icon-x text-xl"></span>
            </button>
        </div>

        <!-- 2. Живой поиск по имени -->
        <div class="p-4 border-b border-slate-100 bg-slate-50/20 shrink-0">
            <div class="relative">
                <input type="text" id="js-icon-search-input" placeholder="Поиск иконки по названию (например: star, heart, award)..." class="w-full pl-11 pr-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" oninput="filterCmsIconsGallery(this.value)" />
                <span class="icon-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></span>
            </div>
        </div>

        <!-- 3. Зона прокрутки сетки динамических иконок -->
        <div class="media-scroll-zone" style="max-height: 380px !important;">
            <div id="js-icons-picker-grid" class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3 w-full">
                <?php if (empty($cmsIconsList)): ?>
                    <p class="text-rose-600 text-xs col-span-full py-12 text-center w-full block font-semibold">
                        Ошибка чтения: Файл /fonts/lucide.css не найден или пуст по пути: <?php echo dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'lucide.css'; ?>
                    </p>
                <?php else: ?>
                    <?php foreach ($cmsIconsList as $iconClass): ?>
                        <?php $cleanName = str_replace('icon-', '', $iconClass); ?>
                        <div onclick="selectIconForTarget('<?php echo e($iconClass); ?>')" class="js-icon-picker-item border border-slate-100 rounded-xl p-3 bg-slate-50/30 hover:bg-emerald-50/40 hover:border-emerald-200 flex flex-col items-center justify-center gap-2 cursor-pointer transition-all duration-150 group" data-icon-name="<?php echo e($cleanName); ?>">
                            <span class="<?php echo e($iconClass); ?> text-2xl text-slate-600 group-hover:text-[var(--primary-color)] group-hover:scale-110 transition-transform"></span>
                            <span class="text-[9px] text-slate-400 text-center font-medium truncate w-full block group-hover:text-[var(--primary-dark)]" title="<?php echo e($cleanName); ?>"><?php echo e($cleanName); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <p id="js-empty-icons-msg" class="hidden text-slate-400 text-xs col-span-full py-12 text-center w-full block">Иконки с таким именем не найдены.</p>
            </div>
        </div>

    </div>
</div>

<!-- Подключаем JS-файл логики галереи и поиска -->
<script src="js/icon_modal.js?v=<?php echo filemtime('js/icon_modal.js'); ?>" type="text/javascript"></script>
