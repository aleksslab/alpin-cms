/**
 * main.js — Админка
 * Аккордеон, бургер-меню, безопасность, тёмная тема
 */
(function() {
    'use strict';

    // ===== 1. ЛОГИКА ЭКРАНА ВХОДА =====
    function initLoginToggle() {
        var loginBox = document.getElementById('login-box');
        var generatorBox = document.getElementById('generator-box');
        var showGenBtn = document.getElementById('show-gen-btn');
        var showLoginBtn = document.getElementById('show-login-btn');

        if (!loginBox || !generatorBox || !showGenBtn || !showLoginBtn) {
            return;
        }

        showGenBtn.addEventListener('click', function() {
            loginBox.classList.add('hidden');
            generatorBox.classList.remove('hidden');
        });

        showLoginBtn.addEventListener('click', function() {
            generatorBox.classList.add('hidden');
            loginBox.classList.remove('hidden');
        });
    }

    // ===== 2. БЕЗОПАСНОСТЬ: КОНТРОЛЬ ПОЛЕЙ =====
    function initSecurityCheck() {
        var secLoginInput = document.querySelector('input[name="sec_login"]');
        var secPasswordInput = document.getElementById('sec-password');

        if (!secLoginInput || !secPasswordInput) {
            return;
        }

        var originalLogin = secLoginInput.value.trim();

        function checkSecurityChanges() {
            var currentLogin = secLoginInput.value.trim();

            if (currentLogin !== originalLogin) {
                secPasswordInput.setAttribute('required', 'required');
                secPasswordInput.classList.add('border-rose-300');
            } else {
                secPasswordInput.removeAttribute('required');
                secPasswordInput.classList.remove('border-rose-300');
            }
        }

        secLoginInput.addEventListener('input', checkSecurityChanges);
        secPasswordInput.addEventListener('input', checkSecurityChanges);
    }

    // ===== 2.1 ПОДТВЕРЖДЕНИЕ ОПАСНЫХ ДЕЙСТВИЙ ПАРОЛЕМ =====
    function initSecurityConfirm() {
        var forms = document.querySelectorAll('form[data-sec-confirm]');
        var modal = document.querySelector('.js-security-confirm');

        if (!forms.length || !modal) {
            return;
        }

        // Без JavaScript аварийное поле пароля остаётся видимым.
        var fallbacks = document.querySelectorAll('[data-sec-password-fallback]');
        for (var i = 0; i < fallbacks.length; i++) {
            fallbacks[i].classList.add('hidden');
        }

        var textBox = modal.querySelector('.js-security-confirm-text');
        var modalForm = modal.querySelector('.js-security-confirm-form');
        var passwordInput = modalForm ? modalForm.querySelector('input[name="sec_current_password"]') : null;
        var errorBox = modal.querySelector('.js-security-confirm-error');
        var submitBtn = modal.querySelector('.js-security-confirm-submit');
        var cancelBtn = modal.querySelector('.js-security-confirm-cancel');

        if (!modalForm || !passwordInput || !errorBox || !submitBtn || !cancelBtn) {
            return;
        }

        var messages = {
            save: 'Сохранить настройки безопасности?',
            unban: 'Разблокировать IP-адрес?',
            ban: 'Занести IP-адрес в постоянный чёрный список?'
        };
        // form.submit() не передаёт имя нажатой кнопки, поэтому флаг действия
        // добавляем скрытым полем — на нём держится POST-роутинг админки.
        var actionFlags = {
            save: 'save_security',
            unban: 'action_unban',
            ban: 'action_manual_ban'
        };
        var pendingForm = null;
        var pendingAction = '';
        var isChecking = false;
        // Номер поколения модалки. Растёт при каждом открытии/закрытии и при
        // отправке: ответ, пришедший после смены поколения, игнорируется.
        var confirmSeq = 0;

        function showError(message) {
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        }

        function openModal(form, action) {
            confirmSeq++;
            // Прошлый запрос мог остаться «в полёте», а его ответ — проигнорирован
            // (модалку закрыли). Сбрасываем состояние, иначе форма не отправится.
            isChecking = false;
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-60');
            pendingForm = form;
            pendingAction = action;

            var text = messages[action] || 'Подтвердите действие текущим паролем.';
            var ipInput = form.querySelector('input[name="unban_ip"], input[name="manual_ban_ip"]');
            if (ipInput && ipInput.value.trim() !== '') {
                text = text.replace('IP-адрес', 'IP-адрес ' + ipInput.value.trim());
            }
            if (textBox) {
                textBox.textContent = text;
            }

            errorBox.textContent = '';
            errorBox.classList.add('hidden');
            passwordInput.value = '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            window.setTimeout(function() {
                passwordInput.focus();
            }, 30);
        }

        function closeModal() {
            confirmSeq++;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            passwordInput.value = '';
            pendingForm = null;
            pendingAction = '';
        }

        for (var j = 0; j < forms.length; j++) {
            forms[j].addEventListener('submit', function(event) {
                var action = this.getAttribute('data-sec-confirm') || '';
                if (!messages[action]) {
                    return;
                }
                event.preventDefault();
                openModal(this, action);
            });
        }

        cancelBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        modalForm.addEventListener('submit', function(event) {
            event.preventDefault();

            if (isChecking || !pendingForm) {
                return;
            }

            var password = passwordInput.value;
            if (password === '') {
                showError('Введите текущий пароль.');
                passwordInput.focus();
                return;
            }

            // Фиксируем цель до запроса: пока он идёт, модалку могут закрыть
            // или переоткрыть для другой формы — тогда ответ уже не нужен.
            var form = pendingForm;
            var action = pendingAction;
            var seq = ++confirmSeq;

            var csrfInput = form.querySelector('input[name="csrf_token"]');
            var data = new FormData();
            data.append('csrf_token', csrfInput ? csrfInput.value : '');
            data.append('security_confirm', '1');
            data.append('confirm_action', action);
            data.append('sec_current_password', password);

            isChecking = true;
            errorBox.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-60');

            fetch('index.php', {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('http');
                    }
                    return response.json();
                })
                .then(function(json) {
                    if (seq !== confirmSeq) {
                        return;
                    }
                    if (json && json.logout) {
                        window.location.href = 'index.php?msg=security_failed';
                        return;
                    }
                    if (!json || !json.ok) {
                        showError((json && json.error) ? json.error : 'Не удалось проверить пароль.');
                        return;
                    }

                    var flag = actionFlags[action] || '';
                    closeModal();

                    if (flag) {
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = flag;
                        hidden.value = '1';
                        form.appendChild(hidden);
                    }

                    // form.submit() не вызывает событие submit — повторного
                    // перехвата модалкой не будет.
                    form.submit();
                })
                .catch(function(err) {
                    if (seq !== confirmSeq) {
                        return;
                    }
                    // 403 — истёк CSRF-токен или сессия; 5xx — сбой сервера.
                    // Ответ в этом случае не JSON, поэтому разбираем по статусу.
                    if (err && err.message === 'http') {
                        showError('Сессия истекла или сервер отклонил запрос. Обновите страницу.');
                        return;
                    }
                    showError('Не удалось проверить пароль. Проверьте соединение и повторите.');
                })
                .then(function() {
                    if (seq !== confirmSeq) {
                        return;
                    }
                    isChecking = false;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-60');
                });
        });
    }

    // ===== 3. МОБИЛЬНЫЙ БУРГЕР-МЕНЮ =====
    function initBurgerMenu() {
        var burgerBtn = document.getElementById('js-burger-trigger');
        var burgerIcon = document.getElementById('js-burger-icon');
        var sidebar = document.getElementById('js-admin-sidebar');
        var backdrop = document.getElementById('js-sidebar-backdrop');

        if (!burgerBtn || !sidebar || !backdrop || !burgerIcon) {
            return;
        }

        function toggleSidebar() {
            var isOpen = sidebar.classList.toggle('active');
            backdrop.classList.toggle('active', isOpen);

            burgerIcon.className = isOpen ? 'icon-x text-xl' : 'icon-menu text-xl';
        }

        burgerBtn.addEventListener('click', toggleSidebar);
        backdrop.addEventListener('click', toggleSidebar);
    }

    // ===== 4. АККОРДЕОН В САЙДБАРЕ =====
    function initSidebarAccordion() {
        var menuGroups = document.querySelectorAll('.sidebar-menu-group');

        menuGroups.forEach(function(group) {
            var trigger = group.querySelector('.js-group-trigger');

            if (!trigger) {
                return;
            }

            // Если группа активна — открываем
            if (group.classList.contains('is-active-group')) {
                group.classList.add('open');
            }

            trigger.addEventListener('click', function() {
                group.classList.toggle('open');
            });
        });
    }

    // ===== 5. СВОРАЧИВАНИЕ САЙДБАРА (ДЕСКТОП) =====
    function initSidebarCollapse() {
        var root = document.documentElement;
        var toggleBtn = document.getElementById('js-sidebar-collapse');
        var icon = document.getElementById('js-sidebar-collapse-icon');
        var sidebar = document.getElementById('js-admin-sidebar');
        var storageKey = 'admin-sidebar-collapsed';

        if (!toggleBtn || !icon || !sidebar) {
            return;
        }

        var isCollapsed = root.classList.contains('admin-sidebar-collapsed');
        var desktopQuery = window.matchMedia('(min-width: 768px)');

        // В узкой колонке нет подписей — переносим их в title как подсказки.
        // На мобилке подписи видны, там title не нужен.
        function syncLinkTitles(collapsed) {
            var links = sidebar.querySelectorAll('.sidebar-menu-link');
            var useTitles = collapsed && desktopQuery.matches;

            links.forEach(function (link) {
                if (!useTitles) {
                    link.removeAttribute('title');
                    return;
                }

                var label = link.querySelector('span:not([class^="icon-"])');
                var text = label ? label.textContent.trim() : '';

                if (text) {
                    link.setAttribute('title', text);
                }
            });
        }

        function applyState(collapsed) {
            root.classList.toggle('admin-sidebar-collapsed', collapsed);
            icon.className = collapsed ? 'icon-panel-left-open' : 'icon-panel-left-close';
            toggleBtn.title = collapsed ? 'Развернуть меню' : 'Свернуть меню';
            toggleBtn.setAttribute('aria-label', toggleBtn.title);
            toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            syncLinkTitles(collapsed);
        }

        applyState(isCollapsed);

        toggleBtn.addEventListener('click', function () {
            isCollapsed = !root.classList.contains('admin-sidebar-collapsed');
            applyState(isCollapsed);

            try {
                localStorage.setItem(storageKey, isCollapsed ? '1' : '0');
            } catch (e) {}
        });

        // При переходе через 768px подсказки пора убрать или вернуть.
        function handleBreakpointChange() {
            syncLinkTitles(root.classList.contains('admin-sidebar-collapsed'));
        }
        if (desktopQuery.addEventListener) {
            desktopQuery.addEventListener('change', handleBreakpointChange);
        } else if (desktopQuery.addListener) {
            desktopQuery.addListener(handleBreakpointChange);
        }
    }

    // ===== 6. ТЁМНАЯ ТЕМА =====
    function initThemeToggle() {
        var storageKey = 'admin-theme';
        var toggleBtn = document.getElementById('js-theme-toggle');
        var icon = document.getElementById('js-theme-icon');

        function getCurrentTheme() {
            return localStorage.getItem(storageKey) || 'light';
        }

        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem(storageKey, theme);

            if (icon) {
                icon.className = theme === 'dark' ? 'icon-sun text-lg' : 'icon-moon text-lg';
            }
        }

        function toggleTheme() {
            var current = getCurrentTheme();
            setTheme(current === 'light' ? 'dark' : 'light');
        }

        // Инициализация
        setTheme(getCurrentTheme());

        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleTheme);
        }
    }

    // ===== 7. ТАЙМЕР СЕССИИ =====
    function initSessionTimer() {
        var notice = document.getElementById('js-session-notice');
        if (!notice) {
            return;
        }

        var countdownEl = notice.querySelector('[data-session-countdown]');
        var extendBtn = notice.querySelector('.js-session-extend');

        var lifetime = parseInt(notice.getAttribute('data-lifetime'), 10) || 0;
        var warnAt = parseInt(notice.getAttribute('data-warn'), 10) || 0;
        var pingUrl = notice.getAttribute('data-ping-url') || 'index.php?ajax=session_ping';
        var loginUrl = notice.getAttribute('data-login-url') || 'index.php?msg=timeout';

        if (lifetime <= 0) {
            return;
        }

        var deadline = Date.now() + lifetime * 1000;
        var lastPingAt = Date.now();
        var throttleMs = Math.max(15, Math.min(60, Math.floor(lifetime / 4))) * 1000;
        var lastActivityAt = 0;
        var pingInFlight = false;
        var zeroRequested = false;
        var zeroRetryAt = 0;
        var redirecting = false;

        function formatTime(seconds) {
            if (seconds < 0) {
                seconds = 0;
            }
            var minutes = Math.floor(seconds / 60);
            var rest = seconds % 60;
            return (minutes < 10 ? '0' : '') + minutes + ':' + (rest < 10 ? '0' : '') + rest;
        }

        function remainingSeconds() {
            return Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        }

        function showNotice() {
            notice.classList.remove('hidden');
        }

        function hideNotice() {
            notice.classList.add('hidden');
        }

        function isNoticeVisible() {
            return !notice.classList.contains('hidden');
        }

        function redirectToLogin() {
            if (redirecting) {
                return;
            }
            redirecting = true;
            window.location.href = loginUrl;
        }

        function updateCountdown() {
            if (redirecting) {
                return;
            }

            var left = remainingSeconds();

            if (countdownEl) {
                countdownEl.textContent = formatTime(left);
            }

            if (left <= warnAt) {
                showNotice();
            }

            if (left <= 0 && !zeroRequested && Date.now() >= zeroRetryAt) {
                zeroRequested = true;
                ping(true, 0);
            }
        }

        function resetTimer(seconds, newLifetime, newWarn) {
            if (typeof newLifetime === 'number' && newLifetime > 0) {
                lifetime = newLifetime;
                throttleMs = Math.max(15, Math.min(60, Math.floor(lifetime / 4))) * 1000;
            }

            if (typeof newWarn === 'number' && newWarn > 0) {
                warnAt = newWarn;
            }

            var base = (typeof seconds === 'number' && seconds > 0) ? seconds : lifetime;
            deadline = Date.now() + base * 1000;
            lastPingAt = Date.now();
            zeroRequested = false;
            zeroRetryAt = 0;
            hideNotice();
            updateCountdown();
        }

        function ping(force, minIntervalMs) {
            if (pingInFlight || redirecting) {
                return;
            }

            var now = Date.now();
            var minInterval = force ? (minIntervalMs || 0) : throttleMs;
            if ((now - lastPingAt) < minInterval) {
                return;
            }

            pingInFlight = true;
            lastPingAt = now;

            var requestUrl = pingUrl + (pingUrl.indexOf('?') === -1 ? '?' : '&') + '_ts=' + now;

            fetch(requestUrl, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function(response) {
                    if (response.redirected || response.status === 401) {
                        redirectToLogin();
                        return null;
                    }
                    if (!response.ok) {
                        throw new Error('session ping failed');
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (!data) {
                        return;
                    }
                    if (!data.ok) {
                        redirectToLogin();
                        return;
                    }
                    resetTimer(data.remaining, data.lifetime, data.warn);
                })
                .catch(function() {
                    // Сеть или сервер недоступны: не выкидываем сразу, повторим через 5 секунд.
                    zeroRequested = false;
                    zeroRetryAt = Date.now() + 5000;
                    updateCountdown();
                })
                .then(function() {
                    pingInFlight = false;
                });
        }

        function onActivity() {
            if (redirecting) {
                return;
            }

            var now = Date.now();
            if (now - lastActivityAt < 1000) {
                return;
            }
            lastActivityAt = now;

            // Пока предупреждение видно, продлеваем сразу (но не чаще раза в 3 секунды).
            ping(isNoticeVisible(), 3000);
        }

        ['pointerdown', 'keydown', 'wheel', 'touchstart', 'mousemove', 'scroll'].forEach(function(eventName) {
            document.addEventListener(eventName, onActivity, { passive: true });
        });

        // Вернулись в скрытую вкладку — синхронизируемся с сервером: таймаут мог
        // измениться в другой вкладке, а сессия — истечь без нас.
        document.addEventListener('visibilitychange', function() {
            if (document.hidden || redirecting) {
                return;
            }
            updateCountdown();
            ping(true, 0);
        });

        // Страница восстановлена из bfcache (кнопка «Назад») — отсчёт мог устареть.
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                updateCountdown();
                ping(true, 0);
            }
        });

        if (extendBtn) {
            extendBtn.addEventListener('click', function() {
                ping(true, 0);
            });
        }

        // Любой успешный same-origin AJAX к index.php тоже обновил last_activity.
        var nativeFetch = window.fetch;
        if (typeof nativeFetch === 'function') {
            window.fetch = function() {
                var args = arguments;
                return nativeFetch.apply(window, args).then(function(response) {
                    try {
                        if (response && response.ok && !response.redirected) {
                            var responseUrl = response.url || '';
                            if (responseUrl && responseUrl.indexOf('index.php') !== -1) {
                                resetTimer(lifetime);
                            }
                        }
                    } catch (e) {
                        // Таймер продолжит работать по расписанию.
                    }
                    return response;
                });
            };
        }

        updateCountdown();
        window.setInterval(updateCountdown, 1000);
    }

    // ===== ИНИЦИАЛИЗАЦИЯ =====
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initLoginToggle();
            initSecurityCheck();
            initSecurityConfirm();
            initBurgerMenu();
            initSidebarAccordion();
            initSidebarCollapse();
            initThemeToggle();
            initSessionTimer();
        });
    } else {
        initLoginToggle();
        initSecurityCheck();
        initSecurityConfirm();
        initBurgerMenu();
        initSidebarAccordion();
        initSidebarCollapse();
        initThemeToggle();
        initSessionTimer();
    }

})();