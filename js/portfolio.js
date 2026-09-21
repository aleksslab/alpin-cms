/**
 * Портфолио — скрипты для сайта
 */
(function() {
    'use strict';

    // ===== LIGHTBOX =====
    function openPortfolioLightbox(imageSrc, caption) {
        var lightbox = document.getElementById('portfolio-lightbox');
        var img = document.getElementById('portfolio-lightbox-image');
        var captionEl = document.getElementById('portfolio-lightbox-caption');
        
        if (!lightbox || !img) return;
        
        img.src = imageSrc;
        if (captionEl) {
            captionEl.textContent = caption || '';
        }
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    window.closePortfolioLightbox = function(event) {
        var lightbox = document.getElementById('portfolio-lightbox');
        if (!lightbox) return;
        if (event && event.target !== event.currentTarget) return;
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        document.body.style.overflow = '';
    };

    function initPortfolio() {
        var grid = document.querySelector('.portfolio-grid');
        if (!grid) return;

        var enableLightbox = grid.dataset.lightbox === 'true';
        var items = grid.querySelectorAll('.portfolio-item');
        var filters = document.querySelectorAll('.portfolio-filter-btn');
        var loadMoreBtn = document.querySelector('.portfolio-load-more');
        var loadMoreWrap = document.querySelector('[id$="-loadmore-wrap"]');
        var showAll = grid.dataset.showAll === 'true';
        var perPage = parseInt(grid.dataset.perPage) || 6;

        if (!items.length) return;

        var currentFilter = 'all';
        var currentPage = 1;
        var allItems = [];
        var filteredItems = [];

        // Клик по карточке — открытие Lightbox (только если включен)
        items.forEach(function(item) {
            var img = item.querySelector('img');
            var titleEl = item.querySelector('.text-white.text-lg.font-bold');
            if (img && enableLightbox) {
                item.addEventListener('click', function(e) {
                    // Если клик по кнопке "Смотреть" — не открываем Lightbox
                    if (e.target.closest('.inline-block.mt-3')) return;
                    var caption = titleEl ? titleEl.textContent : '';
                    openPortfolioLightbox(img.src, caption);
                });
            }
            allItems.push(item);
        });

        // Закрытие по Escape (только если Lightbox включен)
        if (enableLightbox) {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    var lightbox = document.getElementById('portfolio-lightbox');
                    if (lightbox && !lightbox.classList.contains('hidden')) {
                        closePortfolioLightbox();
                    }
                }
            });
        }

        function getItemsByCategory(category) {
            var result = [];
            allItems.forEach(function(item) {
                var itemCategory = item.dataset.category || '';
                if (category === 'all' || itemCategory === category) {
                    result.push(item);
                }
            });
            return result;
        }

        function updateVisibleItems() {
            filteredItems = getItemsByCategory(currentFilter);
            
            allItems.forEach(function(item) {
                item.style.display = 'none';
            });

            var itemsToShow = filteredItems;
            
            if (!showAll) {
                var limit = perPage * currentPage;
                itemsToShow = filteredItems.slice(0, limit);
            }

            itemsToShow.forEach(function(item) {
                item.style.display = '';
            });

            updateLoadMoreButton();
        }

        function updateLoadMoreButton() {
            if (!loadMoreWrap) return;

            if (showAll) {
                loadMoreWrap.style.display = 'none';
                return;
            }

            var totalInCategory = filteredItems.length;
            var visibleCount = 0;
            allItems.forEach(function(item) {
                if (item.style.display !== 'none') {
                    visibleCount++;
                }
            });

            if (visibleCount < totalInCategory && visibleCount < allItems.length) {
                loadMoreWrap.style.display = 'block';
            } else {
                loadMoreWrap.style.display = 'none';
            }
        }

        function filterItems(category) {
            currentFilter = category;
            currentPage = 1;

            filters.forEach(function(btn) {
                btn.classList.remove('bg-[var(--primary-color)]', 'text-white', 'shadow-lg', 'shadow-[var(--primary-color)]/20');
                btn.classList.add('bg-slate-100', 'text-slate-600');
                if (btn.dataset.filter === category) {
                    btn.classList.remove('bg-slate-100', 'text-slate-600');
                    btn.classList.add('bg-[var(--primary-color)]', 'text-white', 'shadow-lg', 'shadow-[var(--primary-color)]/20');
                }
            });

            updateVisibleItems();
        }

        function loadMore() {
            if (showAll) return;
            currentPage++;
            updateVisibleItems();
        }

        filters.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var filter = this.dataset.filter;
                filterItems(filter);
            });
        });

        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                loadMore();
            });
        }

        // Инициализация
        var hasAllFilter = false;
        filters.forEach(function(btn) {
            if (btn.dataset.filter === 'all') {
                hasAllFilter = true;
            }
        });
        
        if (hasAllFilter) {
            filterItems('all');
        } else if (filters.length > 0) {
            var firstFilter = filters[0].dataset.filter;
            filterItems(firstFilter);
        } else {
            filterItems('all');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPortfolio);
    } else {
        initPortfolio();
    }
})();