/**
 * Слайдер продвинутый — скрипты для админки
 */
(function() {
    function updateSlideNumbers() {
        const container = document.getElementById('slider-rows-container');
        if (!container) return;
        
        const slides = container.querySelectorAll('.slide-item-card');
        slides.forEach(function(slide, index) {
            const label = slide.querySelector('.text-xs.font-bold.text-\\[var\\(--primary-color\\)\\]');
            if (label) {
                label.textContent = 'Слайд #' + (index + 1);
            }
        });
    }

    function initModule() {
        const container = document.getElementById('slider-rows-container');
        if (!container) return;
        
        updateSlideNumbers();
        
        const addBtn = document.getElementById('add-slide-btn');
        if (addBtn) {
            const newBtn = addBtn.cloneNode(true);
            addBtn.parentNode.replaceChild(newBtn, addBtn);
            
            newBtn.addEventListener('click', function() {
                const template = document.getElementById('tmpl-slider-slide');
                if (!template) return;
                
                const index = container.querySelectorAll('.slide-item-card').length + 1;
                let html = template.innerHTML;
                html = html.replace(/__INDEX__/g, index);
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const newSlide = temp.firstElementChild;
                if (newSlide) {
                    container.appendChild(newSlide);
                    newSlide.querySelectorAll('.js-image-url-input').forEach(function(input) {
                        if (typeof updatePreviewOnManualInput === 'function') {
                            updatePreviewOnManualInput(input);
                        }
                    });
                    updateSlideNumbers();
                }
            });
        }
        
        container.addEventListener('click', function(e) {
            const btn = e.target.closest('.js-remove-slide');
            if (!btn) return;
            const card = btn.closest('.slide-item-card');
            if (card) {
                const slides = container.querySelectorAll('.slide-item-card');
                if (slides.length > 1) {
                    card.remove();
                    updateSlideNumbers();
                } else {
                    alert('Должен остаться хотя бы один слайд');
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModule);
    } else {
        initModule();
    }
})();