/**
 * Счетчики (Статистика) — скрипты для админки
 */
(function() {
    'use strict';

    function initModule() {
        const container = document.getElementById('stats-container');
        if (!container) return;

        // Добавление счетчика
        const addBtn = document.getElementById('add-stat-btn');
        if (addBtn) {
            const newBtn = addBtn.cloneNode(true);
            addBtn.parentNode.replaceChild(newBtn, addBtn);
            newBtn.addEventListener('click', function() {
                const template = document.getElementById('tmpl-stat-item');
                if (!template) return;
                
                const index = container.querySelectorAll('.stat-item-card').length + 1;
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

        // Удаление счетчика
        container.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-remove-stat');
            if (!btn) return;
            const item = btn.closest('.stat-item-card');
            if (item) {
                const items = container.querySelectorAll('.stat-item-card');
                if (items.length > 1) {
                    item.remove();
                } else {
                    alert('Должен остаться хотя бы один счетчик');
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