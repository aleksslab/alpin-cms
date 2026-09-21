<?php
/**
 * Модуль: Контакты — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? 'Контакты';
$description = $moduleData['description'] ?? '';
$workingHours = $moduleData['working_hours'] ?? 'Пн-Пт: 9:00 - 21:00';

// Настройки блоков
$contactsTitle = $moduleData['contacts_title'] ?? '';
$contactsSubtitle = $moduleData['contacts_subtitle'] ?? '';
$formTitle = $moduleData['form_title'] ?? '';
$formSubtitle = $moduleData['form_subtitle'] ?? '';
$buttonText = $moduleData['button_text'] ?? 'Отправить';
$mapTitle = $moduleData['map_title'] ?? '';
$mapSubtitle = $moduleData['map_subtitle'] ?? '';
$mapIframe = $moduleData['map_iframe'] ?? '';
$settingsClass = $moduleData['settings']['class'] ?? '';
$showPrivacyPolicy = $moduleData['show_privacy_policy'] ?? true;

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

$settings = getSettingsData();
$phone = $settings['phone'] ?? '';
$email = $settings['email'] ?? '';
$address = $settings['address'] ?? '';
$token = $_SESSION['csrf_token'] ?? '';

$hasLeft = !empty($leftBlocks);
$hasRight = !empty($rightBlocks);

if (!$hasLeft && !$hasRight) {
    return;
}

// Классы для колонок
if ($hasLeft && $hasRight) {
    $leftColClass = '';
    $rightColClass = '';
} elseif ($hasLeft && !$hasRight) {
    $leftColClass = 'lg:col-span-2 max-w-3xl mx-auto w-full';
    $rightColClass = 'hidden';
} elseif (!$hasLeft && $hasRight) {
    $leftColClass = 'hidden';
    $rightColClass = 'lg:col-span-2 max-w-2xl mx-auto w-full';
}

// Собираем обязательные поля
$requiredFields = [];
if ($showName && $requiredName) $requiredFields[] = 'name';
if ($showEmailForm && $requiredEmailForm) $requiredFields[] = 'email';
if ($showPhoneForm && $requiredPhoneForm) $requiredFields[] = 'phone';
if ($showMessageForm && $requiredMessageForm) $requiredFields[] = 'message';

$hasHeader = !empty($title) || !empty($subtitle) || !empty($description);

// ============================================================
// ФУНКЦИЯ РЕНДЕРИНГА КОНТАКТНОЙ ИНФОРМАЦИИ
// ============================================================
if (!function_exists('renderContactsInfo')) {
    function renderContactsInfo($showPhone, $phone, $showEmail, $email, $showAddress, $address, $showWorkingHoursField, $workingHours) {
        ?>
        <div class="space-y-8">
            <?php if ($showPhone && !empty($phone)): ?>
            <a href="tel:<?php echo e($phone); ?>" class="flex items-center gap-6 group">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-[var(--primary-color)] group-hover:bg-[var(--primary-color)] group-hover:text-white transition-colors">
                    <span class="icon-phone text-2xl"></span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Телефон</p>
                    <p class="text-2xl font-bold text-slate-800"><?php echo formatPhone($phone); ?></p>
                </div>
            </a>
            <?php endif; ?>

            <?php if ($showEmail && !empty($email)): ?>
            <a href="mailto:<?php echo e($email); ?>" class="flex items-center gap-6 group">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-[var(--primary-color)] group-hover:bg-[var(--primary-color)] group-hover:text-white transition-colors">
                    <span class="icon-mail text-2xl"></span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Email</p>
                    <p class="text-xl font-bold text-slate-800"><?php echo $email; ?></p>
                </div>
            </a>
            <?php endif; ?>

            <?php if ($showAddress && !empty($address)): ?>
            <div class="flex items-center gap-6">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-[var(--primary-color)]">
                    <span class="icon-map-pin text-2xl"></span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Адрес</p>
                    <p class="text-lg font-bold text-slate-800"><?php echo e($address); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($showWorkingHoursField && !empty($workingHours)): ?>
            <div class="flex items-center gap-6">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-[var(--primary-color)]">
                    <span class="icon-clock text-2xl"></span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Часы работы</p>
                    <p class="text-lg font-bold text-slate-800"><?php echo e($workingHours); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

// ============================================================
// ФУНКЦИЯ РЕНДЕРИНГА ФОРМЫ
// ============================================================
if (!function_exists('renderContactForm')) {
    function renderContactForm($token, $requiredFields, $formTitle, $formSubtitle, $buttonText, $showName, $requiredName, $showEmailForm, $requiredEmailForm, $showPhoneForm, $requiredPhoneForm, $showMessageForm, $requiredMessageForm, $showPrivacyPolicy) {
        ?>
        <div class="w-full relative id-form-container">
            <form id="contactForm" class="w-full bg-[var(--bg-section)] rounded-[2.5rem]">
                <input type="hidden" name="csrf_token" value="<?php echo $token; ?>" />
                <input type="hidden" name="required_fields" value='<?php echo json_encode($requiredFields); ?>' />
                <input type="hidden" name="module" value="contacts" />

                <?php if (!empty($formTitle) || !empty($formSubtitle)): ?>
                <div class="mb-6">
                    <?php if (!empty($formTitle)): ?>
                    <h3 class="text-2xl font-bold text-slate-800 mb-3"><?php echo e($formTitle); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($formSubtitle)): ?>
                    <p class="text-slate-500"><?php echo e($formSubtitle); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div id="contacts-form-messages"></div>

                <div id="formFields" class="space-y-5">
                    <?php if ($showName): ?>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-2">Имя <?php if ($requiredName): ?><span class="text-red-500">*</span><?php endif; ?></label>
                        <input type="text" name="name" <?php if ($requiredName): ?>required<?php endif; ?> class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)] focus:ring-1 focus:ring-[var(--primary-color)]" placeholder="Иван Иванов" />
                    </div>
                    <?php endif; ?>

                    <?php if ($showEmailForm): ?>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-2">Email <?php if ($requiredEmailForm): ?><span class="text-red-500">*</span><?php endif; ?></label>
                        <input type="email" name="email" <?php if ($requiredEmailForm): ?>required<?php endif; ?> class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)] focus:ring-1 focus:ring-[var(--primary-color)]" placeholder="email@example.com" />
                    </div>
                    <?php endif; ?>

                    <?php if ($showPhoneForm): ?>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-2">Телефон <?php if ($requiredPhoneForm): ?><span class="text-red-500">*</span><?php endif; ?></label>
                        <input type="tel" name="phone" <?php if ($requiredPhoneForm): ?>required<?php endif; ?> class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)] focus:ring-1 focus:ring-[var(--primary-color)]" placeholder="+7 (999) 000-00-00" />
                    </div>
                    <?php endif; ?>

                    <?php if ($showMessageForm): ?>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-2">Сообщение <?php if ($requiredMessageForm): ?><span class="text-red-500">*</span><?php endif; ?></label>
                        <textarea name="message" rows="3" <?php if ($requiredMessageForm): ?>required<?php endif; ?> class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)] focus:ring-1 focus:ring-[var(--primary-color)] resize-none" placeholder="Ваше сообщение..."></textarea>
                    </div>
                    <?php endif; ?>

                    <?php if ($showPrivacyPolicy): ?>
                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="privacy_policy" id="privacy_policy" required class="w-5 h-5 mt-0.5 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)] flex-shrink-0">
                        <label for="privacy_policy" class="text-sm text-slate-500">
                            Я соглашаюсь с <button type="button" onclick="openPolicy('privacy')" class="text-[var(--primary-color)] hover:underline font-medium">политикой конфиденциальности</button>
                        </label>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="w-full bg-[var(--primary-color)] text-white font-bold py-4 rounded-xl hover:bg-[var(--primary-dark)] transition-colors mt-2 shadow-lg shadow-[var(--primary-color)]/20 cursor-pointer"><?php echo e($buttonText); ?></button>
                </div>
            </form>
        </div>
        <?php
    }
}
?>

<!-- ============================================================ -->
<!-- ОБЩИЙ ЗАГОЛОВОК МОДУЛЯ -->
<!-- ============================================================ -->
<?php if ($hasHeader): ?>
<div class="text-center max-w-3xl mx-auto mb-12">
    <?php if (!empty($subtitle)): ?>
    <span class="text-[var(--primary-color)] font-bold tracking-wider uppercase text-sm block mb-2"><?php echo e($subtitle); ?></span>
    <?php endif; ?>

    <?php if (!empty($title)): ?>
    <h2 class="text-3xl md:text-4xl font-bold text-slate-800 mb-4"><?php echo e($title); ?></h2>
    <?php endif; ?>

    <?php if (!empty($description)): ?>
    <p class="text-[var(--text-muted)] max-w-2xl mx-auto text-lg"><?php echo e($description); ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start pt-2 <?php echo e($settingsClass); ?>">
    
    <!-- ЛЕВАЯ КОЛОНКА -->
    <?php if ($hasLeft): ?>
    <div class="<?php echo $leftColClass; ?> space-y-8 px-8 lg:px-12">
        <?php foreach ($leftBlocks as $block): ?>
            <?php if ($block === 'contacts'): ?>
                <?php if (!empty($contactsTitle) || !empty($contactsSubtitle)): ?>
                <div class="mb-6">
                    <?php if (!empty($contactsTitle)): ?>
                    <h3 class="text-2xl font-bold text-slate-800 mb-2"><?php echo e($contactsTitle); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($contactsSubtitle)): ?>
                    <p class="text-slate-500"><?php echo e($contactsSubtitle); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php renderContactsInfo($showPhone, $phone, $showEmail, $email, $showAddress, $address, $showWorkingHoursField, $workingHours); ?>
            <?php elseif ($block === 'form'): ?>
                <?php renderContactForm($token, $requiredFields, $formTitle, $formSubtitle, $buttonText, $showName, $requiredName, $showEmailForm, $requiredEmailForm, $showPhoneForm, $requiredPhoneForm, $showMessageForm, $requiredMessageForm, $showPrivacyPolicy); ?>
            <?php elseif ($block === 'map' && !empty($mapIframe)): ?>
                <?php if (!empty($mapTitle) || !empty($mapSubtitle)): ?>
                <div class="mb-6">
                    <?php if (!empty($mapTitle)): ?>
                    <h3 class="text-2xl font-bold text-slate-800 mb-2"><?php echo e($mapTitle); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($mapSubtitle)): ?>
                    <p class="text-slate-500"><?php echo e($mapSubtitle); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="rounded-2xl overflow-hidden border border-slate-200">
                    <?php echo $mapIframe; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- ПРАВАЯ КОЛОНКА -->
    <?php if ($hasRight): ?>
    <div class="<?php echo $rightColClass; ?> space-y-8 px-8 lg:px-12">
        <?php foreach ($rightBlocks as $block): ?>
            <?php if ($block === 'contacts'): ?>
                <?php if (!empty($contactsTitle) || !empty($contactsSubtitle)): ?>
                <div class="mb-6">
                    <?php if (!empty($contactsTitle)): ?>
                    <h3 class="text-2xl font-bold text-slate-800 mb-2"><?php echo e($contactsTitle); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($contactsSubtitle)): ?>
                    <p class="text-slate-500"><?php echo e($contactsSubtitle); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php renderContactsInfo($showPhone, $phone, $showEmail, $email, $showAddress, $address, $showWorkingHoursField, $workingHours); ?>
            <?php elseif ($block === 'form'): ?>
                <?php renderContactForm($token, $requiredFields, $formTitle, $formSubtitle, $buttonText, $showName, $requiredName, $showEmailForm, $requiredEmailForm, $showPhoneForm, $requiredPhoneForm, $showMessageForm, $requiredMessageForm, $showPrivacyPolicy); ?>
            <?php elseif ($block === 'map' && !empty($mapIframe)): ?>
                <?php if (!empty($mapTitle) || !empty($mapSubtitle)): ?>
                <div class="mb-6">
                    <?php if (!empty($mapTitle)): ?>
                    <h3 class="text-2xl font-bold text-slate-800 mb-2"><?php echo e($mapTitle); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($mapSubtitle)): ?>
                    <p class="text-slate-500"><?php echo e($mapSubtitle); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="rounded-2xl overflow-hidden border border-slate-200">
                    <?php echo $mapIframe; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
</div>