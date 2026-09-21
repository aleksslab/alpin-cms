/**
 * Временная шкала — скрипты для сайта
 */
(function() {
    'use strict';

    function initTimeline() {
        const isMobile = window.innerWidth <= 768;

        // Обработка линий "в пути" для вертикальной
        const verticalItems = document.querySelectorAll('.timeline-vertical .timeline-item');
        verticalItems.forEach(function(item, index) {
            const line = item.querySelector('.timeline-line');
            if (!line) return;
            
            const isCompleted = item.classList.contains('timeline-item-completed');
            const nextItem = verticalItems[index + 1];
            
            if (!nextItem) return;
            
            const nextCompleted = nextItem.classList.contains('timeline-item-completed');
            
            if (isCompleted && !nextCompleted) {
                line.style.background = 'linear-gradient(to bottom, var(--primary-color, #10B981) 50%, #e2e8f0 50%)';
            } else if (isCompleted && nextCompleted) {
                line.style.background = 'var(--primary-color, #10B981)';
            } else {
                line.style.background = '#e2e8f0';
            }
        });

        // Обработка линий "в пути" для горизонтальной (только десктоп)
        if (!isMobile) {
            const horizontalItems = document.querySelectorAll('.timeline-horizontal .timeline-item');
            horizontalItems.forEach(function(item, index) {
                const isLast = index === horizontalItems.length - 1;
                
                if (isLast) {
                    item.style.setProperty('--line-bg', 'transparent');
                    return;
                }
                
                const isCompleted = item.classList.contains('timeline-item-completed');
                const nextItem = horizontalItems[index + 1];
                
                if (!nextItem) return;
                
                const nextCompleted = nextItem.classList.contains('timeline-item-completed');
                
                let gradient;
                if (isCompleted && !nextCompleted) {
                    gradient = 'linear-gradient(to right, var(--primary-color, #10B981) 50%, #e2e8f0 50%)';
                } else if (isCompleted && nextCompleted) {
                    gradient = 'var(--primary-color, #10B981)';
                } else {
                    gradient = '#e2e8f0';
                }
                
                item.style.setProperty('--line-bg', gradient);
            });
        }
    }

    // Пересчитываем при ресайзе
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            initTimeline();
        }, 200);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTimeline);
    } else {
        initTimeline();
    }
})();