<?php
/**
 * Модуль: Калькулятор корма — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? 'Калькулятор корма';
$description = $moduleData['description'] ?? '';
$showDisclaimer = $moduleData['show_disclaimer'] ?? true;
$disclaimer = $moduleData['disclaimer'] ?? '*Приведённые расчёты являются приблизительными. Рекомендуемые нормы кормления см. на упаковке конкретного корма. Перед изменением рациона проконсультируйтесь с ветеринаром.';
$settingsClass = $moduleData['settings']['class'] ?? '';
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Узнайте идеальную суточную порцию..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Калькулятор корма" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
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
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field flex items-end">
                <label class="flex items-center gap-2 cursor-pointer pb-2">
                    <input type="hidden" name="show_disclaimer" value="0">
                    <input type="checkbox" name="show_disclaimer" value="1" <?php echo $showDisclaimer ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm text-slate-600 font-medium">Показывать дисклеймер</span>
                </label>
            </div>
        </div>

        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Текст дисклеймера</label>
            <textarea name="disclaimer" rows="3" placeholder="Текст предупреждения..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($disclaimer); ?></textarea>
        </div>
    </div>
</div>