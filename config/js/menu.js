(function() {
    'use strict';    

    /**
     * Экранирует HTML-спецсимволы.
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // 1. Инициализация состояния (State) из скрытого инпута, заполненного PHP
    const jsonInput = document.getElementById('menu-items-json-input');
    let menuState = [];
    try {
        menuState = jsonInput && jsonInput.value ? JSON.parse(jsonInput.value) : [];
    } catch(e) {
        menuState = [];
    }

    // ГЛОБАЛЬНЫЙ МАССИВ ДЛЯ СВЁРНУТЫХ ПУНКТОВ
    let collapsedDropdowns = [];

    // АВТОМАТИЧЕСКОЕ СВОРАЧИВАНИЕ: при первом старте заносим все ID дропдаунов в массив свёрнутых
    menuState.forEach(function(item) {
        if (item.type === 'dropdown' && item.id) {
            collapsedDropdowns.push(item.id);
        }
    });

    let editingItemId = null;
    const typeLabels = {
        'page': 'Страница', 'section': 'Раздел', 'custom': 'Ссылка', 'dropdown': 'Выпадающее', 'divider': 'Разделитель'
    };

    // Глобальные селекторы контейнеров
    const tbody = document.getElementById('menu-items-tbody');
    const mobileContainer = document.getElementById('menu-items-mobile');
    const emptyMessage = document.getElementById('empty-menu-message');
    const countSpan = document.getElementById('items-count');

    // 2. Главная реактивная функция рендеринга интерфейса
    function renderMenu() {
        if (!tbody || !mobileContainer) return;

        // Полностью очищаем старый интерфейс перед перерисовкой
        tbody.innerHTML = '';
        mobileContainer.innerHTML = '';

        // Управляем заглушкой "Нет пунктов"
        if (menuState.length === 0) {
            if (emptyMessage) emptyMessage.classList.remove('hidden');
            if (countSpan) countSpan.textContent = '0 пунктов';
            if (jsonInput) jsonInput.value = '[]';
            return;
        }
        if (emptyMessage) emptyMessage.classList.add('hidden');
        if (countSpan) countSpan.textContent = menuState.length + ' пунктов';

        // Итерируем и рендерим каждый элемент из массива памяти
        menuState.forEach(function(item, index) {
            // Гарантируем наличие стабильного системного ID у элемента
            if (!item.id) {
                item.id = 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
            }
            item.order = index;

            // Определяем состояние свёрнутости текущего выпадающего списка
            const isCollapsed = collapsedDropdowns.includes(item.id);

            // --- 1. РЕНДЕР КОРНЕВОГО ЭЛЕМЕНТА ДЛЯ ДЕСКТОПА (ТАБЛИЦА) ---
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 transition-colors border-b border-slate-100';
            
            let labelHTML = item.label;
            if (item.type === 'dropdown') {
                const childCount = item.children ? item.children.length : 0;
                const chevronIcon = isCollapsed ? 'icon-chevron-right' : 'icon-chevron-down';
                
                labelHTML = `
                    <button type="button" data-toggle-id="${escapeHtml(item.id)}" class="js-toggle-dropdown-btn inline-flex items-center gap-1 font-bold text-slate-900 hover:text-[var(--primary-color)] transition-colors text-left cursor-pointer">
                        <span class="${chevronIcon} text-xs text-slate-400 mr-1 transition-transform"></span>
                        <span class="icon-folder text-amber-500 mr-1"></span>
                        ${escapeHtml(item.label)} 
                        <span class="text-[10px] text-slate-400 font-normal ml-1">(${childCount})</span>
                    </button>
                `;
            }

            tr.innerHTML = `
                <td class="py-3 px-3 font-mono text-xs text-slate-400">${index + 1}</td>
                <td class="py-3 font-semibold text-slate-800">${labelHTML}</td>
                <td class="py-3"><span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold text-slate-600 bg-slate-100">${escapeHtml(typeLabels[item.type] || item.type)}</span></td>
                <td class="py-3 text-right px-3">
                    <div class="flex items-center justify-end gap-1.5">
                        <button type="button" data-id="${escapeHtml(item.id)}" class="js-edit-btn w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:text-slate-800 bg-white hover:bg-slate-50 transition-all"><span class="icon-edit text-sm"></span></button>
                        <button type="button" data-id="${escapeHtml(item.id)}" data-dir="up" class="js-move-btn w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-400 hover:text-slate-600 bg-white hover:bg-slate-50 transition-all"><span class="icon-chevron-up text-xs"></span></button>
                        <button type="button" data-id="${escapeHtml(item.id)}" data-dir="down" class="js-move-btn w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-400 hover:text-slate-600 bg-white hover:bg-slate-50 transition-all"><span class="icon-chevron-down text-xs"></span></button>
                        <button type="button" data-id="${escapeHtml(item.id)}" class="js-del-btn w-8 h-8 rounded-lg flex items-center justify-center border border-rose-100 text-rose-500 hover:text-rose-800 bg-white hover:bg-rose-50 transition-all"><span class="icon-trash-2 text-sm"></span></button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);

            // --- 2. РЕНДЕР ПОДПУНКТОВ ДЛЯ ДЕСКТОПА (ЕСЛИ ОНИ НЕ СВЁРНУТЫ) ---
            if (item.type === 'dropdown' && item.children && item.children.length > 0 && !isCollapsed) {
                item.children.forEach(function(child) {
                    const childTr = document.createElement('tr');
                    childTr.className = 'bg-slate-50/40 border-b border-slate-100/70 text-xs text-slate-600 animate-fade-in';
                    childTr.innerHTML = `
                        <td class="py-2 px-3 text-center text-slate-300 font-mono">—</td>
                        <td class="py-2 pl-8 font-medium flex items-center gap-1 text-slate-700">
                            <span class="icon-corner-down-right text-slate-400 text-sm"></span>
                            ${child.label}
                        </td>
                        <td class="py-2">
                            <span class="text-[10px] text-slate-400 uppercase tracking-wider bg-white border border-slate-200 px-2 py-0.5 rounded">
                                подпункт: ${typeLabels[child.type] || child.type}
                            </span>
                        </td>
                        <td class="py-2 text-right px-3">
                            <span class="text-[10px] text-slate-400 italic pr-2">управляется в модалке родителя</span>
                        </td>
                    `;
                    tbody.appendChild(childTr);
                });
            }
            // --- 3. РЕНДЕР КОРНЕВОГО ЭЛЕМЕНТА ДЛЯ МОБИЛКИ (КАРТОЧКИ) ---
            const card = document.createElement('div');
            card.className = 'bg-white p-4 rounded-2xl border border-slate-100 shadow-sm';
            
            let mobileChildHTML = '';
            // На мобильной версии подпункты тоже скрываются на основе стейта свернутости
            if (item.type === 'dropdown' && item.children && item.children.length > 0 && !isCollapsed) {
                mobileChildHTML = `<div class="mt-3 pt-3 border-t border-dashed border-slate-100 space-y-1.5">`;
                item.children.forEach(function(child) {
                    mobileChildHTML += `
                    <div class="flex items-center gap-1 text-xs text-slate-600 pl-4">
                        <span class="icon-corner-down-right text-slate-400"></span>
                        <span class="font-medium">${escapeHtml(child.label)}</span>
                        <span class="text-[9px] text-slate-400 bg-slate-50 border border-slate-100 px-1.5 rounded ml-auto">${escapeHtml(typeLabels[child.type] || child.type)}</span>
                    </div>`;
                });
                mobileChildHTML += `</div>`;
            }

            // Заголовок карточки дропдауна на мобилке дублирует кнопку клика для интерактивного сворачивания
            let mobileLabelHTML = labelHTML;
            if (item.type === 'dropdown') {
                mobileLabelHTML = `
                <button type="button" data-toggle-id="${escapeHtml(item.id)}" class="js-toggle-dropdown-btn text-base font-bold text-slate-800 text-left cursor-pointer">
                    ${escapeHtml(item.label)} 
                    <span class="text-xs text-slate-400 font-normal">(${item.children ? item.children.length : 0})</span>
                </button>
            `;
            }

            card.innerHTML = `
                <div class="flex items-start justify-between border-b border-slate-100 pb-3 mb-3">
                    <span class="text-base font-bold text-slate-800">${mobileLabelHTML}</span>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold text-slate-600 bg-slate-100 flex-shrink-0">${escapeHtml(typeLabels[item.type] || item.type)}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 mb-3">
                    <div><span class="block text-[9px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">№</span><span class="font-mono font-medium text-slate-700">${index + 1}</span></div>
                </div>
                ${mobileChildHTML}
                <div class="flex items-center gap-2 pt-3 mt-3 border-t border-slate-100">
                    <button type="button" data-id="${escapeHtml(item.id)}" class="js-edit-btn flex-1 h-10 bg-white border border-slate-200 text-slate-600 rounded-lg text-xs font-bold flex items-center justify-center gap-1 transition-all"><span class="icon-edit text-xs"></span> Редактировать</button>
                    <button type="button" data-id="${escapeHtml(item.id)}" data-dir="up" class="js-move-btn w-10 h-10 flex-shrink-0 bg-white border border-slate-200 text-slate-400 rounded-lg flex items-center justify-center transition-all"><span class="icon-chevron-up text-xs"></span></button>
                    <button type="button" data-id="${escapeHtml(item.id)}" data-dir="down" class="js-move-btn w-10 h-10 flex-shrink-0 bg-white border border-slate-200 text-slate-400 rounded-lg flex items-center justify-center transition-all"><span class="icon-chevron-down text-xs"></span></button>
                    <button type="button" data-id="${escapeHtml(item.id)}" class="js-del-btn w-10 h-10 flex-shrink-0 bg-white border border-rose-100 text-rose-500 rounded-lg flex items-center justify-center transition-all"><span class="icon-trash-2 text-sm"></span></button>
                </div>
            `;
            mobileContainer.appendChild(card);
        });

        // Жестко фиксируем актуальное состояние в скрытом инпуте для одной точечной POST-отправки на бэкенд
        if (jsonInput) {
            jsonInput.value = JSON.stringify(menuState);
        }
    }


    // === ЕДИНЫЙ ДИСПЕТЧЕР КЛИКОВ (ДЕЛЕГИРОВАНИЕ СОБЫТИЙ) ===
    document.getElementById('menu-items-container').addEventListener('click', function(e) {
        
        // 1. ПЕРЕХВАТ КЛИКА ПО НАЗВАНИЮ/СТРЕЛОЧКЕ DROPDOWN (СВОРАЧИВАНИЕ)
        const toggleBtn = e.target.closest('.js-toggle-dropdown-btn');
        if (toggleBtn) {
            e.preventDefault();
            e.stopPropagation(); // Изолируем клик от всплытия

            const toggleId = toggleBtn.dataset.toggleId;
            const idx = collapsedDropdowns.indexOf(toggleId);
            
            if (idx === -1) {
                collapsedDropdowns.push(toggleId); // Сворачиваем
            } else {
                collapsedDropdowns.splice(idx, 1); // Разворачиваем
            }
            
            renderMenu(); // Перерисовываем реактивный интерфейс
            return; // Прерываем дальнейший анализ клика
        }

        // 2. АНАЛИЗ ОСТАЛЬНЫХ КНОПОК УПРАВЛЕНИЯ
        const btn = e.target.closest('button');
        if (!btn) return;

        const id = btn.dataset.id;
        const index = menuState.findIndex(i => i.id === id);
        if (index === -1) return;

        if (btn.classList.contains('js-del-btn')) {
            if (confirm('Удалить этот пункт из меню?')) {
                menuState.splice(index, 1);
                renderMenu();
            }
        } else if (btn.classList.contains('js-edit-btn')) {
            window.openEditItemModal(id);
        } else if (btn.classList.contains('js-move-btn')) {
            const dir = btn.dataset.dir;
            if (dir === 'up' && index > 0) {
                [menuState[index], menuState[index - 1]] = [menuState[index - 1], menuState[index]];
            } else if (dir === 'down' && index < menuState.length - 1) {
                [menuState[index], menuState[index + 1]] = [menuState[index + 1], menuState[index]];
            }
            renderMenu();
        }
    });
    
    // === ПЕРЕКЛЮЧЕНИЕ ВИДИМОСТИ ПОЛЕЙ В МОДАЛКЕ ===
    window.toggleItemFields = function(type) {
        const labelWrap = document.getElementById('item-label-wrap');
        const urlWrap = document.getElementById('item-url-wrap');
        const pageWrap = document.getElementById('item-page-wrap');
        const childrenWrap = document.getElementById('item-children-wrap');

        if (!labelWrap || !urlWrap || !pageWrap || !childrenWrap) return;

        // По умолчанию скрываем все специфичные поля
        labelWrap.style.display = 'none';
        urlWrap.style.display = 'none';
        pageWrap.style.display = 'none';
        childrenWrap.style.display = 'none';

        // Включаем нужные поля в зависимости от выбранного типа
        if (type === 'divider') {
            labelWrap.style.display = 'block';
            document.getElementById('item-label').placeholder = 'Текст разделителя';
        } else if (type === 'page') {
            labelWrap.style.display = 'block';
            pageWrap.style.display = 'block';
            document.getElementById('item-label').placeholder = 'Название пункта';
        } else if (type === 'section' || type === 'custom') {
            labelWrap.style.display = 'block';
            urlWrap.style.display = 'block';
            document.getElementById('item-label').placeholder = 'Название пункта';
            document.getElementById('item-url').placeholder = (type === 'section') ? '/#section-id' : 'https://example.com';
        } else if (type === 'dropdown') {
            labelWrap.style.display = 'block';
            childrenWrap.style.display = 'block';
            document.getElementById('item-label').placeholder = 'Название раздела';
        }
    };

    // 4. Логика работы с Модальным окном
    window.openAddItemModal = function() {
        editingItemId = null;
        document.getElementById('edit-item-id').value = '';
        document.getElementById('item-modal-title').textContent = 'Добавить пункт меню';
        document.getElementById('item-modal-save-btn').textContent = 'Добавить';
        document.getElementById('item-type').value = 'page';
        document.getElementById('item-label').value = '';
        document.getElementById('item-url').value = '';
        document.getElementById('item-children-container').innerHTML = '<div class="text-center py-4 text-xs text-slate-400 border-2 border-dashed border-slate-200 rounded-lg">Нет вложенных пунктов</div>';
        window.toggleItemFields('page');
        window.openItemModal();;
    };

    window.openEditItemModal = function(id) {
        const item = menuState.find(i => i.id === id);
        if (!item) return;

        editingItemId = id;
        document.getElementById('edit-item-id').value = id;
        document.getElementById('item-modal-title').textContent = 'Редактировать пункт меню';
        document.getElementById('item-modal-save-btn').textContent = 'Сохранить';
        
        document.getElementById('item-type').value = item.type;
        window.toggleItemFields(item.type);
        document.getElementById('item-label').value = item.label;

        if (item.type === 'page') {
            document.getElementById('item-page').value = item.page_id || '/';
        } else if (item.type === 'section' || item.type === 'custom') {
            document.getElementById('item-url').value = item.url || '';
        } else if (item.type === 'dropdown') {
            window.loadChildItems(item.children || []);
        }
        window.openItemModal();;
    };
    
    // === СОХРАНЕНИЕ ПУНКТА (ИЗ МОДАЛКИ В МАССИВ ПАМЯТИ) ===
    window.saveItem = function() {
        const type = document.getElementById('item-type').value;
        const label = document.getElementById('item-label').value.trim();
        const url = document.getElementById('item-url').value.trim();
        const pageUrl = document.getElementById('item-page').value;
        const children = window.getChildItems();

        if (!label && type !== 'divider') {
            alert('Введите название пункта');
            return;
        }
        if (type === 'dropdown' && children.length === 0) {
            alert('Добавьте хотя бы один вложенный пункт');
            return;
        }

        // Унифицируем структуру данных пункта меню
        const itemData = {
            id: editingItemId,
            type: type,
            label: label,
            url: (type === 'section' || type === 'custom') ? url : '',
            page_id: (type === 'page') ? pageUrl : '',
            children: (type === 'dropdown') ? children : undefined
        };

        if (editingItemId) {
            // Если редактировали существующий — обновляем в массиве по ID
            const index = menuState.findIndex(i => i.id === editingItemId);
            if (index !== -1) {
                menuState[index] = itemData;
            }
        } else {
            // Если это новый пункт — генерируем стабильный ID на клиенте
            itemData.id = 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
            menuState.push(itemData);
        }

        // Перерисовываем интерфейс и закрываем окно
        renderMenu();
        window.closeItemModal();
    };

    // === СБОРКА ВЛОЖЕННЫХ ПУНКТОВ ИЗ ИНПУТОВ МОДАЛКИ ===
    window.getChildItems = function() {
        const container = document.getElementById('item-children-container');
        if (!container) return [];
        
        const rows = container.querySelectorAll('.child-item');
        const result = [];

        rows.forEach(function(row) {
            const type = row.querySelector('.child-type').value;
            const label = row.querySelector('.child-label').value.trim();
            const val = row.querySelector('.child-url').value.trim();

            if (label) {
                result.push({
                    label: label,
                    type: type,
                    page_id: type === 'page' ? val : undefined,
                    url: type !== 'page' ? val : undefined
                });
            }
        });
        return result;
    };
    
    // === ДИНАМИЧЕСКОЕ ДОБАВЛЕНИЕ СТРОКИ ВЛОЖЕННОГО ПУНКТА ===
    window.addChildItem = function() {
        const container = document.getElementById('item-children-container');
        const template = document.getElementById('child-item-template');
        if (!container || !template) return;

        // Если внутри была заглушка "Нет вложенных пунктов" — очищаем контейнер
        if (container.querySelector('.text-center')) {
            container.innerHTML = '';
        }

        // Клонируем чистую структуру из тега template
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
    };

    // === ДИНАМИЧЕСКОЕ УДАЛЕНИЕ СТРОКИ ВЛОЖЕННОГО ПУНКТА ===
    window.removeChildItem = function(button) {
        const row = button.closest('.child-item');
        const container = document.getElementById('item-children-container');
        if (!row || !container) return;

        row.remove();

        // Если удалили последний пункт — возвращаем красивую заглушку
        if (container.querySelectorAll('.child-item').length === 0) {
            container.innerHTML = '<div class="text-center py-4 text-xs text-slate-400 border-2 border-dashed border-slate-200 rounded-lg">Нет вложенных пунктов</div>';
        }
    };

    // === ПЕРЕДАЧА ВЛОЖЕННЫХ ПУНКТОВ В ИНТЕРФЕЙС МОДАЛКИ ===
    window.loadChildItems = function(children) {
        const container = document.getElementById('item-children-container');
        if (!container) return;
        
        container.innerHTML = '';

        if (!children || children.length === 0) {
            container.innerHTML = '<div class="text-center py-4 text-xs text-slate-400 border-2 border-dashed border-slate-200 rounded-lg">Нет вложенных пунктов</div>';
            return;
        }

        // Последовательно вызываем добавление строк и наполняем данными объектов
        children.forEach(function(child) {
            window.addChildItem();
            const lastRow = container.querySelector('.child-item:last-child');
            if (lastRow) {
                lastRow.querySelector('.child-type').value = child.type || 'page';
                lastRow.querySelector('.child-label').value = child.label || '';
                lastRow.querySelector('.child-url').value = child.type === 'page' ? (child.page_id || '') : (child.url || '');
            }
        });
    };

    // === ГЛОБАЛЬНЫЕ ФУНКЦИИ ОТКРЫТИЯ/ЗАКРЫТИЯ МОДАЛКИ ===
    window.openItemModal = function() {
        const modal = document.getElementById('item-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('active');
        }
    };

    window.closeItemModal = function() {
        const modal = document.getElementById('item-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('active');
        }
        editingItemId = null;
        document.getElementById('edit-item-id').value = '';
    };

    // Первичный рендер дерева меню на основе данных из PHP при загрузке страницы
    renderMenu();

})(); // Конец IIFE обёртки
