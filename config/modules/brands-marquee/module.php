<?php
/**
 * Модуль: Бегущая лента — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$speed = $moduleData['speed'] ?? 15;
$direction = $moduleData['direction'] ?? 'left';
$logoHeight = $moduleData['logo_height'] ?? 80;
$settingsClass = $moduleData['settings']['class'] ?? '';

if (empty($items)) {
    $items = [
        ['image' => '', 'url' => '', 'title' => ''],
        ['image' => '', 'url' => '', 'title' => ''],
        ['image' => '', 'url' => '', 'title' => '']
    ];
}
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Нам доверяют" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Партнеры и клиенты" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки ленты -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки ленты</h4>
        
        <div class="editor-row !items-start">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Длительность одного оборота (сек)</label>
                <input type="number" name="speed" value="<?php echo e($speed); ?>" placeholder="15" min="5" max="60" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                <p class="text-[10px] text-slate-400 mt-1">Чем меньше число, тем быстрее движение</p>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Направление</label>
                <select name="direction" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="left" <?php echo $direction == 'left' ? 'selected' : ''; ?>>Влево</option>
                    <option value="right" <?php echo $direction == 'right' ? 'selected' : ''; ?>>Вправо</option>
                </select>
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Высота логотипа (px)</label>
                <input type="number" name="logo_height" value="<?php echo e($logoHeight); ?>" placeholder="80" min="30" max="200" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>
    </div>

    <!-- БЛОК 3: Логотипы -->
    <div id="brands-container" class="space-y-3">
        <?php 
        $itemTemplate = '
        <div class="brand-item-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Логотип #{INDEX}</span>
                <button type="button" class="js-remove-brand w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить логотип">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div class="js-media-module-container">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Изображение</label>
                <div class="flex gap-2 items-center js-media-input-group">
                    <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview" title="Кликните для увеличения" style="width:56px;height:56px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                        <img src="{IMAGE}" class="js-slide-preview-img w-full h-full object-contain {IMG_CLASS}" alt="Превью" onerror="this.classList.add(\'hidden\'); this.nextElementSibling.classList.remove(\'hidden\');">
                        <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 {PLACEHOLDER_CLASS}">
                            <span class="icon-image text-base"></span>
                        </div>
                    </div>
                    <input type="text" name="items[{INDEX}][image]" value="{IMAGE}" placeholder="/images/logo.png" 
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
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ссылка</label>
                    <input type="text" name="items[{INDEX}][url]" value="{URL}" placeholder="https://site.com" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Название (alt)</label>
                    <input type="text" name="items[{INDEX}][title]" value="{TITLE}" placeholder="Компания" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
        </div>';
        ?>
        
        <?php foreach ($items as $idx => $item): ?>
            <?php 
            $num = $idx + 1;
            $image = $item['image'] ?? '';
            $hasImg = !empty($image);
            $imgClass = $hasImg ? '' : 'hidden';
            $placeholderClass = $hasImg ? 'hidden' : '';
            echo str_replace(
                ['{INDEX}', '{IMAGE}', '{URL}', '{TITLE}', '{IMG_CLASS}', '{PLACEHOLDER_CLASS}'],
                [$num, e($image), e($item['url'] ?? ''), e($item['title'] ?? ''), $imgClass, $placeholderClass],
                $itemTemplate
            );
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-brand-item">
        <?php echo str_replace(
            ['{INDEX}', '{IMAGE}', '{URL}', '{TITLE}', '{IMG_CLASS}', '{PLACEHOLDER_CLASS}'],
            ['__INDEX__', '', '', '', 'hidden', ''],
            $itemTemplate
        ); ?>
    </template>

    <button type="button" id="add-brand-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить логотип
    </button>
</div>

<?php include APP_ROOT . '/config/core/media_modal.php'; ?>