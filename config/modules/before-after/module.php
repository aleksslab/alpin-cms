<?php
/**
 * Модуль: До и После — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$beforeImage = $moduleData['before_image'] ?? '';
$afterImage = $moduleData['after_image'] ?? '';
$orientation = $moduleData['orientation'] ?? 'horizontal';
$defaultPosition = $moduleData['default_position'] ?? 50;
$settingsClass = $moduleData['settings']['class'] ?? '';
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Результаты работы" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="До и После" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки слайдера</h4>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ориентация</label>
                <select name="orientation" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="horizontal" <?php echo $orientation == 'horizontal' ? 'selected' : ''; ?>>Горизонтальная</option>
                    <option value="vertical" <?php echo $orientation == 'vertical' ? 'selected' : ''; ?>>Вертикальная</option>
                </select>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Позиция разделителя (%)</label>
                <input type="number" name="default_position" value="<?php echo e($defaultPosition); ?>" placeholder="50" min="10" max="90" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <!-- Пусто -->
            </div>
        </div>
    </div>

    <!-- БЛОК 3: Изображения -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-4">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Изображения</h4>
        
        <!-- Предупреждение -->
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700">
            <span class="icon-alert-triangle mr-1"></span>
            Рекомендуется использовать изображения с одинаковым соотношением сторон для корректного отображения.
        </div>
        
        <!-- ДО -->
        <div class="js-media-module-container">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">До</label>
            <div class="flex gap-2 items-center js-media-input-group">
                <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview" title="Кликните для увеличения" style="width:80px;height:60px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                    <?php 
                    $hasBefore = !empty($beforeImage);
                    $beforePreview = $hasBefore ? ((stripos($beforeImage, 'http://') === 0 || stripos($beforeImage, 'https://') === 0) ? $beforeImage : '../' . $beforeImage) : '';
                    ?>
                    <img src="<?php echo $beforePreview; ?>" class="js-slide-preview-img w-full h-full object-cover <?php echo $hasBefore ? '' : 'hidden'; ?>" alt="До" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                    <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 <?php echo $hasBefore ? 'hidden' : ''; ?>">
                        <span class="icon-image text-2xl"></span>
                    </div>
                </div>
                <input type="text" name="before_image" value="<?php echo e($beforeImage); ?>" placeholder="/images/before.jpg" 
                       class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                       oninput="updatePreviewOnManualInput(this)" />
                <button type="button" onclick="openMediaModal(this)" 
                        class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                    <span class="icon-log-out rotate-90 text-sm"></span> Фото
                </button>
            </div>
        </div>

        <!-- ПОСЛЕ -->
        <div class="js-media-module-container">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">После</label>
            <div class="flex gap-2 items-center js-media-input-group">
                <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview" title="Кликните для увеличения" style="width:80px;height:60px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                    <?php 
                    $hasAfter = !empty($afterImage);
                    $afterPreview = $hasAfter ? ((stripos($afterImage, 'http://') === 0 || stripos($afterImage, 'https://') === 0) ? $afterImage : '../' . $afterImage) : '';
                    ?>
                    <img src="<?php echo $afterPreview; ?>" class="js-slide-preview-img w-full h-full object-cover <?php echo $hasAfter ? '' : 'hidden'; ?>" alt="После" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                    <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 <?php echo $hasAfter ? 'hidden' : ''; ?>">
                        <span class="icon-image text-2xl"></span>
                    </div>
                </div>
                <input type="text" name="after_image" value="<?php echo e($afterImage); ?>" placeholder="/images/after.jpg" 
                       class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                       oninput="updatePreviewOnManualInput(this)" />
                <button type="button" onclick="openMediaModal(this)" 
                        class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                    <span class="icon-log-out rotate-90 text-sm"></span> Фото
                </button>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/config/core/media_modal.php'; ?>