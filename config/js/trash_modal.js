(function() {
    'use strict';

    function getSelectedFiles() {
        var files = [];
        document.querySelectorAll('.trash-item-checkbox:checked').forEach(function(cb) {
            var item = cb.closest('.trash-item');
            if (item) files.push(item.dataset.file);
        });
        return files;
    }

    function updateState() {
        var files = getSelectedFiles();
        var count = files.length;
        var counter = document.getElementById('trash-selected-count');
        if (counter) counter.textContent = count;

        var restoreBtn = document.getElementById('trash-restore-btn');
        var deleteBtn = document.getElementById('trash-delete-btn');
        if (restoreBtn) restoreBtn.disabled = count === 0;
        if (deleteBtn) deleteBtn.disabled = count === 0;

        var selectAll = document.getElementById('trash-select-all');
        if (selectAll) {
            var all = document.querySelectorAll('.trash-item-checkbox');
            var checked = document.querySelectorAll('.trash-item-checkbox:checked');
            selectAll.checked = (all.length > 0 && all.length === checked.length);
            selectAll.indeterminate = (checked.length > 0 && checked.length < all.length);
        }
    }

    function submitAction(action, files) {
        var form = document.getElementById('trash-action-form');
        if (!form) return;

        var actionInput = document.getElementById('trash-form-action');
        if (actionInput) actionInput.value = action;

        var filesContainer = document.getElementById('trash-form-files');
        if (filesContainer) {
            filesContainer.innerHTML = '';
            files.forEach(function(f) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'trash_files[]';
                input.value = f;
                filesContainer.appendChild(input);
            });
        }

        form.submit();
    }

    window.openTrashModal = function() {
        var modal = document.getElementById('trash-modal');
        if (modal) modal.classList.add('active');
    };

    window.closeTrashModal = function() {
        var modal = document.getElementById('trash-modal');
        if (modal) modal.classList.remove('active');
    };

    window.trashRestoreSelected = function() {
        var files = getSelectedFiles();
        if (files.length === 0) return;
        if (!confirm('Восстановить выбранные элементы?')) return;
        submitAction('restore', files);
    };

    window.trashDeleteSelected = function() {
        var files = getSelectedFiles();
        if (files.length === 0) return;
        if (!confirm('Удалить выбранные элементы НАВСЕГДА? Восстановить будет невозможно.')) return;
        submitAction('delete_selected', files);
    };

    window.trashClearAll = function() {
        if (!confirm('Очистить корзину полностью? Все элементы будут удалены НАВСЕГДА.')) return;
        submitAction('clear_all', []);
    };

    function init() {
        var selectAll = document.getElementById('trash-select-all');
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                var checked = this.checked;
                document.querySelectorAll('.trash-item-checkbox').forEach(function(cb) {
                    cb.checked = checked;
                });
                updateState();
            });
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList && e.target.classList.contains('trash-item-checkbox')) {
                updateState();
            }
        });

        updateState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();