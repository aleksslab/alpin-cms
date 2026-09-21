<?php
/**
 * Модуль: Портфолио — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$categories = $moduleData['categories'] ?? '';
$cols = $moduleData['cols'] ?? 3;
$perPage = $moduleData['per_page'] ?? 6;
$showAll = $moduleData['show_all'] ?? false;
$showTags = $moduleData['show_tags'] ?? true;
$showAllFilter = $moduleData['show_all_filter'] ?? true;
$enableLightbox = $moduleData['enable_lightbox'] ?? true;
$settingsClass = $moduleData['settings']['class'] ?? '';

if (!is_array($items) || empty($items)) {
    $items = [
        ['image' => '', 'title' => '', 'category' => '', 'link' => '', 'description' => '']
    ];
}

// Приводим категории к массиву
if (is_string($categories)) {
    $categories = array_filter(array_map('trim', explode('|', $categories)));
}
if (!is_array($categories) || empty($categories)) {
    $categories = ['Все'];
}

$colsOptions = [2, 3, 4];
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Наши работы" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Портфолио проектов" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки отображения -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки отображения</h4>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Колонок</label>
                <select name="cols" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <?php foreach ($colsOptions as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $cols == $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Количество на страницу</label>
                <input type="number" name="per_page" value="<?php echo e($perPage); ?>" min="3" max="24" step="3" placeholder="6" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field flex items-end">
                <label class="flex items-center gap-2 cursor-pointer pb-2">
                    <input type="hidden" name="show_all" value="0">
                    <input type="checkbox" name="show_all" value="1" <?php echo $showAll ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm text-slate-600 font-medium">Отображать все карточки</span>
                </label>
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field flex items-end">
                <label class="flex items-center gap-2 cursor-pointer pb-2">
                    <input type="hidden" name="show_tags" value="0">
                    <input type="checkbox" name="show_tags" value="1" <?php echo $showTags ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm text-slate-600 font-medium">Отображать теги в карточках</span>
                </label>
            </div>
            <div class="editor-field flex items-end">
                <label class="flex items-center gap-2 cursor-pointer pb-2">
                    <input type="hidden" name="show_all_filter" value="0">
                    <input type="checkbox" name="show_all_filter" value="1" <?php echo $showAllFilter ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm text-slate-600 font-medium">Показывать "Все" в фильтрах</span>
                </label>
            </div>
        </div>

        <!-- Lightbox -->
        <div class="editor-row">
            <div class="editor-field flex items-end">
                <label class="flex items-center gap-2 cursor-pointer pb-2">
                    <input type="hidden" name="enable_lightbox" value="0">
                    <input type="checkbox" name="enable_lightbox" value="1" <?php echo $enableLightbox ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm text-slate-600 font-medium">Открывать изображения в Lightbox</span>
                </label>
            </div>
            <div class="editor-field">
                <!-- Пусто для выравнивания -->
            </div>
        </div>
    </div>

    <!-- БЛОК 3: Категории -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Категории</h4>
        <div id="categories-container" class="flex flex-wrap gap-2">
            <?php foreach ($categories as $cat): ?>
                <?php if ($cat !== 'Все'): ?>
                    <span class="category-tag inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-sm text-slate-700">
                        <?php echo e($cat); ?>
                        <button type="button" class="js-remove-category text-slate-400 hover:text-rose-500 transition-colors" data-category="<?php echo e($cat); ?>">
                            <span class="icon-x text-sm"></span>
                        </button>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="flex gap-2">
            <input type="text" id="new-category-input" placeholder="Новая категория..." class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            <button type="button" id="add-category-btn" class="px-4 py-2 bg-[var(--primary-color)] text-white font-medium text-sm rounded-lg hover:bg-[var(--primary-dark)] transition-all cursor-pointer">
                Добавить
            </button>
        </div>
        <input type="hidden" name="categories" value="<?php echo e(implode('|', $categories)); ?>" id="categories-hidden">
    </div>

    <!-- БЛОК 4: Работы -->
    <div id="portfolio-items-container" class="space-y-3">
        <?php 
        $itemTemplate = '
        <div class="portfolio-item-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3 js-media-module-container">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Работа #{INDEX}</span>
                <button type="button" class="js-remove-portfolio-item w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить работу">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Изображение</label>
                <div class="flex gap-2 items-center js-media-input-group">
                    <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview" style="width:80px;height:60px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                        <img src="{PREVIEW_SRC}" class="js-slide-preview-img w-full h-full object-cover {PREVIEW_HIDDEN_CLASS}" alt="Превью" onerror="this.classList.add(\'hidden\'); this.nextElementSibling.classList.remove(\'hidden\');">
                        <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 {PLACEHOLDER_HIDDEN_CLASS}">
                            <span class="icon-image text-lg"></span>
                        </div>
                    </div>
                    <input type="text" name="items[{INDEX}][image]" value="{IMAGE}" placeholder="/images/project.jpg" 
                           class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                           oninput="updatePreviewOnManualInput(this)" />
                    <button type="button" onclick="openMediaModal(this)" 
                            class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                        <span class="icon-log-out rotate-90 text-sm"></span> Фото
                    </button>
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Название</label>
                    <input type="text" name="items[{INDEX}][title]" value="{TITLE}" placeholder="Название проекта" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ссылка</label>
                    <input type="text" name="items[{INDEX}][link]" value="{LINK}" placeholder="https://..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Категория</label>
                    <select name="items[{INDEX}][category]" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] category-select">
                        <option value="">Выберите категорию</option>
                        {CATEGORY_OPTIONS}
                    </select>
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
                    <input type="text" name="items[{INDEX}][description]" value="{DESCRIPTION}" placeholder="Краткое описание" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
        </div>';
        ?>
        
        <?php 
        // Генерация опций для категорий
        $categoryOptions = '';
        foreach ($categories as $cat) {
            if ($cat !== 'Все') {
                $categoryOptions .= '<option value="' . e($cat) . '">' . e($cat) . '</option>';
            }
        }
        
        foreach ($items as $idx => $item): ?>
            <?php 
            $num = $idx + 1;
            $imgValue = $item['image'] ?? '';
            $hasImg = !empty($imgValue);
            $previewSrc = $hasImg ? ((stripos($imgValue, 'http://') === 0 || stripos($imgValue, 'https://') === 0) ? $imgValue : '../' . $imgValue) : '';
            $imgClass = $hasImg ? '' : 'hidden';
            $placeholderClass = $hasImg ? 'hidden' : '';
            
            $selectedCategory = $item['category'] ?? '';
            $optionsWithSelected = '';
            foreach ($categories as $cat) {
                if ($cat === 'Все') continue;
                $selected = $cat === $selectedCategory ? 'selected' : '';
                $optionsWithSelected .= '<option value="' . e($cat) . '" ' . $selected . '>' . e($cat) . '</option>';
            }
            
            echo str_replace(
                ['{INDEX}', '{IMAGE}', '{TITLE}', '{LINK}', '{DESCRIPTION}', '{PREVIEW_SRC}', '{PREVIEW_HIDDEN_CLASS}', '{PLACEHOLDER_HIDDEN_CLASS}', '{CATEGORY_OPTIONS}'],
                [$num, e($imgValue), e($item['title'] ?? ''), e($item['link'] ?? ''), e($item['description'] ?? ''), $previewSrc, $imgClass, $placeholderClass, $optionsWithSelected],
                $itemTemplate
            );
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-portfolio-item">
        <?php 
        $templateOptions = '';
        foreach ($categories as $cat) {
            if ($cat === 'Все') continue;
            $templateOptions .= '<option value="' . e($cat) . '">' . e($cat) . '</option>';
        }
        echo str_replace(
            ['{INDEX}', '{IMAGE}', '{TITLE}', '{LINK}', '{DESCRIPTION}', '{PREVIEW_SRC}', '{PREVIEW_HIDDEN_CLASS}', '{PLACEHOLDER_HIDDEN_CLASS}', '{CATEGORY_OPTIONS}'],
            ['__INDEX__', '', '', '', '', '', 'hidden', '', $templateOptions],
            $itemTemplate
        );
        ?>
    </template>

    <button type="button" id="add-portfolio-item-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить работу
    </button>
</div>

<?php 
$mediaTargetFolder = 'portfolio'; 
include APP_ROOT . '/config/core/media_modal.php'; 
?>