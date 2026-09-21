<?php
/**
 * Модуль: Форма захвата лидов — админка
 */

$title = $moduleData['title'] ?? 'Оставить заявку';
$subtitle = $moduleData['subtitle'] ?? '';
$description = $moduleData['description'] ?? '';
$buttonText = $moduleData['button_text'] ?? 'Отправить';
$successMessage = $moduleData['success_message'] ?? '';
$settingsClass = $moduleData['settings']['class'] ?? '';
$showPrivacyPolicy = $moduleData['show_privacy_policy'] ?? true;

$showName = $moduleData['show_name'] ?? true;
$requiredName = $moduleData['required_name'] ?? false;
$showEmail = $moduleData['show_email'] ?? true;
$requiredEmail = $moduleData['required_email'] ?? true;
$showPhone = $moduleData['show_phone'] ?? true;
$requiredPhone = $moduleData['required_phone'] ?? false;
$showMessage = $moduleData['show_message'] ?? true;
$requiredMessage = $moduleData['required_message'] ?? false;
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Краткое описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Оставить заявку" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки отображения -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки отображения</h4>
        
        <!-- Текст кнопки и Класс модуля в 1 ряд -->
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Текст кнопки</label>
                <input type="text" name="button_text" value="<?php echo e($buttonText); ?>" placeholder="Отправить" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>
        
        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Сообщение об успехе</label>
            <textarea name="success_message" rows="2" placeholder="Оставьте пустым для использования стандартного сообщения" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($successMessage); ?></textarea>
            <p class="text-[10px] text-slate-400 mt-1">Если не заполнено, будет использовано сообщение от mailer.php</p>
        </div>

        <!-- Политика конфиденциальности -->
        <label class="flex items-center gap-2 cursor-pointer pt-2 border-t border-slate-200/60">
            <input type="hidden" name="show_privacy_policy" value="0">
            <input type="checkbox" name="show_privacy_policy" value="1" <?php echo $showPrivacyPolicy ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
            <span class="text-sm text-slate-600">Показывать согласие с политикой конфиденциальности</span>
        </label>
    </div>

    <!-- БЛОК 3: Настройки полей -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200">
        <h4 class="text-sm font-bold text-slate-700 mb-3">Настройки полей</h4>
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
                        ['name' => 'email', 'label' => 'Email', 'show' => $showEmail, 'required' => $requiredEmail],
                        ['name' => 'phone', 'label' => 'Телефон', 'show' => $showPhone, 'required' => $requiredPhone],
                        ['name' => 'message', 'label' => 'Сообщение', 'show' => $showMessage, 'required' => $requiredMessage],
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