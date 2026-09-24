<?php
/**
 * Модуль: Обложка-лендинг (Hero-Landing) — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';

$layout = $moduleData['layout'] ?? 'centered';

$backgroundType = $moduleData['background_type'] ?? 'color';
$backgroundColor = $moduleData['background_color'] ?? '#1a1e23';
$backgroundImage = $moduleData['background_image'] ?? '';

$image = $moduleData['image'] ?? '';
$imagePosition = $moduleData['image_position'] ?? 'right';
$imageStyle = $moduleData['image_style'] ?? 'browser';

$buttonText = $moduleData['button_text'] ?? '';
$buttonLink = $moduleData['button_link'] ?? '';

$buttonText2 = $moduleData['button_text_2'] ?? '';
$buttonLink2 = $moduleData['button_link_2'] ?? '';

$features = $moduleData['features'] ?? [];

$settingsClass = $moduleData['settings']['class'] ?? '';

if (empty($features)) {
    $features = [
        ['icon' => 'icon-zap', 'text' => '']
    ];
}
?>

<div class="space-y-4">

    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Возможности" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="CMS без базы данных" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание блока..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Раскладка -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Раскладка</h4>
        
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Тип раскладки</label>
            <select name="layout" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                <option value="centered" <?php echo $layout === 'centered' ? 'selected' : ''; ?>>По центру (одна колонка)</option>
                <option value="split" <?php echo $layout === 'split' ? 'selected' : ''; ?>>Две колонки (текст + визуал)</option>
            </select>
            <p class="text-[10px] text-slate-400 mt-1">Split — текст слева, картинка справа (или наоборот)</p>
        </div>
    </div>

    <!-- БЛОК 3: Кнопки -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Кнопки</h4>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Основная кнопка — текст</label>
                <input type="text" name="button_text" value="<?php echo e($buttonText); ?>" placeholder="Скачать бесплатно" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Основная кнопка — ссылка</label>
                <input type="text" name="button_link" value="<?php echo e($buttonLink); ?>" placeholder="/download" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Вторая кнопка — текст</label>
                <input type="text" name="button_text_2" value="<?php echo e($buttonText2); ?>" placeholder="Смотреть демо" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Вторая кнопка — ссылка</label>
                <input type="text" name="button_link_2" value="<?php echo e($buttonLink2); ?>" placeholder="/demo" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>
        <p class="text-[10px] text-slate-400">Оставьте пустым, чтобы не показывать</p>
    </div>

    <!-- БЛОК 4: Фон -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Фон</h4>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Тип фона</label>
                <select name="background_type" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="color" <?php echo $backgroundType === 'color' ? 'selected' : ''; ?>>Цвет</option>
                    <option value="image" <?php echo $backgroundType === 'image' ? 'selected' : ''; ?>>Изображение</option>
                </select>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Цвет фона</label>
                <input type="color" name="background_color" value="<?php echo e($backgroundColor ?: '#1a1e23'); ?>" class="w-full h-10 p-1 border border-slate-200 rounded-lg cursor-pointer">
            </div>
        </div>

        <div class="js-media-module-container">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Изображение фона (если тип «Изображение»)</label>
            <div class="flex gap-2 items-center js-media-input-group">
                <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview" style="width:80px;height:60px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                    <?php 
                    $hasBgImg = !empty($backgroundImage);
                    $bgPreviewSrc = $hasBgImg ? ((stripos($backgroundImage, 'http://') === 0 || stripos($backgroundImage, 'https://') === 0) ? $backgroundImage : '../' . $backgroundImage) : '';
                    ?>
                    <img src="<?php echo $bgPreviewSrc; ?>" class="js-slide-preview-img w-full h-full object-cover <?php echo $hasBgImg ? '' : 'hidden'; ?>" alt="Превью" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                    <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 <?php echo $hasBgImg ? 'hidden' : ''; ?>">
                        <span class="icon-image text-lg"></span>
                    </div>
                </div>
                <input type="text" name="background_image" value="<?php echo e($backgroundImage); ?>" placeholder="/images/hero-bg.jpg" 
                       class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                       oninput="updatePreviewOnManualInput(this)" />
                <button type="button" onclick="openMediaModal(this)" 
                        class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                    <span class="icon-log-out rotate-90 text-sm"></span> Фото
                </button>
            </div>
        </div>
    </div>

    <!-- БЛОК 5: Визуал (только для split) -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Визуал справа (только для split)</h4>

        <div class="js-media-module-container">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Изображение</label>
            <div class="flex gap-2 items-center js-media-input-group">
                <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview" style="width:80px;height:60px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                    <?php 
                    $hasImg = !empty($image);
                    $previewSrc = $hasImg ? ((stripos($image, 'http://') === 0 || stripos($image, 'https://') === 0) ? $image : '../' . $image) : '';
                    ?>
                    <img src="<?php echo $previewSrc; ?>" class="js-slide-preview-img w-full h-full object-cover <?php echo $hasImg ? '' : 'hidden'; ?>" alt="Превью" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                    <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 <?php echo $hasImg ? 'hidden' : ''; ?>">
                        <span class="icon-image text-lg"></span>
                    </div>
                </div>
                <input type="text" name="image" value="<?php echo e($image); ?>" placeholder="/images/admin-mockup.jpg" 
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
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Позиция</label>
                <select name="image_position" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="right" <?php echo $imagePosition === 'right' ? 'selected' : ''; ?>>Справа</option>
                    <option value="left" <?php echo $imagePosition === 'left' ? 'selected' : ''; ?>>Слева</option>
                </select>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Стиль</label>
                <select name="image_style" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="browser" <?php echo $imageStyle === 'browser' ? 'selected' : ''; ?>>В стиле браузера (рамка + точки)</option>
                    <option value="shadow" <?php echo $imageStyle === 'shadow' ? 'selected' : ''; ?>>Тень</option>
                    <option value="none" <?php echo $imageStyle === 'none' ? 'selected' : ''; ?>>Без стиля</option>
                </select>
            </div>
        </div>
    </div>

    <!-- БЛОК 6: Микро-факты -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Микро-факты (иконка + текст)</h4>

        <div id="hero-landing-features-container" class="space-y-2">
            <?php 
            $featureTemplate = '
            <div class="feature-row flex gap-2 items-center">
                <div class="flex gap-2 items-center js-icon-input-group flex-1">
                    <div class="icon-preview-wrapper">
                        <span class="js-icon-preview-icon {ICON}"></span>
                    </div>
                    <input type="text" name="features[{INDEX}][icon]" value="{ICON}" placeholder="icon-zap" 
                           class="js-icon-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                    <button type="button" onclick="openIconModal(this)" 
                            class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                        <span class="icon-search text-sm"></span>
                    </button>
                </div>
                <input type="text" name="features[{INDEX}][text]" value="{TEXT}" placeholder="PHP 7.4+"
                       class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                <button type="button" class="js-remove-feature w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer flex-shrink-0" title="Удалить">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>';
            ?>

            <?php foreach ($features as $idx => $feature): ?>
                <?php 
                $icon = $feature['icon'] ?? 'icon-star';
                if (!empty($icon) && strpos($icon, 'icon-') !== 0) {
                    $icon = 'icon-' . $icon;
                }
                echo str_replace(
                    ['{INDEX}', '{ICON}', '{TEXT}'],
                    [$idx + 1, e($icon), e($feature['text'] ?? '')],
                    $featureTemplate
                );
                ?>
            <?php endforeach; ?>
        </div>

        <template id="tmpl-hero-landing-feature">
            <?php echo str_replace(
                ['{INDEX}', '{ICON}', '{TEXT}'],
                ['__INDEX__', 'icon-star', ''],
                $featureTemplate
            ); ?>
        </template>

        <button type="button" id="add-hero-landing-feature-btn" class="w-full py-2 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-xs flex items-center justify-center gap-2">
            <span class="icon-plus text-sm"></span> Добавить факт
        </button>
    </div>

    <!-- БЛОК 7: Класс модуля -->
    <div class="editor-field">
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
        <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="hero-compact, py-16..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
    </div>

</div>

<?php 
include APP_ROOT . '/config/core/media_modal.php'; 
include APP_ROOT . '/config/core/icon_modal.php'; 
?>