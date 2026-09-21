/**
 * Модуль: Меню сайта — скрипты для вертикального меню (аккордеон)
 */
(function() {
    'use strict';

    /**
     * Переключает видимость контейнера и вращает иконку
     */
    function toggleDropdown(id, btn) {
        var container = document.getElementById(id);
        if (!container) return;

        var icon = btn ? btn.querySelector('.icon-chevron-right') : null;
        var isHidden = container.classList.contains('hidden');

        if (isHidden) {
            container.classList.remove('hidden');
            if (icon) {
                icon.classList.add('rotate-90');
            }
        } else {
            container.classList.add('hidden');
            if (icon) {
                icon.classList.remove('rotate-90');
            }
        }
    }

    /**
     * Инициализация: вешаем обработчики на все кнопки с data-toggle
     */
    function init() {
        document.querySelectorAll('[data-toggle-dropdown]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-toggle-dropdown');
                toggleDropdown(id, this);
            });
        });
    }

    // Запускаем инициализацию после загрузки DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Для обратной совместимости с onclick (если используется)
    window.toggleMobileDropdown = toggleDropdown;

})();