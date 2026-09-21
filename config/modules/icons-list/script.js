/**
 * Список с иконками — скрипты для админки (модуль)
 */
(function() {
    'use strict';

    function renumberItems() {
        const container = document.getElementById('icons-rows-container');
        if (!container) return;
        
        const items = container.querySelectorAll('.bg-slate-50\\/50.p-4.rounded-xl.border.border-slate-200.space-y-3');
        items.forEach(function(item, index) {
            const number = index + 1;
            const label = item.querySelector('.text-xs.font-bold.text-\\[var\\(--primary-color\\)\\].uppercase.tracking-wider');
            if (label) {
                label.textContent = 'Иконка #' + number;
            }
            
            const inputs = item.querySelectorAll('input, textarea');
            inputs.forEach(function(input) {
                if (input.name) {
                    input.name = input.name.replace(/items\[\d+\]/, 'items[' + number + ']');
                }
            });
        });
    }

    function initIconsModule() {
        const container = document.getElementById('icons-rows-container');
        if (!container) return;

        renumberItems();

        const addBtn = document.getElementById('add-icon-btn');
        if (addBtn) {
            const newBtn = addBtn.cloneNode(true);
            addBtn.parentNode.replaceChild(newBtn, addBtn);

            newBtn.addEventListener('click', function() {
                const template = document.getElementById('tmpl-icon-item');
                if (!template) return;

                const index = container.querySelectorAll('.bg-slate-50\\/50.p-4.rounded-xl.border.border-slate-200.space-y-3').length + 1;
                let html = template.innerHTML;
                html = html.replace(/__INDEX__/g, index);
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const newItem = temp.firstElementChild;
                if (newItem) {
                    container.appendChild(newItem);
                    renumberItems();
                }
            });
        }

        container.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-remove-icon');
            if (!btn) return;
            const card = btn.closest('.bg-slate-50\\/50.p-4.rounded-xl.border.border-slate-200.space-y-3');
            if (card) {
                const items = container.querySelectorAll('.bg-slate-50\\/50.p-4.rounded-xl.border.border-slate-200.space-y-3');
                if (items.length > 1) {
                    card.remove();
                    renumberItems();
                } else {
                    alert('Должна остаться хотя бы одна иконка');
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initIconsModule);
    } else {
        initIconsModule();
    }
})();