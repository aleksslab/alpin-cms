/**
 * До и После — скрипты для сайта
 */
(function() {
    'use strict';

    function initBeforeAfter() {
        const containers = document.querySelectorAll('.before-after-container');
        
        containers.forEach(function(container) {
            const wrapper = container.querySelector('.ba-wrapper');
            const beforeClip = container.querySelector('.ba-before-clip');
            const baseImg = container.querySelector('.ba-img-base');
            const overlayImg = container.querySelector('.ba-img-overlay');
            const handle = container.querySelector('.ba-handle');
            const isVertical = container.classList.contains('ba-vertical');
            const defaultPos = parseFloat(container.getAttribute('data-default')) || 50;
            
            if (!wrapper || !beforeClip || !handle || !baseImg || !overlayImg) return;
            
            let currentPos = defaultPos;
            
            // Синхронизация строго по размерам родительского контейнера wrapper
            function syncSizes() {
                const width = wrapper.offsetWidth;
                const height = wrapper.offsetHeight;
                
                if (width > 0 && height > 0) {
                    overlayImg.style.setProperty('--ba-w', width + 'px');
                    overlayImg.style.setProperty('--ba-h', height + 'px');
                }
            }
            
            function getPos(clientX, clientY) {
                const rect = wrapper.getBoundingClientRect();
                if (isVertical) {
                    return ((clientY - rect.top) / rect.height) * 100;
                } else {
                    return ((clientX - rect.left) / rect.width) * 100;
                }
            }
            
            function setPosition(pos) {
                currentPos = Math.max(0, Math.min(100, pos));
                if (isVertical) {
                    beforeClip.style.height = currentPos + '%';
                    handle.style.top = currentPos + '%';
                } else {
                    beforeClip.style.width = currentPos + '%';
                    handle.style.left = currentPos + '%';
                }
            }
            
            function drag(e) {
                if (e.cancelable) e.preventDefault(); 
                const clientX = e.touches ? e.touches.clientX : e.clientX;
                const clientY = e.touches ? e.touches.clientY : e.clientY;
                setPosition(getPos(clientX, clientY));
            }
            
            function startDrag(e) {
                document.addEventListener('mousemove', drag);
                document.addEventListener('mouseup', endDrag);
                document.addEventListener('touchmove', drag, { passive: false });
                document.addEventListener('touchend', endDrag);
                drag(e);
            }
            
            function endDrag() {
                document.removeEventListener('mousemove', drag);
                document.removeEventListener('mouseup', endDrag);
                document.removeEventListener('touchmove', drag);
                document.removeEventListener('touchend', endDrag);
            }
            
            handle.addEventListener('mousedown', startDrag);
            handle.addEventListener('touchstart', startDrag, { passive: true });
            
            // Ждем загрузки базовой картинки, чтобы контейнер wrapper обрел финальную высоту
            if (baseImg.complete) {
                syncSizes();
            } else {
                baseImg.addEventListener('load', syncSizes);
            }
            
            setPosition(currentPos);
            
            let resizeTimeout;
            window.addEventListener('resize', function() {
                syncSizes();
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    setPosition(currentPos);
                }, 50);
            });
            
            window.addEventListener('load', syncSizes);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBeforeAfter);
    } else {
        initBeforeAfter();
    }
})();
