/**
 * Слайдер продвинутый — скрипты для сайта
 */
(function() {
    let currentSlide = 0;
    let slides = [];
    let dots = [];
    let carouselInterval;
    let isAnimating = false;

    function initCarousel() {
        const container = document.querySelector('.carousel-module .relative');
        if (!container) return;
        
        slides = container.querySelectorAll('.carousel-slide');
        dots = container.querySelectorAll('.carousel-dot');

        if (!slides.length) return;

        const autoplay = container.dataset.autoplay === 'true';
        const interval = parseInt(container.dataset.interval) || 6000;
        const effect = container.dataset.effect || 'fade';
        const speed = parseInt(container.dataset.speed) || 600;
        const pauseOnHover = container.dataset.pauseOnHover === 'true';
        const showArrows = container.dataset.showArrows === 'true';
        const showDots = container.dataset.showDots === 'true';
        const swipe = container.dataset.swipe === 'true';

        // Скрываем элементы если отключены
        if (!showArrows) {
            container.querySelectorAll('.carousel-prev, .carousel-next').forEach(function(el) {
                el.style.display = 'none';
            });
        }
        if (!showDots) {
            const dotsContainer = container.querySelector('.absolute.bottom-8.left-1\\/2');
            if (dotsContainer) dotsContainer.style.display = 'none';
        }

        // Устанавливаем CSS переменную для скорости
        container.style.setProperty('--slider-speed', speed + 'ms');

        function showSlide(index, direction) {
            if (isAnimating || index === currentSlide) return;
            if (index < 0 || index >= slides.length) return;
            
            isAnimating = true;
            
            const current = slides[currentSlide];
            const next = slides[index];
            
            // Убираем активный класс у текущего
            current.classList.remove('opacity-100', 'z-10', 'active', 'prev', 'next');
            current.classList.add('opacity-0', 'z-0');
            
            // Для slide эффекта нужно знать направление
            if (effect === 'slide') {
                const dir = direction || (index > currentSlide ? 1 : -1);
                current.style.transition = 'none';
                current.style.transform = 'translateX(0)';
                current.style.opacity = '1';
                
                next.style.transition = 'none';
                next.style.transform = dir === 1 ? 'translateX(100%)' : 'translateX(-100%)';
                next.style.opacity = '1';
                next.classList.add('opacity-100', 'z-10');
                next.classList.remove('opacity-0', 'z-0');
                
                // Принудительный reflow
                void next.offsetWidth;
                
                current.style.transition = 'transform ' + speed + 'ms ease, opacity ' + speed + 'ms ease';
                next.style.transition = 'transform ' + speed + 'ms ease, opacity ' + speed + 'ms ease';
                
                current.style.transform = dir === 1 ? 'translateX(-100%)' : 'translateX(100%)';
                current.style.opacity = '0';
                next.style.transform = 'translateX(0)';
                next.style.opacity = '1';
                
                setTimeout(function() {
                    current.style.transition = '';
                    current.style.transform = '';
                    current.style.opacity = '';
                    next.style.transition = '';
                    next.style.transform = '';
                    next.style.opacity = '';
                    current.classList.add('opacity-0', 'z-0');
                    current.classList.remove('opacity-100', 'z-10');
                    next.classList.add('opacity-100', 'z-10');
                    next.classList.remove('opacity-0', 'z-0');
                    currentSlide = index;
                    isAnimating = false;
                    updateDots();
                }, speed + 50);
                
                return;
            }
            
            // Для zoom эффекта
            if (effect === 'zoom') {
                next.style.transition = 'none';
                next.style.transform = 'scale(1.2)';
                next.style.opacity = '0';
                next.classList.add('opacity-0', 'z-10');
                next.classList.remove('opacity-100', 'z-0');
                
                current.style.transition = 'transform ' + speed + 'ms ease, opacity ' + speed + 'ms ease';
                current.style.transform = 'scale(0.8)';
                current.style.opacity = '0';
                
                // Принудительный reflow
                void next.offsetWidth;
                
                next.style.transition = 'transform ' + speed + 'ms ease, opacity ' + speed + 'ms ease';
                next.style.transform = 'scale(1)';
                next.style.opacity = '1';
                next.classList.add('opacity-100');
                next.classList.remove('opacity-0');
                
                setTimeout(function() {
                    current.style.transition = '';
                    current.style.transform = '';
                    current.style.opacity = '';
                    next.style.transition = '';
                    next.style.transform = '';
                    next.style.opacity = '';
                    current.classList.add('opacity-0', 'z-0');
                    current.classList.remove('opacity-100', 'z-10');
                    next.classList.add('opacity-100', 'z-10');
                    next.classList.remove('opacity-0', 'z-0');
                    currentSlide = index;
                    isAnimating = false;
                    updateDots();
                }, speed + 50);
                
                return;
            }
            
            // Для fade (по умолчанию)
            next.classList.remove('opacity-0', 'z-0');
            next.classList.add('opacity-100', 'z-10');
            
            setTimeout(function() {
                current.classList.add('opacity-0', 'z-0');
                current.classList.remove('opacity-100', 'z-10');
                currentSlide = index;
                isAnimating = false;
                updateDots();
            }, speed + 50);
        }

        function nextSlide(direction) {
            const next = (currentSlide + 1) % slides.length;
            showSlide(next, direction || 1);
        }

        function prevSlide(direction) {
            const prev = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(prev, direction || -1);
        }

        function goTo(index) {
            if (index === currentSlide) return;
            const direction = index > currentSlide ? 1 : -1;
            showSlide(index, direction);
        }

        function updateDots() {
            if (!dots.length) return;
            dots.forEach(function(dot, i) {
                dot.classList.remove('h-2', 'w-8', 'bg-white/80');
                dot.classList.add('w-2', 'bg-white/50', 'hover:bg-white/80');
                if (i === currentSlide) {
                    dot.classList.remove('w-2', 'bg-white/50', 'hover:bg-white/80');
                    dot.classList.add('h-2', 'w-8', 'bg-white/80');
                }
            });
        }

        function startCarouselTimer() {
            clearInterval(carouselInterval);
            if (autoplay && slides.length > 1) {
                carouselInterval = setInterval(function() {
                    nextSlide(1);
                }, interval);
            }
        }

        function stopCarouselTimer() {
            clearInterval(carouselInterval);
        }

        // Кнопки
        container.querySelectorAll('.carousel-prev').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                stopCarouselTimer();
                prevSlide(-1);
                if (autoplay) startCarouselTimer();
            });
        });

        container.querySelectorAll('.carousel-next').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                stopCarouselTimer();
                nextSlide(1);
                if (autoplay) startCarouselTimer();
            });
        });

        // Точки
        dots.forEach(function(dot, index) {
            dot.addEventListener('click', function() {
                stopCarouselTimer();
                goTo(index);
                if (autoplay) startCarouselTimer();
            });
        });

        // Остановка при наведении
        if (pauseOnHover) {
            container.addEventListener('mouseenter', stopCarouselTimer);
            container.addEventListener('mouseleave', startCarouselTimer);
        }

        // Swipe
        if (swipe) {
            let touchStartX = 0;
            let touchStartY = 0;
            
            container.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
            }, { passive: true });
            
            container.addEventListener('touchend', function(e) {
                const diffX = e.changedTouches[0].screenX - touchStartX;
                const diffY = e.changedTouches[0].screenY - touchStartY;
                
                if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 30) {
                    stopCarouselTimer();
                    if (diffX > 0) {
                        prevSlide(-1);
                    } else {
                        nextSlide(1);
                    }
                    if (autoplay) startCarouselTimer();
                }
            }, { passive: true });
        }

        // Keyboard
        document.addEventListener('keydown', function(e) {
            const isSliderFocused = container.contains(document.activeElement);
            if (!isSliderFocused) return;
            
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                stopCarouselTimer();
                prevSlide(-1);
                if (autoplay) startCarouselTimer();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                stopCarouselTimer();
                nextSlide(1);
                if (autoplay) startCarouselTimer();
            }
        });

        // Устанавливаем начальное состояние
        slides.forEach(function(slide, i) {
            if (i === 0) {
                slide.classList.add('opacity-100', 'z-10');
                slide.classList.remove('opacity-0', 'z-0');
            } else {
                slide.classList.add('opacity-0', 'z-0');
                slide.classList.remove('opacity-100', 'z-10');
            }
        });

        updateDots();
        startCarouselTimer();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCarousel);
    } else {
        initCarousel();
    }
})();