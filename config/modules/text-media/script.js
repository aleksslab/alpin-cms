/**
 * Текст + Медиа — скрипты для админки (модуль).
 *
 * Два независимых списка в одном модуле:
 *   - features (особенности)
 *   - stats    (статистика)
 *
 * Контейнеры получили префикс "tm-", чтобы не конфликтовать
 * с одноимённым контейнером модуля stats (#stats-container).
 */
(function() {
    'use strict';

    function init() {
        if (typeof window.initListModule !== 'function') return;

        // 1) Особенности
        window.initListModule({
            container:     'tm-features-container',
            prefix:        'features',
            template:      'tmpl-feature',
            cardSelector:  '.feature-item',
            labelSelector: '.js-feature-num',
            labelText:     'Особенность #',
            addBtn:        'add-feature-btn',
            removeBtn:     '.js-remove-feature',
            minCount:      1,
            minMessage:    'Должна остаться хотя бы одна особенность'
        });

        // 2) Статистика
        window.initListModule({
            container:     'tm-stats-container',
            prefix:        'stats',
            template:      'tmpl-stat',
            cardSelector:  '.stat-item',
            labelSelector: '.js-stat-num',
            labelText:     'Статистика #',
            addBtn:        'add-stat-btn',
            removeBtn:     '.js-remove-stat',
            minCount:      1,
            minMessage:    'Должна остаться хотя бы одна статистика'
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();