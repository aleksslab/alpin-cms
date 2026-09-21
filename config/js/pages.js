/**
 * Управление страницами (админка)
 * Скрипты для модуля pages.php
 */

document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.querySelector('input[name="title"]');
    const slugInput = document.getElementById('slug-input');
    const slugPreview = document.getElementById('slug-preview');
    const pageForm = document.querySelector('form[action*="action=create"], form[action*="action=edit"]');
    let slugManuallyEdited = false;
    let slugGenerated = false; // Флаг, что алиас уже сгенерирован
    
    // --- Функция генерации алиаса через AJAX ---
    function generateSlug(title, callback) {
        if (!title.trim() || slugGenerated) {
            if (callback) callback(slugInput.value || '');
            return;
        }
        
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
        
        const formData = new FormData();
        formData.append('text', title);
        formData.append('csrf_token', csrfToken);
        
        fetch('index.php?fm_api_action=1&action=translit', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.transliterated) {
                let slug = data.transliterated
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
        .catch(() => {
            // fallback: простая транслитерация
            let slug = title.toLowerCase()
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
    
    // --- Событие: потеря фокуса с поля заголовка ---
    if (titleInput && slugInput) {
        titleInput.addEventListener('blur', function() {
            const title = this.value.trim();
            const slug = slugInput.value.trim();
            
            // Если алиас пустой и не был сгенерирован — генерируем
            if (!slug && !slugGenerated && title) {
                generateSlug(title);
            }
        });
    }
    
    // --- Событие: ручное редактирование алиаса ---
    if (slugInput && slugPreview) {
        slugInput.addEventListener('focus', function() {
            slugManuallyEdited = true;
        });
        
        slugInput.addEventListener('input', function() {
            slugPreview.textContent = '/' + (this.value.trim() || '');
        });
        
        slugInput.addEventListener('blur', function() {
            if (!this.value.trim()) {
                slugManuallyEdited = false;
                slugGenerated = false; // Разрешаем генерацию заново
            }
        });
    }
    
    // --- Обработка отправки формы: если алиас пуст — генерируем ---
    if (pageForm) {
        pageForm.addEventListener('submit', function(e) {
            const title = titleInput ? titleInput.value.trim() : '';
            
            // 1. Проверяем заголовок. Если он пустой — перехватываем управление
            if (!title) {
                e.preventDefault(); // Блокируем отправку формы
                
                const sectionsContainer = document.getElementById('page-sections');
                if (sectionsContainer) {
                    // Берем самую первую секцию внутри контейнера (Основные настройки)
                    const firstSection = sectionsContainer.firstElementChild;
                    if (firstSection) {
                        const content = firstSection.querySelector('.section-content');
                        const arrow = firstSection.querySelector('.section-arrow');

                        // Просто убираем hidden, если вкладка была закрыта
                        if (content) content.classList.remove('hidden');
                        if (arrow) arrow.classList.replace('icon-chevron-right', 'icon-chevron-down');
                    }
                }
                
                // Фокусируем инпут и выводим предупреждение
                titleInput.focus();
                alert('Пожалуйста, заполните заголовок страницы.');
                return;
            }

            // 2. Если заголовок заполнен, но алиас (slug) пуст — генерируем его перед сабмитом
            const slug = slugInput.value.trim();            
            if (!slug) {
                e.preventDefault();
                
                const title = titleInput.value.trim();
                if (title) {
                    generateSlug(title, function() {
                        pageForm.submit();
                    });
                } else {
                    alert('Пожалуйста, заполните заголовок страницы.');
                }
            }
        });
    }
    
});