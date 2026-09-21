/**
 * Квиз — скрипты для админки
 */
(function() {
    'use strict';

    function initModule() {
        // ===== ВОПРОСЫ =====
        const questionsContainer = document.getElementById('questions-container');
        if (questionsContainer) {
            const addBtn = document.getElementById('add-question-btn');
            if (addBtn) {
                const newBtn = addBtn.cloneNode(true);
                addBtn.parentNode.replaceChild(newBtn, addBtn);
                newBtn.addEventListener('click', function() {
                    const template = document.getElementById('tmpl-question');
                    if (!template) return;
                    
                    const index = questionsContainer.querySelectorAll('.question-item-card').length + 1;
                    let html = template.innerHTML;
                    html = html.replace(/__INDEX__/g, index);
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const newItem = temp.firstElementChild;
                    if (newItem) {
                        questionsContainer.appendChild(newItem);
                    }
                });
            }
            
            questionsContainer.addEventListener('click', function(e) {
                const btn = e.target.closest('.js-remove-question');
                if (!btn) return;
                const item = btn.closest('.question-item-card');
                if (item) {
                    const items = questionsContainer.querySelectorAll('.question-item-card');
                    if (items.length > 1) {
                        item.remove();
                    } else {
                        alert('Должен остаться хотя бы один вопрос');
                    }
                }
            });

            // Показываем/скрываем варианты в зависимости от типа вопроса
            questionsContainer.addEventListener('change', function(e) {
                const select = e.target.closest('.question-type-select');
                if (!select) return;
                const card = select.closest('.question-item-card');
                if (!card) return;
                const optionsContainer = card.querySelector('.question-options-container');
                if (!optionsContainer) return;
                
                const type = select.value;
                if (type === 'text') {
                    optionsContainer.style.display = 'none';
                } else {
                    optionsContainer.style.display = 'block';
                }
            });

            // Инициализация видимости вариантов
            questionsContainer.querySelectorAll('.question-item-card').forEach(function(card) {
                const select = card.querySelector('.question-type-select');
                const optionsContainer = card.querySelector('.question-options-container');
                if (select && optionsContainer) {
                    if (select.value === 'text') {
                        optionsContainer.style.display = 'none';
                    }
                }
            });
        }

        // ===== ПОЛЯ =====
        const fieldsContainer = document.getElementById('fields-container');
        if (fieldsContainer) {
            const addBtn = document.getElementById('add-field-btn');
            if (addBtn) {
                const newBtn = addBtn.cloneNode(true);
                addBtn.parentNode.replaceChild(newBtn, addBtn);
                newBtn.addEventListener('click', function() {
                    const template = document.getElementById('tmpl-field');
                    if (!template) return;
                    
                    const index = fieldsContainer.querySelectorAll('.field-item-card').length + 1;
                    let html = template.innerHTML;
                    html = html.replace(/__INDEX__/g, index);
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const newItem = temp.firstElementChild;
                    if (newItem) {
                        fieldsContainer.appendChild(newItem);
                    }
                });
            }
            
            fieldsContainer.addEventListener('click', function(e) {
                const btn = e.target.closest('.js-remove-field');
                if (!btn) return;
                const item = btn.closest('.field-item-card');
                if (item) {
                    const items = fieldsContainer.querySelectorAll('.field-item-card');
                    if (items.length > 1) {
                        item.remove();
                    } else {
                        alert('Должно остаться хотя бы одно поле');
                    }
                }
            });
        }

    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModule);
    } else {
        initModule();
    }
})();