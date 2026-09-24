<?php
/**
 * Модуль: Счетчики (Статистика) — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$stats = $moduleData['stats'] ?? [];
$cols = $moduleData['cols'] ?? 4;
$animation = $moduleData['animation'] ?? true;
$duration = $moduleData['duration'] ?? 1.5;
$settingsClass = $moduleData['settings']['class'] ?? '';

if (empty($stats)) {
    $stats = [
        ['icon' => '', 'value' => '', 'label' => ''],
        ['icon' => '', 'value' => '', 'label' => ''],
        ['icon' => '', 'value' => '', 'label' => ''],
        ['icon' => '', 'value' => '', 'label' => '']
    ];
}

$durationOptions = [0.5, 1.0, 1.5, 2.0, 2.5, 3.0];
$colsOptions = [2, 3, 4];
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Наши достижения" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Мы гордимся результатами" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
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
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Колонок в ряду</label>
                <select name="cols" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <?php foreach ($colsOptions as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $cols == $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Анимация</label>
                <div class="flex items-center gap-3 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="animation" value="0">
                        <input type="checkbox" name="animation" value="1" <?php echo $animation ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                        <span class="text-sm text-slate-600">Включена</span>
                    </label>
                </div>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Скорость анимации</label>
                <select name="duration" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <?php foreach ($durationOptions as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $duration == $option ? 'selected' : ''; ?>><?php echo $option; ?> с</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- БЛОК 3: Счетчики -->
    <div id="stats-container" class="space-y-3">
        <?php 
        $statTemplate = '
        <div class="stat-item-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="js-stat-num text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Счетчик #{INDEX}</span>
                <button type="button" class="js-remove-stat w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить счетчик">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Иконка</label>
                <div class="flex gap-2 items-center js-icon-input-group">
                    <div class="icon-preview-wrapper">
                        <span class="js-icon-preview-icon {ICON}"></span>
                    </div>
                    <input type="text" name="stats[{INDEX}][icon]" value="{ICON}" placeholder="icon-star" 
                           class="js-icon-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                    <button type="button" onclick="openIconModal(this)" 
                            class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                        <span class="icon-search text-sm"></span> Иконка
                    </button>
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Значение</label>
                    <input type="text" name="stats[{INDEX}][value]" value="{VALUE}" placeholder="1247+" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подпись</label>
                    <input type="text" name="stats[{INDEX}][label]" value="{LABEL}" placeholder="клиентов" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
        </div>';
        ?>
        
        <?php foreach ($stats as $idx => $item): ?>
            <?php 
            $num = $idx + 1;
            $icon = $item['icon'] ?? '';
            if (!empty($icon) && strpos($icon, 'icon-') !== 0) {
                $icon = 'icon-' . $icon;
            }
            echo str_replace(
                ['{INDEX}', '{ICON}', '{VALUE}', '{LABEL}'],
                [$num, e($icon), e($item['value'] ?? ''), e($item['label'] ?? '')],
                $statTemplate
            );
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-stat-item">
        <?php echo str_replace(
            ['{INDEX}', '{ICON}', '{VALUE}', '{LABEL}'],
            ['__INDEX__', 'icon-star', '', ''],
            $statTemplate
        ); ?>
    </template>

    <button type="button" id="add-stat-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить счетчик
    </button>
</div>

<?php include APP_ROOT . '/config/core/icon_modal.php'; ?>