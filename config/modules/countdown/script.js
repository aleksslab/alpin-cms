/**
 * Таймер обратного отсчета — скрипты для админки
 */
(function() {
    'use strict';

    function initModule() {
        // Для админки пока ничего не нужно, все настройки статичные
        // Можно добавить превью таймера, но пока обойдемся
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModule);
    } else {
        initModule();
    }
})();