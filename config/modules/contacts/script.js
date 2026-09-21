/**
 * Контакты — скрипты для админки
 */
(function() {
    'use strict';

    function updateHiddenInputs(container, name) {
        const items = container.querySelectorAll('.block-item');
        items.forEach(function(item, index) {
            const hidden = item.querySelector('input[type="hidden"]');
            if (hidden) {
                hidden.name = name;
                hidden.value = item.dataset.block;
            }
        });
    }

    function initBlockManager(containerId, selectId, addBtnId, hiddenName) {
        const container = document.getElementById(containerId);
        const select = document.getElementById(selectId);
        const addBtn = document.getElementById(addBtnId);
        
        if (!container || !select || !addBtn) return;
        
        container.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-move-up');
            if (btn) {
                const item = btn.closest('.block-item');
                const prev = item.previousElementSibling;
                if (prev) {
                    container.insertBefore(item, prev);
                    updateHiddenInputs(container, hiddenName);
                }
                return;
            }
            
            const downBtn = e.target.closest('.js-move-down');
            if (downBtn) {
                const item = downBtn.closest('.block-item');
                const next = item.nextElementSibling;
                if (next) {
                    container.insertBefore(item, next.nextSibling);
                    updateHiddenInputs(container, hiddenName);
                }
                return;
            }
            
            const removeBtn = e.target.closest('.js-remove-block');
            if (removeBtn) {
                const item = removeBtn.closest('.block-item');
                const blockType = item.dataset.block;
                if (item) {
                    item.remove();
                    const parentContainer = item.closest('.bg-slate-50\\/50');
                    const parentSelect = parentContainer.querySelector('select');
                    if (parentSelect) {
                        const option = document.createElement('option');
                        option.value = blockType;
                        option.textContent = blockType.charAt(0).toUpperCase() + blockType.slice(1);
                        parentSelect.appendChild(option);
                    }
                    setTimeout(function() {
                        updateHiddenInputs(container, hiddenName);
                    }, 0);
                }
                return;
            }
        });
        
        addBtn.addEventListener('click', function() {
            const value = select.value;
            if (!value) return;
            
            const label = select.options[select.selectedIndex].text;
            const item = document.createElement('div');
            item.className = 'flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200 block-item';
            item.dataset.block = value;
            item.innerHTML = `
                <button type="button" class="js-move-up w-6 h-6 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Вверх">
                    <span class="icon-chevron-up text-xs"></span>
                </button>
                <button type="button" class="js-move-down w-6 h-6 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Вниз">
                    <span class="icon-chevron-down text-xs"></span>
                </button>
                <button type="button" class="js-remove-block w-6 h-6 rounded flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Удалить">
                    <span class="icon-x text-xs"></span>
                </button>
                <span class="text-sm font-medium text-slate-700">${label}</span>
                <input type="hidden" name="${hiddenName}" value="${value}">
            `;
            container.appendChild(item);
            select.remove(select.selectedIndex);
            updateHiddenInputs(container, hiddenName);
        });
    }

    function initFormFieldsDependencies() {
        const showCheckboxes = document.querySelectorAll('input[type="checkbox"][name^="show_"]');
        
        showCheckboxes.forEach(function(showCheckbox) {
            const fieldName = showCheckbox.name.replace('show_', '');
            const requiredCheckbox = document.querySelector('input[type="checkbox"][name="required_' + fieldName + '"]');
            
            if (!requiredCheckbox) return;

            function updateRequiredState() {
                const isChecked = showCheckbox.checked;
                if (isChecked) {
                    requiredCheckbox.disabled = false;
                    requiredCheckbox.closest('label').classList.remove('opacity-30', 'pointer-events-none');
                } else {
                    requiredCheckbox.disabled = true;
                    requiredCheckbox.closest('label').classList.add('opacity-30', 'pointer-events-none');
                }
            }

            showCheckbox.addEventListener('change', updateRequiredState);
            updateRequiredState();
        });
    }

    function initModule() {
        initBlockManager('left-blocks-container', 'left-block-select', 'add-left-block', 'left_blocks[]');
        initBlockManager('right-blocks-container', 'right-block-select', 'add-right-block', 'right_blocks[]');
        initFormFieldsDependencies();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModule);
    } else {
        initModule();
    }
})();