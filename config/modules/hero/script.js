/**
 * Обложка (Hero) — скрипты для админки
 */
(function() {
    'use strict';

    function initHeroModule() {
        var typeSelect = document.querySelector('select[name="background_type"]');
        if (!typeSelect) return;

        typeSelect.addEventListener('change', function() {
            var imageField = document.querySelector('.js-media-module-container');
            var colorField = document.querySelector('.js-color-field');
            
            if (this.value === 'image') {
                if (imageField) imageField.classList.remove('hidden');
                if (colorField) colorField.classList.add('hidden');
            } else {
                if (imageField) imageField.classList.add('hidden');
                if (colorField) colorField.classList.remove('hidden');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeroModule);
    } else {
        initHeroModule();
    }
})();