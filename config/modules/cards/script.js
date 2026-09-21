/**
 * Карточки — скрипты для админки (модуль)
 */
(function() {
    function initCardsModule() {
        const container = document.getElementById('cards-rows-container');
        if (!container) return;

        const addBtn = document.getElementById('add-card-btn');
        if (addBtn) {
            const newBtn = addBtn.cloneNode(true);
            addBtn.parentNode.replaceChild(newBtn, addBtn);

            newBtn.addEventListener('click', function() {
                const template = document.getElementById('tmpl-card-item');
                if (!template) return;

                const index = container.querySelectorAll('.card-item-card').length + 1;
                let html = template.innerHTML;
                html = html.replace(/__INDEX__/g, index);
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const newItem = temp.firstElementChild;
                if (newItem) {
                    container.appendChild(newItem);
                    newItem.querySelectorAll('.js-image-url-input').forEach(function(input) {
                        if (typeof updatePreviewOnManualInput === 'function') {
                            updatePreviewOnManualInput(input);
                        }
                    });
                }
            });
        }

        container.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-remove-card');
            if (!btn) return;
            const card = btn.closest('.card-item-card');
            if (card) {
                const items = container.querySelectorAll('.card-item-card');
                if (items.length > 1) {
                    card.remove();
                } else {
                    alert('Должна остаться хотя бы одна карточка');
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCardsModule);
    } else {
        initCardsModule();
    }
})();