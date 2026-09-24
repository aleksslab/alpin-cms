<?php
/**
 * Модуль: Список с иконками — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$settingsClass = $moduleData['settings']['class'] ?? '';

if (empty($items)) {
    $items = [
        ['icon' => 'icon-star', 'title' => '', 'text' => '']
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
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Наши преимущества" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
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

    <!-- БЛОК 3: Иконки -->
    <div id="icons-rows-container" class="space-y-3">
        <?php 
        $itemTemplate = '
        <div class="js-icon-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="js-icon-num text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Иконка #{INDEX}</span>
                <button type="button" class="js-remove-icon w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить иконку">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Иконка</label>
                    <div class="flex gap-2 items-center js-icon-input-group">
                        <div class="icon-preview-wrapper">
                            <span class="js-icon-preview-icon {ICON}"></span>
                        </div>
                        <input type="text" name="items[{INDEX}][icon]" value="{ICON}" placeholder="icon-star" 
                               class="js-icon-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                        <button type="button" onclick="openIconModal(this)" class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-bold text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                            <span class="icon-search text-sm"></span> Иконка
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
                    <input type="text" name="items[{INDEX}][title]" value="{TITLE}" placeholder="Введите заголовок..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
                <textarea name="items[{INDEX}][text]" rows="3" placeholder="Введите описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">{TEXT}</textarea>
            </div>
        </div>';
        ?>

        <?php foreach ($items as $idx => $item): ?>
            <?php 
            $itemNumber = $idx + 1;
            $iconClass = $item['icon'] ?? 'icon-star';
            if (!empty($iconClass) && strpos($iconClass, 'icon-') !== 0) {
                $iconClass = 'icon-' . $iconClass;
            }
            echo str_replace(
                ['{INDEX}', '{ICON}', '{TITLE}', '{TEXT}'], 
                [$itemNumber, e($iconClass), e($item['title'] ?? ''), e($item['text'] ?? '')], 
                $itemTemplate
            ); 
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-icon-item">
        <?php echo str_replace(
            ['{INDEX}', '{ICON}', '{TITLE}', '{TEXT}'], 
            ['__INDEX__', 'icon-star', '', ''], 
            $itemTemplate
        ); ?>
    </template>

    <button type="button" id="add-icon-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить иконку
    </button>
</div>

<?php 
include APP_ROOT . '/config/core/icon_modal.php'; 
?>