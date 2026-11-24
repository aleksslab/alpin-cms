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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApp);
    } else {
        initApp();
    }

})();

/**
 * Реестр плавающих элементов у нижнего края окна.
 *
 * Элементы с одинаковой стороной встают друг над другом. Якоря (кнопка
 * «наверх», баннер cookie) остаются на своём отступе из CSS, элементы
 * с `above: true` (кнопка обратного звонка) поднимаются над ними.
 * Оверлеи (баннер cookie-согласия) временно прячут элементы с `above`.
 *
 * Плагины регистрируют свои элементы после загрузки — реестр сам следит
 * за размером и видимостью через ResizeObserver, слушателя прокрутки нет.
 */
(function () {
    'use strict';

    var GAP = 12;
    var entries = [];
    var timer = null;
    var observer = (typeof window.ResizeObserver === 'function')
        ? new window.ResizeObserver(schedule)
        : null;

    /**
     * Проверяет, виден ли элемент.
     *
     * @param {Element} el
     * @returns {boolean}
     */
    function isVisible(el) {
        if (!el || !el.getClientRects().length) {
            return false;
        }

        var style = window.getComputedStyle(el);

        return style.display !== 'none' && style.visibility !== 'hidden';
    }

    /**
     * Читает отступ снизу из CSS, не учитывая наш инлайн-стиль.
     *
     * Переход по `bottom` на время замера выключается: иначе
     * getComputedStyle возвращает промежуточное значение анимации
     * и элемент «залипает» на старом отступе.
     *
     * @param {Element} el
     * @returns {number}
     */
    function cssBottom(el) {
        var savedBottom = el.style.bottom;
        var savedTransition = el.style.transition;

        el.style.transition = 'none';
        el.style.bottom = '';
        var value = parseFloat(window.getComputedStyle(el).bottom);
        el.style.bottom = savedBottom;
        el.style.transition = savedTransition;

        return isFinite(value) ? value : 0;
    }

    /**
     * Возвращает базовый отступ элемента.
     *
     * @param {Object} entry
     * @returns {number}
     */
    function baseOf(entry) {
        if (typeof entry.base === 'function') {
            return entry.base();
        }

        if (typeof entry.base === 'number') {
            return entry.base;
        }

        return cssBottom(entry.el);
    }

    /**
     * Пересчитывает положения элементов.
     *
     * @returns {void}
     */
    function layout() {
        var overlay = null;

        entries.forEach(function (entry) {
            if (entry.overlay && isVisible(entry.el)) {
                overlay = entry;
            }
        });

        entries.forEach(function (entry) {
            if (entry.above) {
                entry.el.style.visibility = overlay ? 'hidden' : '';
            }
        });

        if (overlay) {
            return;
        }

        ['left', 'right'].forEach(function (side) {
            var cursor = 0;

            // Якоря остаются на своих местах, но задают высоту стека.
            entries.forEach(function (entry) {
                if (entry.overlay || entry.above || entry.side !== side || !isVisible(entry.el)) {
                    return;
                }

                cursor = Math.max(cursor, baseOf(entry) + entry.el.getBoundingClientRect().height + GAP);
            });

            entries.forEach(function (entry) {
                if (!entry.above || entry.side !== side || !isVisible(entry.el)) {
                    return;
                }

                var bottom = Math.max(baseOf(entry), cursor);

                entry.el.style.bottom = bottom + 'px';
            });
        });
    }

    /**
     * Откладывает пересчёт: ResizeObserver может сработать пачкой.
     *
     * @returns {void}
     */
    function schedule() {
        window.clearTimeout(timer);
        timer = window.setTimeout(layout, 60);
    }

    window.FloatStack = {
        /**
         * Регистрирует плавающий элемент.
         *
         * @param {Element} el
         * @param {{side?: string, above?: boolean, overlay?: boolean, base?: number|function}} options
         * @returns {void}
         */
        register: function (el, options) {
            if (!el) {
                return;
            }

            options = options || {};

            entries.push({
                el: el,
                side: options.side === 'left' ? 'left' : 'right',
                above: !!options.above,
                overlay: !!options.overlay,
                base: (options.base === undefined) ? null : options.base
            });

            if (observer) {
                observer.observe(el);
            }

            layout();
        },

        /**
         * Убирает элемент из реестра.
         *
         * @param {Element} el
         * @returns {void}
         */
        unregister: function (el) {
            entries = entries.filter(function (entry) {
                return entry.el !== el;
            });

            if (observer) {
                observer.unobserve(el);
            }

            layout();
        },

        /**
         * Пересчитывает положения вручную.
         *
         * @returns {void}
         */
        refresh: layout
    };

    window.addEventListener('resize', schedule);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', layout);
    } else {
        layout();
    }

    var backToTop = document.getElementById('back-to-top');

    if (backToTop) {
        window.FloatStack.register(backToTop, { side: 'right' });
    }
})();
