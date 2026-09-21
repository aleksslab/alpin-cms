/**
 * Яндекс.Карты — скрипты для админки
 */
(function() {
    'use strict';

    function initModule() {
        const container = document.getElementById('map-items-container');
        if (!container) return;

        // Добавление метки
        const addBtn = document.getElementById('add-map-item-btn');
        if (addBtn) {
            const newBtn = addBtn.cloneNode(true);
            addBtn.parentNode.replaceChild(newBtn, addBtn);
            newBtn.addEventListener('click', function() {
                const template = document.getElementById('tmpl-map-item');
                if (!template) return;
                
                const index = container.querySelectorAll('.map-item-card').length + 1;
                let html = template.innerHTML;
                html = html.replace(/__INDEX__/g, index);
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const newItem = temp.firstElementChild;
                if (newItem) {
                    container.appendChild(newItem);
                }
            });
        }

        // Удаление метки
        container.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-remove-map-item');
            if (!btn) return;
            const item = btn.closest('.map-item-card');
            if (item) {
                const items = container.querySelectorAll('.map-item-card');
                if (items.length > 1) {
                    item.remove();
                } else {
                    alert('Должна остаться хотя бы одна метка');
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModule);
    } else {
        initModule();
    }
})();