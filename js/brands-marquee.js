/**
 * Бегущая лента — скрипты для сайта
 */
(function() {
    'use strict';

    function initMarquee() {
        const wrappers = document.querySelectorAll('.marquee-wrapper');
        
        wrappers.forEach(function(wrapper) {
            const track = wrapper.querySelector('.marquee-track');
            if (!track) return;
            
            // Получаем все элементы
            const items = track.querySelectorAll('.marquee-item');
            if (items.length === 0) return;
            
            // Клонируем элементы, пока трек не станет шире контейнера минимум в 2 раза
            const wrapperWidth = wrapper.clientWidth;
            let trackWidth = track.scrollWidth;
            
            // Сначала клонируем хотя бы 2 раза
            const originalItems = [];
            items.forEach(function(item) {
                originalItems.push(item.cloneNode(true));
            });
            
            // Добавляем клоны, пока трек не станет шире контейнера в 2 раза
            let clonesAdded = 0;
            while (trackWidth < wrapperWidth * 2 && clonesAdded < 10) {
                originalItems.forEach(function(item) {
                    const clone = item.cloneNode(true);
                    track.appendChild(clone);
                });
                clonesAdded++;
                trackWidth = track.scrollWidth;
            }
            
            // Запускаем анимацию
            let isPaused = false;
            
            wrapper.addEventListener('mouseenter', function() {
                isPaused = true;
                track.style.animationPlayState = 'paused';
            });
            
            wrapper.addEventListener('mouseleave', function() {
                isPaused = false;
                track.style.animationPlayState = 'running';
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMarquee);
    } else {
        initMarquee();
    }
})();