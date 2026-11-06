/**
 * Плагин «Предпросмотр»: клиентская часть.
 *
 * Постоянная preview-ссылка в редакторе страницы и предпросмотр текущего
 * состояния конструктора. Ядро вызывает window.cmsPreviewPage() из
 * window.previewPage(), если плагин активен.
 */
(function () {
    'use strict';

    /**
     * Экранирует строку для вставки в HTML.
     *
     * @param {string} text
     * @returns {string}
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(text)));
        return div.innerHTML;
    }

    // ===== КОПИРОВАНИЕ ПОСТОЯННОЙ PREVIEW-ССЫЛКИ =====
    window.copyPagePreviewUrl = function () {
        var input = document.getElementById('js-page-preview-url');
        if (!input) return;

        input.select();
        input.setSelectionRange(0, 99999);  // для мобилок

        try {
            navigator.clipboard.writeText(input.value).then(function () {
                var target = window.event ? window.event.target : null;
                var btn = target && target.closest ? target.closest('button') : null;
                if (btn) {
                    var original = btn.innerHTML;
                    btn.innerHTML = '<span class="icon-check text-sm"></span> Скопировано';
                    setTimeout(function () { btn.innerHTML = original; }, 1500);
                }
            });
        } catch (e) {
            document.execCommand('copy');
        }
    };

    // ===== СОЗДАНИЕ/ПЕРЕГЕНЕРАЦИЯ ПОСТОЯННОГО ТОКЕНА =====
    window.regeneratePagePreviewToken = function () {
        var tokenHidden = document.getElementById('js-preview-token-hidden');
        var hasToken = tokenHidden && tokenHidden.value.trim() !== '';

        if (hasToken) {
            if (!confirm('Создать новую preview-ссылку?\n\nСтарая ссылка перестанет работать после сохранения страницы.')) {
                return;
            }
        }

        fetch('index.php?tab=pages&ajax=generate_preview_token', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success || !data.token) {
                alert('Ошибка генерации токена.');
                return;
            }

            var slugInput = document.getElementById('slug-input');
            var slug = slugInput ? slugInput.value.trim() : '';
            var baseUrl = window.location.protocol + '//' + window.location.host;
            var url = baseUrl + '/' + slug + '?preview=' + data.token;

            var block = document.getElementById('js-page-preview-block');
            if (!block) return;

            block.innerHTML = [
                '<div class="flex flex-col lg:flex-row gap-2 mb-2">',
                    '<input type="text" readonly id="js-page-preview-url" value="' + escapeHtml(url) + '" ',
                        'class="flex-1 min-w-0 px-3 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-mono text-slate-600 select-all focus:outline-none">',
                    '<button type="button" onclick="window.copyPagePreviewUrl()" ',
                        'class="w-full lg:w-auto lg:flex-initial lg:w-[140px] px-4 py-2.5 bg-slate-100 border border-slate-200 text-slate-700 text-sm font-bold rounded-xl hover:bg-slate-200 transition-all whitespace-nowrap flex items-center justify-center gap-2">',
                        '<span class="icon-copy text-sm"></span> Копировать',
                    '</button>',
                    '<button type="button" onclick="window.deletePagePreviewToken()" ',
                        'class="w-full lg:w-auto lg:flex-initial lg:w-[140px] px-4 py-2.5 bg-rose-50 border border-rose-200 text-rose-600 text-sm font-bold rounded-xl hover:bg-rose-100 transition-all whitespace-nowrap flex items-center justify-center gap-2">',
                        '<span class="icon-trash-2 text-sm"></span> Удалить',
                    '</button>',
                '</div>',
                '<p class="text-[10px] text-slate-400">',
                    'Ссылка открывает страницу в любом статусе. Не индексируется поисковиками. ',
                    'Изменения вступят в силу <strong>после сохранения страницы</strong>.',
                '</p>',
                '<input type="hidden" name="preview_token" id="js-preview-token-hidden" value="' + escapeHtml(data.token) + '">'
            ].join('');
        })
        .catch(function () {
            alert('Ошибка соединения.');
        });
    };

    // ===== УДАЛЕНИЕ ТОКЕНА ИЗ ФОРМЫ =====
    window.deletePagePreviewToken = function () {
        if (!confirm('Удалить preview-ссылку?\n\nСтарая ссылка перестанет работать после сохранения страницы. Восстановить нельзя — можно только создать новую.')) {
            return;
        }

        var block = document.getElementById('js-page-preview-block');
        if (!block) return;

        block.innerHTML = [
            '<button type="button" onclick="window.regeneratePagePreviewToken()" ',
                'class="w-full lg:w-auto px-4 py-2.5 bg-[var(--primary-color)] text-white text-sm font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all whitespace-nowrap flex items-center justify-center gap-2">',
                '<span class="icon-link text-sm"></span> Создать preview-ссылку',
            '</button>',
            '<p class="text-[10px] text-slate-400 mt-2">',
                'Создаёт постоянную ссылку для согласования с заказчиком. ',
                'Ссылка будет записана <strong>после сохранения страницы</strong>.',
            '</p>',
            '<input type="hidden" name="preview_token" id="js-preview-token-hidden" value="">'
        ].join('');
    };

    // ===== ПРЕДПРОСМОТР ИЗ КОНСТРУКТОРА =====
    window.cmsPreviewPage = async function (pageId) {
        // === Собираем состояние формы ===
        var title = document.querySelector('input[name="title"]')?.value || '';
        var slug = document.querySelector('input[name="slug"]')?.value || '';
        var template = document.getElementById('template-hidden')?.value
                    || document.getElementById('constructor-template-select')?.value
                    || 'full-width';
        var showHeader = document.querySelector('input[name="show_header"]')?.checked ? '1' : '0';
        var showFooter = document.querySelector('input[name="show_footer"]')?.checked ? '1' : '0';
        var metaDescription = document.querySelector('textarea[name="meta_description"]')?.value || '';
        var metaKeywords = document.querySelector('input[name="meta_keywords"]')?.value || '';
        var rowsJson = document.getElementById('rows-json-input')?.value || '[]';

        // CSRF
        var csrfInput = document.querySelector('input[name="csrf_token"]');
        var csrfToken = csrfInput ? csrfInput.value : '';
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден. Обновите страницу.');
            return;
        }

        // === Запоминаем preview_id из прошлого раза (перезапись) ===
        pageId = pageId || '';
        var storageKey = 'preview_id_' + (pageId || 'new_' + slug);
        var existingPreviewId = sessionStorage.getItem(storageKey) || '';

        // === Собираем FormData ===
        var formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('page_id', pageId);
        formData.append('title', title);
        formData.append('slug', slug);
        formData.append('template', template);
        formData.append('show_header', showHeader);
        formData.append('show_footer', showFooter);
        formData.append('meta_description', metaDescription);
        formData.append('meta_keywords', metaKeywords);
        formData.append('rows_json', rowsJson);
        if (existingPreviewId) {
            formData.append('existing_preview_id', existingPreviewId);
        }

        // === Открываем пустое окно ЗАРАНЕЕ (чтобы браузер не блокировал popup) ===
        var previewWindow = window.open('about:blank', '_blank');

        try {
            var response = await fetch('index.php?tab=pages&ajax=preview_create', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            var data = await response.json();

            if (!data.success || !data.preview_id || !data.url) {
                if (previewWindow) previewWindow.close();
                alert('Ошибка создания превью: ' + (data.error || 'неизвестная ошибка'));
                return;
            }

            // Запоминаем preview_id для перезаписи
            sessionStorage.setItem(storageKey, data.preview_id);

            // Открываем URL превью
            if (previewWindow) {
                previewWindow.location.href = data.url;
            } else {
                // Fallback: если popup заблокирован — переходим в текущей вкладке
                window.location.href = data.url;
            }
        } catch (error) {
            if (previewWindow) previewWindow.close();
            alert('Ошибка сети при создании превью: ' + error.message);
        }
    };
})();
