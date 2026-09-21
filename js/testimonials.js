/**
 * Отзывы клиентов — скрипты для сайта
 */
(function() {
    'use strict';

    function openTestimonialVideo(videoId) {
        const lightbox = document.getElementById('testimonial-lightbox');
        const iframe = document.getElementById('testimonial-video-iframe');
        
        if (!lightbox || !iframe) return;
        
        iframe.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1';
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    window.closeTestimonialLightbox = function(event) {
        const lightbox = document.getElementById('testimonial-lightbox');
        const iframe = document.getElementById('testimonial-video-iframe');
        
        if (!lightbox || !iframe) return;
        
        if (event && event.target !== event.currentTarget) return;
        
        iframe.src = '';
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        document.body.style.overflow = '';
    };

    function initTestimonials() {
        // Обработка кнопок play
        const playBtns = document.querySelectorAll('.testimonial-play-btn');
        playBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const video = this.getAttribute('data-video');
                if (video) {
                    openTestimonialVideo(video);
                }
            });
        });

        // Закрытие по Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const lightbox = document.getElementById('testimonial-lightbox');
                if (lightbox && !lightbox.classList.contains('hidden')) {
                    closeTestimonialLightbox();
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTestimonials);
    } else {
        initTestimonials();
    }
})();