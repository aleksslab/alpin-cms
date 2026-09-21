<?php
/**
 * Модуль: Таймер обратного отсчета — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$startDate = $moduleData['start_date'] ?? '';
$endDate = $moduleData['end_date'] ?? '';
$expiredText = $moduleData['expired_text'] ?? 'Событие наступило!';
$settingsClass = $moduleData['settings']['class'] ?? '';
$showSeconds = $moduleData['show_seconds'] ?? true;
$displayType = $moduleData['display_type'] ?? 'blocks'; // blocks, progress

// Форматы даты
$dateFormats = [
    'DD.MM.YYYY HH:MM' => 'dd.mm.yyyy hh:MM',
    'DD.MM.YYYY' => 'dd.mm.yyyy',
    'YYYY-MM-DD HH:MM' => 'yyyy-mm-dd hh:MM',
    'MM/DD/YYYY HH:MM' => 'mm/dd/yyyy hh:MM'
];
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Не упустите момент" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="До конца акции осталось" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание события..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Дата и настройки -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки таймера</h4>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Дата начала</label>
                <input type="datetime-local" name="start_date" value="<?php echo e($startDate); ?>" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Дата окончания</label>
                <input type="datetime-local" name="end_date" value="<?php echo e($endDate); ?>" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Формат даты</label>
                <select name="date_format" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <?php foreach ($dateFormats as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo ($moduleData['date_format'] ?? 'DD.MM.YYYY HH:MM') == $key ? 'selected' : ''; ?>><?php echo $key; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Текст после завершения</label>
                <input type="text" name="expired_text" value="<?php echo e($expiredText); ?>" placeholder="Событие наступило!" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Тип отображения</label>
                <select name="display_type" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="blocks" <?php echo $displayType == 'blocks' ? 'selected' : ''; ?>>Блоки (Дни/Часы/Минуты)</option>
                    <option value="progress" <?php echo $displayType == 'progress' ? 'selected' : ''; ?>>Прогресс-бар</option>
                </select>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="show_seconds" value="0">
                <input type="checkbox" name="show_seconds" value="1" <?php echo $showSeconds ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                <span class="text-sm text-slate-600">Отображать секунды</span>
            </label>
        </div>
    </div>
</div>