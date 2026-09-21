/**
 * Экранирует HTML-спецсимволы для безопасной вставки в innerHTML.
 * @param {*} text Значение для экранирования
 * @returns {string}
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

document.addEventListener('DOMContentLoaded', () => {
    
    // НАСТРОЙКА И ИНИЦИАЛИЗАЦИЯ ACE EDITOR
    let aceEditorInstance = null;
    const aceContainer = document.getElementById('js-fm-ace-editor');
    
    if (aceContainer && typeof ace !== 'undefined') {
        // Указываем путь к локальной папке с модулями Ace
        ace.config.set('basePath', 'js/ace/');
        
        // Подключаем расширение автодополнения слов
        ace.require("ace/ext/language_tools"); 
        
        aceEditorInstance = ace.edit('js-fm-ace-editor');
        aceEditorInstance.setTheme('ace/theme/monokai');
        
        aceEditorInstance.setOptions({
            fontSize: '14px',
            enableBasicAutocompletion: true, 
            enableLiveAutocompletion: true,  
            showPrintMargin: false,
            tabSize: 4,
            useSoftTabs: true,
            useWorker: false // Отключаем воркеры синтаксиса для избежания сетевых ошибок 404
        });
        
        window.aceEditorInstance = aceEditorInstance;
    }
    
    // Фиксируем DOM-элементы модуля Проводника
    const tilesGrid = document.getElementById('js-fm-tiles-grid');
    const breadcrumbsContainer = document.getElementById('js-fm-breadcrumbs');
    const uploadBtn = document.getElementById('js-fm-upload-btn');
    const fileInput = document.getElementById('js-fm-file-input');
    
    // Переменная текущей открытой папки (путь от корня сайта)
    let fmCurrentDirectory = '';

    // Поддерживаемые графические форматы для живых превью
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif', 'ico'];

    /**
     * ГЛАВНАЯ ФУНКЦИЯ: Загрузка данных из API ядра сайта
     * @param {string} targetDir — относительный путь папки для сканирования
     */
    async function loadCmsDirectory(targetDir = '') {
        window.loadCmsDirectory = loadCmsDirectory;
        if (!tilesGrid) return;

        // Включаем пульсирующий визуальный лоадер
        tilesGrid.innerHTML = `
            <div class="col-span-full py-4 text-center text-slate-400 text-sm font-medium animate-pulse">
                <span class="icon-refresh-cw inline-block animate-spin mr-2 text-base"></span> Сканирование файловой системы...
            </div>
        `;

        try {
            // Отправляем GET-запрос к нашему авторизованному API шлюзу index.php
            const response = await fetch(`index.php?fm_api_action=1&action=list&dir=${encodeURIComponent(targetDir)}`);
            
            if (!response.ok) {
                throw new Error(`Ошибка сервера: ${response.status}`);
            }

            const data = await response.json();

            if (data.error) {
                tilesGrid.innerHTML = `<div class="col-span-full py-8 text-center text-rose-500 font-bold bg-rose-50 rounded-2xl border border-rose-100">${data.error}</div>`;
                return;
            }

            // Обновляем указатель текущей рабочей папки
            fmCurrentDirectory = data.currentDir;

            // Запускаем перерисовку хлебных крошек и плиток элементов
            renderFmBreadcrumbs(data.currentDir);
            renderFmTiles(data.folders, data.files);

        } catch (error) {
            tilesGrid.innerHTML = `<div class="col-span-full py-8 text-center text-rose-500 font-bold bg-rose-50 rounded-2xl border border-rose-100">Не удалось загрузить данные: ${error.message}</div>`;
        }
    }

    /**
     * ФУНКЦИЯ РЕНДЕРА ХЛЕБНЫХ КРОШЕК
     */
    function renderFmBreadcrumbs(currentDir) {
        if (!breadcrumbsContainer) return;

        let html = `<span class="text-slate-600 hover:text-[var(--primary-color)] cursor-pointer transition-colors js-breadcrumb-link" data-path="">Корень сайта</span>`;
        
        if (currentDir) {
            const parts = currentDir.split('/');
            let accumulatedPath = '';

            parts.forEach((part, index) => {
                accumulatedPath += (index === 0 ? '' : '/') + part;
                const isLast = (index === parts.length - 1);

                if (isLast) {
                    html += `
                        <span class="text-slate-300 mx-0.5 font-normal">/</span>
                        <span class="text-slate-400 font-medium">${escapeHtml(part)}</span>
                    `;
                } else {
                    html += `
                        <span class="text-slate-300 mx-0.5 font-normal">/</span>
                        <span class="text-slate-600 hover:text-[var(--primary-color)] cursor-pointer transition-colors js-breadcrumb-link" data-path="${escapeHtml(accumulatedPath)}">${escapeHtml(part)}</span>
                    `;
                }
            });
        }

        breadcrumbsContainer.innerHTML = html;
    }

    /**
     * ФУНКЦИЯ СБОРКИ И СОРТИРОВКИ ПЛИТОК
     */
    function renderFmTiles(folders, files) {
        if (!tilesGrid) return;

        if (folders.length === 0 && files.length === 0) {
            tilesGrid.innerHTML = `
                <div class="col-span-full py-4 text-center text-slate-400 text-sm font-medium">
                    <span class="icon-info inline-block mr-1.5 text-base text-slate-300"></span> Папка пуста
                </div>
            `;
            return;
        }

        let gridHtml = '';

        // 1. Вывод кнопки возврата ".. Назад"
        if (fmCurrentDirectory !== '') {
            const dirParts = fmCurrentDirectory.split('/');
            dirParts.pop();
            const parentDirPath = dirParts.join('/');

            gridHtml += `
                <div class="js-fm-tile-item js-folder-tile" data-path="${escapeHtml(parentDirPath)}">
                    <span class="icon-corner-left-up fm-tile-icon text-amber-500"></span>
                    <span class="fm-tile-name">.. Назад</span>
                </div>
            `;
        }

        // 2. РЕНДЕРИМ ПАПКИ (Унифицированные атрибуты)
        folders.forEach(folder => {
            const fullPath = folder.path + folder.name;
            gridHtml += `
                <div class="js-fm-tile-item js-folder-tile" data-path="${escapeHtml(fullPath)}" data-name="${escapeHtml(folder.name)}" data-size="${escapeHtml(folder.size)}" data-modified="${escapeHtml(folder.modified)}" data-perms="${escapeHtml(folder.perms)}">
                    <span class="icon-folder fm-tile-icon text-emerald-500"></span>
                    <span class="fm-tile-name" title="${escapeHtml(folder.name)}">${escapeHtml(folder.name)}</span>
                </div>
            `;
        });

        // 3. РЕНДЕРИМ ФАЙЛЫ
        files.forEach(file => {
            const isImage = imageExtensions.includes(file.ext);
            let iconBlockHtml = '';

            if (isImage) {
                const fullImgUrl = file.path + file.name;
                iconBlockHtml = `<img src="${escapeHtml(fullImgUrl)}" class="js-fm-tile-thumb w-12 h-12 object-cover rounded-lg border border-slate-200 bg-white" alt="Превью" onerror="this.outerHTML='<span class=\'icon-image fm-tile-icon text-slate-400\'></span>';">`;
            } else {
                let iconClass = 'icon-file text-slate-400';
                if (file.ext === 'php') iconClass = 'icon-code text-rose-500';
                if (file.ext === 'css') iconClass = 'icon-file-text text-pink-500';
                if (file.ext === 'js')  iconClass = 'icon-terminal text-amber-500';
                if (file.ext === 'json') iconClass = 'icon-database text-indigo-500';
                if (file.ext === 'html') iconClass = 'icon-layout text-orange-500';
                if (file.ext === 'htaccess') iconClass = 'icon-settings text-indigo-600';

                iconBlockHtml = `<span class="${iconClass} fm-tile-icon"></span>`;
            }

            gridHtml += `
                <div class="js-fm-tile-item js-file-tile" data-path="${escapeHtml(file.path)}" data-name="${escapeHtml(file.name)}" data-ext="${escapeHtml(file.ext)}" data-size="${escapeHtml(file.size)}" data-modified="${escapeHtml(file.modified)}" data-perms="${escapeHtml(file.perms)}">
                    ${iconBlockHtml}
                    <span class="fm-tile-name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span>
                </div>
            `;
        });

        tilesGrid.innerHTML = gridHtml;
    }
    
    // --- ЛОГИКА ПЕРЕХВАТА КОНТЕКСТНОГО МЕНЮ ---
    const contextMenu = document.getElementById('js-fm-context-menu');
    const menuList = document.getElementById('js-fm-menu-list');
    window.activeMenuTarget = null;
    
    /**
     * УНИВЕРСАЛЬНОЕ УПРАВЛЕНИЕ ВИДИМОСТЬЮ КОНТЕКСТНОГО МЕНЮ
     * @param {boolean} show — true (показать) / false (скрыть)
     * @param {MouseEvent|null} e — событие мыши для расчета координат (нужно только при открытии)
     */
    function toggleFmContextMenu(show, e = null) {
        if (!contextMenu) return;
        
        if (!show) {
            contextMenu.style.setProperty('display', 'none', 'important');
            return;
        }
        
        if (!e) return;
        
        // Логика отображения и позиционирования меню
        contextMenu.style.setProperty('display', 'block', 'important');
        
        if (window.innerWidth > 767) {
            const menuWidth = contextMenu.offsetWidth || 220;
            const menuHeight = contextMenu.offsetHeight || 250;
            const spaceRight = window.innerWidth - e.clientX;
            const spaceBottom = window.innerHeight - e.clientY;
            
            let targetX = spaceRight < menuWidth ? e.clientX - menuWidth : e.clientX;
            let targetY = spaceBottom < menuHeight ? e.clientY - menuHeight : e.clientY;
            
            contextMenu.style.left = `${targetX < 0 ? 10 : targetX}px`;
            contextMenu.style.top = `${targetY < 0 ? 10 : targetY}px`;
        }
    }
    // Сделаем функцию глобальной, чтобы вызывать её из любого места
    window.toggleFmContextMenu = toggleFmContextMenu;


    if (tilesGrid && contextMenu && menuList) {
        tilesGrid.addEventListener('contextmenu', (e) => {
            e.preventDefault(); // Полностью блокируем нативное меню браузера

            const tile = e.target.closest('.js-fm-tile-item');
            
            // Если кликнули на кнопку возврата Назад — меню не открываем
            if (tile && tile.innerText.includes('.. Назад')) return;

            if (tile) {
                // РЕЖИМ А: Клик по файлу/папке
                window.activeMenuTarget = {
                    type: tile.classList.contains('js-folder-tile') ? 'dir' : 'file',
                    name: tile.getAttribute('data-name') || tile.innerText.trim(),
                    path: tile.getAttribute('data-path') || '',
                    ext:  tile.getAttribute('data-ext') || '',
                    size: tile.getAttribute('data-size') || '—',
                    modified: tile.getAttribute('data-modified') || '—',
                    perms: tile.getAttribute('data-perms') || '—'
                };
                renderFmMenuItems(window.activeMenuTarget);
            } else {
                // РЕЖИМ Б: Клик по свободному месту на сетке плиток
                window.activeMenuTarget = null;
                renderFmMenuItems('empty');
            }

            // Позиционируем и показываем меню
            toggleFmContextMenu(true, e);
        });
    }

    /**
     * ГЕНЕРАТОР ПУНКТОВ МЕНЮ ПО ТИПАМ ОБЪЕКТОВ
     */
    function renderFmMenuItems(target) {
        let menuHtml = '';

        if (target === 'empty') {
            // Очищаем текущий путь от слэшей для передачи в функцию Обновить
            const cleanPath = fmCurrentDirectory.replace(/^\/+|\/+$/g, '');

            menuHtml = `
                <li onclick="openFmCreateModal('dir')"><span class="icon-folder-plus"></span>Создать папку</li>
                <li onclick="openFmCreateModal('file')"><span class="icon-file-plus"></span>Создать файл</li>
                <li onclick="toggleFmContextMenu(false); document.getElementById('js-fm-file-input')?.click();"><span class="icon-upload-cloud"></span>Загрузить файлы</li>
                <li onclick="toggleFmContextMenu(false); loadCmsDirectory('${cleanPath}');"><span class="icon-refresh-cw"></span>Обновить</li>
            `;
            menuList.innerHTML = menuHtml;
            return;
        }

        // Стандартная логика для папок и файлов
        if (target.type === 'dir') {
            menuHtml += `
                <li onclick="renameFmElement()"><span class="icon-edit"></span>Переименовать</li>
                <li onclick="openFmChmodModal()"><span class="icon-shield"></span>Изменить атрибуты</li>
                <li onclick="copyFmRelativePath()"><span class="icon-link"></span>Копировать путь</li>
                <li onclick="openFmProperties()"><span class="icon-info"></span>Свойства</li>
                <li class="fm-menu-delete" onclick="deleteFmElement()"><span class="icon-trash-2"></span>Удалить</li>
            `;
        } else {
            const isImg = imageExtensions.includes(target.ext);
            
            if (isImg) {
                menuHtml += `
                    <li onclick="openFmImageModal()"><span class="icon-eye"></span>Просмотр</li>
                    <li onclick="copyFmRelativePath()"><span class="icon-link"></span>Копировать путь</li>
                    <li onclick="renameFmElement()"><span class="icon-edit"></span>Переименовать</li>
                    <li onclick="openFmChmodModal()"><span class="icon-shield"></span>Изменить атрибуты</li>
                    <li onclick="openFmProperties()"><span class="icon-info"></span>Свойства</li>
                    <li class="fm-menu-delete" onclick="deleteFmElement()"><span class="icon-trash-2"></span>Удалить</li>
                `;
            } else {
                menuHtml += `
                    <li onclick="openFmEditor(true)"><span class="icon-eye"></span>Просмотр</li>
                    <li onclick="openFmEditor(false)"><span class="icon-code"></span>Правка</li>
                    <li onclick="renameFmElement()"><span class="icon-edit"></span>Переименовать</li>
                    <li onclick="copyFmRelativePath()"><span class="icon-link"></span>Копировать путь</li>
                    <li onclick="openFmChmodModal()"><span class="icon-shield"></span>Изменить атрибуты</li>
                    <li onclick="openFmProperties()"><span class="icon-info"></span>Свойства</li>
                    <li class="fm-menu-delete" onclick="deleteFmElement()"><span class="icon-trash-2"></span>Удалить</li>
                `;
            }
        }
        menuList.innerHTML = menuHtml;
    }

    // Закрытие меню мимо кликов
    document.addEventListener('click', (e) => {
        if (contextMenu && !contextMenu.contains(e.target)) toggleFmContextMenu(false);
    });
    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape') toggleFmContextMenu(false);
    });
    
    // --- 3. ОБЫЧНЫЕ ЛЕВЫЕ КЛИКИ (ИЗОЛИРОВАННЫЕ СЛУШАТЕЛИ) ---
    if (tilesGrid) {
        tilesGrid.addEventListener('click', (e) => {
            // Блокируем любую навигацию по сетке папок (включая ".. Назад"), если код не сохранен
            const isFolderClick = e.target.closest('.js-folder-tile');
            if (isFolderClick) {
                if (typeof checkFmEditorLeavePermission === 'function' && !checkFmEditorLeavePermission()) {
                    e.stopPropagation();
                    e.preventDefault();
                    return; // Прерываем навигацию, остаемся в редакторе
                }
            }

            // А) ПРОВЕРЯЕМ КЛИК ПО ПАПКЕ ИЛИ КНОПКЕ НАЗАД
            const folderTile = e.target.closest('.js-folder-tile');
            if (folderTile) {
                e.stopPropagation();
                let targetPath = folderTile.getAttribute('data-path') || '';
                if (targetPath.startsWith('/')) {
                    targetPath = targetPath.substring(1);
                }
                loadCmsDirectory(targetPath);
                return;
            }

            // Б) ПРОВЕРЯЕМ КЛИК ПО ФАЙЛУ (КОД ИЛИ КАРТИНКА)
            const fileTile = e.target.closest('.js-file-tile');
            if (fileTile) {
                e.stopPropagation();
                window.activeMenuTarget = {
                    type: 'file',
                    name: fileTile.getAttribute('data-name'),
                    path: fileTile.getAttribute('data-path') || '',
                    ext:  fileTile.getAttribute('data-ext') || '',
                    size: fileTile.getAttribute('data-size') || '—',
                    modified: fileTile.getAttribute('data-modified') || '—',
                    perms: fileTile.getAttribute('data-perms') || '—'
                };
                
                const ext = window.activeMenuTarget.ext;
                if (['php', 'css', 'js', 'json', 'html', 'txt', 'htaccess'].includes(ext)) {
                    openFmEditor(true);
                } else if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif', 'ico'].includes(ext)) {
                    openFmImageModal();
                }
            }
        });
    }

    // В) КЛИК ПО ХЛЕБНЫМ КРОШКАМ (Включая Корень сайта)
    if (breadcrumbsContainer) {
        breadcrumbsContainer.addEventListener('click', (e) => {
            const link = e.target.closest('.js-breadcrumb-link');
            if (link) {
                e.preventDefault();
                let breadcrumbPath = link.getAttribute('data-path') || '';
                loadCmsDirectory(breadcrumbPath);
            }
        });
    }
    
    // Подсистема универсальной множественной загрузки любых файлов проекта
    async function uploadFilesToCurrentDirectory() {
        if (!fileInput || fileInput.files.length === 0) return;
        const uploadText = document.getElementById('js-fm-upload-text');
        const uploadIcon = document.getElementById('js-fm-upload-icon');
        const files = fileInput.files;
        
        if (uploadText) uploadText.innerText = 'Загрузка...';
        if (uploadIcon) {
            uploadIcon.className = 'icon-refresh-cw text-sm inline-block animate-spin';
        }

        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('filemanager_files[]', files[i]);
        }

        let cleanPath = fmCurrentDirectory;
        if (cleanPath.startsWith('/')) cleanPath = cleanPath.substring(1);
        formData.append('dir', cleanPath);

        const systemCsrfTokenInput = document.getElementById('js-fm-csrf-token');
        if (systemCsrfTokenInput && systemCsrfTokenInput.value) {
            formData.append('csrf_token', systemCsrfTokenInput.value);
        }

        try {
            const response = await fetch('index.php?fm_api_action=1&action=upload', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error(`Ошибка сети: ${response.status}`);
            const result = await response.json();

            if (result.error) {
                showFmToast(`Ошибка загрузки: ${result.error}`, 'error');
            } else if (result.success) {
                showFmToast(result.message || 'Файлы успешно загружены!', 'success');
                loadCmsDirectory(cleanPath);
            }
        } catch (error) {
            showFmToast(`Критическая ошибка загрузки: ${error.message}`, 'error');
        } finally {
            fileInput.value = '';
            if (uploadText) uploadText.innerText = 'Загрузить файлы';
            if (uploadIcon) uploadIcon.className = 'icon-upload-cloud text-sm';
        }
    }

    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', uploadFilesToCurrentDirectory);
    }

    // ==================== ПОДСИСТЕМА СОЗДАНИЯ ПАПОК И ФАЙЛОВ ====================

    /**
     * Открытие модального окна создания элемента
     * @param {string} type — 'dir' или 'file'
     */
    function openFmCreateModal(type = 'dir') {
        const modal = document.getElementById('js-fm-create-modal');
        const titleEl = document.getElementById('js-create-title');
        const typeInput = document.getElementById('js-create-type');
        const nameInput = document.getElementById('js-create-name');
        const submitBtn = document.getElementById('js-create-submit-btn');
        const warningZone = document.getElementById('js-create-warning-zone');
        const translitWrapper = document.getElementById('js-create-translit-wrapper');
        
        if (!modal || !titleEl || !typeInput || !nameInput || !submitBtn || !warningZone) return;

        modal.style.setProperty('display', 'flex', 'important');

        typeInput.value = type;
        nameInput.value = '';
        submitBtn.innerText = 'Создать';
        submitBtn.classList.remove('bg-amber-500', 'hover:bg-amber-600');
        submitBtn.classList.add('bg-[var(--primary-color)]', 'hover:bg-[var(--primary-dark)]');
        warningZone.style.setProperty('display', 'none', 'important');
        if (translitWrapper) translitWrapper.style.setProperty('display', 'flex', 'important');

        if (type === 'dir') {
            titleEl.innerHTML = '<span class="icon-folder-plus text-amber-500"></span> Новая папка';
            nameInput.placeholder = 'Имя папки (например, uploads)';
        } else {
            titleEl.innerHTML = '<span class="icon-file-plus text-indigo-500"></span> Новый файл';
            nameInput.placeholder = 'Имя файла (например, robots.txt)';
        }

        modal.classList.add('active');
        
        if (!nameInput.dataset.listenerActive) {
            nameInput.addEventListener('input', handleFmCreateNameInput);
            nameInput.dataset.listenerActive = 'true';
        }

        setTimeout(() => nameInput.focus(), 100);
    }

    /**
     * Закрытие окна создания
     */
    function closeFmCreateModal() {
        const modal = document.getElementById('js-fm-create-modal');
        if (modal) {
            modal.classList.remove('active');
            modal.style.setProperty('display', 'none', 'important');
        }
    }

    /**
     * Живая валидация ввода имени (Поиск кириллицы и пробелов)
     */
    function handleFmCreateNameInput() {
        const nameInput = document.getElementById('js-create-name');
        const warningZone = document.getElementById('js-create-warning-zone');
        const warningText = document.getElementById('js-create-warning-text');
        const submitBtn = document.getElementById('js-create-submit-btn');
        const translitWrapper = document.getElementById('js-create-translit-wrapper');
        
        if (!nameInput || !warningZone || !warningText || !submitBtn) return;

        const value = nameInput.value;
        const hasCyrillicOrSpaces = /[а-яА-ЯёЁ\s]/.test(value);

        if (submitBtn.innerText === 'Всё равно создать' && !hasCyrillicOrSpaces) {
            if (value.includes('.')) {
                warningZone.style.setProperty('display', 'none', 'important');
                submitBtn.innerText = 'Создать';
                submitBtn.classList.remove('bg-amber-500', 'hover:bg-amber-600');
                submitBtn.classList.add('bg-[var(--primary-color)]', 'hover:bg-[var(--primary-dark)]');
            }
            return;
        }

        if (hasCyrillicOrSpaces) {
            warningText.innerText = 'Внимание: Кириллица и пробелы в именах файлов могут усложнить их вызов по прямым ссылкам в браузере.';
            warningZone.style.setProperty('display', 'grid', 'important');
            if (translitWrapper) translitWrapper.style.setProperty('display', 'flex', 'important');
        } else {
            warningZone.style.setProperty('display', 'none', 'important');
        }
    }

    /**
     * Функция интерактивной транслитерации
     */
    async function triggerInModalTranslit() {
        const nameInput = document.getElementById('js-create-name');
        const warningZone = document.getElementById('js-create-warning-zone');
        const submitBtn = document.getElementById('js-create-submit-btn');
        
        if (!nameInput || !warningZone || !submitBtn) return;

        const originalText = nameInput.value;
        if (!originalText.trim()) return;

        const formData = new FormData();
        formData.append('text', originalText);
        
        const systemCsrfTokenInput = document.getElementById('js-fm-csrf-token');
        if (systemCsrfTokenInput && systemCsrfTokenInput.value) {
            formData.append('csrf_token', systemCsrfTokenInput.value);
        }

        try {
            const response = await fetch('index.php?fm_api_action=1&action=translit', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error(`Ошибка сети: ${response.status}`);
            const result = await response.json();

            if (result.transliterated !== undefined) {
                nameInput.value = result.transliterated;
                warningZone.style.setProperty('display', 'none', 'important');
                
                if (document.getElementById('js-create-type').value === 'file' && !result.transliterated.includes('.')) {
                    triggerFmExtensionWarning();
                } else {
                    submitBtn.innerText = 'Создать';
                    submitBtn.classList.remove('bg-amber-500', 'hover:bg-amber-600');
                    submitBtn.classList.add('bg-[var(--primary-color)]', 'hover:bg-[var(--primary-dark)]');
                }
            }
        } catch (error) {
            showFmToast(`Не удалось транслитировать текст: ${error.message}`, 'error');
        }
    }

    /**
     * Вспомогательный вызов софт-вопроса для файлов без расширения
     */
    function triggerFmExtensionWarning() {
        const warningZone = document.getElementById('js-create-warning-zone');
        const warningText = document.getElementById('js-create-warning-text');
        const submitBtn = document.getElementById('js-create-submit-btn');
        const translitWrapper = document.getElementById('js-create-translit-wrapper');

        if (!warningZone || !warningText || !submitBtn) return;

        warningText.innerText = 'Вы создаете файл без расширения (например, .txt или .php). Продолжить?';
        warningZone.style.setProperty('display', 'block', 'important');
        
        if (translitWrapper) {
            translitWrapper.style.setProperty('display', 'none', 'important');
        }
        
        submitBtn.innerText = 'Всё равно создать';
        submitBtn.classList.remove('bg-[var(--primary-color)]', 'hover:bg-[var(--primary-dark)]');
        submitBtn.classList.add('bg-amber-500', 'hover:bg-amber-600');
    }

    /**
     * Обработка отправки формы создания элемента
     */
    async function submitFmCreateForm(event) {
        if (event) event.preventDefault();

        const type = document.getElementById('js-create-type').value;
        const nameInput = document.getElementById('js-create-name');
        const submitBtn = document.getElementById('js-create-submit-btn');
        
        if (!nameInput || !submitBtn) return;
        const name = nameInput.value.trim();
        if (!name) return;

        if (type === 'file' && !name.includes('.')) {
            if (submitBtn.innerText !== 'Всё равно создать') {
                triggerFmExtensionWarning();
                return;
            }
        }

        // РАБОТАЕМ НАПРЯМУЮ С ПЕРЕМЕННОЙ СКРИПТА fmCurrentDirectory
        const cleanDir = fmCurrentDirectory.replace(/^\/+|\/+$/g, '');

        const action = type === 'dir' ? 'create_dir' : 'create_file';
        const formData = new FormData();
        formData.append('name', name);
        formData.append('dir', cleanDir);

        const systemCsrfTokenInput = document.getElementById('js-fm-csrf-token');
        if (systemCsrfTokenInput && systemCsrfTokenInput.value) {
            formData.append('csrf_token', systemCsrfTokenInput.value);
        }

        try {
            const response = await fetch(`index.php?fm_api_action=1&action=${action}`, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error(`Ошибка сети: ${response.status}`);
            const result = await response.json();

            closeFmCreateModal();

            if (result.error) {
                showFmToast(`Внимание: ${result.error}`, 'error');
            } else if (result.success) {
                showFmToast(result.success, 'success');
                
                // Перезагружаем текущую папку через функцию обновления
                loadCmsDirectory(cleanDir);
            }
        } catch (error) {
            closeFmCreateModal();
            showFmToast(`Критическая ошибка создания: ${error.message}`, 'error');
        }
    }

    // Экспортируем функции в глобальную область
    window.openFmCreateModal = openFmCreateModal;
    window.closeFmCreateModal = closeFmCreateModal;
    window.triggerInModalTranslit = triggerInModalTranslit;
    window.submitFmCreateForm = submitFmCreateForm;

    // ==================== ПОДСИСТЕМА ЗАЩИТЫ РЕДАКТОРА ACE ОТ ПОТЕРИ КОДA ====================

    /**
     * Проверка наличия несохраненных изменений в редакторе кода
     * @returns {boolean} true — если изменений нет или пользователь подтвердил уход, false — если уход заблокирован
     */
    function checkFmEditorLeavePermission() {
        const dirtyMarker = document.getElementById('js-fm-dirty-marker');
        // Если маркер существует и он виден (не содержит класс hidden) — значит код изменен
        if (dirtyMarker && !dirtyMarker.classList.contains('hidden')) {
            return confirm('У вас есть несохраненные изменения в редакторе кода. Вы уверены, что хотите покинуть страницу без сохранения?');
        }
        return true;
    }

    // КОНТУР 1: Защита от обновления страницы (F5) или закрытия вкладки браузера
    window.addEventListener('beforeunload', (e) => {
        const dirtyMarker = document.getElementById('js-fm-dirty-marker');
        if (dirtyMarker && !dirtyMarker.classList.contains('hidden')) {
            e.preventDefault();
            e.returnValue = ''; // Вызывает системный диалог подтверждения браузера
        }
    });

    // КОНТУР 2: Защита от перехода по вкладкам сайдбара админки (?tab=...)
    document.querySelectorAll('.sidebar-menu-link').forEach(link => {
        link.addEventListener('click', (e) => {
            if (!checkFmEditorLeavePermission()) {
                e.preventDefault(); // Блокируем переход на другие вкладки
            }
        });
    });

    // КОНТУР 3: Защита от кликов по хлебным крошкам навигации Проводника
    if (breadcrumbsContainer) {
        breadcrumbsContainer.addEventListener('click', (e) => {
            // Перехватываем событие до того, как выполнится логика перехода
            if (e.target.closest('.js-breadcrumb-link')) {
                if (!checkFmEditorLeavePermission()) {
                    e.stopImmediatePropagation(); // Глушим выполнение родного AJAX-запроса навигации
                    e.preventDefault();
                }
            }
        }, true); // Флаг true активирует фазу перехвата клика (capturing)
    }

    // ЗАПУСК СТАРТА: Сканируем корень сайта при входе в Проводник
    loadCmsDirectory('');
});

// Д) Открытие файла во встроенном Ace Editor (Правка / Просмотр)
async function openFmEditor(isReadOnly = false) {
    toggleFmContextMenu(false);
    if (!window.activeMenuTarget || !window.aceEditorInstance) return;
    const browserBlock = document.getElementById('js-fm-main-browser');
    const editorBlock = document.getElementById('js-fm-editor-container');
    const filenameLabel = document.getElementById('js-fm-editor-filename');
    const saveBtn = document.getElementById('js-fm-save-btn');
    const editModeBtn = document.getElementById('js-fm-edit-mode-btn');
    const historyGroup = document.getElementById('js-fm-history-group');
    const dirtyMarker = document.getElementById('js-fm-dirty-marker');

    let cleanPath = window.activeMenuTarget.path;
    if (cleanPath.startsWith('/')) cleanPath = cleanPath.substring(1);

    try {
        const formData = new FormData();
        formData.append('name', window.activeMenuTarget.name);
        formData.append('path', cleanPath);

        const systemCsrfTokenInput = document.getElementById('js-fm-csrf-token');
        if (systemCsrfTokenInput && systemCsrfTokenInput.value) {
            formData.append('csrf_token', systemCsrfTokenInput.value);
        }

        const response = await fetch('index.php?fm_api_action=1&action=get_code', {
            method: 'POST',
            body: formData
        });
        if (!response.ok) throw new Error(`Ошибка сети: ${response.status}`);
        
        const result = await response.json();
        if (result.error) {
            showFmToast(`Ошибка: ${result.error}`, 'error');
            return;
        }

        browserBlock.classList.add('hidden');
        editorBlock.classList.remove('hidden');

        // Скрываем зону загрузки файлов при входе в редактор кода
        const uploadZone = document.getElementById('js-fm-upload-zone');
        if (uploadZone) uploadZone.classList.add('hidden');

        // Скрываем панель создания элементов при входе в редактор
        const structureActions = document.getElementById('js-fm-structure-actions');
        if (structureActions) structureActions.classList.add('hidden');

        if (dirtyMarker) dirtyMarker.classList.add('hidden');
        filenameLabel.innerText = window.activeMenuTarget.name;

        window.aceEditorInstance.setValue(result.code, -1);
        window.aceEditorInstance.getSession().getUndoManager().reset();
        updateFmHistoryButtonsState(); // Синхронизируем состояние стрелок

        let aceMode = 'ace/mode/text';
        if (window.activeMenuTarget.ext === 'php')  aceMode = 'ace/mode/php';
        if (window.activeMenuTarget.ext === 'css')  aceMode = 'ace/mode/css';
        if (window.activeMenuTarget.ext === 'js')   aceMode = 'ace/mode/javascript';
        if (window.activeMenuTarget.ext === 'json') aceMode = 'ace/mode/json';
        if (window.activeMenuTarget.ext === 'html') aceMode = 'ace/mode/html';
        if (window.activeMenuTarget.ext === 'htaccess') aceMode = 'ace/mode/text';
        
        window.aceEditorInstance.getSession().setMode(aceMode);
        window.aceEditorInstance.setReadOnly(isReadOnly);
        
        if (isReadOnly) {
            saveBtn.classList.add('hidden');
            if (historyGroup) historyGroup.classList.add('hidden'); 
            if (editModeBtn) editModeBtn.classList.remove('hidden'); 
        } else {
            if (editModeBtn) editModeBtn.classList.add('hidden');
            if (historyGroup) historyGroup.classList.remove('hidden'); 
            saveBtn.classList.remove('hidden'); 
        }

        window.aceEditorInstance.getSession().off('change');
        window.aceEditorInstance.getSession().on('change', () => {
            if (!window.aceEditorInstance.getReadOnly()) {
                if (dirtyMarker) dirtyMarker.classList.remove('hidden');
                updateFmHistoryButtonsState(); // Пересчитываем стрелки при вводе символов
            }
        });

        // Запуск изолированных хоткеев истории Ace (Ctrl+Z / Ctrl+Y) без перехвата мыши
        window.aceEditorInstance.commands.addCommand({
            name: 'trigger_undo',
            bindKey: { win: 'Ctrl-Z', mac: 'Command-Z' },
            exec: function(editor) {
                if (!editor.getReadOnly()) {
                    editor.undo();
                    updateFmHistoryButtonsState();
                }
            }
        });
        window.aceEditorInstance.commands.addCommand({
            name: 'trigger_redo',
            bindKey: { win: 'Ctrl-Y', mac: 'Command-Y' },
            exec: function(editor) {
                if (!editor.getReadOnly()) {
                    editor.redo();
                    updateFmHistoryButtonsState();
                }
            }
        });
        window.aceEditorInstance.commands.addCommand({
            name: 'save',
            bindKey: { win: 'Ctrl-S', mac: 'Command-S' },
            exec: function() {
                if (!window.aceEditorInstance.getReadOnly()) saveFmCode();
            }
        });

    } catch (error) {
        showFmToast(`Не удалось открыть файл: ${error.message}`, 'error');
    }
}

// Е) Закрытие редактора кода
function closeFmEditor() {
    // Проверяем наличие несохраненных изменений напрямую через DOM
    const dirtyMarker = document.getElementById('js-fm-dirty-marker');
    if (dirtyMarker && !dirtyMarker.classList.contains('hidden')) {
        const leave = confirm('У вас есть несохраненные изменения в редакторе кода. Вы уверены, что хотите покинуть страницу без сохранения?');
        if (!leave) {
            return; // Нажали "Отмена" — прерываем закрытие, остаемся в файле
        }
    }
    
    const browserBlock = document.getElementById('js-fm-main-browser');
    const editorBlock = document.getElementById('js-fm-editor-container');
    if (browserBlock && editorBlock) {
        editorBlock.classList.add('hidden');
        browserBlock.classList.remove('hidden');
        
        // Снова показываем зону загрузки при возврате к сетке файлов
        const uploadZone = document.getElementById('js-fm-upload-zone');
        if (uploadZone) uploadZone.classList.remove('hidden');

        // Снова показываем панель создания при возврате к сетке файлов
        const structureActions = document.getElementById('js-fm-structure-actions');
        if (structureActions) structureActions.classList.remove('hidden');
    }
}

// Ж) AJAX-Сохранение измененного кода на сервер
async function saveFmCode() {
    if (!window.activeMenuTarget || !window.aceEditorInstance) return;

    let cleanPath = window.activeMenuTarget.path;
    if (cleanPath.startsWith('/')) cleanPath = cleanPath.substring(1);
    const updatedCode = window.aceEditorInstance.getValue();

    const formData = new FormData();
    formData.append('name', window.activeMenuTarget.name);
    formData.append('path', cleanPath);
    formData.append('code', updatedCode);

    const systemCsrfTokenInput = document.getElementById('js-fm-csrf-token');
    if (systemCsrfTokenInput && systemCsrfTokenInput.value) {
        formData.append('csrf_token', systemCsrfTokenInput.value);
    }

    try {
        const response = await fetch('index.php?fm_api_action=1&action=save_code', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) throw new Error(`Ошибка сети: ${response.status}`);
        const result = await response.json();

        if (result.error) {
            showFmToast(`Ошибка сохранения: ${result.error}`, 'error');
        } else if (result.success) {
            const dirtyMarker = document.getElementById('js-fm-dirty-marker');
            if (dirtyMarker) dirtyMarker.classList.add('hidden');
            showFmToast(result.success, 'success');
        }
    } catch (error) {
        showFmToast(`Критическая ошибка сохранения: ${error.message}`, 'error');
    }
}

// З) ИНИЦИАЛИЗАЦИЯ КЛИКОВ ДЛЯ КНОПОК НАВИГАЦИИ РЕДАКТОРА
const backBtn = document.getElementById('js-fm-back-btn');
const saveBtn = document.getElementById('js-fm-save-btn');
const editModeBtn = document.getElementById('js-fm-edit-mode-btn');
const undoBtn = document.getElementById('js-fm-undo-btn');
const redoBtn = document.getElementById('js-fm-redo-btn');

if (backBtn) backBtn.addEventListener('click', closeFmEditor);
if (saveBtn) saveBtn.addEventListener('click', saveFmCode);

if (undoBtn) {
    undoBtn.addEventListener('click', () => {
        if (window.aceEditorInstance) {
            window.aceEditorInstance.undo();
            updateFmHistoryButtonsState();
        }
    });
}
if (redoBtn) {
    redoBtn.addEventListener('click', () => {
        if (window.aceEditorInstance) {
            window.aceEditorInstance.redo();
            updateFmHistoryButtonsState();
        }
    });
}
if (editModeBtn && saveBtn) {
    editModeBtn.addEventListener('click', () => {
        if (!window.aceEditorInstance) return;
        const filenameLabel = document.getElementById('js-fm-editor-filename');
        window.aceEditorInstance.setReadOnly(false);
        
        if (filenameLabel && window.activeMenuTarget) {
            filenameLabel.innerText = window.activeMenuTarget.name;
        }
        
        const historyGroup = document.getElementById('js-fm-history-group');
        if (historyGroup) historyGroup.classList.remove('hidden');
        
        editModeBtn.classList.add('hidden');
        saveBtn.classList.remove('hidden');
        window.aceEditorInstance.focus();
    });
}

// И) Вспомогательная функция проверки стека истории Ace Editor
function updateFmHistoryButtonsState() {
    if (!window.aceEditorInstance) return;
    const undoBtn = document.getElementById('js-fm-undo-btn');
    const redoBtn = document.getElementById('js-fm-redo-btn');
    const undoManager = window.aceEditorInstance.getSession().getUndoManager();
    
    if (undoBtn) undoBtn.disabled = !undoManager.hasUndo();
    if (redoBtn) redoBtn.disabled = !undoManager.hasRedo();
}

// К) Свойства
function openFmProperties() {
    toggleFmContextMenu(false);
    if (!window.activeMenuTarget) return;
    const isDir = window.activeMenuTarget.type === 'dir';
    document.getElementById('js-prop-filename').innerText = window.activeMenuTarget.name;
    document.getElementById('js-prop-type').innerText = isDir ? 'Папка директории' : `Файл (${window.activeMenuTarget.ext.toUpperCase()})`;
    document.getElementById('js-prop-size').innerText = isDir ? '— (Каталог)' : window.activeMenuTarget.size;
    document.getElementById('js-prop-modified').innerText = window.activeMenuTarget.modified;
    document.getElementById('js-prop-perms').innerText = window.activeMenuTarget.perms;
    const propsModal = document.getElementById('js-fm-props-modal');
    if (propsModal) propsModal.classList.add('active');
}
function closeFmPropsModal() {
    const propsModal = document.getElementById('js-fm-props-modal');
    if (propsModal) propsModal.classList.remove('active');
}

// Л) Безопасное удаление
async function deleteFmElement() {
    toggleFmContextMenu(false);
    if (!window.activeMenuTarget) return;
    const confirmMsg = window.activeMenuTarget.type === 'dir' 
        ? `Вы уверены, что хотите НАВСЕГДА удалить папку "${window.activeMenuTarget.name}"?`
        : `Вы уверены, что хотите НАВСЕГДА удалить файл "${window.activeMenuTarget.name}"?`;
    if (!confirm(confirmMsg)) return;

    let cleanPath = window.activeMenuTarget.path.replace(/^\/+|\/+$/g, '');

    // КОРРЕКЦИЯ ДЛЯ ПАПОК: если это папка, её path содержит её же имя на конце (например, "images/prod")
    if (window.activeMenuTarget.type === 'dir') {
        const pathParts = cleanPath.split('/');
        if (pathParts[pathParts.length - 1] === window.activeMenuTarget.name) {
            pathParts.pop(); // Удаляем имя самой папки с конца пути
            cleanPath = pathParts.join('/'); // Получаем чистый родительский путь (например, "images")
        }
    }

    const formData = new FormData();
    formData.append('name', window.activeMenuTarget.name);
    formData.append('path', cleanPath);

    const systemCsrfTokenInput = document.getElementById('js-fm-csrf-token');
    if (systemCsrfTokenInput && systemCsrfTokenInput.value) {
        formData.append('csrf_token', systemCsrfTokenInput.value);
    }

    try {
        const response = await fetch('index.php?fm_api_action=1&action=delete', { method: 'POST', body: formData });
        if (!response.ok) throw new Error(`Ошибка сети: ${response.status}`);
        const result = await response.json();
        if (result.error) {
            showFmToast(`Внимание: ${result.error}`, 'error');
        } else if (result.success) {
            showFmToast(result.success, 'success');
            window.loadCmsDirectory(cleanPath);
        }
    } catch (error) {
        showFmToast(`Критическая ошибка: ${error.message}`, 'error');
    }
}

// M) Переименование
async function renameFmElement() {
    toggleFmContextMenu(false);
    if (!window.activeMenuTarget) return;
    const newName = prompt(`Введите новое имя для "${window.activeMenuTarget.name}":`, window.activeMenuTarget.name);
    if (newName === null) return;
    const trimmedNewName = newName.trim();
    if (trimmedNewName === '' || trimmedNewName === window.activeMenuTarget.name) return;

    let cleanPath = window.activeMenuTarget.path.replace(/^\/+|\/+$/g, '');

    // КОРРЕКЦИЯ ДЛЯ ПАПОК: извлекаем чистый родительский путь
    if (window.activeMenuTarget.type === 'dir') {
        const pathParts = cleanPath.split('/');
        if (pathParts[pathParts.length - 1] === window.activeMenuTarget.name) {
            pathParts.pop();
            cleanPath = pathParts.join('/');
        }
    }

    const formData = new FormData();
    formData.append('old_name', window.activeMenuTarget.name);
    formData.append('new_name', trimmedNewName);
    formData.append('path', cleanPath);

    const systemCsrfTokenInput = document.getElementById('js-fm-csrf-token');
    if (systemCsrfTokenInput && systemCsrfTokenInput.value) formData.append('csrf_token', systemCsrfTokenInput.value);

    try {
        const response = await fetch('index.php?fm_api_action=1&action=rename', { method: 'POST', body: formData });
        if (!response.ok) throw new Error(`Ошибка сети: ${response.status}`);
        const result = await response.json();
        if (result.error) {
            showFmToast(`Внимание: ${result.error}`, 'error');
        } else if (result.success) {
            showFmToast(result.success, 'success');
            window.loadCmsDirectory(cleanPath);
        }
    } catch (error) {
        showFmToast(`Критическая ошибка: ${error.message}`, 'error');
    }
}

// Н) Копирование путей
function copyFmRelativePath() {
    toggleFmContextMenu(false);
    if (!window.activeMenuTarget) return;
    let cleanPath = window.activeMenuTarget.path;
    if (cleanPath.startsWith('/')) cleanPath = cleanPath.substring(1);
    const finalUrl = cleanPath + window.activeMenuTarget.name;
    navigator.clipboard.writeText(finalUrl).then(() => {
        showFmToast('Путь скопирован в буфер обмена: ' + finalUrl, 'success');
    }).catch(err => {
        showFmToast(`Не удалось скопировать путь: ${err}`, 'error');
    });
}
        
// О) Функция открытия окна быстрого просмотра картинок
function openFmImageModal() {
    toggleFmContextMenu(false);
    if (!window.activeMenuTarget) return;    
    const imgModal = document.getElementById('js-fm-image-modal');
    const previewImg = document.getElementById('js-fm-preview-image');
    
    if (imgModal && previewImg) {
        const fullImgUrl = window.activeMenuTarget.path + window.activeMenuTarget.name;
        
        previewImg.src = fullImgUrl; 
        imgModal.classList.add('active');
    }
}

// П) Закрытие окна просмотра картинок
function closeFmImageModal() {
    const imgModal = document.getElementById('js-fm-image-modal');
    const previewImg = document.getElementById('js-fm-preview-image');
    
    if (imgModal) {
        imgModal.classList.remove('active');
        if (previewImg) previewImg.src = ''; 
    }
}

function showFmToast(message, type = 'success') {
    const container = document.getElementById('js-fm-toast-container');
    if (!container) return;

    container.classList.remove('hidden');

    // Цвета/иконки как в renderFlash
    const typeClasses = {
        success: 'text-emerald-800 bg-emerald-50 border-emerald-100',
        error:   'text-rose-800 bg-rose-50 border-rose-100',
        warning: 'text-amber-800 bg-amber-50 border-amber-100',
        info:    'text-blue-800 bg-blue-50 border-blue-100'
    };
    const iconClasses = {
        success: 'icon-circle-check',
        error:   'icon-circle-x',
        warning: 'icon-circle-alert',
        info:    'icon-info'
    };

    const typeClass = typeClasses[type] || typeClasses.success;
    const iconClass = iconClasses[type] || iconClasses.success;
    const duration = 5;
    const isAutoClose = duration > 0;

    // Создаём тост
    const toast = document.createElement('div');
    toast.className = 'bg-white border rounded-xl shadow-xl p-5 animate-slide-in pointer-events-auto ' + typeClass;
    toast.setAttribute('data-duration', duration);
    toast.setAttribute('data-autoclose', isAutoClose ? 'true' : 'false');

    let innerHTML = `
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center bg-white/50">
                <span class="${iconClass} !text-4xl"></span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium"></p>
            </div>
            <button type="button" class="close-toast-btn flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-current/50 hover:text-current/80 hover:bg-white/20 transition-colors cursor-pointer">
                <span class="icon-x text-lg"></span>
            </button>
        </div>
    `;

    if (isAutoClose) {
        innerHTML += `
            <div class="mt-3 w-full h-1 bg-white/30 rounded-full overflow-hidden">
                <div class="toast-progress h-full rounded-full bg-current/50" style="width: 100%;"></div>
            </div>
        `;
    }

    toast.innerHTML = innerHTML;
    // Вставляем текст через textContent — защита от XSS
    toast.querySelector('p').textContent = message;

    // Вставляем в контейнер
    container.appendChild(toast);

    // Кнопка закрытия
    const closeBtn = toast.querySelector('.close-toast-btn');
    closeBtn.addEventListener('click', function() {
        closeFmToast(toast);
    });

    // Автозакрытие + прогресс-бар
    if (isAutoClose) {
        const progressBar = toast.querySelector('.toast-progress');
        if (progressBar) {
            progressBar.style.animation = 'toastProgress ' + duration + 's linear forwards';
        }
        setTimeout(function() {
            closeFmToast(toast);
        }, duration * 1000);
    }
}

function closeFmToast(toast) {
    if (!toast) return;
    toast.style.transition = 'all 0.3s ease';
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100px)';
    setTimeout(function() {
        if (toast && toast.parentNode) {
            toast.remove();
        }
        const container = document.getElementById('js-fm-toast-container');
        if (container && container.children.length === 0) {
            container.classList.add('hidden');
        }
    }, 300);
}

// ==================== ПОДСИСТЕМА УПРАВЛЕНИЯ ПРАВАМИ (CHMOD) ====================

function openFmChmodModal() {
    toggleFmContextMenu(false);
    if (!window.activeMenuTarget) return;

    document.getElementById('js-chmod-filename').innerText = window.activeMenuTarget.name;

    let currentPerms = window.activeMenuTarget.perms; 
    if (!currentPerms || currentPerms === '—' || currentPerms.length < 3) {
        currentPerms = "0644";
    }

    setFmCheckboxesByOctal(currentPerms);
    
    const chmodModal = document.getElementById('js-fm-chmod-modal');
    if (chmodModal) chmodModal.classList.add('active');
}

function closeFmChmodModal() {
    const chmodModal = document.getElementById('js-fm-chmod-modal');
    if (chmodModal) chmodModal.classList.remove('active');
}

function calculateFmChmod() {
    const groups = ['u', 'g', 'o'];
    const types = ['r', 'w', 'x'];
    let result = "0";

    groups.forEach(g => {
        let sum = 0;
        types.forEach(t => {
            const cb = document.getElementById(`chmod-${g}-${t}`);
            if (cb && cb.checked) {
                sum += parseInt(cb.value);
            }
        });
        result += sum.toString();
    });

    document.getElementById('js-chmod-octal').value = result;
}

function setFmCheckboxesByOctal(octalStr) {
    if (octalStr.length === 4) octalStr = octalStr.substring(1);
    
    const groups = ['u', 'g', 'o'];
    for (let i = 0; i < 3; i++) {
        const val = parseInt(octalStr[i]) || 0;
        const g = groups[i];
        
        document.getElementById(`chmod-${g}-r`).checked = (val & 4) === 4;
        document.getElementById(`chmod-${g}-w`).checked = (val & 2) === 2;
        document.getElementById(`chmod-${g}-x`).checked = (val & 1) === 1;
    }
    document.getElementById('js-chmod-octal').value = "0" + octalStr;
}

async function submitFmChmodForm(event) {
    event.preventDefault();
    if (!window.activeMenuTarget) return;

    let cleanPath = window.activeMenuTarget.path;
    if (cleanPath.startsWith('/')) cleanPath = cleanPath.substring(1);
    
    const mode = document.getElementById('js-chmod-octal').value;

    const formData = new FormData();
    formData.append('name', window.activeMenuTarget.name);
    formData.append('path', cleanPath);
    formData.append('mode', mode);

    const systemCsrfTokenInput = document.getElementById('js-fm-csrf-token');
    if (systemCsrfTokenInput && systemCsrfTokenInput.value) {
        formData.append('csrf_token', systemCsrfTokenInput.value);
    }

    try {
        const response = await fetch('index.php?fm_api_action=1&action=chmod', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) throw new Error(`Ошибка сети: ${response.status}`);
        const result = await response.json();

        // МГНОВЕННО ЗАКРЫВАЕМ ОКНО CHMOD ПРИ ПОЛУЧЕНИИ ЛЮБОГО ОТВЕТА
        closeFmChmodModal();

        if (result.error) {
            showFmToast(`Внимание: ${result.error}`, 'error');
        } else if (result.success) {
            showFmToast(result.success, 'success');
            
            if (typeof window.loadCmsDirectory === 'function') {
                window.loadCmsDirectory(cleanPath);
            }
        }
    } catch (error) {
        // Закрываем окно даже в случае критического сетевого сбоя
        closeFmChmodModal();
        showFmToast(`Критическая ошибка CHMOD: ${error.message}`, 'error');
    }
}
