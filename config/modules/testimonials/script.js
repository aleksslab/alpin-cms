/**
 * Отзывы клиентов — скрипты для админки
 */
(function() {
    'use strict';

    function initModule() {
        const container = document.getElementById('testimonials-container');
        if (!container) return;

        // Добавление отзыва
        const addBtn = document.getElementById('add-testimonial-btn');
        if (addBtn) {
            const newBtn = addBtn.cloneNode(true);
            addBtn.parentNode.replaceChild(newBtn, addBtn);
            newBtn.addEventListener('click', function() {
                const template = document.getElementById('tmpl-testimonial-item');
                if (!template) return;
                
                const index = container.querySelectorAll('.testimonial-item-card').length + 1;
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

        // Удаление отзыва
        container.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-remove-testimonial');
            if (!btn) return;
            const item = btn.closest('.testimonial-item-card');
            if (item) {
                const items = container.querySelectorAll('.testimonial-item-card');
                if (items.length > 1) {
                    item.remove();
                } else {
                    alert('Должен остаться хотя бы один отзыв');
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