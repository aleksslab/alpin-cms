/**
 * Социальные иконки — скрипты для админки
 */
(function() {
    'use strict';
    
    // Забираем оригинальный спан счетчика один раз при старте до очистки DOM
    const totalSpan = document.getElementById('total');
    const totalHtmlFallback = totalSpan ? totalSpan.outerHTML : '';

    function initModule() {
        const previewContainer = document.getElementById('social-preview');
        if (!previewContainer) return;

        // Сохраняем исходные данные иконок для последующих перерисовок
        let previewData = [];
        const items = previewContainer.querySelectorAll('.flex.flex-col.items-center.gap-1');
        items.forEach(function(item) {
            const iconEl = item.querySelector('span:first-child');
            if (iconEl) {
                const svg = iconEl.querySelector('svg use');
                const iconId = svg ? svg.getAttribute('href') : '';
                const label = item.getAttribute('data-label') || '';
                previewData.push({
                    iconId: iconId || '',
                    label: label || ''
                });
            }
        });

        if (previewData.length === 0) return;

        // Элементы управления основным цветом
        const colorSelect = document.getElementById('icon-color-select');
        const colorPicker = document.getElementById('icon-color-picker');
        const colorFinal = document.getElementById('icon-color-final');

        // Элементы управления цветом ховера
        const hoverSelect = document.getElementById('icon-hover-select');
        const hoverPicker = document.getElementById('icon-hover-picker');
        const hoverFinal = document.getElementById('icon-hover-final');

        const sizeSelect = document.getElementById('icon-size-select');
        const showLabelsCheckbox = document.getElementById('show_labels');

        // === ОБЩАЯ ФУНКЦИЯ ОБНОВЛЕНИЯ ДАННЫХ ===
        function triggerUpdate() {
            const color = colorFinal ? colorFinal.value : 'text-[var(--text-main)]';
            const colorHover = hoverFinal ? hoverFinal.value : 'text-[var(--primary-color)]';
            const size = sizeSelect ? sizeSelect.value : 'w-6 h-6';
            const showLabels = showLabelsCheckbox ? showLabelsCheckbox.checked : false;
            
            renderPreview(previewData, size, color, colorHover, showLabels);
        }

        // === СЛУШАТЕЛИ: ОСНОВНОЙ ЦВЕТ ===
        if (colorSelect) {
            colorSelect.addEventListener('change', function() {
                if (this.value === '__custom__') {
                    colorPicker.classList.remove('hidden');
                    const val = 'text-[' + colorPicker.value + ']';
                    if (colorFinal) colorFinal.value = val;
                } else {
                    colorPicker.classList.add('hidden');
                    if (colorFinal) colorFinal.value = this.value;
                }
                triggerUpdate();
            });
        }

        if (colorPicker) {
            colorPicker.addEventListener('input', function() {
                const val = 'text-[' + this.value + ']';
                if (colorFinal) colorFinal.value = val;
                triggerUpdate();
            });
        }

        // === СЛУШАТЕЛИ: ЦВЕТ ХОВЕРА ===
        if (hoverSelect) {
            hoverSelect.addEventListener('change', function() {
                if (this.value === '__custom__') {
                    hoverPicker.classList.remove('hidden');
                    const val = 'text-[' + hoverPicker.value + ']';
                    if (hoverFinal) hoverFinal.value = val;
                } else {
                    hoverPicker.classList.add('hidden');
                    if (hoverFinal) hoverFinal.value = this.value;
                }
                triggerUpdate();
            });
        }

        if (hoverPicker) {
            hoverPicker.addEventListener('input', function() {
                const val = 'text-[' + this.value + ']';
                if (hoverFinal) hoverFinal.value = val;
                triggerUpdate();
            });
        }

        // === СЛУШАТЕЛИ: РАЗМЕР И ЛЕЙБЛЫ ===
        if (sizeSelect) {
            sizeSelect.addEventListener('change', triggerUpdate);
        }

        if (showLabelsCheckbox) {
            showLabelsCheckbox.addEventListener('change', triggerUpdate);
        }

        // === ЧИСТАЯ ПЕРЕРИСОВКА НА КЛАССАХ TAILWIND ===
        // Передаем colorHover отдельным четвертым аргументом
        function renderPreview(data, size, color, colorHover, showLabels) {
            let html = '';
            
            // Формируем чистый класс ховера с префиксом hover: из переданного аргумента
            const hoverTailwindClass = 'hover:' + colorHover;

            data.forEach(function(item) {
                html += `
                    <div class="flex flex-col items-center gap-1 cursor-pointer transition-all hover:scale-110 ${color} ${hoverTailwindClass}" data-label="${item.label}">
                        <span class="${size} transition-colors">
                            <svg class="w-full h-full" viewBox="0 0 24 24">
                                <use href="${item.iconId}"/>
                            </svg>
                        </span>
                        ${showLabels && item.label ? `<span class="text-xs transition-colors">${item.label}</span>` : ''}
                    </div>
                `;
            });
            
            html += totalHtmlFallback;
            previewContainer.innerHTML = html;
        }

        // === СИНХРОНИЗАЦИЯ ВИДИМОСТИ ПИКЕРОВ ПРИ СТАРТЕ ===
        if (colorSelect && colorPicker) {
            if (colorSelect.value === '__custom__') {
                colorPicker.classList.remove('hidden');
            } else {
                colorPicker.classList.add('hidden');
            }
        }
        
        if (hoverSelect && hoverPicker) {
            if (hoverSelect.value === '__custom__') {
                hoverPicker.classList.remove('hidden');
            } else {
                hoverPicker.classList.add('hidden');
            }
        }

        // === СТАРТОВАЯ ИНИЦИАЛИЗАЦИЯ ПРЕВЬЮ ===
        const initialSize = sizeSelect ? sizeSelect.value : 'w-6 h-6';
        const initialColor = colorFinal ? colorFinal.value : 'text-[var(--text-main)]';
        const initialColorHover = hoverFinal ? hoverFinal.value : 'text-[var(--primary-color)]';
        const initialShowLabels = showLabelsCheckbox ? showLabelsCheckbox.checked : false;
        
        renderPreview(previewData, initialSize, initialColor, initialColorHover, initialShowLabels);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModule);
    } else {
        initModule();
    }
})();
