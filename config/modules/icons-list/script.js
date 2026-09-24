/**
 * Список с иконками — скрипты для админки (модуль).
 */
(function() {
    'use strict';

    function init() {
        if (typeof window.initListModule !== 'function') return;

        window.initListModule({
            container:     'icons-rows-container',
            prefix:        'items',
            template:      'tmpl-icon-item',
            cardSelector:  '.js-icon-card',
            labelSelector: '.js-icon-num',
            labelText:     'Иконка #',
            addBtn:        'add-icon-btn',
            removeBtn:     '.js-remove-icon',
            minCount:      1,
            minMessage:    'Должна остаться хотя бы одна иконка'
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();