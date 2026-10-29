(function() {
    'use strict';

    /**
     * Удаляет все action-поля из формы (если были).
     */
    function clearActionFields() {
        var form = document.getElementById('history-action-form');
        if (!form) return;
        form.querySelectorAll('.history-action-field').forEach(function(el) {
            el.remove();
        });
    }

    /**
     * Добавляет одно action-поле в форму.
     * @param {string} name 'restore_page_version' | 'delete_history_version' | 'clear_page_history'
     */
    function setActionField(name) {
        var form = document.getElementById('history-action-form');
        if (!form) return;

        clearActionFields();

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = '1';
        input.className = 'history-action-field';

        form.appendChild(input);
    }

    window.openHistoryModal = function() {
        var modal = document.getElementById('history-modal');
        if (modal) modal.classList.add('active');
    };

    window.closeHistoryModal = function() {
        var modal = document.getElementById('history-modal');
        if (modal) modal.classList.remove('active');
    };

    window.restoreHistoryVersion = function(timestamp) {
        if (!timestamp || timestamp <= 0) return;

        if (!confirm('Откатить страницу к выбранной версии?\n\nТекущее состояние будет сохранено в истории, откат можно отменить.')) {
            return;
        }

        var form = document.getElementById('history-action-form');
        var tsInput = document.getElementById('history-form-ts');
        if (!form || !tsInput) return;

        tsInput.value = timestamp;
        setActionField('restore_page_version');
        form.submit();
    };

    window.deleteHistoryVersion = function(timestamp) {
        if (!timestamp || timestamp <= 0) return;

        if (!confirm('Удалить эту версию из истории?\n\nВосстановить её будет невозможно.')) {
            return;
        }

        var form = document.getElementById('history-action-form');
        var tsInput = document.getElementById('history-form-ts');
        if (!form || !tsInput) return;

        tsInput.value = timestamp;
        setActionField('delete_history_version');
        form.submit();
    };

    window.clearPageHistory = function() {
        if (!confirm('Очистить всю историю этой страницы?\n\nВсе сохранённые версии будут удалены безвозвратно. Текущее содержимое страницы не пострадает.')) {
            return;
        }

        var form = document.getElementById('history-action-form');
        var tsInput = document.getElementById('history-form-ts');
        if (!form) return;

        if (tsInput) tsInput.value = '';
        setActionField('clear_page_history');
        form.submit();
    };

    function init() {
        // Закрытие по ESC
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            var modal = document.getElementById('history-modal');
            if (modal && modal.classList.contains('active')) {
                window.closeHistoryModal();
            }
        });

        // Закрытие по клику вне модалки
        var modal = document.getElementById('history-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    window.closeHistoryModal();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();