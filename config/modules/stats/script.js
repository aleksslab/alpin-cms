/**
 * Счётчики (Статистика) — скрипты для админки (модуль).
 */
(function() {
    'use strict';

    function init() {
        if (typeof window.initListModule !== 'function') return;

        window.initListModule({
            container:     'stats-container',
            prefix:        'stats',
            template:      'tmpl-stat-item',
            cardSelector:  '.stat-item-card',
            labelSelector: '.js-stat-num',
            labelText:     'Счетчик #',
            addBtn:        'add-stat-btn',
            removeBtn:     '.js-remove-stat',
            minCount:      1,
            minMessage:    'Должен остаться хотя бы один счётчик'
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();