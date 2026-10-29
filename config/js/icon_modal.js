/**
 * Иконки - модалка выбора (глобальный синглтон)
 * Все скрипты обернуты в IIFE с проверкой readyState
 */

(function() {
    'use strict';

    // Проверяем, инициализирован ли уже скрипт
    if (typeof window.iconModalInitialized !== 'undefined') {
        return;
    }
    window.iconModalInitialized = true;

    // Глобальные переменные
    let activeIconInputTarget = null;

    // ===== ОБНОВЛЕНИЕ ПРЕВЬЮ ИКОНКИ =====
    function updateIconPreview(input) {
        if (!input) return;
        
        var group = input.closest('.js-icon-input-group');
        if (!group) return;
        
        var preview = group.querySelector('.js-icon-preview-icon');
        if (!preview) return;
        
        var iconClass = input.value.trim();
        if (iconClass && !iconClass.startsWith('icon-')) {
            iconClass = 'icon-' + iconClass;
        }
        
        preview.className = 'js-icon-preview-icon';
        if (iconClass) {
            preview.classList.add(iconClass);
        }
    }

    // 1. Открытие модального окна выбора иконок
    window.openIconModal = function(button) {
        var fieldCell = button.closest('.editor-field') || button.closest('.editor-row') || button.closest('div');
        if (fieldCell) {
            activeIconInputTarget = fieldCell.querySelector('.js-icon-url-input');
        }
        
        var modal = document.getElementById('icon-picker-modal');
        if (modal) {
            modal.classList.add('active');
            
            var currentIconValue = activeIconInputTarget ? activeIconInputTarget.value.trim() : '';
            if (currentIconValue.startsWith('icon-')) {
                currentIconValue = currentIconValue.replace('icon-', '');
            }

            setTimeout(function() {
                var searchInput = document.getElementById('js-icon-search-input');
                if (searchInput) { 
                    searchInput.value = currentIconValue; 
                    searchInput.focus(); 
                    filterCmsIconsGallery(currentIconValue); 
                }
            }, 100);
        }
    };

    // 2. Закрытие окна
    window.closeIconModal = function() {
        var modal = document.getElementById('icon-picker-modal');
        if (modal) { modal.classList.remove('active'); }
        activeIconInputTarget = null;
    };

    // 3. Передача выбранного класса иконки в инпут
    window.selectIconForTarget = function(iconClass) {
        if (activeIconInputTarget) {
            activeIconInputTarget.value = iconClass;
            // Обновляем превью
            updateIconPreview(activeIconInputTarget);
            activeIconInputTarget.dispatchEvent(new Event('input', { bubbles: true }));
            closeIconModal();
        }
    };

    // 4. ЖИВОЙ КЛИЕНТСКИЙ ПОИСК ПО ИМЕНИ
    window.filterCmsIconsGallery = function(query) {
        var cleanQuery = query.trim().toLowerCase();
        var items = document.querySelectorAll('.js-icon-picker-item');
        var emptyMsg = document.getElementById('js-empty-icons-msg');
        var foundCount = 0;

        items.forEach(function(item) {
            var nameAttr = item.getAttribute('data-icon-name') || '';
            if (nameAttr.includes(cleanQuery)) {
                item.style.setProperty('display', 'flex', 'important');
                foundCount++;
            } else {
                item.style.setProperty('display', 'none', 'important');
            }
        });

        if (emptyMsg) {
            if (foundCount === 0) {
                emptyMsg.classList.remove('hidden');
            } else {
                emptyMsg.classList.add('hidden');
            }
        }
    };

    // ===== ГЛОБАЛЬНЫЙ ОБРАБОТЧИК ДЛЯ ОБНОВЛЕНИЯ ПРЕВЬЮ =====
    document.addEventListener('input', function(e) {
        var input = e.target.closest('.js-icon-url-input');
        if (input) {
            updateIconPreview(input);
        }
    });

    // Закрытие по клику вне модалки
    document.addEventListener('click', function(e) {
        var modal = document.getElementById('icon-picker-modal');
        if (modal && modal.classList.contains('active')) {
            var modalContent = modal.querySelector('.modal-content');
            if (modalContent && !modalContent.contains(e.target) && !e.target.closest('.js-icon-url-input')) {
                closeIconModal();
            }
        }
    });

    // Закрытие по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeIconModal();
        }
    });

})();