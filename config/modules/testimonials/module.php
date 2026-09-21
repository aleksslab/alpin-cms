<?php
/**
 * Модуль: Отзывы клиентов — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$cols = $moduleData['cols'] ?? 3;
$style = $moduleData['style'] ?? 'classic';
$settingsClass = $moduleData['settings']['class'] ?? '';

if (!is_array($items) || empty($items)) {
    $items = [
        ['photo' => '', 'name' => '', 'position' => '', 'text' => '', 'video' => '', 'rating' => 5],
        ['photo' => '', 'name' => '', 'position' => '', 'text' => '', 'video' => '', 'rating' => 5],
        ['photo' => '', 'name' => '', 'position' => '', 'text' => '', 'video' => '', 'rating' => 5]
    ];
}

$ratingOptions = [1, 2, 3, 4, 5];
$colsOptions = [2, 3, 4];
$styleOptions = [
    'classic' => 'Стандартный (фото слева)',
    'centered' => 'С акцентом на фото (фото сверху)'
];
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Отзывы клиентов" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Что говорят о нас" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки -->
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
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Стиль карточки</label>
                <select name="style" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <?php foreach ($styleOptions as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $style == $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <!-- Пусто для выравнивания -->
            </div>
        </div>
    </div>

    <!-- БЛОК 3: Отзывы -->
    <div id="testimonials-container" class="space-y-3">
        <?php 
        $itemTemplate = '
        <div class="testimonial-item-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Отзыв #{INDEX}</span>
                <button type="button" class="js-remove-testimonial w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить отзыв">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div class="js-media-module-container">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Фото</label>
                <div class="flex gap-2 items-center js-media-input-group">
                    <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview" title="Кликните для увеличения" style="width:56px;height:56px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                        <img src="{PHOTO}" class="js-slide-preview-img w-full h-full object-cover {PHOTO_CLASS}" alt="Фото" onerror="this.classList.add(\'hidden\'); this.nextElementSibling.classList.remove(\'hidden\');">
                        <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 {PLACEHOLDER_CLASS}">
                            <span class="icon-image text-base"></span>
                        </div>
                    </div>
                    <input type="text" name="items[{INDEX}][photo]" value="{PHOTO}" placeholder="/images/avatar.jpg" 
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
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Имя</label>
                    <input type="text" name="items[{INDEX}][name]" value="{NAME}" placeholder="Александр" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Должность</label>
                    <input type="text" name="items[{INDEX}][position]" value="{POSITION}" placeholder="CEO компании" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Текст отзыва</label>
                <textarea name="items[{INDEX}][text]" rows="3" placeholder="Текст отзыва..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">{TEXT}</textarea>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Видео (YouTube ID)</label>
                    <input type="text" name="items[{INDEX}][video]" value="{VIDEO}" placeholder="dQw4w9WgXcQ" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Рейтинг</label>
                    <select name="items[{INDEX}][rating]" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                        {RATING_OPTIONS}
                    </select>
                </div>
            </div>
        </div>';
        ?>
        
        <?php foreach ($items as $idx => $item): ?>
            <?php 
            $num = $idx + 1;
            $photo = $item['photo'] ?? '';
            $hasPhoto = !empty($photo);
            $photoClass = $hasPhoto ? '' : 'hidden';
            $placeholderClass = $hasPhoto ? 'hidden' : '';
            $rating = $item['rating'] ?? 5;
            
            // Формируем опции рейтинга
            $ratingOptionsHtml = '';
            foreach ($ratingOptions as $option) {
                $selected = $rating == $option ? 'selected' : '';
                $ratingOptionsHtml .= '<option value="' . $option . '" ' . $selected . '>' . $option . ' ⭐</option>';
            }
            
            $replace = [
                '{INDEX}' => $num,
                '{PHOTO}' => e($photo),
                '{PHOTO_CLASS}' => $photoClass,
                '{PLACEHOLDER_CLASS}' => $placeholderClass,
                '{NAME}' => e($item['name'] ?? ''),
                '{POSITION}' => e($item['position'] ?? ''),
                '{TEXT}' => e($item['text'] ?? ''),
                '{VIDEO}' => e($item['video'] ?? ''),
                '{RATING_OPTIONS}' => $ratingOptionsHtml,
            ];
            
            echo str_replace(
                array_keys($replace),
                array_values($replace),
                $itemTemplate
            );
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-testimonial-item">
        <?php 
        // Для шаблона - рейтинг по умолчанию 5
        $defaultRatingOptions = '';
        foreach ($ratingOptions as $option) {
            $selected = $option == 5 ? 'selected' : '';
            $defaultRatingOptions .= '<option value="' . $option . '" ' . $selected . '>' . $option . ' ⭐</option>';
        }
        
        $templateReplace = [
            '{INDEX}' => '__INDEX__',
            '{PHOTO}' => '',
            '{PHOTO_CLASS}' => 'hidden',
            '{PLACEHOLDER_CLASS}' => '',
            '{NAME}' => '',
            '{POSITION}' => '',
            '{TEXT}' => '',
            '{VIDEO}' => '',
            '{RATING_OPTIONS}' => $defaultRatingOptions,
        ];
        echo str_replace(
            array_keys($templateReplace),
            array_values($templateReplace),
            $itemTemplate
        );
        ?>
    </template>

    <button type="button" id="add-testimonial-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить отзыв
    </button>
</div>

<?php include APP_ROOT . '/config/core/media_modal.php'; ?>