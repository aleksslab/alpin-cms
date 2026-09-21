<?php
/**
 * Модуль: Контакты — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? 'Контакты';
$description = $moduleData['description'] ?? '';
$workingHours = $moduleData['working_hours'] ?? 'Пн-Пт: 9:00 - 21:00';
$settingsClass = $moduleData['settings']['class'] ?? '';
$showPrivacyPolicy = $moduleData['show_privacy_policy'] ?? true;

// Настройки блоков
$contactsTitle = $moduleData['contacts_title'] ?? '';
$contactsSubtitle = $moduleData['contacts_subtitle'] ?? '';
$formTitle = $moduleData['form_title'] ?? '';
$formSubtitle = $moduleData['form_subtitle'] ?? '';
$buttonText = $moduleData['button_text'] ?? 'Отправить';
$mapTitle = $moduleData['map_title'] ?? '';
$mapSubtitle = $moduleData['map_subtitle'] ?? '';
$mapIframe = $moduleData['map_iframe'] ?? '';

// Левая колонка
if (!isset($moduleData['left_blocks']) || !is_array($moduleData['left_blocks'])) {
    $leftBlocks = empty($moduleData) ? ['contacts'] : [];
} else {
    $leftBlocks = $moduleData['left_blocks'];
}

// Правая колонка
if (!isset($moduleData['right_blocks']) || !is_array($moduleData['right_blocks'])) {
    $rightBlocks = empty($moduleData) ? ['form'] : [];
} else {
    $rightBlocks = $moduleData['right_blocks'];
}

$showPhone = $moduleData['show_phone'] ?? true;
$showEmail = $moduleData['show_email'] ?? true;
$showAddress = $moduleData['show_address'] ?? true;
$showWorkingHoursField = $moduleData['show_working_hours'] ?? true;

// Настройки полей формы
$showName = $moduleData['show_name'] ?? true;
$requiredName = $moduleData['required_name'] ?? false;
$showEmailForm = $moduleData['show_email_form'] ?? true;
$requiredEmailForm = $moduleData['required_email_form'] ?? true;
$showPhoneForm = $moduleData['show_phone_form'] ?? true;
$requiredPhoneForm = $moduleData['required_phone_form'] ?? false;
$showMessageForm = $moduleData['show_message_form'] ?? true;
$requiredMessageForm = $moduleData['required_message_form'] ?? false;

$allBlocks = ['contacts', 'form', 'map'];
$blockLabels = [
    'contacts' => 'Контакты',
    'form' => 'Форма',
    'map' => 'Карта'
];
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Текст подзаголовка..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Контакты" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки отображения -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки отображения</h4>
        
        <!-- Класс модуля -->
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
            <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>

        <!-- Политика конфиденциальности -->
        <label class="flex items-center gap-2 cursor-pointer pt-2 border-t border-slate-200/60">
            <input type="hidden" name="show_privacy_policy" value="0">
            <input type="checkbox" name="show_privacy_policy" value="1" <?php echo $showPrivacyPolicy ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
            <span class="text-sm text-slate-600">Показывать согласие с политикой конфиденциальности</span>
        </label>
    </div>

    <!-- БЛОК 3: Настройки контактов -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки контактов</h4>

        <!-- Часы работы -->
        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Часы работы</label>
            <input type="text" name="working_hours" value="<?php echo e($workingHours); ?>" placeholder="Пн-Пт: 9:00 - 21:00" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>

        <!-- ЛЕВАЯ КОЛОНКА -->
        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Левая колонка</label>
            <div id="left-blocks-container" class="space-y-2 mt-2">
                <?php foreach ($leftBlocks as $block): ?>
                <div class="flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200 block-item" data-block="<?php echo e($block); ?>">
                    <button type="button" class="js-move-up w-6 h-6 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Вверх">
                        <span class="icon-chevron-up text-xs"></span>
                    </button>
                    <button type="button" class="js-move-down w-6 h-6 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Вниз">
                        <span class="icon-chevron-down text-xs"></span>
                    </button>
                    <button type="button" class="js-remove-block w-6 h-6 rounded flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Удалить">
                        <span class="icon-x text-xs"></span>
                    </button>
                    <span class="text-sm font-medium text-slate-700"><?php echo e($blockLabels[$block] ?? $block); ?></span>
                    <input type="hidden" name="left_blocks[]" value="<?php echo e($block); ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-2 flex gap-2">
                <select id="left-block-select" class="flex-1 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="">— Добавить блок —</option>
                    <?php foreach ($allBlocks as $block): ?>
                        <?php if (!in_array($block, $leftBlocks)): ?>
                        <option value="<?php echo e($block); ?>"><?php echo e($blockLabels[$block]); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="add-left-block" class="px-4 py-1.5 bg-[var(--primary-color)] text-white text-sm font-bold rounded-lg hover:bg-[var(--primary-dark)] transition-all">Добавить</button>
            </div>
        </div>

        <!-- ПРАВАЯ КОЛОНКА -->
        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Правая колонка</label>
            <div id="right-blocks-container" class="space-y-2 mt-2">
                <?php foreach ($rightBlocks as $block): ?>
                <div class="flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200 block-item" data-block="<?php echo e($block); ?>">
                    <button type="button" class="js-move-up w-6 h-6 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Вверх">
                        <span class="icon-chevron-up text-xs"></span>
                    </button>
                    <button type="button" class="js-move-down w-6 h-6 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Вниз">
                        <span class="icon-chevron-down text-xs"></span>
                    </button>
                    <button type="button" class="js-remove-block w-6 h-6 rounded flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Удалить">
                        <span class="icon-x text-xs"></span>
                    </button>
                    <span class="text-sm font-medium text-slate-700"><?php echo e($blockLabels[$block] ?? $block); ?></span>
                    <input type="hidden" name="right_blocks[]" value="<?php echo e($block); ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-2 flex gap-2">
                <select id="right-block-select" class="flex-1 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="">— Добавить блок —</option>
                    <?php foreach ($allBlocks as $block): ?>
                        <?php if (!in_array($block, $rightBlocks)): ?>
                        <option value="<?php echo e($block); ?>"><?php echo e($blockLabels[$block]); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="add-right-block" class="px-4 py-1.5 bg-[var(--primary-color)] text-white text-sm font-bold rounded-lg hover:bg-[var(--primary-dark)] transition-all">Добавить</button>
            </div>
        </div>

        <!-- Чекбоксы контактов -->
        <div class="grid grid-cols-2 gap-3 pt-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="show_phone" value="0">
                <input type="checkbox" name="show_phone" value="1" <?php echo $showPhone ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                <span class="text-sm text-slate-600">Телефон</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="show_email" value="0">
                <input type="checkbox" name="show_email" value="1" <?php echo $showEmail ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                <span class="text-sm text-slate-600">Email</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="show_address" value="0">
                <input type="checkbox" name="show_address" value="1" <?php echo $showAddress ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                <span class="text-sm text-slate-600">Адрес</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="show_working_hours" value="0">
                <input type="checkbox" name="show_working_hours" value="1" <?php echo $showWorkingHoursField ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                <span class="text-sm text-slate-600">Часы работы</span>
            </label>
        </div>
    </div>

    <!-- БЛОК 4: Настройки блоков -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки блоков</h4>

        <!-- БЛОК "КОНТАКТЫ" -->
        <div class="space-y-3 pb-3 border-b border-slate-200/60">
            <h5 class="text-sm font-bold text-slate-700">Контакты</h5>
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
                    <input type="text" name="contacts_title" value="<?php echo e($contactsTitle); ?>" placeholder="Наши контакты" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
                    <input type="text" name="contacts_subtitle" value="<?php echo e($contactsSubtitle); ?>" placeholder="Текст подзаголовка..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
        </div>

        <!-- БЛОК "ФОРМА" -->
        <div class="space-y-3 pb-3 border-b border-slate-200/60">
            <h5 class="text-sm font-bold text-slate-700">Форма</h5>
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
                    <input type="text" name="form_title" value="<?php echo e($formTitle); ?>" placeholder="Оставить заявку" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
                    <input type="text" name="form_subtitle" value="<?php echo e($formSubtitle); ?>" placeholder="Краткое описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Текст кнопки</label>
                <input type="text" name="button_text" value="<?php echo e($buttonText); ?>" placeholder="Отправить" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <!-- БЛОК "КАРТА" -->
        <div class="space-y-3">
            <h5 class="text-sm font-bold text-slate-700">Карта</h5>
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
                    <input type="text" name="map_title" value="<?php echo e($mapTitle); ?>" placeholder="Мы на карте" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
                    <input type="text" name="map_subtitle" value="<?php echo e($mapSubtitle); ?>" placeholder="Текст подзаголовка..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Iframe карты</label>
                <textarea name="map_iframe" rows="3" placeholder="<iframe src=...></iframe>" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono"><?php echo e($mapIframe); ?></textarea>
            </div>
        </div>
    </div>

    <!-- БЛОК 5: Настройки полей формы -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200">
        <h4 class="text-sm font-bold text-slate-700 mb-3">Настройки полей формы</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="pb-2 pr-4 font-medium">Поле</th>
                        <th class="pb-2 pr-4 font-medium text-center">Отображать</th>
                        <th class="pb-2 font-medium text-center">Обязательное</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php 
                    $formFields = [
                        ['name' => 'name', 'label' => 'Имя', 'show' => $showName, 'required' => $requiredName],
                        ['name' => 'email_form', 'label' => 'Email', 'show' => $showEmailForm, 'required' => $requiredEmailForm],
                        ['name' => 'phone_form', 'label' => 'Телефон', 'show' => $showPhoneForm, 'required' => $requiredPhoneForm],
                        ['name' => 'message_form', 'label' => 'Сообщение', 'show' => $showMessageForm, 'required' => $requiredMessageForm],
                    ];
                    foreach ($formFields as $field): 
                    ?>
                    <tr>
                        <td class="py-2 pr-4 font-medium text-slate-700"><?php echo $field['label']; ?></td>
                        <td class="py-2 pr-4 text-center">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="hidden" name="show_<?php echo $field['name']; ?>" value="0">
                                <input type="checkbox" name="show_<?php echo $field['name']; ?>" value="1" <?php echo $field['show'] ? 'checked' : ''; ?> 
                                       class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                            </label>
                        </td>
                        <td class="py-2 text-center">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="hidden" name="required_<?php echo $field['name']; ?>" value="0">
                                <input type="checkbox" name="required_<?php echo $field['name']; ?>" value="1" <?php echo $field['required'] ? 'checked' : ''; ?> 
                                       class="w-4 h-4 text-rose-500 rounded border-slate-300 focus:ring-rose-500">
                            </label>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-[10px] text-slate-400 mt-2">Обязательное поле учитывается только если поле отображается.</p>
    </div>
</div>