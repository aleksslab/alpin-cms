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
            var isLoginChanged = (currentLogin !== originalLogin);

            if (isLoginChanged) {
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

    // ===== 5. ТЁМНАЯ ТЕМА =====
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

    // ===== 6. ТАЙМЕР СЕССИИ =====
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
            initBurgerMenu();
            initSidebarAccordion();
            initThemeToggle();
            initSessionTimer();
        });
    } else {
        initLoginToggle();
        initSecurityCheck();
        initBurgerMenu();
        initSidebarAccordion();
        initThemeToggle();
        initSessionTimer();
    }

})();