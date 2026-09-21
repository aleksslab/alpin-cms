<?php
/**
 * Модуль: Слайдер — админка
 */

$slides = $moduleData['slides'] ?? [];
$autoplay = $moduleData['autoplay'] ?? true;
$interval = $moduleData['interval'] ?? 5000;
$settingsClass = $moduleData['settings']['class'] ?? '';

if (empty($slides)) {
    $slides = [
        ['image' => '', 'title' => '', 'subtitle' => '', 'link' => '#']
    ];
}
?>

<div class="space-y-4">
    <!-- БЛОК 1: Настройки отображения -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки слайдера</h4>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Автопрокрутка</label>
                <select name="autoplay" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="1" <?php echo $autoplay ? 'selected' : ''; ?>>Включена</option>
                    <option value="0" <?php echo !$autoplay ? 'selected' : ''; ?>>Выключена</option>
                </select>
            </div>
        </div>

        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Интервал (мс)</label>
            <input type="number" name="interval" value="<?php echo $interval; ?>" min="1000" max="10000" step="500" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <!-- БЛОК 2: Слайды -->
    <div id="carousel-rows-container" class="space-y-3">
        <?php 
        $slideTemplate = '
        <div class="slide-item-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3 js-media-module-container">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Слайд #{INDEX}</span>
                <button type="button" class="js-remove-slide w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить слайд">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Изображение</label>
                    <div class="flex gap-2 items-center js-media-input-group">
                        <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview" style="width:56px;height:56px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                            <img src="{PREVIEW_SRC}" class="js-slide-preview-img w-full h-full object-cover {PREVIEW_HIDDEN_CLASS}" alt="Превью" onerror="this.classList.add(\'hidden\'); this.nextElementSibling.classList.remove(\'hidden\');">
                            <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 {PLACEHOLDER_HIDDEN_CLASS}">
                                <span class="icon-image text-lg"></span>
                            </div>
                        </div>
                        <input type="text" name="slides[{INDEX}][image]" value="{IMAGE_URL}" placeholder="/images/slides/banner.jpg" 
                               class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                               oninput="updatePreviewOnManualInput(this)" />
                        <button type="button" onclick="openMediaModal(this)" 
                                class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-bold text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                            <span class="icon-log-out rotate-90 text-sm"></span> Фото
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
                    <input type="text" name="slides[{INDEX}][title]" value="{TITLE}" placeholder="Введите заголовок слайда..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ссылка</label>
                    <input type="text" name="slides[{INDEX}][link]" value="{LINK}" placeholder="https://..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
                <textarea name="slides[{INDEX}][subtitle]" rows="2" placeholder="Введите подзаголовок..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] resize-y">{SUBTITLE}</textarea>
            </div>
        </div>';
        ?>

        <?php foreach ($slides as $idx => $slide): ?>
            <?php 
            $imgValue = $slide['image'] ?? '';
            $hasImg = !empty($imgValue);
            $previewSrc = $hasImg ? ((stripos($imgValue, 'http://') === 0 || stripos($imgValue, 'https://') === 0) ? $imgValue : '../' . $imgValue) : '';
            $imgClass = $hasImg ? '' : 'hidden';
            $placeholderClass = $hasImg ? 'hidden' : '';

            echo str_replace(
                ['{INDEX}', '{IMAGE_URL}', '{TITLE}', '{SUBTITLE}', '{LINK}', '{PREVIEW_SRC}', '{PREVIEW_HIDDEN_CLASS}', '{PLACEHOLDER_HIDDEN_CLASS}'], 
                [($idx + 1), e($imgValue), e($slide['title'] ?? ''), e($slide['subtitle'] ?? ''), e($slide['link'] ?? '#'), $previewSrc, $imgClass, $placeholderClass], 
                $slideTemplate
            ); 
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-carousel-slide">
        <?php echo str_replace(
            ['{INDEX}', '{IMAGE_URL}', '{TITLE}', '{SUBTITLE}', '{LINK}', '{PREVIEW_SRC}', '{PREVIEW_HIDDEN_CLASS}', '{PLACEHOLDER_HIDDEN_CLASS}'], 
            ['__INDEX__', '', '', '', '', '', 'hidden', ''], 
            $slideTemplate
        ); ?>
    </template>

    <button type="button" id="add-slide-row-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить слайд
    </button>
</div>

<?php 
$mediaTargetFolder = 'slides'; 
include APP_ROOT . '/config/core/media_modal.php'; 
?>