/**
 * FAQ — скрипты для админки (модуль)
 */
(function() {
    'use strict';

    function renumberItems() {
        const container = document.getElementById('faq-items-container');
        if (!container) return;

        const items = container.querySelectorAll('.faq-item-card');
        items.forEach(function(item, index) {
            const number = index + 1;
            const label = item.querySelector('.text-xs.font-bold.text-\\[var\\(--primary-color\\)\\]');
            if (label) {
                label.textContent = 'Вопрос #' + number;
            }

            const inputs = item.querySelectorAll('input, textarea');
            inputs.forEach(function(input) {
                if (input.name) {
                    input.name = input.name.replace(/items\[\d+\]/, 'items[' + number + ']');
                }
            });
        });
    }

    function toggleHtmlHints(show) {
        document.querySelectorAll('.js-html-hint').forEach(function(el) {
            if (show) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        });
    }

    function initFaqModule() {
        const container = document.getElementById('faq-items-container');
        if (!container) return;

        // Чекбокс HTML — ищем именно чекбокс, а не скрытый инпут
        const htmlCheckbox = document.querySelector('input[type="checkbox"][name="allow_html"]');
        if (htmlCheckbox) {
            htmlCheckbox.addEventListener('change', function() {
                toggleHtmlHints(this.checked);
            });
            toggleHtmlHints(htmlCheckbox.checked);
        }

        // Кнопка добавления
        const addBtn = document.getElementById('add-faq-btn');
        if (addBtn) {
            // Заменяем кнопку, чтобы избежать дублирования обработчиков
            const newBtn = addBtn.cloneNode(true);
            addBtn.parentNode.replaceChild(newBtn, addBtn);

            newBtn.addEventListener('click', function() {
                const template = document.getElementById('tmpl-faq-item');
                if (!template) return;

                const index = container.querySelectorAll('.faq-item-card').length + 1;
                let html = template.innerHTML;
                html = html.replace(/__INDEX__/g, index);

                const temp = document.createElement('div');
                temp.innerHTML = html;
                const newItem = temp.firstElementChild;

                if (newItem) {
                    container.appendChild(newItem);

                    // Обновляем подсказку в новом элементе
                    const hint = newItem.querySelector('.js-html-hint');
                    if (hint && htmlCheckbox) {
                        if (htmlCheckbox.checked) {
                            hint.classList.remove('hidden');
                        } else {
                            hint.classList.add('hidden');
                        }
                    }

                    renumberItems();
                }
            });
        }

        // Удаление (делегирование)
        container.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-remove-faq');
            if (!btn) return;

            const card = btn.closest('.faq-item-card');
            if (!card) return;

            const items = container.querySelectorAll('.faq-item-card');
            if (items.length > 1) {
                card.remove();
                renumberItems();
            } else {
                alert('Должен остаться хотя бы один вопрос');
            }
        });

        // Первоначальная нумерация
        renumberItems();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFaqModule);
    } else {
        initFaqModule();
    }
})();