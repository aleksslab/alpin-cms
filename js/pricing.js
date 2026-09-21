/**
 * Тарифная сетка — скрипты для сайта
 */
(function() {
    'use strict';

    function initPricing() {
        const toggleBtns = document.querySelectorAll('.pricing-toggle-btn');
        
        toggleBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const period = this.getAttribute('data-period');
                const container = this.closest('.flex.justify-center');
                
                // Обновляем кнопки
                container.querySelectorAll('.pricing-toggle-btn').forEach(function(b) {
                    b.classList.remove('text-white', 'bg-[var(--primary-color)]', 'shadow-lg');
                    b.classList.add('text-slate-600');
                });
                this.classList.remove('text-slate-600');
                this.classList.add('text-white', 'bg-[var(--primary-color)]', 'shadow-lg');
                
                // Обновляем цены во всех карточках
                document.querySelectorAll('.pricing-card').forEach(function(card) {
                    const monthPrice = card.querySelector('.pricing-price-month');
                    const yearPrice = card.querySelector('.pricing-price-year');
                    
                    if (period === 'month') {
                        monthPrice.classList.remove('hidden');
                        yearPrice.classList.add('hidden');
                    } else {
                        monthPrice.classList.add('hidden');
                        yearPrice.classList.remove('hidden');
                    }
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPricing);
    } else {
        initPricing();
    }
})();