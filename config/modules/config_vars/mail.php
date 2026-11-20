<?php
/**
 * Секция «Почта (SMTP)» в настройках сайта.
 *
 * Поля пишутся не в settings.json, а в config/data/mail_config.json:
 * сохранение перехватывает handleSaveSettings() по флагу mail_config_section.
 */

if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$mailConfig     = get_mail_config(true);
$mailConfigured = mail_agent_is_configured($mailConfig);
$mailSiteEmail  = mail_site_email();
$mailSiteName   = mail_site_name();
$mailHasPassword = trim((string)($mailConfig['password_enc'] ?? '')) !== '';
$mailLastTest    = (int)($mailConfig['last_test_at'] ?? 0);
$mailLastError   = trim((string)($mailConfig['last_error'] ?? ''));
?>
<!-- СЕКЦИЯ: Почта (SMTP) -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Почта (SMTP)</span>
            <span class="text-[10px] text-slate-400">(агент отправки писем)</span>
        </div>
        <?php if ($mailConfigured): ?>
            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-bold">Настроен</span>
        <?php else: ?>
            <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-bold">Не настроен</span>
        <?php endif; ?>
    </div>

    <div class="section-content p-6 bg-slate-50 hidden">
        <input type="hidden" name="mail_config_section" value="1">

        <div class="space-y-5">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="mail_enabled" value="0">
                <input type="checkbox" name="mail_enabled" value="1" <?php echo !empty($mailConfig['enabled']) ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                <span class="text-sm font-medium text-slate-700">Включить почтовый агент</span>
            </label>
            <p class="text-[10px] text-slate-400 -mt-3">
                Пока агент не настроен, формы, которые умеют только отправлять письмо,
                на сайте не выводятся.
            </p>

            <div class="grid grid-cols-1 lg:grid-cols-2! gap-4 items-start">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">SMTP-хост</label>
                    <input type="text" name="mail_host" value="<?php echo e($mailConfig['host'] ?? ''); ?>"
                           class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 font-mono focus:outline-none focus:border-[var(--primary-color)]"
                           placeholder="smtp.yandex.ru">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Порт</label>
                    <input type="number" name="mail_port" id="js-mail-port" value="<?php echo (int)($mailConfig['port'] ?? 587); ?>" min="1" max="65535"
                           class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 font-mono focus:outline-none focus:border-[var(--primary-color)]">
                    <p class="text-[10px] text-slate-400 mt-2">STARTTLS — 587, SSL/TLS — 465, без шифрования — 25</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2! gap-4 items-start">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Шифрование</label>
                    <select name="mail_encryption" id="js-mail-encryption" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]">
                        <option value="tls" <?php echo ($mailConfig['encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>STARTTLS (587)</option>
                        <option value="ssl" <?php echo ($mailConfig['encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL/TLS (465)</option>
                        <option value="none" <?php echo ($mailConfig['encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>Без шифрования</option>
                    </select>
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Авторизация</label>
                    <label class="flex items-center gap-2 cursor-pointer h-[52px]">
                        <input type="hidden" name="mail_auth" value="0">
                        <input type="checkbox" name="mail_auth" id="js-mail-auth" value="1" <?php echo !empty($mailConfig['auth']) ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                        <span class="text-sm font-medium text-slate-700">Логин и пароль</span>
                    </label>
                </div>
            </div>

            <div id="js-mail-auth-row" class="grid grid-cols-1 lg:grid-cols-2! gap-4 items-start<?php echo empty($mailConfig['auth']) ? ' hidden' : ''; ?>">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Логин</label>
                    <input type="text" name="mail_username" value="<?php echo e($mailConfig['username'] ?? ''); ?>" autocomplete="off"
                           class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 font-mono focus:outline-none focus:border-[var(--primary-color)]"
                           placeholder="user@example.com">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Пароль</label>
                    <div class="flex items-start gap-2">
                        <input type="text" name="mail_password" id="js-mail-password" value="" autocomplete="new-password"
                               class="flex-1 min-w-0 px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 font-mono focus:outline-none focus:border-[var(--primary-color)]"
                               placeholder="<?php echo $mailHasPassword ? '********' : 'Пароль SMTP'; ?>">
                        <?php if ($mailHasPassword): ?>
                            <button type="button" id="js-mail-clear-password" title="Очистить пароль" aria-label="Очистить пароль"
                                    class="flex items-center justify-center w-[54px] h-[54px] shrink-0 bg-white border border-slate-200 text-slate-500 rounded-xl hover:text-rose-600 hover:border-rose-200 transition-colors">
                                <span class="icon-eraser text-base" id="js-mail-clear-icon"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="mail_clear_password" id="js-mail-clear-flag" value="0">
                    <p class="text-[10px] text-slate-400 mt-2" id="js-mail-password-hint">
                        <?php if ($mailHasPassword): ?>
                            Пароль сохранён. Введите новый, чтобы заменить; пустое поле — оставить как есть.
                        <?php else: ?>
                            Символы видны, чтобы проверить ввод.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2! gap-4 items-start">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">E-mail отправителя</label>
                    <input type="email" name="mail_from_email" value="<?php echo e($mailConfig['from_email'] ?? ''); ?>"
                           class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]"
                           placeholder="<?php echo e($mailSiteEmail !== '' ? $mailSiteEmail : 'info@company.com'); ?>">
                    <p class="text-[10px] text-slate-400 mt-2">Пусто — e-mail сайта (<?php echo e($mailSiteEmail !== '' ? $mailSiteEmail : 'не задан'); ?>)</p>
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Имя отправителя</label>
                    <input type="text" name="mail_from_name" value="<?php echo e($mailConfig['from_name'] ?? ''); ?>"
                           class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]"
                           placeholder="<?php echo e($mailSiteName); ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2! gap-4 items-start">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">E-mail получателя</label>
                    <input type="email" name="mail_to_email" value="<?php echo e($mailConfig['to_email'] ?? ''); ?>"
                           class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]"
                           placeholder="<?php echo e($mailSiteEmail !== '' ? $mailSiteEmail : 'info@company.com'); ?>">
                    <p class="text-[10px] text-slate-400 mt-2">Пусто — e-mail сайта</p>
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Таймаут, сек</label>
                    <input type="number" name="mail_timeout" value="<?php echo (int)($mailConfig['timeout'] ?? 15); ?>" min="5" max="60"
                           class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 font-mono focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>

            <div class="border-t border-slate-200/60 pt-4 flex flex-col sm:flex-row sm:items-center gap-3">
                <button type="button" id="js-mail-test"
                        class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-bold rounded-xl hover:bg-slate-100 transition-all">
                    Отправить тестовое письмо
                </button>
                <span id="js-mail-test-result" class="text-xs text-slate-500"></span>
            </div>

            <?php if ($mailLastTest > 0 || $mailLastError !== ''): ?>
                <div class="text-[10px] text-slate-400">
                    <?php if ($mailLastTest > 0): ?>
                        Последняя проверка: <?php echo e(date('d.m.Y H:i', $mailLastTest)); ?>.
                    <?php endif; ?>
                    <?php if ($mailLastError !== ''): ?>
                        <div class="text-rose-600">
                            Ошибка: <?php echo e($mailLastError); ?>
                            <?php if (stripos($mailLastError, 'authentic') !== false): ?>
                                <div class="text-slate-500 mt-1">
                                    Проверьте логин и пароль: для большинства почтовых сервисов нужен
                                    отдельный пароль для внешних приложений, обычный пароль не подходит.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    /**
     * Подставляет типовой порт при смене способа шифрования.
     *
     * @returns {void}
     */
    function initMailPort() {
        var select = document.getElementById('js-mail-encryption');
        var port = document.getElementById('js-mail-port');

        if (!select || !port || select.dataset.bound === '1') {
            return;
        }

        select.dataset.bound = '1';

        var presets = { tls: '587', ssl: '465', none: '25' };

        select.addEventListener('change', function () {
            var value = presets[select.value];
            if (value) {
                port.value = value;
            }
        });
    }

    /**
     * Кнопка «Очистить» вместо чекбокса: помечает пароль на удаление.
     *
     * Кнопка компактная — иконка в размер поля ввода; при повторном нажатии
     * очистка отменяется, иконка меняется на «вернуть».
     *
     * @returns {void}
     */
    function initMailPassword() {
        var input = document.getElementById('js-mail-password');
        var flag = document.getElementById('js-mail-clear-flag');
        var button = document.getElementById('js-mail-clear-password');
        var hint = document.getElementById('js-mail-password-hint');
        var icon = document.getElementById('js-mail-clear-icon');

        if (!input || !flag || !button || button.dataset.bound === '1') {
            return;
        }

        button.dataset.bound = '1';

        var initialPlaceholder = input.getAttribute('placeholder') || '';
        var marked = false;

        /**
         * Переключает вид кнопки в состояние «очистить / отменить».
         *
         * @param {boolean} active
         * @returns {void}
         */
        function paint(active) {
            button.classList.toggle('bg-white', !active);
            button.classList.toggle('bg-rose-50', active);
            button.classList.toggle('border-slate-200', !active);
            button.classList.toggle('border-rose-200', active);
            button.classList.toggle('text-slate-500', !active);
            button.classList.toggle('text-rose-600', active);

            var title = active ? 'Отменить очистку пароля' : 'Очистить пароль';
            button.title = title;
            button.setAttribute('aria-label', title);

            if (icon) {
                icon.className = active ? 'icon-undo-2 text-base' : 'icon-eraser text-base';
            }
        }

        button.addEventListener('click', function () {
            marked = !marked;

            flag.value = marked ? '1' : '0';
            input.disabled = marked;
            input.value = '';
            input.setAttribute('placeholder', marked ? 'Пароль будет удалён' : initialPlaceholder);
            paint(marked);

            if (hint) {
                hint.textContent = marked
                    ? 'Пароль удалится при сохранении настроек.'
                    : 'Пароль сохранён. Введите новый, чтобы заменить; пустое поле — оставить как есть.';
            }
        });
    }

    /**
     * Скрывает логин и пароль, если авторизация на SMTP не нужна.
     *
     * Значения при этом не стираются: при включении авторизации обратно
     * поля возвращаются с сохранёнными данными.
     *
     * @returns {void}
     */
    function initMailAuth() {
        var checkbox = document.getElementById('js-mail-auth');
        var row = document.getElementById('js-mail-auth-row');

        if (!checkbox || !row || checkbox.dataset.bound === '1') {
            return;
        }

        checkbox.dataset.bound = '1';

        /**
         * @returns {void}
         */
        function sync() {
            row.classList.toggle('hidden', !checkbox.checked);
        }

        checkbox.addEventListener('change', sync);
        sync();
    }

    /**
     * Тестовая отправка письма с текущими значениями формы.
     *
     * @returns {void}
     */
    function initMailTest() {
        var button = document.getElementById('js-mail-test');
        var result = document.getElementById('js-mail-test-result');

        if (!button || !result || button.dataset.bound === '1') {
            return;
        }

        button.dataset.bound = '1';

        button.addEventListener('click', function () {
            var form = button.closest('form');
            if (!form) {
                return;
            }

            button.disabled = true;
            result.className = 'text-xs text-slate-500';
            result.textContent = 'Отправляем тестовое письмо…';

            fetch('index.php?tab=config_vars&ajax=mail_test', {
                method: 'POST',
                body: new FormData(form)
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.success) {
                        result.className = 'text-xs text-emerald-600';
                        result.textContent = 'Письмо отправлено. Проверьте ящик получателя; не забудьте сохранить настройки.';
                    } else {
                        result.className = 'text-xs text-rose-600';
                        result.textContent = data.error || 'Не удалось отправить письмо.';
                    }
                })
                .catch(function () {
                    result.className = 'text-xs text-rose-600';
                    result.textContent = 'Ошибка сети при проверке почты.';
                })
                .finally(function () {
                    button.disabled = false;
                });
        });
    }

    function initMailSection() {
        initMailPort();
        initMailAuth();
        initMailPassword();
        initMailTest();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMailSection);
    } else {
        initMailSection();
    }
})();
</script>