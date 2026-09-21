<?php
/**
 * Модуль: Карточки — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$settingsClass = $moduleData['settings']['class'] ?? '';

if (empty($items)) {
    $items = [
        ['image' => '', 'name' => '', 'class_name' => '', 'link' => '#', 'icon' => 'icon-star', 'features' => [], 'visible' => true]
    ];
}
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Наши преимущества" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Наши бренды" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание блока..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки отображения -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки отображения</h4>
        
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
            <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <!-- БЛОК 3: Карточки -->
    <div id="cards-rows-container" class="space-y-3">
        <?php 
        $itemTemplate = '
        <div class="card-item-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3 js-media-module-container">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Карточка #{INDEX}</span>
                <button type="button" class="js-remove-card w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить карточку">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="items[{INDEX}][visible]" value="0">
                    <input type="checkbox" name="items[{INDEX}][visible]" value="1" {VISIBLE_CHECKED} class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-xs font-medium text-slate-600">Отображать</span>
                </label>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Изображение</label>
                    <div class="flex gap-2 items-center js-media-input-group">
                        <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview" style="width:80px;height:60px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                            <img src="{PREVIEW_SRC}" class="js-slide-preview-img w-full h-full object-cover {PREVIEW_HIDDEN_CLASS}" alt="Превью" onerror="this.classList.add(\'hidden\'); this.nextElementSibling.classList.remove(\'hidden\');">
                            <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 {PLACEHOLDER_HIDDEN_CLASS}">
                                <span class="icon-image text-lg"></span>
                            </div>
                        </div>
                        <input type="text" name="items[{INDEX}][image]" value="{IMAGE_URL}" placeholder="/images/cards/photo.png" 
                               class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                               oninput="updatePreviewOnManualInput(this)" />
                        <button type="button" onclick="openMediaModal(this)" 
                                class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                            <span class="icon-log-out rotate-90 text-sm"></span> Фото
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Название</label>
                    <input type="text" name="items[{INDEX}][name]" value="{NAME}" placeholder="Название..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
                    <input type="text" name="items[{INDEX}][class_name]" value="{CLASS_NAME}" placeholder="Подзаголовок..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ссылка</label>
                    <input type="text" name="items[{INDEX}][link]" value="{LINK}" placeholder="#" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Иконка</label>
                    <div class="flex gap-2 items-center js-icon-input-group">
                        <div class="icon-preview-wrapper">
                            <span class="js-icon-preview-icon {ICON}"></span>
                        </div>
                        <input type="text" name="items[{INDEX}][icon]" value="{ICON}" placeholder="icon-star" 
                               class="js-icon-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                        <button type="button" onclick="openIconModal(this)" 
                                class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                            <span class="icon-search text-sm"></span> Иконка
                        </button>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Особенности (через запятую)</label>
                <input type="text" name="items[{INDEX}][features]" value="{FEATURES}" placeholder="Особенность 1, Особенность 2..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>';
        ?>

        <?php foreach ($items as $idx => $item): ?>
            <?php 
            $cardNumber = $idx + 1;
            $imgValue = $item['image'] ?? '';
            $hasImg = !empty($imgValue);
            $previewSrc = $hasImg ? ((stripos($imgValue, 'http://') === 0 || stripos($imgValue, 'https://') === 0) ? $imgValue : '../' . $imgValue) : '';
            $imgClass = $hasImg ? '' : 'hidden';
            $placeholderClass = $hasImg ? 'hidden' : '';
            
            $features = '';
            if (!empty($item['features'])) {
                if (is_array($item['features'])) {
                    $features = implode(', ', $item['features']);
                } else {
                    $features = $item['features'];
                }
            }
            
            $visibleChecked = (!isset($item['visible']) || $item['visible']) ? 'checked' : '';
            
            $icon = $item['icon'] ?? '';
            if (!empty($icon) && strpos($icon, 'icon-') !== 0) {
                $icon = 'icon-' . $icon;
            }

            echo str_replace(
                ['{INDEX}', '{IMAGE_URL}', '{NAME}', '{CLASS_NAME}', '{LINK}', '{ICON}', '{FEATURES}', '{PREVIEW_SRC}', '{PREVIEW_HIDDEN_CLASS}', '{PLACEHOLDER_HIDDEN_CLASS}', '{VISIBLE_CHECKED}'], 
                [$cardNumber, e($imgValue), e($item['name'] ?? ''), e($item['class_name'] ?? ''), e($item['link'] ?? '#'), e($icon), e($features), $previewSrc, $imgClass, $placeholderClass, $visibleChecked], 
                $itemTemplate
            ); 
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-card-item">
        <?php echo str_replace(
            ['{INDEX}', '{IMAGE_URL}', '{NAME}', '{CLASS_NAME}', '{LINK}', '{ICON}', '{FEATURES}', '{PREVIEW_SRC}', '{PREVIEW_HIDDEN_CLASS}', '{PLACEHOLDER_HIDDEN_CLASS}', '{VISIBLE_CHECKED}'], 
            ['__INDEX__', '', '', '', '', 'icon-star', '', '', 'hidden', '', ''], 
            $itemTemplate
        ); ?>
    </template>

    <button type="button" id="add-card-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить карточку
    </button>
</div>

<?php 
$mediaTargetFolder = 'cards'; 
include APP_ROOT . '/config/core/media_modal.php'; 
include APP_ROOT . '/config/core/icon_modal.php'; 
?>