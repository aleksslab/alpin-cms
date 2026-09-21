/**
 * Счетчики (Статистика) — анимация при скролле
 */
(function() {
    'use strict';

    function animateCounter(element) {
        const target = element.getAttribute('data-target');
        const duration = parseFloat(element.getAttribute('data-duration')) || 1.5;
        const noAnimation = element.hasAttribute('data-no-animation');
        
        if (noAnimation || !target) {
            return;
        }

        // Ищем число в начале строки
        const numberMatch = target.match(/^([\d,.]+)/);
        if (!numberMatch) {
            return;
        }

        // Для расчетов заменяем запятую на точку
        const numStrForCalc = numberMatch[1].replace(/,/g, '.');
        const suffix = target.substring(numberMatch[0].length);
        const targetNum = parseFloat(numStrForCalc);
        
        if (isNaN(targetNum) || targetNum <= 0) {
            return;
        }

        // Определяем формат вывода
        const originalNumber = numberMatch[1];
        const hasDecimal = originalNumber.includes(',') || originalNumber.includes('.');
        let decimals = 0;
        let decimalSeparator = ',';
        
        if (hasDecimal) {
            if (originalNumber.includes(',')) {
                const parts = originalNumber.split(',');
                decimals = parts[1] ? parts[1].length : 0;
                decimalSeparator = ',';
            } else if (originalNumber.includes('.')) {
                const parts = originalNumber.split('.');
                decimals = parts[1] ? parts[1].length : 0;
                decimalSeparator = '.';
            }
        }
        
        let startTime = null;
        const startValue = 0;
        const endValue = targetNum;

        // Стартуем с 0
        element.textContent = '0' + suffix;

        function updateCounter(timestamp) {
            if (!startTime) startTime = timestamp;
            const elapsed = (timestamp - startTime) / 1000;
            const progress = Math.min(elapsed / duration, 1);
            
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const currentValue = startValue + (endValue - startValue) * easeOutQuart;
            
            let displayValue;
            if (hasDecimal && decimals > 0) {
                // Форматируем с нужным количеством знаков
                displayValue = currentValue.toFixed(decimals);
                // Меняем точку на запятую если нужно
                if (decimalSeparator === ',') {
                    displayValue = displayValue.replace('.', ',');
                }
            } else {
                displayValue = Math.round(currentValue).toString();
            }
            
            element.textContent = displayValue + suffix;
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        }

        requestAnimationFrame(updateCounter);
    }

    function initCounters() {
        const counters = document.querySelectorAll('.stat-value:not(.animated)');
        
        if (counters.length === 0) return;

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    element.classList.add('animated');
                    animateCounter(element);
                    observer.unobserve(element);
                }
            });
        }, {
            threshold: 0.3,
            rootMargin: '0px 0px -50px 0px'
        });

        counters.forEach(function(counter) {
            observer.observe(counter);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCounters);
    } else {
        initCounters();
    }
})();