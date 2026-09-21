<?php
/**
 * Модуль: Форма захвата лидов — сайт
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

$requiredFields = [];
if ($showName && $requiredName) $requiredFields[] = 'name';
if ($showEmail && $requiredEmail) $requiredFields[] = 'email';
if ($showPhone && $requiredPhone) $requiredFields[] = 'phone';
if ($showMessage && $requiredMessage) $requiredFields[] = 'message';

$token = $_SESSION['csrf_token'] ?? '';
$hasHeader = !empty($title) || !empty($subtitle) || !empty($description);
?>

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

<div class="max-w-2xl mx-auto <?php echo e($settingsClass); ?>" data-success-message="<?php echo e($successMessage); ?>">
    <div class="bg-[var(--bg-section)] p-8 lg:p-12 rounded-[2.5rem]">
        <form id="leadForm" class="space-y-5">
            <div id="lead-form-messages"></div>
            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>" />
            <input type="hidden" name="required_fields" value='<?php echo json_encode($requiredFields); ?>' />

            <div id="leadFormFields" class="space-y-5">
                <?php if ($showName): ?>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">Имя <?php if ($requiredName): ?><span class="text-red-500">*</span><?php endif; ?></label>
                    <input type="text" name="name" <?php if ($requiredName): ?>required<?php endif; ?> class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)] focus:ring-1 focus:ring-[var(--primary-color)]" placeholder="Иван Иванов">
                </div>
                <?php endif; ?>

                <?php if ($showEmail): ?>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">Email <?php if ($requiredEmail): ?><span class="text-red-500">*</span><?php endif; ?></label>
                    <input type="email" name="email" <?php if ($requiredEmail): ?>required<?php endif; ?> class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)] focus:ring-1 focus:ring-[var(--primary-color)]" placeholder="email@example.com">
                </div>
                <?php endif; ?>

                <?php if ($showPhone): ?>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">Телефон <?php if ($requiredPhone): ?><span class="text-red-500">*</span><?php endif; ?></label>
                    <input type="tel" name="phone" <?php if ($requiredPhone): ?>required<?php endif; ?> class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)] focus:ring-1 focus:ring-[var(--primary-color)]" placeholder="+7 (999) 000-00-00">
                </div>
                <?php endif; ?>

                <?php if ($showMessage): ?>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">Сообщение <?php if ($requiredMessage): ?><span class="text-red-500">*</span><?php endif; ?></label>
                    <textarea name="message" rows="3" <?php if ($requiredMessage): ?>required<?php endif; ?> class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)] focus:ring-1 focus:ring-[var(--primary-color)] resize-none" placeholder="Ваше сообщение..."></textarea>
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

                <button type="submit" class="w-full bg-[var(--primary-color)] text-white font-bold py-4 rounded-xl hover:bg-[var(--primary-dark)] transition-colors mt-2 shadow-lg shadow-[var(--primary-color)]/20 cursor-pointer">
                    <?php echo e($buttonText); ?>
                </button>
            </div>
        </form>
    </div>
</div>