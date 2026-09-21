/**
 * Бегущая лента — скрипты для админки
 */
(function() {
    'use strict';

    function initModule() {
        const container = document.getElementById('brands-container');
        if (!container) return;

        // Добавление логотипа
        const addBtn = document.getElementById('add-brand-btn');
        if (addBtn) {
            const newBtn = addBtn.cloneNode(true);
            addBtn.parentNode.replaceChild(newBtn, addBtn);
            newBtn.addEventListener('click', function() {
                const template = document.getElementById('tmpl-brand-item');
                if (!template) return;
                
                const index = container.querySelectorAll('.brand-item-card').length + 1;
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

        // Удаление логотипа
        container.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-remove-brand');
            if (!btn) return;
            const item = btn.closest('.brand-item-card');
            if (item) {
                const items = container.querySelectorAll('.brand-item-card');
                if (items.length > 1) {
                    item.remove();
                } else {
                    alert('Должен остаться хотя бы один логотип');
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