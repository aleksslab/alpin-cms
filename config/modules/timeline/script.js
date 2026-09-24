/**
 * Временная шкала — скрипты для админки (модуль).
 */
(function() {
    'use strict';

    function init() {
        if (typeof window.initListModule !== 'function') return;

        window.initListModule({
            container:     'timeline-container',
            prefix:        'items',
            template:      'tmpl-timeline-item',
            cardSelector:  '.timeline-item-card',
            labelSelector: '.js-timeline-num',
            labelText:     'Этап #',
            addBtn:        'add-timeline-btn',
            removeBtn:     '.js-remove-timeline',
            minCount:      1,
            minMessage:    'Должен остаться хотя бы один этап'
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();