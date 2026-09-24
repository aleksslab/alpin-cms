/**
 * Hero-Landing — скрипты для админки (модуль).
 * Управление списком микро-фактов (features[]).
 */
(function() {
    'use strict';

    function init() {
        const container = document.getElementById('hero-landing-features-container');
        if (!container) return;

        const addBtn = document.getElementById('add-hero-landing-feature-btn');
        if (addBtn) {
            const newBtn = addBtn.cloneNode(true);
            addBtn.parentNode.replaceChild(newBtn, addBtn);

            newBtn.addEventListener('click', function() {
                const template = document.getElementById('tmpl-hero-landing-feature');
                if (!template) return;

                // Через getNextIndex (max+1) — как в других модулях
                let index;
                if (typeof window.getNextIndex === 'function') {
                    index = window.getNextIndex(container, 'features');
                } else {
                    index = container.querySelectorAll('.feature-row').length;
                }

                let html = template.innerHTML.replace(/__INDEX__/g, index);
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const newRow = temp.firstElementChild;
                if (newRow) {
                    container.appendChild(newRow);
                }
            });
        }

        container.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-remove-feature');
            if (!btn) return;
            const row = btn.closest('.feature-row');
            if (row) {
                row.remove();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();