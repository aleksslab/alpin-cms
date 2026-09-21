/**
 * config_vars.js — Настройки сайта
 * Социальные сети, превью хедера/футера, аккордеон, цветовая схема, минификация
 */
(function() {
    'use strict';

    // ===== АККОРДЕОН =====
    window.toggleSection = function(header) {
        var currentSection = header.closest('.border');
        var currentContent = header.nextElementSibling;
        var currentArrow = header.querySelector('.section-arrow');

        var isOpen = !currentContent.classList.contains('hidden');

        var allSections = document.querySelectorAll('#config-sections .border');
        allSections.forEach(function(section) {
            var content = section.querySelector('.section-content');
            var arrow = section.querySelector('.section-arrow');
            if (content) {
                content.classList.add('hidden');
            }
            if (arrow) {
                arrow.classList.remove('icon-chevron-down');
                arrow.classList.add('icon-chevron-right');
            }
        });

        if (!isOpen) {
            currentContent.classList.remove('hidden');
            currentArrow.classList.remove('icon-chevron-right');
            currentArrow.classList.add('icon-chevron-down');
        }
    };

    // ===== СОЦИАЛЬНЫЕ СЕТИ =====
    var socialNetworks = window.socialNetworks || {};
    var selectedSocialId = null;

    function updateDropdownState() {
        var menu = document.getElementById('js-social-dropdown-menu');
        var btn = document.getElementById('js-social-dropdown-btn');
        var wrapper = document.getElementById('js-social-dropdown-wrapper');
        
        if (!menu || !btn || !wrapper) return;
        
        var items = menu.querySelectorAll('.js-social-dropdown-item');
        
        if (items.length === 0) {
            wrapper.style.opacity = '0.4';
            wrapper.style.cursor = 'default';
            wrapper.style.pointerEvents = 'none';
            btn.querySelector('#js-social-dropdown-selected').innerHTML = 'Нет доступных соцсетей';
        } else {
            wrapper.style.opacity = '1';
            wrapper.style.cursor = 'pointer';
            wrapper.style.pointerEvents = 'auto';
            if (btn.querySelector('#js-social-dropdown-selected').innerHTML === 'Нет доступных соцсетей') {
                btn.querySelector('#js-social-dropdown-selected').innerHTML = 'Выберите соцсеть';
            }
        }
    }

    window.addSocialRowFromDropdown = function() {
        if (!selectedSocialId) {
            alert('Сначала выберите соцсеть');
            return;
        }
        
        var container = document.getElementById('social-extra-container');
        var social = socialNetworks[selectedSocialId];
        if (!social) return;
        
        if (container.querySelector('[data-social-id="' + selectedSocialId + '"]')) {
            alert('Эта соцсеть уже добавлена');
            return;
        }
        
        var row = document.createElement('div');
        row.className = 'js-social-row flex items-center gap-2 bg-white p-2.5 rounded-xl border border-slate-200';
        row.dataset.socialId = selectedSocialId;
        
        var iconId = selectedSocialId === 'tg_channel' ? 'telegram' : selectedSocialId;
        row.innerHTML = [
            '<svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">',
                '<use href="/fonts/brands.svg#' + iconId + '"/>',
            '</svg>',
            '<span class="text-xs font-medium text-slate-500 w-28 flex-shrink-0">' + social.label + '</span>',
            '<input type="text" name="socials[' + selectedSocialId + ']" value="" ',
                'class="w-full px-2 py-1.5 bg-transparent border-0 focus:ring-0 text-sm text-slate-800 placeholder-slate-400" ',
                'placeholder="' + social.label + '">',
            '<button type="button" onclick="window.removeSocialRow(this)" ',
                'class="text-slate-300 hover:text-rose-500 transition-colors flex-shrink-0">',
                '<span class="icon-x text-sm"></span>',
            '</button>'
        ].join('');
        
        container.appendChild(row);
        
        var menu = document.getElementById('js-social-dropdown-menu');
        var item = menu.querySelector('.js-social-dropdown-item[data-id="' + selectedSocialId + '"]');
        if (item) item.remove();
        
        selectedSocialId = null;
        document.getElementById('js-social-dropdown-selected').innerHTML = 'Выберите соцсеть';
        
        updateDropdownState();
    };

    window.removeSocialRow = function(btn) {
        var row = btn.closest('.js-social-row');
        if (!row) return;
        
        var id = row.dataset.socialId;
        var primaryIds = ['vk', 'ok', 'telegram'];
        
        if (primaryIds.indexOf(id) !== -1) {
            alert('Эту соцсеть нельзя удалить');
            return;
        }
        
        var social = socialNetworks[id];
        var menu = document.getElementById('js-social-dropdown-menu');
        
        if (social && menu) {
            var item = document.createElement('div');
            item.className = 'js-social-dropdown-item flex items-center gap-2 px-3 py-2 hover:bg-slate-50 cursor-pointer transition-colors';
            item.dataset.id = id;
            var iconId = id === 'tg_channel' ? 'telegram' : id;
            item.innerHTML = [
                '<svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">',
                    '<use href="/fonts/brands.svg#' + iconId + '"/>',
                '</svg>',
                '<span class="text-sm text-slate-700">' + social.label + '</span>'
            ].join('');
            
            item.addEventListener('click', function() {
                var id = this.dataset.id;
                var label = this.textContent.trim();
                
                selectedSocialId = id;
                
                var selectedSpan = document.getElementById('js-social-dropdown-selected');
                var iconId = id === 'tg_channel' ? 'telegram' : id;
                selectedSpan.innerHTML = [
                    '<span class="flex items-center gap-2">',
                        '<svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">',
                            '<use href="/fonts/brands.svg#' + iconId + '"/>',
                        '</svg>',
                        '<span>' + label + '</span>',
                    '</span>'
                ].join('');
                
                menu.classList.add('hidden');
            });
            
            menu.appendChild(item);
            
            var btn = document.getElementById('js-social-dropdown-btn');
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            btn.disabled = false;
        }
        
        row.remove();
        updateDropdownState();
    };

    function initSocials() {
        var btn = document.getElementById('js-social-dropdown-btn');
        var menu = document.getElementById('js-social-dropdown-menu');
        
        if (!btn || !menu) return;
        
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var items = menu.querySelectorAll('.js-social-dropdown-item');
            if (items.length === 0) return;
            menu.classList.toggle('hidden');
        });
        
        menu.querySelectorAll('.js-social-dropdown-item').forEach(function(item) {
            item.addEventListener('click', function() {
                var id = this.dataset.id;
                var label = this.textContent.trim();
                
                selectedSocialId = id;
                
                var selectedSpan = document.getElementById('js-social-dropdown-selected');
                var iconId = id === 'tg_channel' ? 'telegram' : id;
                selectedSpan.innerHTML = [
                    '<span class="flex items-center gap-2">',
                        '<svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">',
                            '<use href="/fonts/brands.svg#' + iconId + '"/>',
                        '</svg>',
                        '<span>' + label + '</span>',
                    '</span>'
                ].join('');
                
                menu.classList.add('hidden');
            });
        });
        
        document.addEventListener('click', function() {
            menu.classList.add('hidden');
        });
        
        updateDropdownState();
    }

    // ===== ПРЕВЬЮ ХЕДЕРА/ФУТЕРА =====
    function initPreviews() {
        var headerSelect = document.querySelector('select[name="header_variant"]');
        var headerPreview = document.querySelector('.header-preview-container .js-slide-preview-img');
        var headerEye = document.querySelector('.header-preview-container .icon-eye');
        
        if (headerSelect && headerPreview) {
            headerSelect.addEventListener('change', function() {
                var selectedOption = this.options[this.selectedIndex];
                var previewUrl = selectedOption.getAttribute('data-preview');
                
                if (previewUrl) {
                    headerPreview.src = previewUrl;
                    if (headerEye) {
                        headerEye.classList.add('icon-eye');
                        headerEye.classList.remove('icon-eye-off');
                    }
                } else {
                    headerPreview.removeAttribute('src');
                    if (headerEye) {
                        headerEye.classList.add('icon-eye-off');
                        headerEye.classList.remove('icon-eye');
                    }
                }
            });
        }
        
        var footerSelect = document.querySelector('select[name="footer_variant"]');
        var footerPreview = document.querySelector('.footer-preview-container .js-slide-preview-img');
        var footerEye = document.querySelector('.footer-preview-container .icon-eye');
        
        if (footerSelect && footerPreview) {
            footerSelect.addEventListener('change', function() {
                var selectedOption = this.options[this.selectedIndex];
                var previewUrl = selectedOption.getAttribute('data-preview');
                
                if (previewUrl) {
                    footerPreview.src = previewUrl;
                    if (footerEye) {
                        footerEye.classList.add('icon-eye');
                        footerEye.classList.remove('icon-eye-off');
                    }
                } else {
                    footerPreview.removeAttribute('src');
                    if (footerEye) {
                        footerEye.classList.add('icon-eye-off');
                        footerEye.classList.remove('icon-eye');
                    }
                }
            });
        }
    }

    // ===== СИНХРОНИЗАЦИЯ COLOR + TEXT =====
    function initColorPickers() {
        document.querySelectorAll('.color-picker-sync').forEach(function(container) {
            var picker = container.querySelector('input[type="color"]');
            var text = container.querySelector('input[type="text"]');
            
            if (!picker || !text) return;
            
            picker.addEventListener('input', function() {
                text.value = this.value;
            });
            
            text.addEventListener('input', function() {
                var val = this.value.trim();
                if (/^#[0-9a-f]{3}$|^#[0-9a-f]{6}$/i.test(val)) {
                    picker.value = val;
                }
            });
        });
    }

    // ===== МИНИФИКАЦИЯ =====
    function initMinifyExceptions() {
        var modal = document.getElementById('minify-exceptions-modal');
        var hidden = document.getElementById('minify-exceptions-hidden');
        if (!modal || !hidden) return;

        var observer = new MutationObserver(function() {
            if (modal.classList.contains('active')) {
                loadExceptionsFromHidden();
            }
        });
        observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
    }

    function loadExceptionsFromHidden() {
        var hidden = document.getElementById('minify-exceptions-hidden');
        var checkboxes = document.querySelectorAll('#minify-exceptions-modal .minify-exception-checkbox');
        if (!hidden || !checkboxes.length) return;

        try {
            var data = JSON.parse(hidden.value || '{}');
            checkboxes.forEach(function(cb) {
                cb.checked = data[cb.name] === '1';
            });
        } catch(e) {
            // если JSON невалидный — ничего не делаем
        }
    }
    
    function getCurrentExceptionsFromHidden() {
        var hidden = document.getElementById('minify-exceptions-hidden');
        if (!hidden) return null;

        try {
            return JSON.parse(hidden.value || '{}');
        } catch(e) {
            return null;
        }
    }

    window.openMinifyExceptions = function() {
        document.getElementById('minify-exceptions-modal').classList.add('active');
    };

    window.saveMinifyExceptions = function() {
        var modal = document.getElementById('minify-exceptions-modal');
        var hidden = document.getElementById('minify-exceptions-hidden');
        if (!modal || !hidden) return;

        var data = {};
        var checkboxes = modal.querySelectorAll('.minify-exception-checkbox');
        checkboxes.forEach(function(cb) {
            data[cb.name] = cb.checked ? '1' : '0';
        });
        hidden.value = JSON.stringify(data);

        modal.classList.remove('active');

        refreshMinifyStatsWithExceptions(data);
    };

    function refreshMinifyStatsWithExceptions(data) {
        var statsBlock = document.getElementById('minify-stats-block');
        if (!statsBlock) return;

        var params = [];
        for (var key in data) {
            if (data[key] === '1') {
                params.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
        }

        var url = 'index.php?tab=config_vars&action=get_minify_stats';
        if (params.length > 0) {
            url += '&' + params.join('&');
        }

        fetch(url, {
            method: 'GET',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success && data.stats) {
                var checkbox = document.querySelector('input[type="checkbox"][name="assets_minify"]');
                var statsBlock = document.getElementById('minify-stats-block');
                if (checkbox && checkbox.checked && statsBlock) {
                    statsBlock.style.display = 'grid';
                }
                updateMinifyStatsUI(data.stats);
            }
        })
        .catch(function() {
            // тихо падаем
        });
    }

    window.rebuildAllMinFiles = function() {
        if (!confirm('Пересобрать .min файлы для всех модулей?')) {
            return;
        }

        var token = document.querySelector('input[name="csrf_token"]');
        if (!token) {
            alert('Ошибка: CSRF-токен не найден');
            return;
        }

        // Получаем исключения из скрытого поля ДО пересборки
        var exceptions = getCurrentExceptionsFromHidden();
        var params = [];
        if (exceptions) {
            for (var key in exceptions) {
                if (exceptions[key] === '1') {
                    params.push(encodeURIComponent(key) + '=' + encodeURIComponent(exceptions[key]));
                }
            }
        }

        var body = 'rebuild_min_all=1&csrf_token=' + encodeURIComponent(token.value);
        if (params.length > 0) { body += '&' + params.join('&'); }
        fetch('index.php?tab=config_vars', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                alert(data.message || 'Пересборка завершена');
                // Обновляем статистику с теми же параметрами
                refreshMinifyStatsWithParams(params);
            } else {
                alert(data.error || 'Ошибка');
            }
        })
        .catch(function() {
            alert('Ошибка соединения');
        });
    };

    window.deleteAllMinFiles = function() {
        if (!confirm('Удалить все .min файлы для всех модулей?\n\nЭто действие необратимо. После удаления можно пересобрать заново.')) {
            return;
        }

        if (!confirm('Вы уверены? .min файлы будут полностью удалены.')) {
            return;
        }

        var token = document.querySelector('input[name="csrf_token"]');
        if (!token) {
            alert('Ошибка: CSRF-токен не найден');
            return;
        }

        fetch('index.php?tab=config_vars', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'delete_min_all=1&csrf_token=' + encodeURIComponent(token.value)
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                alert(data.message || 'Все .min файлы удалены');
                refreshMinifyStats();
            } else {
                alert(data.error || 'Ошибка');
            }
        })
        .catch(function() {
            alert('Ошибка соединения');
        });
    };

    function refreshMinifyStatsWithParams(params) {
        var statsBlock = document.getElementById('minify-stats-block');
        if (!statsBlock) return;

        var url = 'index.php?tab=config_vars&action=get_minify_stats';
        if (params && params.length > 0) {
            url += '&' + params.join('&');
        }

        fetch(url, {
            method: 'GET',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success && data.stats) {
                var checkbox = document.querySelector('input[type="checkbox"][name="assets_minify"]');
                var statsBlock = document.getElementById('minify-stats-block');
                if (checkbox && checkbox.checked && statsBlock) {
                    statsBlock.style.display = 'grid';
                }
                updateMinifyStatsUI(data.stats);
            }
        })
        .catch(function() {
            // тихо падаем
        });
    }

    function refreshMinifyStats() {
        refreshMinifyStatsWithParams(null);
    }

    function initMinifyStatsVisibility() {
        var statsBlock = document.getElementById('minify-stats-block');
        if (!statsBlock) return;

        function toggleStats() {
            var checkbox = document.querySelector('input[type="checkbox"][name="assets_minify"]');
            if (!checkbox) return;

            if (checkbox.checked) {
                statsBlock.style.display = 'grid';

                // При загрузке страницы тоже учитываем исключения
                var exceptions = getCurrentExceptionsFromHidden();
                var params = [];
                if (exceptions) {
                    for (var key in exceptions) {
                        if (exceptions[key] === '1') {
                            params.push(encodeURIComponent(key) + '=1');
                        }
                    }
                }
                refreshMinifyStatsWithParams(params);
            } else {
                statsBlock.style.display = 'none';
            }
        }

        var checkbox = document.querySelector('input[type="checkbox"][name="assets_minify"]');
        if (checkbox) {
            checkbox.addEventListener('change', toggleStats);
        }

        toggleStats();
    }

    function updateMinifyStatsUI(stats) {
        var cssMinified = document.getElementById('minify-css-minified');
        var cssTotal = document.getElementById('minify-css-total');
        var cssRatio = document.getElementById('minify-css-ratio');
        var cssOriginal = document.getElementById('minify-css-original');
        var cssMinifiedSize = document.getElementById('minify-css-minified-size');

        if (cssMinified) cssMinified.textContent = stats.css.minified;
        if (cssTotal) cssTotal.textContent = stats.css.total;
        if (cssRatio) {
            cssRatio.textContent = stats.css.total > 0 ? ' (' + Math.round(stats.css.ratio * 100) + '%)' : '';
        }
        if (cssOriginal) cssOriginal.textContent = formatBytes(stats.css.original_size);
        if (cssMinifiedSize) cssMinifiedSize.textContent = formatBytes(stats.css.minified_size);

        var jsMinified = document.getElementById('minify-js-minified');
        var jsTotal = document.getElementById('minify-js-total');
        var jsRatio = document.getElementById('minify-js-ratio');
        var jsOriginal = document.getElementById('minify-js-original');
        var jsMinifiedSize = document.getElementById('minify-js-minified-size');

        if (jsMinified) jsMinified.textContent = stats.js.minified;
        if (jsTotal) jsTotal.textContent = stats.js.total;
        if (jsRatio) {
            jsRatio.textContent = stats.js.total > 0 ? ' (' + Math.round(stats.js.ratio * 100) + '%)' : '';
        }
        if (jsOriginal) jsOriginal.textContent = formatBytes(stats.js.original_size);
        if (jsMinifiedSize) jsMinifiedSize.textContent = formatBytes(stats.js.minified_size);
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        var units = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 10) / 10 + ' ' + units[i];
    }
    
    // ===== КЭШИРОВАНИЕ =====
    window.clearAllCache = function() {
        if (!confirm('Очистить весь кеш страниц?')) {
            return;
        }

        var token = document.querySelector('input[name="csrf_token"]');
        if (!token) {
            alert('Ошибка: CSRF-токен не найден');
            return;
        }

        fetch('index.php?tab=config_vars', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'clear_cache=1&csrf_token=' + encodeURIComponent(token.value)
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                alert(data.message || 'Кеш очищен');
                // Просто обновляем цифры на странице
                document.getElementById('cache-count').textContent = '0';
                document.getElementById('cache-size').textContent = '0 B';
            } else {
                alert(data.error || 'Ошибка');
            }
        })
        .catch(function() {
            alert('Ошибка соединения');
        });
    };

    // ===== ИНИЦИАЛИЗАЦИЯ =====
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initSocials();
            initPreviews();
            initColorPickers();
            initMinifyStatsVisibility();
            initMinifyExceptions();
        });
    } else {
        initSocials();
        initPreviews();
        initColorPickers();
        initMinifyStatsVisibility();
        initMinifyExceptions();
    }

})();