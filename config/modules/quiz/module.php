<?php
/**
 * Модуль: Квиз — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$questions = $moduleData['questions'] ?? [];
$fields = $moduleData['fields'] ?? [];
$goal = $moduleData['goal'] ?? 'qualification';
$settingsClass = $moduleData['settings']['class'] ?? '';
$showPrivacyPolicy = $moduleData['show_privacy_policy'] ?? true;
$successMessage = $moduleData['success_message'] ?? '';

if (!is_array($questions) || empty($questions)) {
    $questions = [
        ['text' => '', 'type' => 'single', 'options' => [''], 'required' => true]
    ];
}

if (!is_array($fields) || empty($fields)) {
    $fields = [
        ['type' => 'name', 'label' => 'Имя', 'required' => true],
        ['type' => 'phone', 'label' => 'Телефон', 'required' => true]
    ];
}

$questionTypes = [
    'single' => 'Один вариант (radio)',
    'multiple' => 'Несколько вариантов (checkbox)',
    'text' => 'Текстовый ответ (textarea)'
];

$fieldTypes = [
    'name' => 'Имя',
    'phone' => 'Телефон',
    'email' => 'Email',
    'text' => 'Текстовое поле'
];

$goalOptions = [
    'qualification' => 'Квалификация',
    'leads' => 'Сбор контактов',
    'personalization' => 'Персонализация'
];
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Подберите идеальное решение" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Пройдите квиз" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки отображения -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки отображения</h4>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Цель</label>
                <select name="goal" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <?php foreach ($goalOptions as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $goal == $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field flex items-end">
                <label class="flex items-center gap-2 cursor-pointer pb-2">
                    <input type="hidden" name="show_privacy_policy" value="0">
                    <input type="checkbox" name="show_privacy_policy" value="1" <?php echo $showPrivacyPolicy ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm text-slate-600 font-medium">Показывать согласие с политикой конфиденциальности</span>
                </label>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Сообщение об успехе</label>
                <input type="text" name="success_message" value="<?php echo e($successMessage); ?>" placeholder="Спасибо! Мы свяжемся с вами..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>
    </div>

    <!-- БЛОК 3: Вопросы -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Вопросы</h4>
        <div id="questions-container" class="space-y-3">
            <?php 
            $questionTemplate = '
            <div class="question-item-card bg-white p-4 rounded-xl border border-slate-200 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Вопрос #{INDEX}</span>
                    <button type="button" class="js-remove-question w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить вопрос">
                        <span class="icon-x text-sm"></span>
                    </button>
                </div>
                
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Текст вопроса</label>
                    <input type="text" name="questions[{INDEX}][text]" value="{TEXT}" placeholder="Введите вопрос..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                
                <div class="editor-row">
                    <div class="editor-field">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Тип вопроса</label>
                        <select name="questions[{INDEX}][type]" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] question-type-select">
                            {TYPE_OPTIONS}
                        </select>
                    </div>
                    <div class="editor-field flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer pb-2">
                            <input type="hidden" name="questions[{INDEX}][required]" value="0">
                            <input type="checkbox" name="questions[{INDEX}][required]" value="1" {REQUIRED_CHECKED} class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                            <span class="text-sm text-slate-600 font-medium">Обязательный</span>
                        </label>
                    </div>
                </div>
                
                <div class="question-options-container">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Варианты (каждый с новой строки)</label>
                    <textarea name="questions[{INDEX}][options]" rows="4" placeholder="Вариант 1&#10;Вариант 2&#10;Вариант 3" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] resize-y">{OPTIONS}</textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Для типов "Один вариант" и "Несколько вариантов"</p>
                </div>
            </div>';
            ?>
            
            <?php 
            $typeOptionsHtml = '';
            foreach ($questionTypes as $key => $label) {
                $typeOptionsHtml .= '<option value="' . $key . '">' . $label . '</option>';
            }
            
            foreach ($questions as $idx => $q):
                $num = $idx + 1;
                $options = is_array($q['options']) ? implode("\n", $q['options']) : ($q['options'] ?? '');
                $requiredChecked = !empty($q['required']) ? 'checked' : '';
                $selectedType = $q['type'] ?? 'single';
                $typeOptions = '';
                foreach ($questionTypes as $key => $label) {
                    $selected = $key === $selectedType ? 'selected' : '';
                    $typeOptions .= '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
                }
                echo str_replace(
                    ['{INDEX}', '{TEXT}', '{OPTIONS}', '{REQUIRED_CHECKED}', '{TYPE_OPTIONS}'],
                    [$num, e($q['text'] ?? ''), e($options), $requiredChecked, $typeOptions],
                    $questionTemplate
                );
            endforeach; ?>
        </div>

        <template id="tmpl-question">
            <?php 
            $defaultTypeOptions = '';
            foreach ($questionTypes as $key => $label) {
                $selected = $key === 'single' ? 'selected' : '';
                $defaultTypeOptions .= '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
            }
            echo str_replace(
                ['{INDEX}', '{TEXT}', '{OPTIONS}', '{REQUIRED_CHECKED}', '{TYPE_OPTIONS}'],
                ['__INDEX__', '', '', '', $defaultTypeOptions],
                $questionTemplate
            ); ?>
        </template>

        <button type="button" id="add-question-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
            <span class="icon-plus text-sm"></span> Добавить вопрос
        </button>
    </div>

    <!-- БЛОК 4: Поля для сбора данных -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Поля для сбора данных</h4>
        <div id="fields-container" class="space-y-3">
            <?php 
            $fieldTemplate = '
            <div class="field-item-card bg-white p-4 rounded-xl border border-slate-200 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Поле #{INDEX}</span>
                    <button type="button" class="js-remove-field w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить поле">
                        <span class="icon-x text-sm"></span>
                    </button>
                </div>
                
                <div class="editor-row">
                    <div class="editor-field">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Тип поля</label>
                        <select name="fields[{INDEX}][type]" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                            {FIELD_TYPE_OPTIONS}
                        </select>
                    </div>
                    <div class="editor-field flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer pb-2">
                            <input type="hidden" name="fields[{INDEX}][required]" value="0">
                            <input type="checkbox" name="fields[{INDEX}][required]" value="1" {FIELD_REQUIRED_CHECKED} class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                            <span class="text-sm text-slate-600 font-medium">Обязательное</span>
                        </label>
                    </div>
                </div>
                
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подпись</label>
                    <input type="text" name="fields[{INDEX}][label]" value="{FIELD_LABEL}" placeholder="Введите подпись..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>';
            ?>
            
            <?php 
            $fieldTypeOptionsHtml = '';
            foreach ($fieldTypes as $key => $label) {
                $fieldTypeOptionsHtml .= '<option value="' . $key . '">' . $label . '</option>';
            }
            
            foreach ($fields as $idx => $f):
                $num = $idx + 1;
                $selectedType = $f['type'] ?? 'text';
                $typeOptions = '';
                foreach ($fieldTypes as $key => $label) {
                    $selected = $key === $selectedType ? 'selected' : '';
                    $typeOptions .= '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
                }
                $requiredChecked = !empty($f['required']) ? 'checked' : '';
                echo str_replace(
                    ['{INDEX}', '{FIELD_LABEL}', '{FIELD_REQUIRED_CHECKED}', '{FIELD_TYPE_OPTIONS}'],
                    [$num, e($f['label'] ?? ''), $requiredChecked, $typeOptions],
                    $fieldTemplate
                );
            endforeach; ?>
        </div>

        <template id="tmpl-field">
            <?php 
            $defaultFieldTypeOptions = '';
            foreach ($fieldTypes as $key => $label) {
                $selected = $key === 'text' ? 'selected' : '';
                $defaultFieldTypeOptions .= '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
            }
            echo str_replace(
                ['{INDEX}', '{FIELD_LABEL}', '{FIELD_REQUIRED_CHECKED}', '{FIELD_TYPE_OPTIONS}'],
                ['__INDEX__', '', '', $defaultFieldTypeOptions],
                $fieldTemplate
            ); ?>
        </template>

        <button type="button" id="add-field-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
            <span class="icon-plus text-sm"></span> Добавить поле
        </button>
    </div>
</div>