/**
 * Конструктор страниц
 * Управление рядами, колонками и модулями
 */

/**
 * Экранирует HTML-спецсимволы для безопасной вставки в innerHTML.
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

/**
 * Экранирует строку для безопасной вставки в JS-строку внутри HTML-атрибута.
 */
function escapeJsString(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/</g, '\\x3C')
        .replace(/>/g, '\\x3E')
        .replace(/&/g, '\\x26');
}

// Защита от дублирования - проверяем, что класс ещё не объявлен
if (typeof PageConstructor === 'undefined') {
    
    // Вспомогательная функция для генерации уникального ID в JS
    function generateJsId(prefix) {
        return prefix + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
    }

    // Список всех возможных Tailwind-классов ширины
    const WIDTH_CLASSES = [
        'w-2/12', 'w-3/12', 'w-4/12', 'w-5/12', 
        'w-6/12', 'w-7/12', 'w-8/12', 'w-9/12', 'w-10/12', 
        'w-11/12', 'w-full'
    ];

    // Индекс по умолчанию (w-full - последний)
    const DEFAULT_WIDTH_INDEX = WIDTH_CLASSES.length - 1;

    function getWidthIndex(widthClass) {
        const index = WIDTH_CLASSES.indexOf(widthClass);
        return index !== -1 ? index : DEFAULT_WIDTH_INDEX;
    }
    
    class PageConstructor {
        constructor() {
            this.app = document.getElementById('constructor-app');
            if (!this.app) return;
            
            this.pageId = this.app.dataset.pageId;
            this.token = this.app.dataset.token;
            this.modules = JSON.parse(this.app.dataset.modules || '{}');
            this.data = JSON.parse(document.getElementById('constructor-data')?.value || '[]');
            
            this.currentRowId = null;
            this.currentColId = null;
            this.rowCounter = 0;
            this.colCounter = 0;
            this.moduleCounters = {};
            
            this.init();
        }
        
        init() {
            // Валидация _jsId: генерируем заново, если нет или формат неверный
            var idPattern = /^(row|col|mod)_\d+_[a-z0-9]+$/;

            this.data.forEach(row => {
                if (!row._jsId || !idPattern.test(row._jsId)) {
                    row._jsId = generateJsId('row');
                }

                row.columns?.forEach(col => {
                    if (!col._jsId || !idPattern.test(col._jsId)) {
                        col._jsId = generateJsId('col');
                    }
                    // Если нет width_class, устанавливаем w-full
                    if (!col.width_class) {
                        col.width_class = 'w-full';
                    }
                    col.modules?.forEach(mod => {
                        if (!mod._jsId || !idPattern.test(mod._jsId)) {
                            mod._jsId = generateJsId('mod');
                        }
                    });
                });
            });

            this.render();
            this.applyUserClasses();
            this.setupEventHandlers();
        }
        
        // === НАСТРОЙКА ОБРАБОТЧИКОВ ===
        setupEventHandlers() {
            this.setupIdHandlers();
            this.setupClassHandlers();
            this.applyUserClasses();
        }
        
        // === ПРИМЕНЕНИЕ ПОЛЬЗОВАТЕЛЬСКИХ КЛАССОВ ПРИ ЗАГРУЗКЕ ===
        applyUserClasses() {
            // Вспомогательная функция для добавления ! (если его нет)
            const addImportant = (cls) => {
                if (cls.startsWith('!') || cls.endsWith('!')) {
                    return cls;
                }
                return cls + '!';
            };
            
            // Применяем классы для рядов
            document.querySelectorAll('.constructor-row').forEach(rowEl => {
                const jsRowId = rowEl.dataset.rowId;
                const row = this.data.find(r => r._jsId === jsRowId);
                if (!row) return;
                
                const userClass = row.settings?.class || '';
                
                // Базовые системные классы для ряда
                const baseClasses = [
                    'constructor-row',
                    'bg-white',
                    'border',
                    'border-slate-200',
                    'rounded-xl',
                    'overflow-hidden',
                    'shadow-sm'
                ];
                
                let allClasses = [...baseClasses];
                
                // Добавляем пользовательские классы с !
                if (userClass) {
                    const parts = userClass.split(' ');
                    const importantParts = parts.map(cls => addImportant(cls));
                    allClasses.push(...importantParts);
                }
                
                rowEl.className = allClasses.join(' ');
            });
            
            // Применяем классы для колонок
            document.querySelectorAll('.constructor-column').forEach(colEl => {
                const jsColId = colEl.dataset.colId;
                
                // Находим колонку в данных
                let targetCol = null;
                for (const row of this.data) {
                    const col = row.columns?.find(c => c._jsId === jsColId);
                    if (col) {
                        targetCol = col;
                        break;
                    }
                }
                if (!targetCol) return;
                
                const userClass = targetCol.settings?.class || '';
                const widthClass = targetCol.width_class || 'w-full';
                
                // Базовые системные классы для колонки
                const baseClasses = [
                    'constructor-column',
                    'bg-slate-50/50',
                    'border',
                    'border-slate-200',
                    'rounded-lg',
                    'p-2',
                    'flex-shrink-0',
                    'min-h-[40px]'
                ];
                
                let allClasses = [...baseClasses];
                
                // Добавляем класс ширины
                allClasses.push(widthClass);
                
                // Добавляем пользовательские классы с !
                if (userClass) {
                    const parts = userClass.split(' ');
                    const importantParts = parts.map(cls => addImportant(cls));
                    allClasses.push(...importantParts);
                }
                
                colEl.className = allClasses.join(' ');
            });
        }
        
        // === СОХРАНЕНИЕ ID ПРИ ПОТЕРЕ ФОКУСА ===
        setupIdHandlers() {
            // ID рядов
            document.querySelectorAll('.row-id-input').forEach(input => {
                input.removeEventListener('blur', this._rowIdHandler);
                
                this._rowIdHandler = (e) => {
                    const jsRowId = e.target.dataset.rowId;
                    const row = this.data.find(r => r._jsId === jsRowId);
                    if (row) {
                        row.id = e.target.value.trim();
                        this.updateFormData();
                    }
                };
                input.addEventListener('blur', this._rowIdHandler);
            });
            
            // ID колонок
            document.querySelectorAll('.col-id-input').forEach(input => {
                input.removeEventListener('blur', this._colIdHandler);
                
                this._colIdHandler = (e) => {
                    const jsColId = e.target.dataset.colId;
                    for (const row of this.data) {
                        const col = row.columns?.find(c => c._jsId === jsColId);
                        if (col) {
                            col.id = e.target.value.trim();
                            this.updateFormData();
                            break;
                        }
                    }
                };
                input.addEventListener('blur', this._colIdHandler);
            });
        }
        
        // === СОХРАНЕНИЕ КЛАССОВ ПРИ ПОТЕРЕ ФОКУСА ===
        setupClassHandlers() {
            // === КЛАССЫ РЯДОВ ===
            document.querySelectorAll('.row-class-input').forEach(input => {
                input.removeEventListener('blur', this._rowClassHandler);
                
                this._rowClassHandler = (e) => {
                    const jsRowId = e.target.dataset.rowId;
                    const row = this.data.find(r => r._jsId === jsRowId);
                    if (!row) return;
                    
                    const newClass = e.target.value.trim();
                    
                    // Сохраняем в данных
                    if (!row.settings) row.settings = {};
                    row.settings.class = newClass;
                    
                    // Полностью пересобираем классы для контейнера
                    const container = e.target.closest('.constructor-row');
                    if (container) {
                        const userClass = row.settings?.class || '';
                        
                        // Базовые системные классы для ряда
                        const baseClasses = [
                            'constructor-row',
                            'bg-white',
                            'border',
                            'border-slate-200',
                            'rounded-xl',
                            'overflow-hidden',
                            'shadow-sm'
                        ];
                        
                        let allClasses = [...baseClasses];
                        
                        // Добавляем пользовательские классы с !
                        if (userClass) {
                            const parts = userClass.split(' ');
                            const importantParts = parts.map(cls => {
                                if (cls.startsWith('!') || cls.endsWith('!')) return cls;
                                return cls + '!';
                            });
                            allClasses.push(...importantParts);
                        }
                        
                        container.className = allClasses.join(' ');
                    }
                    
                    this.updateFormData();
                };
                input.addEventListener('blur', this._rowClassHandler);
            });
            
            // === КЛАССЫ КОЛОНОК ===
            document.querySelectorAll('.col-class-input').forEach(input => {
                input.removeEventListener('blur', this._colClassHandler);
                
                this._colClassHandler = (e) => {
                    const jsColId = e.target.dataset.colId;
                    const newClass = e.target.value.trim();
                    
                    // Находим колонку в данных
                    let targetCol = null;
                    for (const row of this.data) {
                        const col = row.columns?.find(c => c._jsId === jsColId);
                        if (col) {
                            targetCol = col;
                            break;
                        }
                    }
                    
                    if (!targetCol) return;
                    
                    // Проверяем, есть ли в новом классе класс ширины
                    let newWidthClass = null;
                    let cleanedNewClass = newClass;
                    if (newClass) {
                        const parts = newClass.split(' ');
                        const filteredParts = [];
                        for (const part of parts) {
                            const clean = part.replace(/^!/, '').replace(/!$/, '');
                            if (WIDTH_CLASSES.includes(clean)) {
                                newWidthClass = clean;
                            } else {
                                filteredParts.push(part);
                            }
                        }
                        cleanedNewClass = filteredParts.join(' ');
                    }
                    
                    // Если пользователь указал класс ширины - обновляем ширину колонки
                    if (newWidthClass) {
                        targetCol.width_class = newWidthClass;
                    }
                    
                    // Сохраняем классы в данных (без класса ширины, только пользовательские)
                    if (!targetCol.settings) targetCol.settings = {};
                    targetCol.settings.class = cleanedNewClass;
                    
                    // Полностью пересобираем классы для контейнера
                    const container = document.querySelector(`.constructor-column[data-col-id="${jsColId}"]`);
                    if (container) {
                        const widthClass = targetCol.width_class || 'w-full';
                        const userClass = targetCol.settings?.class || '';
                        
                        // Базовые системные классы для колонки
                        const baseClasses = [
                            'constructor-column',
                            'bg-slate-50/50',
                            'border',
                            'border-slate-200',
                            'rounded-lg',
                            'p-2',
                            'flex-shrink-0',
                            'min-h-[40px]'
                        ];
                        
                        let allClasses = [...baseClasses];
                        
                        // Добавляем класс ширины
                        allClasses.push(widthClass);
                        
                        // Добавляем пользовательские классы с !
                        if (userClass) {
                            const parts = userClass.split(' ');
                            const importantParts = parts.map(cls => {
                                if (cls.startsWith('!') || cls.endsWith('!')) return cls;
                                return cls + '!';
                            });
                            allClasses.push(...importantParts);
                        }
                        
                        container.className = allClasses.join(' ');
                        
                        // Обновляем текст в span с классом ширины
                        const widthSpan = container.querySelector('.w-12.text-center.font-mono');
                        if (widthSpan) {
                            widthSpan.textContent = widthClass;
                        }
                    }
                    
                    this.updateFormData();
                };
                input.addEventListener('blur', this._colClassHandler);
            });
        }
        
        // === ОБНОВЛЕНИЕ ДАННЫХ В ФОРМЕ ===
        updateFormData() {
            const rowsInput = document.getElementById('rows-json-input');
            if (rowsInput) {
                // Сохраняем данные как есть, с _jsId
                rowsInput.value = JSON.stringify(this.data);
            }
            const dataEl = document.getElementById('constructor-data');
            if (dataEl) {
                dataEl.value = JSON.stringify(this.data);
            }
        }
        
        // === РЯДЫ ===
        addRow(zone) {
            if (!zone) return;
            const jsId = generateJsId('row');
            this.data.push({ 
                id: '', 
                _jsId: jsId,
                zone, 
                settings: { class: '' }, 
                columns: [] 
            });
            this.render();
            this.applyUserClasses();
            this.setupEventHandlers();
            this.scrollTo(jsId);
        }
        
        deleteRow(jsRowId) {
            if (!jsRowId || !confirm('Удалить этот ряд?')) return;
            const index = this.data.findIndex(row => row._jsId === jsRowId);
            if (index === -1) return;
            this.data.splice(index, 1);
            this.render();
            this.applyUserClasses();
            this.setupEventHandlers();
        }
        
        moveRow(jsRowId, direction) {
            const index = this.data.findIndex(row => row._jsId === jsRowId);
            if (index === -1) return;
            const newIndex = direction === 'up' ? index - 1 : index + 1;
            if (newIndex < 0 || newIndex >= this.data.length) return;
            const [row] = this.data.splice(index, 1);
            this.data.splice(newIndex, 0, row);
            this.render();
            this.applyUserClasses();
            this.setupEventHandlers();
        }
        
        // === КОЛОНКИ ===
        addColumn(jsRowId) {
            const row = this.data.find(r => r._jsId === jsRowId);
            if (!row) {
                alert('Ряд не найден');
                return;
            }

            const jsId = generateJsId('col');

            // Всегда добавляем колонку с w-full
            row.columns.push({
                id: '',
                _jsId: jsId,
                width_class: 'w-full',
                settings: { class: '' },
                modules: []
            });

            this.render();
            this.applyUserClasses();
            this.setupEventHandlers();
            this.scrollTo(jsId);
            this.updateFormData();
        }
        
        deleteColumn(jsColId) {
            if (!jsColId || !confirm('Удалить эту колонку?')) return;
            for (const row of this.data) {
                const idx = row.columns.findIndex(col => col._jsId === jsColId);
                if (idx === -1) continue;
                row.columns.splice(idx, 1);
                break;
            }
            this.render();
            this.applyUserClasses();
            this.setupEventHandlers();
            this.updateFormData();
        }
        
        changeColumnWidth(jsColId, direction) {
            for (const row of this.data) {
                const col = row.columns.find(c => c._jsId === jsColId);
                if (!col) continue;
                
                // Находим текущий индекс
                const currentIndex = getWidthIndex(col.width_class);
                // Вычисляем новый индекс
                const newIndex = Math.max(0, Math.min(currentIndex + direction, WIDTH_CLASSES.length - 1));
                
                if (newIndex !== currentIndex) {
                    col.width_class = WIDTH_CLASSES[newIndex];
                    
                    // Обновляем отображение
                    const container = document.querySelector(`.constructor-column[data-col-id="${jsColId}"]`);
                    if (container) {
                        // Полностью пересобираем классы
                        const userClass = col.settings?.class || '';
                        const widthClass = col.width_class || 'w-full';
                        
                        const baseClasses = [
                            'constructor-column',
                            'bg-slate-50/50',
                            'border',
                            'border-slate-200',
                            'rounded-lg',
                            'p-2',
                            'flex-shrink-0',
                            'min-h-[40px]'
                        ];
                        
                        let allClasses = [...baseClasses];
                        allClasses.push(widthClass);
                        
                        if (userClass) {
                            const parts = userClass.split(' ');
                            const importantParts = parts.map(cls => {
                                if (cls.startsWith('!') || cls.endsWith('!')) return cls;
                                return cls + '!';
                            });
                            allClasses.push(...importantParts);
                        }
                        
                        container.className = allClasses.join(' ');
                        
                        // Обновляем текст в span
                        const widthSpan = container.querySelector('.w-12.text-center.font-mono');
                        if (widthSpan) {
                            widthSpan.textContent = widthClass;
                        }
                    }
                    
                    this.updateFormData();
                }
                break;
            }
        }
        
        // === МОДУЛИ ===
        addModule(jsRowId, moduleType) {
            const row = this.data.find(r => r._jsId === jsRowId);
            if (!row) return;
            if (row.columns.length === 0) {
                const jsId = generateJsId('col');
                row.columns.push({ 
                    id: '', 
                    _jsId: jsId, 
                    width_class: 'w-full', 
                    settings: { class: '' }, 
                    modules: [] 
                });
            }
            this.addModuleToColumn(row.columns[row.columns.length - 1]._jsId, moduleType);
        }
        
        addModuleToColumn(jsColId, moduleType) {
            let col = null;
            for (const row of this.data) {
                col = row.columns.find(c => c._jsId === jsColId);
                if (col) break;
            }
            if (!col) return;

            // Проверяем, распакован ли модуль (делаем синхронный запрос с await)
            const checkModule = async () => {
                try {
                    const currentAction = this.pageId ? 'edit' : 'create';
                    const response = await fetch('index.php?tab=pages&action=' + currentAction + '&id=' + encodeURIComponent(this.pageId) + '&tab_edit=constructor&ajax=check_module&module=' + encodeURIComponent(moduleType));
                    const data = await response.json();

                    if (!data.installed) {
                        // Модуль не распакован — распаковываем
                        const formData = new FormData();
                        formData.append('csrf_token', this.token);
                        formData.append('unpack_module', '1');
                        formData.append('module_id', moduleType);
                        const unpackResponse = await fetch('index.php?tab=pages&action=edit&id=' + encodeURIComponent(this.pageId) + '&tab_edit=constructor', {
                            method: 'POST',
                            body: formData
                        });
                        const unpackData = await unpackResponse.json();

                        if (!unpackData.success) {
                            alert('Ошибка распаковки модуля: ' + unpackData.message);
                            return;
                        }
                    }

                    // Добавляем модуль
                    this.doAddModule(jsColId, moduleType);

                } catch (error) {
                    alert('Ошибка при работе с модулем. Попробуйте обновить страницу.');
                }
            };

            checkModule();
        }

        /**
         * Добавляет модуль в колонку (внутренний метод, без проверки распаковки)
         */
        doAddModule(jsColId, moduleType) {
            let col = null;
            for (const row of this.data) {
                col = row.columns.find(c => c._jsId === jsColId);
                if (col) break;
            }
            if (!col) return;

            if (!this.moduleCounters[moduleType]) {
                this.moduleCounters[moduleType] = 0;
            }
            this.moduleCounters[moduleType]++;

            const jsId = generateJsId('mod');
            const moduleInfo = this.modules[moduleType] || {};
            const defaults = moduleInfo.defaults || {};

            col.modules.push({
                id: '',
                _jsId: jsId,
                type: moduleType,
                global: false,
                data: defaults,
                settings: {
                    class: ''
                }
            });

            this.render();
            this.applyUserClasses();
            this.setupEventHandlers();
            this.scrollTo(jsId);
            this.updateFormData();
        }
        
        deleteModule(jsModId) {
            if (!jsModId || !confirm('Удалить этот модуль?')) return;
            for (const row of this.data) {
                for (const col of row.columns) {
                    const idx = col.modules.findIndex(m => m._jsId === jsModId);
                    if (idx !== -1) {
                        col.modules.splice(idx, 1);
                        break;
                    }
                }
            }
            this.render();
            this.applyUserClasses();
            this.setupEventHandlers();
            this.updateFormData(); // Обновляем скрытое поле
        }
        
        // === МОДАЛКИ ===
        openModuleModal(jsRowId) {
            this.currentRowId = jsRowId;
            this.currentColId = null;
            const modal = document.getElementById('module-select-modal');
            if (modal) modal.classList.add('active');
        }
        
        openModuleModalForColumn(jsColId) {
            this.currentColId = jsColId;
            this.currentRowId = null;
            const modal = document.getElementById('module-select-modal');
            if (modal) modal.classList.add('active');
        }
        
        closeModuleModal() {
            const modal = document.getElementById('module-select-modal');
            if (modal) modal.classList.remove('active');
        }
        
        selectModule(moduleType) {
            if (this.currentRowId) this.addModule(this.currentRowId, moduleType);
            else if (this.currentColId) this.addModuleToColumn(this.currentColId, moduleType);
            this.closeModuleModal();
        }
        
        // === НАСТРОЙКИ МОДУЛЯ ===
        openModuleSettings(jsModId) {
            // Находим модуль в данных
            let moduleData = null;
            let moduleType = null;
            let colId = null;

            for (const row of this.data) {
                for (const col of row.columns) {
                    const mod = col.modules.find(m => m._jsId === jsModId);
                    if (mod) {
                        moduleData = mod;
                        moduleType = mod.type;
                        colId = col.id;
                        break;
                    }
                }
                if (moduleData) break;
            }

            if (!moduleData || !moduleType) {
                alert('Модуль не найден');
                return;
            }

            this._editingModuleId = jsModId;
            this._editingModuleType = moduleType;
            this._editingModuleColId = colId;

            const titleEl = document.getElementById('settings-module-name');
            const info = this.modules[moduleType] || {};
            if (titleEl) {
                titleEl.textContent = info.name || moduleType;
            }

            const modal = document.getElementById('module-settings-modal');
            if (modal) modal.classList.add('active');

            const content = document.getElementById('module-settings-content');
            if (content) {
                content.innerHTML = `
                    <div class="text-center py-8 text-slate-400">
                        <span class="icon-refresh-cw animate-spin inline-block mr-2"></span> 
                        Загрузка настроек...
                    </div>
                `;

                // Получаем CSRF-токен
                const token = document.querySelector('input[name="csrf_token"]')?.value || '';

                // Передаём И data, И settings
                const payload = {
                    data: moduleData.data || {},
                    settings: moduleData.settings || {}
                };

                // Отправляем данные через POST
                const formData = new FormData();
                formData.append('csrf_token', token);
                formData.append('module_type', moduleType);
                formData.append('data', JSON.stringify(payload));

                fetch('index.php?tab=pages&action=edit&id=' + encodeURIComponent(this.pageId) + '&tab_edit=constructor&ajax=module_settings', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        content.innerHTML = data.html;
                        this.executeScripts(content);
                    } else {
                        const errorText = data.error || 'Ошибка загрузки настроек';
                        content.innerHTML = `
                            <div class="p-4 text-rose-500 text-center">
                                <span class="icon-alert-triangle text-2xl block mb-2"></span>
                                <p class="js-module-error-text"></p>
                            </div>
                        `;
                        content.querySelector('.js-module-error-text').textContent = errorText;
                    }
                })
                .catch(() => {
                    content.innerHTML = `
                        <div class="p-4 text-rose-500 text-center">
                            <span class="icon-alert-triangle text-2xl block mb-2"></span>
                            <p>Ошибка сети при загрузке настроек</p>
                        </div>
                    `;
                });
            }
        }

        /**
         * Выполняет скрипты внутри контейнера после вставки HTML
         */
        executeScripts(container) {
            const scripts = container.querySelectorAll('script');
            const promises = [];

            scripts.forEach(function(oldScript) {
                const newScript = document.createElement('script');

                // Копируем атрибуты
                Array.from(oldScript.attributes).forEach(function(attr) {
                    newScript.setAttribute(attr.name, attr.value);
                });

                // Если есть src — загружаем внешний скрипт
                if (oldScript.src) {
                    const promise = new Promise(function(resolve, reject) {
                        newScript.onload = resolve;
                        newScript.onerror = function() {
                            resolve(); // резолвим, чтобы не ломать цепочку
                        };
                    });
                    promises.push(promise);
                    newScript.src = oldScript.src;
                } else {
                    // Инлайн-скрипт — копируем содержимое
                    newScript.textContent = oldScript.textContent;
                }

                // Заменяем старый скрипт на новый
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            // Возвращаем Promise, который выполнится после загрузки всех скриптов
            return Promise.all(promises);
        }

        closeModuleSettings() {
            const modal = document.getElementById('module-settings-modal');
            if (modal) modal.classList.remove('active');

            this._editingModuleId = null;
            this._editingModuleType = null;
            this._editingModuleColId = null;
        }

        saveModuleSettings() {
            const moduleId = this._editingModuleId;
            const moduleType = this._editingModuleType;

            if (!moduleId || !moduleType) {
                alert('Модуль не найден');
                return;
            }

            // Принудительная синхронизация Ace Editor с textarea
            const editor = window.aceEditors?.['text-editor'];
            if (editor) {
                const textarea = document.getElementById('text-editor');
                if (textarea) {
                    textarea.value = editor.getValue();
                }
            }

            const form = document.getElementById('module-settings-form');
            if (!form) {
                alert('Форма не найдена');
                return;
            }

            const formData = new FormData(form);
            const data = {};
            const settings = {};

            for (const [key, value] of formData.entries()) {
                if (key === 'csrf_token' || key === 'save_module_settings') continue;
                if (value === '') continue;

                // Если это поле настроек (settings_*) — сохраняем в settings
                if (key.startsWith('settings_')) {
                    const settingKey = key.replace('settings_', '');
                    settings[settingKey] = value;
                    continue;
                }

                // Обычные поля данных
                if (key.includes('[') && key.includes(']')) {
                    const matches = key.match(/(.+?)\[(\d+)\]\[(.+?)\]/);
                    if (matches) {
                        const [, fieldName, index, subField] = matches;
                        if (!data[fieldName]) data[fieldName] = [];
                        if (!data[fieldName][parseInt(index)]) data[fieldName][parseInt(index)] = {};
                        data[fieldName][parseInt(index)][subField] = value;
                    } else {
                        const cleanKey = key.replace(/\[\]$/, '');
                        if (!data[cleanKey]) data[cleanKey] = [];
                        data[cleanKey].push(value);
                    }
                } else {
                    data[key] = value;
                }
            }

            for (const key in data) {
                if (Array.isArray(data[key])) {
                    data[key] = data[key].filter(item => item !== undefined && item !== null && item !== '');
                }
            }

            // Добавляем content из Ace Editor вручную
            const editorContent = window.aceEditors?.['text-editor'];
            if (editorContent) {
                const content = editorContent.getValue();
                if (content) {
                    data.content = content;
                }
            }

            // Обновляем данные модуля в this.data
            let found = false;
            for (const row of this.data) {
                for (const col of row.columns) {
                    const mod = col.modules.find(m => m._jsId === moduleId);
                    if (mod) {
                        // Сохраняем данные
                        mod.data = data;
                        // Сохраняем настройки отдельно
                        mod.settings = {
                            class: settings.class || ''
                        };
                        found = true;
                        break;
                    }
                }
                if (found) break;
            }

            if (!found) {
                alert('Модуль не найден в конструкторе');
                return;
            }

            // Обновляем скрытое поле с данными
            this.updateFormData();

            // Закрываем модалку
            this.closeModuleSettings();

            alert('Настройки обновлены! Не забудьте сохранить страницу.');
        }
        
        // === РЕНДЕРИНГ ===
        render() {
            this.updateFormData();
            
            const renderZone = (zoneEl) => {
                const zoneName = zoneEl.dataset.zone;
                if (!zoneName) return;
                const container = zoneEl.querySelector('.constructor-rows');
                if (!container) return;
                
                const rows = this.data.filter(row => row.zone === zoneName);
                if (!rows.length) {
                    container.innerHTML = `<div class="text-center text-slate-400 text-sm py-6 border-2 border-dashed border-slate-200 rounded-xl">Нет рядов. Нажмите "Добавить ряд", чтобы начать.</div>`;
                    return;
                }
                container.innerHTML = rows.map(row => this.renderRow(row)).join('');
            };
            
            const zones = document.querySelectorAll('.constructor-zone');
            if (zones.length) {
                zones.forEach(renderZone);
            }
            
            // Применяем пользовательские классы после рендеринга
            this.applyUserClasses();
        }
        
        renderRow(row) {
            const cols = row.columns || [];
            const rowClass = row.settings?.class || '';
            const rowId = row.id || '';
            const jsRowId = row._jsId || generateJsId('row');
            
            return `
                <div class="constructor-row bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm ${rowClass}" 
                     data-row-id="${escapeHtml(jsRowId)}" 
                     data-zone="${escapeHtml(row.zone)}">
                    
                    <div class="bg-slate-50/50 px-3 py-2 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-3 text-xs text-slate-500">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-bold text-slate-400">ID:</span>
                                <input type="text" value="${escapeHtml(rowId)}" placeholder="(не задан)" 
                                       class="row-id-input text-[10px] px-1.5 py-0.5 border border-slate-200 rounded bg-white w-24 focus:outline-none focus:border-[var(--primary-color)] font-mono"
                                       data-row-id="${escapeHtml(jsRowId)}">
                            </div>
                            
                            <span class="text-slate-300">|</span>
                            
                            <div class="flex items-center gap-1.5 flex-1 min-w-[120px]">
                                <span class="text-[10px] font-bold text-slate-400">Класс:</span>
                                <input type="text" value="${escapeHtml(rowClass)}" placeholder="w-full, bg-slate-50, my-4..." 
                                       class="row-class-input text-[10px] px-2 py-0.5 border border-slate-200 rounded bg-white w-full max-w-[200px] focus:outline-none focus:border-[var(--primary-color)]"
                                       data-row-id="${escapeHtml(jsRowId)}">
                            </div>
                            
                            <span class="text-slate-300">|</span>
                            <span class="text-[10px] text-slate-400">Колонок: ${cols.length}</span>
                        </div>
                        
                        <div class="flex gap-0.5">
                            <button type="button" onclick="window.moveRow('${escapeJsString(jsRowId)}', 'up')" class="w-7 h-7 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Переместить вверх">
                                <span class="icon-chevron-up text-xs"></span>
                            </button>
                            <button type="button" onclick="window.moveRow('${escapeJsString(jsRowId)}', 'down')" class="w-7 h-7 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Переместить вниз">
                                <span class="icon-chevron-down text-xs"></span>
                            </button>
                            <button type="button" onclick="window.deleteRow('${escapeJsString(jsRowId)}')" class="w-7 h-7 rounded flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Удалить ряд">
                                <span class="icon-trash-2 text-xs"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="px-3 py-1.5 bg-slate-50/30 border-b border-slate-100 flex flex-wrap items-center gap-2">
                        <button type="button" onclick="window.addColumn('${escapeJsString(jsRowId)}')" class="px-2.5 py-0.5 text-[10px] bg-white border border-slate-200 text-slate-600 font-bold rounded hover:bg-slate-50 transition-all flex items-center gap-1">
                            <span class="icon-plus text-xs"></span> Колонка
                        </button>
                        <button type="button" onclick="window.openModuleModal('${escapeJsString(jsRowId)}')" class="px-2.5 py-0.5 text-[10px] bg-white border border-slate-200 text-slate-600 font-bold rounded hover:bg-slate-50 transition-all flex items-center gap-1">
                            <span class="icon-plus text-xs"></span> Модуль
                        </button>
                        <span class="text-[10px] text-slate-400 ml-1">
                            Ширина: ${cols.map(c => c.width_class || 'w-full').join(' + ')}
                        </span>
                    </div>
                    
                    <div class="constructor-columns p-2 flex flex-wrap" data-row-id="${escapeHtml(jsRowId)}">
                        ${cols.map(col => this.renderColumn(col, jsRowId)).join('')}
                    </div>
                </div>
            `;
        }

        renderColumn(col, jsRowId) {
            const colClass = col.settings?.class || '';
            const colId = col.id || '';
            const jsColId = col._jsId || generateJsId('col');
            const widthClass = col.width_class || 'w-full';

            return `
                <div class="constructor-column bg-slate-50/50 border border-slate-200 rounded-lg p-2 ${escapeHtml(widthClass)} flex-shrink-0 ${escapeHtml(colClass)}" 
                     data-col-id="${escapeHtml(jsColId)}" 
                     data-row-id="${escapeHtml(jsRowId)}">
                    
                    <div class="flex items-center justify-between mb-1.5 text-[10px] text-slate-500">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <div class="flex items-center gap-1">
                                <span class="text-[9px] font-bold text-slate-400">ID:</span>
                                <input type="text" value="${escapeHtml(jsColId)}" placeholder="(не задан)" 
                                       class="col-id-input text-[9px] px-1 py-0 border border-slate-200 rounded bg-white w-20 focus:outline-none focus:border-[var(--primary-color)] font-mono"
                                       data-col-id="${escapeHtml(jsColId)}">
                            </div>
                            
                            <span class="text-slate-300">|</span>
                            
                            <div class="flex items-center gap-0.5">
                                <button type="button" onclick="window.changeColumnWidth('${escapeJsString(jsColId)}', -1)" class="w-4 h-4 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                                    <span class="icon-chevron-left text-[8px]"></span>
                                </button>
                                <span class="text-[9px] font-bold text-slate-700 w-12 text-center font-mono">${escapeHtml(widthClass)}</span>
                                <button type="button" onclick="window.changeColumnWidth('${escapeJsString(jsColId)}', 1)" class="w-4 h-4 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                                    <span class="icon-chevron-right text-[8px]"></span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex gap-0.5">
                            <button type="button" onclick="window.deleteColumn('${escapeJsString(jsColId)}')" class="w-5 h-5 rounded flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Удалить колонку">
                                <span class="icon-x text-[9px]"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-1.5 flex items-center gap-1.5">
                        <span class="text-[9px] font-bold text-slate-400">Класс:</span>
                        <input type="text" value="${escapeHtml(colClass)}" placeholder="bg-white, p-4, border..." 
                               class="col-class-input text-[9px] px-1.5 py-0.5 border border-slate-200 rounded bg-white w-full focus:outline-none focus:border-[var(--primary-color)]"
                               data-col-id="${escapeHtml(jsColId)}">
                    </div>
                    
                    <div class="space-y-1.5 min-h-[40px]" data-col-id="${escapeHtml(jsColId)}">
                        ${(col.modules || []).map(mod => this.renderModule(mod, jsColId)).join('')}
                        <button type="button" onclick="window.openModuleModalForColumn('${escapeJsString(jsColId)}')" class="w-full py-1 text-[10px] border border-dashed border-slate-300 text-slate-400 rounded hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all flex items-center justify-center gap-0.5">
                            <span class="icon-plus text-[10px]"></span> Добавить модуль
                        </button>
                    </div>
                </div>
            `;
        }
        
        renderModule(mod, jsColId) {
            const info = this.modules[mod.type] || { name: mod.type, icon: 'icon-box', has_settings: false };
            const hasSettings = info.has_settings === true || info.has_settings === 1 || info.has_settings === 'true' || info.has_settings === '1';
            const modData = mod.data || {};
            const modId = mod.id || '';
            const jsModId = mod._jsId || generateJsId('mod');

            let preview = '';
            if (modData && Object.keys(modData).length > 0) {
                const keys = Object.keys(modData);
                if (keys.length > 0) {
                    const firstKey = keys[0];
                    const firstVal = modData[firstKey];
                    if (typeof firstVal === 'string' && firstVal.length > 0) {
                        const previewText = firstVal.substring(0, 20) + (firstVal.length > 20 ? '...' : '');
                        preview = `<span class="text-[8px] text-slate-400 truncate max-w-[60px]">${escapeHtml(previewText)}</span>`;
                    } else if (Array.isArray(firstVal) && firstVal.length > 0) {
                        preview = `<span class="text-[8px] text-slate-400">${firstVal.length} эл.</span>`;
                    }
                }
            }

            return `
                <div class="bg-white border border-slate-200 rounded-md p-1.5 group" 
                     data-module-id="${escapeHtml(jsModId)}" 
                     data-type="${escapeHtml(mod.type)}"
                     data-col-id="${escapeHtml(jsColId)}">
                    <div class="flex items-center justify-between gap-1">
                        <div class="flex items-center gap-1.5 flex-1 min-w-0">
                            <span class="text-[8px] font-mono text-slate-400">${escapeHtml(modId)}</span>
                            <span class="${info.icon || 'icon-box'} text-slate-400 text-xs"></span>
                            <span class="text-[10px] font-medium text-slate-700 truncate">${escapeHtml(info.name || mod.type)}</span>
                            ${preview}
                        </div>

                        <div class="flex items-center gap-0.5 flex-shrink-0">
                            ${hasSettings ? 
                                `<button type="button" onclick="window.openModuleSettings('${escapeJsString(jsModId)}')" class="w-5 h-5 rounded flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Настройки">
                                    <span class="icon-edit text-[10px]"></span>
                                </button>` : ''
                            }
                            <button type="button" onclick="window.deleteModule('${escapeJsString(jsModId)}')" class="w-5 h-5 rounded flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Удалить модуль">
                                <span class="icon-trash-2 text-[10px]"></span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // === ВСПОМОГАТЕЛЬНЫЕ ===
        scrollTo(id) {
            setTimeout(() => {
                const el = document.querySelector(`[data-row-id="${id}"], [data-col-id="${id}"], [data-module-id="${id}"]`);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.style.outline = '2px solid var(--primary-color)';
                    el.style.outlineOffset = '2px';
                    setTimeout(() => el.style.outline = 'none', 2000);
                }
            }, 100);
        }
        
        preview() {
            if (!this.pageId) return;

            // Валидация ID страницы: только латиница, цифры, дефис, подчёркивание, слэш
            if (!/^[a-z0-9\-_\/]+$/i.test(this.pageId)) {
                alert('Некорректный ID страницы для предпросмотра.');
                return;
            }

            window.open('/' + this.pageId + '?preview=1', '_blank');
        }
    }

    // === ИНИЦИАЛИЗАЦИЯ ===
    let constructorInstance = null;

    function initConstructor() {
        if (constructorInstance) {
            const app = document.getElementById('constructor-app');
            if (app) {
                constructorInstance.pageId = app.dataset.pageId || '';
                constructorInstance.modules = JSON.parse(app.dataset.modules || '{}');
                constructorInstance.data = JSON.parse(document.getElementById('constructor-data')?.value || '[]');
                constructorInstance.render();
                constructorInstance.applyUserClasses();
                constructorInstance.setupEventHandlers();
            }
            if (typeof window.initAccordion === 'function') {
                window.initAccordion(true);
            }
            return;
        }
        constructorInstance = new PageConstructor();
        window.constructor = constructorInstance;
        if (typeof window.initAccordion === 'function') {
            window.initAccordion();
        }
    }

    // === ГЛОБАЛЬНЫЕ ФУНКЦИИ ===
    window.addRow = (zone) => window.constructor?.addRow(zone);
    window.addColumn = (rowId) => window.constructor?.addColumn(rowId);
    window.deleteRow = (rowId) => window.constructor?.deleteRow(rowId);
    window.moveRow = (rowId, dir) => window.constructor?.moveRow(rowId, dir);
    window.deleteColumn = (colId) => window.constructor?.deleteColumn(colId);
    window.changeColumnWidth = (colId, dir) => window.constructor?.changeColumnWidth(colId, dir);
    window.deleteModule = (modId) => window.constructor?.deleteModule(modId);
    window.openModuleModal = (rowId) => window.constructor?.openModuleModal(rowId);
    window.openModuleModalForColumn = (colId) => window.constructor?.openModuleModalForColumn(colId);
    window.openModuleSettings = (modId) => window.constructor?.openModuleSettings(modId);
    window.closeModuleSettings = () => window.constructor?.closeModuleSettings();
    window.closeModuleModal = () => window.constructor?.closeModuleModal();
    window.saveModuleSettings = () => window.constructor?.saveModuleSettings();
    window.selectModule = (type) => window.constructor?.selectModule(type);
    window.previewPage = () => window.constructor?.preview();
    
    // === АККОРДЕОН ДЛЯ РАЗДЕЛОВ СТРАНИЦЫ ===
    window.toggleSection = function(headerEl) {
        const content = headerEl.nextElementSibling;
        const arrow = headerEl.querySelector('.section-arrow');

        if (!content) return;

        const isHidden = content.classList.contains('hidden');

        // Если секция открыта — закрываем её
        if (!isHidden) {
            content.classList.add('hidden');
            if (arrow) {
                arrow.className = 'section-arrow icon-chevron-right text-xs text-slate-400';
            }
            return;
        }

        // Если секция закрыта — открываем её и закрываем все остальные
        // Закрываем все секции
        const allSections = document.querySelectorAll('#page-sections .border');
        allSections.forEach(function(section) {
            const secContent = section.querySelector('.section-content');
            const secArrow = section.querySelector('.section-header .section-arrow');
            if (secContent && secContent !== content) {
                secContent.classList.add('hidden');
                if (secArrow) {
                    secArrow.className = 'section-arrow icon-chevron-right text-xs text-slate-400';
                }
            }
        });

        // Открываем текущую секцию
        content.classList.remove('hidden');
        if (arrow) {
            arrow.className = 'section-arrow icon-chevron-down text-xs text-slate-400';
        }
    };

    // === ИНИЦИАЛИЗАЦИЯ АККОРДЕОНА ===
    window.initAccordion = function(keepState = false) {
        const sections = document.querySelectorAll('#page-sections .border');

        sections.forEach(function(section, index) {
            const content = section.querySelector('.section-content');
            const header = section.querySelector('.section-header');
            const arrow = header?.querySelector('.section-arrow');

            if (!content || !header) return;

            if (keepState) {
                // Сохраняем текущее состояние
                if (content.classList.contains('hidden')) {
                    if (arrow) arrow.className = 'section-arrow icon-chevron-right text-xs text-slate-400';
                } else {
                    if (arrow) arrow.className = 'section-arrow icon-chevron-down text-xs text-slate-400';
                }
                return;
            }

            // Первая инициализация — первая секция открыта, остальные закрыты
            if (index === 0) {
                content.classList.remove('hidden');
                if (arrow) arrow.className = 'section-arrow icon-chevron-down text-xs text-slate-400';
            } else {
                content.classList.add('hidden');
                if (arrow) arrow.className = 'section-arrow icon-chevron-right text-xs text-slate-400';
            }
        });
    };
    
    window.initConstructor = initConstructor;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initConstructor);
    } else {
        initConstructor();
    }
    
    // Запускаем аккордеон при загрузке DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.initAccordion === 'function') {
                window.initAccordion();
            }
        });
    } else {
        if (typeof window.initAccordion === 'function') {
            window.initAccordion();
        }
    }
}

// Если класс уже объявлен - просто переинициализируем
else {    
    function reinitConstructor() {
        if (window.constructor) {
            const app = document.getElementById('constructor-app');
            if (app && app.dataset.pageId) {
                window.constructor.pageId = app.dataset.pageId;
                window.constructor.modules = JSON.parse(app.dataset.modules || '{}');
                window.constructor.data = JSON.parse(document.getElementById('constructor-data')?.value || '[]');
                window.constructor.render();
                window.constructor.applyUserClasses();
                window.constructor.setupEventHandlers();
            }
        } else {
            if (typeof PageConstructor !== 'undefined') {
                window.constructor = new PageConstructor();
            }
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', reinitConstructor);
    } else {
        reinitConstructor();
    }
}