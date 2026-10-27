/**
 * Переключатель темы на фронте
 *
 * Логика:
 *   - Тема хранится в localStorage('site-theme') — 'light' или 'dark'.
 *   - Применяется через атрибут data-theme="dark" на <html>.
 *   - Если пользователь не выбирал — используется theme_default (сервер
 *     уже выставил data-theme в inline-скрипте до рендера).
 *   - Кнопка: #js-theme-toggle, иконка: #js-theme-icon.
 *
 */
(function() {
    'use strict';

    var STORAGE_KEY = 'site-theme';
    var toggleBtn = document.getElementById('js-theme-toggle');
    var icon = document.getElementById('js-theme-icon');

    if (!toggleBtn) {
        return; // кнопки нет — нечего делать
    }

    function getCurrentTheme() {
        // Что реально применено сейчас
        var attr = document.documentElement.getAttribute('data-theme');
        return attr === 'dark' ? 'dark' : 'light';
    }

    function setTheme(theme) {
        var isDark = (theme === 'dark');

        if (isDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }

        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {
            // localStorage недоступен (приватный режим, отключён) — молча
        }

        if (icon) {
            icon.className = isDark ? 'icon-sun text-lg' : 'icon-moon text-lg';
        }

        toggleBtn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        toggleBtn.setAttribute(
            'title',
            isDark ? 'Переключить на светлую тему' : 'Переключить на тёмную тему'
        );
    }

    function toggleTheme() {
        var current = getCurrentTheme();
        setTheme(current === 'dark' ? 'light' : 'dark');
    }

    function init() {
        // Синхронизируем иконку с текущим состоянием (inline-скрипт уже
        // мог поставить data-theme="dark" до рендера).
        var current = getCurrentTheme();
        if (icon) {
            icon.className = current === 'dark' ? 'icon-sun text-lg' : 'icon-moon text-lg';
        }
        toggleBtn.setAttribute('aria-pressed', current === 'dark' ? 'true' : 'false');
        toggleBtn.setAttribute(
            'title',
            current === 'dark' ? 'Переключить на светлую тему' : 'Переключить на тёмную тему'
        );

        toggleBtn.addEventListener('click', toggleTheme);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();