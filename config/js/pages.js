/**
 * pages.js — Управление страницами (админка)
 * Генерация slug, валидация формы, переключение полей статуса.
 */
(function() {
    'use strict';

    // ===== ХЕЛПЕР ЭКРАНИРОВАНИЯ =====
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // ===== ВНУТРЕННИЕ ПЕРЕМЕННЫЕ (приватные для IIFE) =====

    // ===== ВНУТРЕННИЕ ПЕРЕМЕННЫЕ (приватные для IIFE) =====
    var titleInput = null;
    var slugInput = null;
    var slugPreview = null;
    var pageForm = null;
    var slugManuallyEdited = false;
    var slugGenerated = false;

    // ===== ГЕНЕРАЦИЯ SLUG ЧЕРЕЗ AJAX-ТРАНСЛИТЕРАЦИЮ =====
    function generateSlug(title, callback) {
        if (!title.trim() || slugGenerated) {
            if (callback) callback(slugInput ? slugInput.value : '');
            return;
        }

        var csrfInput = document.querySelector('input[name="csrf_token"]');
        var csrfToken = csrfInput ? csrfInput.value : '';

        var formData = new FormData();
        formData.append('text', title);
        formData.append('csrf_token', csrfToken);

        fetch('index.php?fm_api_action=1&action=translit', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.transliterated) {
                var slug = data.transliterated
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');

                slugInput.value = slug;
                if (slugPreview) {
                    slugPreview.textContent = '/' + (slug || '');
                }
                slugGenerated = true;
                if (callback) callback(slug);
            }
        })
        .catch(function() {
            // Fallback: простая транслитерация без сервера
            var slug = title.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugInput.value = slug;
            if (slugPreview) {
                slugPreview.textContent = '/' + (slug || '');
            }
            slugGenerated = true;
            if (callback) callback(slug);
        });
    }

    // ===== ИНИЦИАЛИЗАЦИЯ ПОЛЕЙ SLUG =====
    function initSlugFields() {
        titleInput = document.querySelector('input[name="title"]');
        slugInput = document.getElementById('slug-input');
        slugPreview = document.getElementById('slug-preview');
        pageForm = document.querySelector('form[action*="action=create"], form[action*="action=edit"]');

        if (!titleInput || !slugInput) {
            return;
        }

        // Потеря фокуса с заголовка — генерируем slug, если он пуст
        titleInput.addEventListener('blur', function() {
            var title = this.value.trim();
            var slug = slugInput.value.trim();
            if (!slug && !slugGenerated && title) {
                generateSlug(title);
            }
        });

        if (slugPreview) {
            slugInput.addEventListener('focus', function() {
                slugManuallyEdited = true;
            });

            slugInput.addEventListener('input', function() {
                slugPreview.textContent = '/' + (this.value.trim() || '');
            });

            slugInput.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    slugManuallyEdited = false;
                    slugGenerated = false;
                }
            });
        }
    }

    // ===== ВАЛИДАЦИЯ ФОРМЫ ПРИ ОТПРАВКЕ =====
    function initFormValidation() {
        if (!pageForm) {
            return;
        }

        pageForm.addEventListener('submit', function(e) {
            var title = titleInput ? titleInput.value.trim() : '';

            // 1. Заголовок обязателен
            if (!title) {
                e.preventDefault();

                var sectionsContainer = document.getElementById('page-sections');
                if (sectionsContainer) {
                    var firstSection = sectionsContainer.firstElementChild;
                    if (firstSection) {
                        var content = firstSection.querySelector('.section-content');
                        var arrow = firstSection.querySelector('.section-arrow');
                        if (content) content.classList.remove('hidden');
                        if (arrow) arrow.classList.replace('icon-chevron-right', 'icon-chevron-down');
                    }
                }

                titleInput.focus();
                alert('Пожалуйста, заполните заголовок страницы.');
                return;
            }

            // 2. Если slug пуст — генерируем перед сабмитом
            var slug = slugInput ? slugInput.value.trim() : '';
            if (!slug) {
                e.preventDefault();
                generateSlug(title, function() {
                    pageForm.submit();
                });
            }
        });
    }
    
    // ===== PREVIEW-ССЫЛКА СТРАНИЦЫ (постоянная) =====
    window.copyPagePreviewUrl = function() {
        var input = document.getElementById('js-page-preview-url');
        if (!input) return;

        input.select();
        input.setSelectionRange(0, 99999);  // для мобилок

        try {
            navigator.clipboard.writeText(input.value).then(function() {
                // Визуальный feedback
                var btn = event.target.closest('button');
                if (btn) {
                    var original = btn.innerHTML;
                    btn.innerHTML = '<span class="icon-check text-sm"></span> Скопировано';
                    setTimeout(function() { btn.innerHTML = original; }, 1500);
                }
            });
        } catch (e) {
            // Fallback
            document.execCommand('copy');
        }
    };

    window.regeneratePagePreviewToken = function() {
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
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.token) {
                alert('Ошибка генерации токена.');
                return;
            }

            // Собираем URL на клиенте
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
        .catch(function() {
            alert('Ошибка соединения.');
        });
    };
    
    window.deletePagePreviewToken = function() {
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

    // ===== ПЕРЕКЛЮЧЕНИЕ ПОЛЕЙ СТАТУСА ПУБЛИКАЦИИ =====
    window.toggleStatusFields = function() {
        var radios = document.querySelectorAll('input[name="status"]');
        var activeStatus = null;
        radios.forEach(function(r) { if (r.checked) activeStatus = r.value; });

        var datesRow = document.getElementById('js-dates-row');
        var publishField = document.getElementById('js-publish-at-field');
        var unpublishField = document.getElementById('js-unpublish-at-field');
        var publishInput = document.getElementById('publish-at-input');
        var unpublishInput = document.getElementById('unpublish-at-input');
        var requiredStar = document.querySelector('.js-required-star');

        if (!datesRow || !publishField || !unpublishField || !publishInput || !unpublishInput) return;

        // Подсветка активной radio-карточки
        document.querySelectorAll('.js-status-radio').forEach(function(label) {
            var input = label.querySelector('input[type="radio"]');
            if (input && input.checked) {
                label.classList.add('border-[var(--primary-color)]', 'bg-emerald-50/30');
            } else {
                label.classList.remove('border-[var(--primary-color)]', 'bg-emerald-50/30');
            }
        });

        // Хелперы для управления состоянием
        function setDisabled(input, disabled) {
            input.disabled = disabled;
            if (disabled) {
                input.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
                input.classList.remove('bg-white');
            } else {
                input.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
                input.classList.add('bg-white');
            }
        }

        function clearInput(input) {
            input.value = '';
        }

        // Логика по статусу
        if (activeStatus === 'draft' || activeStatus === 'archived') {
            // Скрываем всю строку с датами
            datesRow.style.display = 'none';
            publishInput.required = false;
            unpublishInput.required = false;
            clearInput(publishInput);
            clearInput(unpublishInput);
            setDisabled(publishInput, false);
            setDisabled(unpublishInput, false);
        }
        else if (activeStatus === 'scheduled') {
            // Обе даты активны, publish_at обязателен
            datesRow.style.display = '';
            publishField.style.display = '';
            unpublishField.style.display = '';
            publishInput.required = true;
            unpublishInput.required = false;
            setDisabled(publishInput, false);
            setDisabled(unpublishInput, false);
            if (requiredStar) requiredStar.style.display = '';
        }
        else if (activeStatus === 'published') {
            // publish_at выключен, unpublish_at опционален
            datesRow.style.display = '';
            publishField.style.display = '';
            unpublishField.style.display = '';
            publishInput.required = false;
            unpublishInput.required = false;
            clearInput(publishInput);
            setDisabled(publishInput, true);
            setDisabled(unpublishInput, false);
            if (requiredStar) requiredStar.style.display = 'none';
        }
    };

    // ===== ТОЧКА ВХОДА =====
    function init() {
        initSlugFields();
        initFormValidation();
        window.toggleStatusFields();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();