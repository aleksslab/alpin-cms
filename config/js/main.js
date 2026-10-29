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

    // ===== ИНИЦИАЛИЗАЦИЯ =====
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initLoginToggle();
            initSecurityCheck();
            initBurgerMenu();
            initSidebarAccordion();
            initThemeToggle();
        });
    } else {
        initLoginToggle();
        initSecurityCheck();
        initBurgerMenu();
        initSidebarAccordion();
        initThemeToggle();
    }

})();