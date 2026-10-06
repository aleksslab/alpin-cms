/**
 * pages.js — Управление страницами (админка)
 * Генерация slug, валидация формы, переключение полей статуса.
 */
(function() {
    'use strict';

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
        var pageIdInput = document.querySelector('input[name="page_id_for_preview"]');
        if (!pageIdInput) return;

        var pageId = pageIdInput.value;
        if (!pageId) return;

        var hasToken = !!document.getElementById('js-page-preview-url');
        var message = hasToken
            ? 'Сбросить текущую preview-ссылку и создать новую?\n\nСтарая ссылка сразу перестанет работать.'
            : 'Создать постоянную preview-ссылку для этой страницы?';

        if (!confirm(message)) return;

        var csrfInput = document.querySelector('input[name="csrf_token"]');
        var csrfToken = csrfInput ? csrfInput.value : '';
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден.');
            return;
        }

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?tab=pages&action=edit&id=' + encodeURIComponent(pageId);

        var inputs = {
            csrf_token: csrfToken,
            regenerate_page_preview_token: '1',
            page_id: pageId
        };

        for (var key in inputs) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = inputs[key];
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    };
    
    window.deletePagePreviewToken = function() {
        var pageIdInput = document.querySelector('input[name="page_id_for_preview"]');
        if (!pageIdInput) return;

        var pageId = pageIdInput.value;
        if (!pageId) return;

        if (!confirm('Удалить preview-ссылку?\n\nСтарая ссылка сразу перестанет работать. Восстановить нельзя — можно только создать новую.')) {
            return;
        }

        var csrfInput = document.querySelector('input[name="csrf_token"]');
        var csrfToken = csrfInput ? csrfInput.value : '';
        if (!csrfToken) {
            alert('Ошибка: CSRF-токен не найден.');
            return;
        }

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?tab=pages&action=edit&id=' + encodeURIComponent(pageId);

        var inputs = {
            csrf_token: csrfToken,
            delete_page_preview_token: '1',
            page_id: pageId
        };

        for (var key in inputs) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = inputs[key];
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
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