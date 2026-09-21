/**
 * Таймер обратного отсчета — скрипты для сайта
 */
(function() {
    'use strict';

    function updateCountdown(container) {
        const startDateStr = container.getAttribute('data-countdown-start');
        const endDateStr = container.getAttribute('data-countdown-end');
        const showSeconds = container.getAttribute('data-show-seconds') === 'true';
        const displayType = container.getAttribute('data-display-type');
        const expiredText = container.getAttribute('data-expired-text') || 'Событие наступило!';
        
        const endDate = new Date(endDateStr).getTime();
        const now = new Date().getTime();
        let diff = endDate - now;

        // Если таймер завершен
        if (diff <= 0) {
            if (displayType === 'blocks') {
                const blocks = container.querySelectorAll('.countdown-block');
                blocks.forEach(function(block) {
                    block.style.display = 'none';
                });
                
                const existingExpired = container.querySelector('.countdown-expired');
                if (!existingExpired) {
                    const expiredEl = document.createElement('div');
                    expiredEl.className = 'countdown-expired text-center text-2xl font-bold text-slate-800';
                    expiredEl.textContent = expiredText;
                    const blocksContainer = container.querySelector('.countdown-blocks');
                    if (blocksContainer) {
                        blocksContainer.after(expiredEl);
                    }
                }
            } else {
                const bar = container.querySelector('.countdown-progress-bar');
                const percentage = container.querySelector('.countdown-percentage');
                const timeLeft = container.querySelector('.countdown-time-left');
                
                if (bar) bar.style.width = '100%';
                if (percentage) percentage.textContent = '100%';
                if (timeLeft) timeLeft.textContent = expiredText;
            }
            return;
        }

        if (displayType === 'blocks') {
            // Блочный таймер
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            const blocks = container.querySelectorAll('.countdown-block');
            blocks.forEach(function(block) {
                const unit = block.querySelector('.countdown-value').getAttribute('data-unit');
                let value = 0;
                if (unit === 'days') value = days;
                else if (unit === 'hours') value = hours;
                else if (unit === 'minutes') value = minutes;
                else if (unit === 'seconds') value = seconds;
                
                const valueEl = block.querySelector('.countdown-value');
                valueEl.textContent = String(value).padStart(2, '0');
            });
        } else {
            // Прогресс-бар
            const startDate = new Date(startDateStr).getTime();
            
            // Если дата начала не указана или меньше текущей - используем текущую как старт
            let startTime = startDate;
            if (!startDateStr || startTime > now) {
                startTime = now - (1000 * 60 * 60 * 24 * 7); // 7 дней назад для теста
            }
            
            const totalDuration = endDate - startTime;
            const elapsed = now - startTime;
            const percentage = Math.min(100, Math.max(0, (elapsed / totalDuration) * 100));
            
            const bar = container.querySelector('.countdown-progress-bar');
            const percentageEl = container.querySelector('.countdown-percentage');
            const timeLeft = container.querySelector('.countdown-time-left');
            
            // Обновляем прогресс-бар
            if (bar) bar.style.width = percentage + '%';
            if (percentageEl) percentageEl.textContent = Math.round(percentage) + '%';
            
            // Обновляем время сверху
            if (timeLeft) {
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                
                let timeText = '';
                if (days > 0) timeText += days + ' д ';
                if (hours > 0) timeText += hours + ' ч ';
                if (minutes > 0) timeText += minutes + ' мин ';
                if (showSeconds) timeText += seconds + ' сек';
                
                if (timeText.trim() === '') {
                    timeText = 'Меньше минуты';
                }
                
                timeLeft.textContent = timeText.trim();
            }
        }
    }

    function initCountdowns() {
        const containers = document.querySelectorAll('[data-countdown-end]');
        
        if (containers.length === 0) return;

        containers.forEach(function(container) {
            updateCountdown(container);
            setInterval(function() {
                updateCountdown(container);
            }, 1000);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCountdowns);
    } else {
        initCountdowns();
    }
})();