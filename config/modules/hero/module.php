<?php
/**
 * Модуль: Обложка (Hero) — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$backgroundType = $moduleData['background_type'] ?? 'image';
$backgroundImage = $moduleData['background_image'] ?? '';
$backgroundColor = $moduleData['background_color'] ?? '';
$buttonText = $moduleData['button_text'] ?? '';
$buttonLink = $moduleData['button_link'] ?? '#';
$settingsClass = $moduleData['settings']['class'] ?? '';
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Premium корма" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Здоровое питание для питомцев" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
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
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Тип фона</label>
                <select name="background_type" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="image" <?php echo $backgroundType === 'image' ? 'selected' : ''; ?>>Изображение</option>
                    <option value="color" <?php echo $backgroundType === 'color' ? 'selected' : ''; ?>>Цвет</option>
                </select>
            </div>
            <div class="editor-field js-color-field <?php echo $backgroundType === 'image' ? 'hidden' : ''; ?>">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Цвет фона</label>
                <input type="color" name="background_color" value="<?php echo e($backgroundColor); ?>" class="w-full h-10 p-1 border border-slate-200 rounded-lg cursor-pointer">
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Текст кнопки</label>
                <input type="text" name="button_text" value="<?php echo e($buttonText); ?>" placeholder="Узнать больше" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ссылка</label>
                <input type="text" name="button_link" value="<?php echo e($buttonLink); ?>" placeholder="#" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <!-- Пусто для выравнивания -->
            </div>
        </div>
    </div>

    <!-- БЛОК 3: Фоновое изображение -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3 js-media-module-container <?php echo $backgroundType === 'color' ? 'hidden' : ''; ?>">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Фоновое изображение</h4>
        
        <div class="flex gap-2 items-center js-media-input-group">
            <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview" style="width:80px;height:60px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                <?php 
                $hasImg = !empty($backgroundImage);
                $previewSrc = $hasImg ? ((stripos($backgroundImage, 'http://') === 0 || stripos($backgroundImage, 'https://') === 0) ? $backgroundImage : '../' . $backgroundImage) : '';
                $imgClass = $hasImg ? '' : 'hidden';
                $placeholderClass = $hasImg ? 'hidden' : '';
                ?>
                <img src="<?php echo $previewSrc; ?>" class="js-slide-preview-img w-full h-full object-cover <?php echo $imgClass; ?>" alt="Превью" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 <?php echo $placeholderClass; ?>">
                    <span class="icon-image text-lg"></span>
                </div>
            </div>
            <input type="text" name="background_image" value="<?php echo e($backgroundImage); ?>" placeholder="/images/hero/banner.jpg" 
                   class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                   oninput="updatePreviewOnManualInput(this)" />
            <button type="button" onclick="openMediaModal(this)" 
                    class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-bold text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                <span class="icon-log-out rotate-90 text-sm"></span> Фото
            </button>
        </div>
    </div>
</div>

<?php 
$mediaTargetFolder = 'hero'; 
include APP_ROOT . '/config/core/media_modal.php'; 
?>