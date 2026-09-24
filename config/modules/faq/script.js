/**
 * FAQ — скрипты для админки (модуль).
 */
(function() {
    'use strict';

    function toggleHtmlHints(show) {
        document.querySelectorAll('.js-html-hint').forEach(function(el) {
            el.classList.toggle('hidden', !show);
        });
    }

    function initHtmlHintsToggle() {
        const htmlCheckbox = document.querySelector('input[type="checkbox"][name="allow_html"]');
        if (!htmlCheckbox) return;

        htmlCheckbox.addEventListener('change', function() {
            toggleHtmlHints(this.checked);
        });
        toggleHtmlHints(htmlCheckbox.checked);
    }

    function init() {
        // 1) Основная механика списка
        if (typeof window.initListModule === 'function') {
            window.initListModule({
                container:     'faq-items-container',
                prefix:        'items',
                template:      'tmpl-faq-item',
                cardSelector:  '.faq-item-card',
                labelSelector: '.js-faq-num',
                labelText:     'Вопрос #',
                addBtn:        'add-faq-btn',
                removeBtn:     '.js-remove-faq',
                minCount:      1,
                minMessage:    'Должен остаться хотя бы один вопрос',
                onAfterAdd: function(card) {
                    // Синхронизируем видимость HTML-подсказки в новой карточке
                    const htmlCheckbox = document.querySelector('input[type="checkbox"][name="allow_html"]');
                    const hint = card.querySelector('.js-html-hint');
                    if (hint && htmlCheckbox) {
                        hint.classList.toggle('hidden', !htmlCheckbox.checked);
                    }
                }
            });
        }

        // 2) Дополнительная логика модуля
        initHtmlHintsToggle();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();