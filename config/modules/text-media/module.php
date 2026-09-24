<?php
/**
 * Модуль: Текст + Медиа — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$image = $moduleData['image'] ?? '';
$imagePosition = $moduleData['image_position'] ?? 'left';
$badgeText = $moduleData['badge_text'] ?? '';
$badgeLabel = $moduleData['badge_label'] ?? '';
$features = $moduleData['features'] ?? [];
$stats = $moduleData['stats'] ?? [];
$settingsClass = $moduleData['settings']['class'] ?? '';

if (empty($features)) {
    $features = [
        ['icon' => 'icon-sparkles', 'title' => '', 'desc' => '']
    ];
}

if (empty($stats)) {
    $stats = [
        ['value' => '', 'label' => ''],
        ['value' => '', 'label' => ''],
        ['value' => '', 'label' => '']
    ];
}
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Почему мы" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Заголовок блока" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание блока..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки отображения -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки отображения</h4>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Положение картинки</label>
                <select name="image_position" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="left" <?php echo $imagePosition === 'left' ? 'selected' : ''; ?>>Слева</option>
                    <option value="right" <?php echo $imagePosition === 'right' ? 'selected' : ''; ?>>Справа</option>
                </select>
            </div>
        </div>
    </div>

    <!-- БЛОК 3: Изображение и плашка -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Изображение и плашка</h4>
        
        <div class="js-media-module-container">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Изображение</label>
            <div class="flex gap-2 items-center js-media-input-group">
                <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview" style="width:80px;height:60px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                    <?php 
                    $hasImg = !empty($image);
                    $previewSrc = $hasImg ? ((stripos($image, 'http://') === 0 || stripos($image, 'https://') === 0) ? $image : '../' . $image) : '';
                    $imgClass = $hasImg ? '' : 'hidden';
                    $placeholderClass = $hasImg ? 'hidden' : '';
                    ?>
                    <img src="<?php echo $previewSrc; ?>" class="js-slide-preview-img w-full h-full object-cover <?php echo $imgClass; ?>" alt="Превью" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                    <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 <?php echo $placeholderClass; ?>">
                        <span class="icon-image text-lg"></span>
                    </div>
                </div>
                <input type="text" name="image" value="<?php echo e($image); ?>" placeholder="/images/banner.jpg" 
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
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Текст плашки</label>
                <input type="text" name="badge_text" value="<?php echo e($badgeText); ?>" placeholder="100%" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подпись плашки</label>
                <input type="text" name="badge_label" value="<?php echo e($badgeLabel); ?>" placeholder="Гарантия качества" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>
    </div>

    <!-- БЛОК 4: Особенности -->
    <div id="tm-features-container" class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Особенности</h4>
        
        <?php 
        $featureTemplate = '
        <div class="feature-item bg-white p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="js-feature-num text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Особенность #{INDEX}</span>
                <button type="button" class="js-remove-feature w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Иконка</label>
                <div class="flex gap-2 items-center js-icon-input-group">
                    <div class="icon-preview-wrapper">
                        <span class="js-icon-preview-icon {ICON}"></span>
                    </div>
                    <input type="text" name="features[{INDEX}][icon]" value="{ICON}" placeholder="icon-star" 
                           class="js-icon-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                    <button type="button" onclick="openIconModal(this)" 
                            class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                        <span class="icon-search text-sm"></span> Иконка
                    </button>
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
                <input type="text" name="features[{INDEX}][title]" value="{TITLE}" placeholder="Заголовок" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
                <textarea name="features[{INDEX}][desc]" rows="2" placeholder="Описание особенности..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">{DESC}</textarea>
            </div>
        </div>';
        ?>
        
        <?php foreach ($features as $idx => $item): ?>
            <?php 
            $num = $idx + 1;
            $icon = $item['icon'] ?? 'icon-star';
            if (!empty($icon) && strpos($icon, 'icon-') !== 0) {
                $icon = 'icon-' . $icon;
            }
            echo str_replace(
                ['{INDEX}', '{ICON}', '{TITLE}', '{DESC}'],
                [$num, e($icon), e($item['title'] ?? ''), e($item['desc'] ?? '')],
                $featureTemplate
            );
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-feature">
        <?php echo str_replace(['{INDEX}', '{ICON}', '{TITLE}', '{DESC}'], ['__INDEX__', 'icon-star', '', ''], $featureTemplate); ?>
    </template>

    <button type="button" id="add-feature-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить особенность
    </button>

    <!-- БЛОК 5: Статистика -->
    <div id="tm-stats-container" class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Статистика</h4>
        
        <?php 
        $statTemplate = '
        <div class="stat-item bg-white p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="js-stat-num text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Статистика #{INDEX}</span>
                <button type="button" class="js-remove-stat w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Значение</label>
                    <input type="text" name="stats[{INDEX}][value]" value="{VALUE}" placeholder="12+" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подпись</label>
                    <input type="text" name="stats[{INDEX}][label]" value="{LABEL}" placeholder="Лет опыта" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
        </div>';
        ?>
        
        <?php foreach ($stats as $idx => $item): ?>
            <?php 
            $num = $idx + 1;
            echo str_replace(
                ['{INDEX}', '{VALUE}', '{LABEL}'],
                [$num, e($item['value'] ?? ''), e($item['label'] ?? '')],
                $statTemplate
            );
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-stat">
        <?php echo str_replace(['{INDEX}', '{VALUE}', '{LABEL}'], ['__INDEX__', '', ''], $statTemplate); ?>
    </template>

    <button type="button" id="add-stat-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить статистику
    </button>
</div>

<?php 
include APP_ROOT . '/config/core/media_modal.php'; 
include APP_ROOT . '/config/core/icon_modal.php'; 
?>