<?php
/**
 * Модуль: Временная шкала — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$orientation = $moduleData['orientation'] ?? 'vertical';
$settingsClass = $moduleData['settings']['class'] ?? '';

// Проверяем, что $items - массив
if (!is_array($items) || empty($items)) {
    $items = [
        ['date' => '', 'title' => '', 'description' => '', 'icon' => '', 'completed' => false],
        ['date' => '', 'title' => '', 'description' => '', 'icon' => '', 'completed' => false],
        ['date' => '', 'title' => '', 'description' => '', 'icon' => '', 'completed' => false]
    ];
}
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Как мы работаем" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Этапы развития" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
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
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ориентация</label>
                <select name="orientation" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="vertical" <?php echo $orientation == 'vertical' ? 'selected' : ''; ?>>Вертикальная</option>
                    <option value="horizontal" <?php echo $orientation == 'horizontal' ? 'selected' : ''; ?>>Горизонтальная</option>
                </select>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>
    </div>

    <!-- БЛОК 3: Этапы -->
    <div id="timeline-container" class="space-y-3">
        <?php 
        $itemTemplate = '
        <div class="timeline-item-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="js-timeline-num text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Этап #{INDEX}</span>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="hidden" name="items[{INDEX}][completed]" value="0">
                        <input type="checkbox" name="items[{INDEX}][completed]" value="1" {CHECKED} class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                        <span class="text-[10px] font-medium text-slate-500">Завершен</span>
                    </label>
                    <button type="button" class="js-remove-timeline w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить этап">
                        <span class="icon-x text-sm"></span>
                    </button>
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Дата</label>
                    <input type="text" name="items[{INDEX}][date]" value="{DATE}" placeholder="2024 год" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
                    <input type="text" name="items[{INDEX}][title]" value="{TITLE}" placeholder="Название этапа" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
                <textarea name="items[{INDEX}][description]" rows="2" placeholder="Описание этапа..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">{DESCRIPTION}</textarea>
            </div>
            
            <div>
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
        </div>';
        ?>
        
        <?php foreach ($items as $idx => $item): ?>
            <?php 
            $num = $idx + 1;
            $icon = $item['icon'] ?? '';
            if (!empty($icon) && strpos($icon, 'icon-') !== 0) {
                $icon = 'icon-' . $icon;
            }
            $checked = !empty($item['completed']) ? 'checked' : '';
            echo str_replace(
                ['{INDEX}', '{DATE}', '{TITLE}', '{DESCRIPTION}', '{ICON}', '{CHECKED}'],
                [$num, e($item['date'] ?? ''), e($item['title'] ?? ''), e($item['description'] ?? ''), e($icon), $checked],
                $itemTemplate
            );
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-timeline-item">
        <?php echo str_replace(
            ['{INDEX}', '{DATE}', '{TITLE}', '{DESCRIPTION}', '{ICON}', '{CHECKED}'],
            ['__INDEX__', '', '', '', 'icon-star', ''],
            $itemTemplate
        ); ?>
    </template>

    <button type="button" id="add-timeline-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить этап
    </button>
</div>

<?php include APP_ROOT . '/config/core/icon_modal.php'; ?>