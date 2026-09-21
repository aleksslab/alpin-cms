/**
 * Временная шкала — скрипты для админки
 */
(function() {
    'use strict';

    function initModule() {
        const container = document.getElementById('timeline-container');
        if (!container) return;

        // Добавление этапа
        const addBtn = document.getElementById('add-timeline-btn');
        if (addBtn) {
            const newBtn = addBtn.cloneNode(true);
            addBtn.parentNode.replaceChild(newBtn, addBtn);
            newBtn.addEventListener('click', function() {
                const template = document.getElementById('tmpl-timeline-item');
                if (!template) return;
                
                const index = container.querySelectorAll('.timeline-item-card').length + 1;
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

        // Удаление этапа
        container.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-remove-timeline');
            if (!btn) return;
            const item = btn.closest('.timeline-item-card');
            if (item) {
                const items = container.querySelectorAll('.timeline-item-card');
                if (items.length > 1) {
                    item.remove();
                } else {
                    alert('Должен остаться хотя бы один этап');
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