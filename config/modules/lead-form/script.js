/**
 * Форма захвата лидов — скрипты для админки
 */
(function() {
    'use strict';

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
        initFormFieldsDependencies();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModule);
    } else {
        initModule();
    }
})();