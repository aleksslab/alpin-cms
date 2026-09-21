(function() {
    'use strict';

    // Состояние мобильного меню (State)
    let isMobileMenuOpen = false;

    // === 1. УПРАВЛЕНИЕ МОБИЛЬНЫМ МЕНЮ (БУРГЕР) ===
    window.toggleMobileMenu = function() {
        isMobileMenuOpen = !isMobileMenuOpen;
        const menu = document.getElementById('mobile-menu');
        const icon = document.getElementById('burger-icon');
        const bttBtn = document.getElementById('back-to-top');

        if (!menu || !icon) return;

        if (isMobileMenuOpen) {
            menu.classList.replace('hidden', 'flex');
            icon.className = 'icon-x text-xl';
            if (bttBtn) {
                bttBtn.classList.replace('flex', 'hidden');
            }
        } else {
            menu.classList.replace('flex', 'hidden');
            icon.className = 'icon-menu text-xl';
            if (bttBtn && window.pageYOffset > 400) {
                bttBtn.classList.replace('hidden', 'flex');
            }
        }
    };

    // === 2. УПРАВЛЕНИЕ МОБИЛЬНЫМИ АККОРДЕОНАМИ (DROPDOWN) ===
    window.toggleMobileDropdown = function(id, button) {
        const container = document.getElementById(id);
        if (!container || !button) return;

        const arrow = button.querySelector('.icon-chevron-right, .icon-chevron-down');

        if (container.classList.contains('hidden')) {
            container.classList.remove('hidden');
            if (arrow) {
                arrow.className = 'icon-chevron-down text-xs text-[var(--primary-color)] transition-transform rotate-180';
            }
            button.classList.add('text-[var(--primary-color)]');
        } else {
            container.classList.add('hidden');
            if (arrow) {
                arrow.className = 'icon-chevron-right text-xs text-slate-400 transition-transform';
            }
            button.classList.remove('text-[var(--primary-color)]');
        }
    };

    // === 3. ПЛАВНЫЙ СКРОЛЛИНГ К СЕКЦИЯМ ===
    window.scrollToId = function(id) {
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
        } else {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };
    // === 4. УПРАВЛЕНИЕ ОКНАМИ ПОЛИТИК (ФЕТЧ КЛИЕНТ) ===
    window.openPolicy = function(type) {
        const modal = document.getElementById('policy-modal');
        const title = document.getElementById('modal-title');
        const body = document.getElementById('modal-body');
        if (!modal || !title || !body) return;

        let url = '';
        if (type === 'cookie') {
            title.innerText = 'Политика обработки cookie файлов';
            url = 'includes/cookies.php';
        } else {
            title.innerText = 'Политика конфиденциальности';
            url = 'includes/privacy.php';
        }

        document.body.style.overflow = 'hidden';
        modal.classList.replace('hidden', 'flex');
        modal.style.zIndex = '100';
        
        body.classList.add('flex', 'justify-center');
        body.innerHTML = '<span class="loader"></span>';

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error(`Ошибка загрузки: ${response.statusText}`);
                return response.text();
            })
            .then(html => {
                body.innerHTML = html;
                body.classList.remove('flex', 'justify-center');
            })
            .catch(error => {
                body.innerHTML = '';
                const errorDiv = document.createElement('div');
                errorDiv.textContent = 'Не удалось загрузить содержимое: ' + error.message;
                body.appendChild(errorDiv);
            });
    };

    window.closePolicy = function() {
        const modal = document.getElementById('policy-modal');
        if (modal) modal.classList.replace('flex', 'hidden');
        document.body.style.overflow = 'auto';
    };

    // Вспомогательные хелперы работы с системными Cookies
    function setCookie(name, value, days) {
        const d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/';
    }

    function getCookie(name) {
        const cname = name + '=';
        const decodedCookie = decodeURIComponent(document.cookie);
        const ca = decodedCookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i].trim();
            if (c.indexOf(cname) === 0) return c.substring(cname.length, c.length);
        }
        return '';
    }

    // === 5. БЕЗОПАСНАЯ ИНИЦИАЛИЗАЦИЯ ПО READYSTATE ===
    function initApp() {
        // Кнопка наверх (Скролл-контроль)
        window.addEventListener('scroll', () => {
            const btn = document.getElementById('back-to-top');
            if (!btn) return;
            if (!isMobileMenuOpen && window.pageYOffset > 400) btn.classList.replace('hidden', 'flex');
            else btn.classList.replace('flex', 'hidden');
        });

        // Автоматический перехват и плавный скролл всех якорных ссылок меню
        document.body.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href') || '';
            if (href.includes('#')) {
                const id = href.split('#')[1];
                if (isMobileMenuOpen) window.toggleMobileMenu();
                
                if (id && document.getElementById(id)) {
                    e.preventDefault();
                    window.scrollToId(id);
                }
            }
        });

        // Баннер куки-файлов
        const cookieBanner = document.getElementById('cookie-banner');
        const cookieBannerClose = document.getElementById('close-cookie-banner');
        if (cookieBanner) {
            cookieBanner.style.display = (getCookie('cookie-accept') === 'accepted') ? 'none' : 'block';
            if (cookieBannerClose) {
                cookieBannerClose.addEventListener('click', () => {
                    setCookie('cookie-accept', 'accepted', 30);
                    cookieBanner.style.display = 'none';
                });
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApp);
    } else {
        initApp();
    }

})();
