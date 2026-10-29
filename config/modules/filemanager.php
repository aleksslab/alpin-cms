<?php
// --- config/modules/filemanager.php (Финальная нативная разметка) ---
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) { 
    die('Доступ запрещен'); 
}
?>

<style>
@keyframes toastProgress {
    from { width: 100%; }
    to { width: 0%; }
}
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
.animate-slide-in {
    animation: slideIn 0.3s ease forwards;
}
</style>

<!-- ХРАНИЛИЩЕ CSRF-ТОКЕНА ДЛЯ СИСТЕМНЫХ AJAX-ОПЕРАЦИЙ ПРОВОДНИКА -->
<input type="hidden" id="js-fm-csrf-token" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

<!-- ЗАГОЛОВОК СЕКЦИИ -->
<div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center mb-6">
    <div id="js-fm-toast-container" class="fixed top-24 right-4 md:right-6 z-[9999] flex flex-col gap-3 max-w-md w-full pointer-events-none hidden"></div>
    <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Проводник</h1>
        <p class="text-slate-500 text-sm">Управление структурой проекта, загрузка медиа и прямое редактирование файлов.</p>
    </div>
</div>

<!-- ХЛЕБНЫЕ КРОШКИ НАВИГАЦИИ -->
<div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex items-center gap-2 mb-8 text-sm font-bold text-slate-600">
    <span class="icon-folder text-base text-[var(--primary-color)] shrink-0"></span>
    <div id="js-fm-breadcrumbs" class="flex items-center gap-1 flex-wrap">
        <span class="text-slate-400 cursor-pointer hover:text-slate-700">Корень сайта</span>
    </div>
</div>

<!-- ПРЕДУПРЕЖДЕНИЕ О СИСТЕМНЫХ ФАЙЛАХ -->
<div class="bg-slate-50 border border-slate-200 text-slate-700 p-2 rounded-xl text-sm flex items-center gap-3 mb-4">
    <span class="icon-alert-triangle text-slate-500 text-base flex-shrink-0"></span>
    <div>
        <span class="font-bold">Внимание:</span> 
        Удаление или изменение системных файлов 
        (<span class="font-mono bg-white border border-slate-200 p-1 rounded-md">index.php</span>, 
        <span class="font-mono bg-white border border-slate-200 p-1 rounded-md">config.php</span>, 
        <span class="font-mono bg-white border border-slate-200 p-1 rounded-md">.htaccess</span>) 
        может нарушить работу сайта. Будьте осторожны.
    </div>
</div>

<!-- ПАНЕЛЬ УПРАВЛЕНИЯ СТРУКТУРОЙ ДИРЕКТОРИИ -->
<div id="js-fm-structure-actions" class="flex flex-col md:flex-row items-stretch sm:items-center gap-2 mb-6 bg-slate-50/50 p-3 rounded-xl border border-slate-100">
    
    <!-- ЗОНА ЗАГРУЗКИ ФАЙЛОВ (Интегрирована первой кнопкой в ряд) -->
    <div id="js-fm-upload-zone" class="flex">
        <!-- Скрытый инпут для множественного выбора файлов -->
        <input type="file" id="js-fm-file-input" name="filemanager_files[]" multiple class="hidden">
        
        <!-- Кнопка-триггер (теперь адаптивная w-full sm:w-auto) -->
        <button type="button" id="js-fm-upload-btn" class="w-full sm:w-auto bg-[var(--primary-color)] text-white font-bold px-5 py-2.5 rounded-xl hover:bg-[var(--primary-dark)] transition-all cursor-pointer text-xs shadow-lg shadow-[var(--primary-color)]/20 h-10 flex items-center justify-center gap-2 select-none">
            <span class="icon-upload-cloud text-sm" id="js-fm-upload-icon"></span> 
            <span id="js-fm-upload-text">Загрузить файлы</span>
        </button>
    </div>

    <!-- КНОПКА: СОЗДАТЬ ПАПКУ -->
    <button type="button" onclick="openFmCreateModal('dir')" class="px-4 py-2 bg-white border border-slate-200 hover:border-slate-300 text-slate-700 text-xs font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-1.5 cursor-pointer h-10 select-none">
        <span class="icon-folder-plus text-sm text-amber-500"></span> Создать папку
    </button>
    
    <!-- КНОПКА: СОЗДАТЬ ФАЙЛ -->
    <button type="button" onclick="openFmCreateModal('file')" class="px-4 py-2 bg-white border border-slate-200 hover:border-slate-300 text-slate-700 text-xs font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-1.5 cursor-pointer h-10 select-none">
        <span class="icon-file-plus text-sm text-indigo-500"></span> Создать файл
    </button>
</div>

<!-- ГЛАВНЫЙ ЭКРАН: СЕТКА ПЛИТОК -->
<div id="js-fm-main-browser" class="space-y-4">
    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Содержимое директории</h3>
    <div id="js-fm-tiles-grid" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4 w-full">
        <!-- Заглушка загрузки -->
        <div class="col-span-full py-4 text-center text-slate-400 text-sm font-medium animate-pulse">
            <span class="icon-refresh-cw inline-block animate-spin mr-2 text-base"></span> Сканирование файловой системы...
        </div>
    </div>
</div>

<!-- ОКНО ВСТРОЕННОГО РЕДАКТОРА КОДА ACE EDITOR -->
<div id="js-fm-editor-container" class="hidden space-y-5 animate-fade-in">
    <div class="flex flex-col md:flex-row gap-4 justify-between items-start md:items-center border-b border-slate-100 pb-4">
        
        <!-- Левый блок шапки: кнопка Назад, кнопки Истории, Название файла и Маркер -->
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" id="js-fm-back-btn" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all cursor-pointer h-10 flex items-center gap-1.5">
                <span class="icon-arrow-left text-sm"></span> Назад к файлам
            </button>
            
            <!-- БЛОК КНОПОК ИСТОРИИ ИЗМЕНЕНИЙ (Undo/Redo) -->
            <div id="js-fm-history-group" class="hidden flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200/60 h-10">
                <button type="button" id="js-fm-undo-btn" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-white transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed" title="Отменить действие (Ctrl+Z)" disabled>
                    <span class="icon-rotate-ccw text-sm"></span>
                </button>
                <button type="button" id="js-fm-redo-btn" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-white transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed" title="Повторить отменённое (Ctrl+Y)" disabled>
                    <span class="icon-rotate-cw text-sm"></span>
                </button>
            </div>
            
            <!-- Название и маркер правок -->
            <div class="flex items-center gap-2">
                <span id="js-fm-editor-filename" class="text-base font-bold text-slate-800 truncate max-w-xs md:max-w-md"></span>
                <span id="js-fm-dirty-marker" class="hidden text-xs font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-md animate-pulse">Не сохранено</span>
            </div>
        </div>
        
        <!-- Правый блок шапки: кнопки Разрешить правку и Сохранить -->
        <div class="flex items-center gap-2 w-full md:w-auto justify-end">
            <button type="button" id="js-fm-edit-mode-btn" class="w-full hidden px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl transition-all cursor-pointer h-10 flex items-center justify-center gap-1.5 shadow-lg shadow-amber-500/10">
                <span class="icon-edit text-sm"></span> Разрешить правку
            </button>
            
            <button type="button" id="js-fm-save-btn" class="bg-[var(--primary-color)] text-white font-bold px-6 py-2.5 rounded-xl hover:bg-[var(--primary-dark)] transition-all cursor-pointer text-xs shadow-lg shadow-[var(--primary-color)]/20 h-10 flex items-center justify-center">
                Сохранить файл
            </button>
        </div>
    </div>
    
    <!-- Рабочая область IDE -->
    <div id="js-fm-ace-editor" class="w-full border border-slate-200/80 rounded-2xl shadow-inner text-sm overflow-hidden" style="height: 500px; min-height: 450px;"></div>
</div>

<!-- ГЛОБАЛЬНОЕ КОНТЕКСТНОЕ МЕНЮ -->
<div id="js-fm-context-menu" class="fm-context-menu" style="display: none;">
    <!-- ИСПРАВЛЕНО: Тег ul теперь закрывается корректно своим парным тегом </ul> -->
    <ul id="js-fm-menu-list" class="p-1 my-1 space-y-0.5">
        <!-- Наполняется в JS -->
    </ul>
</div>

<!-- НАТИВНАЯ ПЛАШКА "СВОЙСТВА ОБЪЕКТА" -->
<div id="js-fm-props-modal" class="modal-backdrop-fixed animate-fade-in">
    <div class="modal-content-card flex-col" style="max-width: 400px !important;">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <div>
                <h3 class="text-base font-bold text-slate-800">Свойства элемента</h3>
                <p id="js-prop-filename" class="text-xs text-slate-500 font-mono mt-1 text-emerald-600 truncate max-w-[280px]"></p>
            </div>
            <button type="button" onclick="closeFmPropsModal()" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all cursor-pointer">
                <span class="icon-x text-lg"></span>
            </button>
        </div>
        
        <div class="p-5 space-y-4 text-sm">
            <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                <span class="text-slate-400 font-medium">Тип объекта:</span>
                <span id="js-prop-type" class="font-bold text-slate-700"></span>
            </div>
            <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                <span class="text-slate-400 font-medium">Размер на диске:</span>
                <span id="js-prop-size" class="font-bold text-slate-700 font-mono"></span>
            </div>
            <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                <span class="text-slate-400 font-medium">Изменён:</span>
                <span id="js-prop-modified" class="font-bold text-slate-700 font-mono"></span>
            </div>
            <div class="flex justify-between items-center pb-1">
                <span class="text-slate-400 font-medium">Права доступа (CHMOD):</span>
                <span id="js-prop-perms" class="font-bold text-slate-700 font-mono bg-slate-100 px-2 py-0.5 rounded-md text-xs"></span>
            </div>
        </div>
    </div>
</div>

<!-- НАТИВНОЕ ОКНО ПРОВЕРКИ ИЗОБРАЖЕНИЙ (ZOOM PREVIEW MODAL) -->
<div id="js-fm-image-modal" class="modal-backdrop-fixed animate-fade-in" style="z-index: 10000 !important;">
    <!-- Кнопка закрытия в верхнем правом углу экрана -->
    <button type="button" onclick="closeFmImageModal()" class="fixed top-6 right-6 w-12 h-12 flex items-center justify-center bg-slate-900/60 border border-slate-750 text-white rounded-xl hover:bg-slate-900 transition-all cursor-pointer shadow-lg backdrop-blur-md">
        <span class="icon-x text-xl"></span>
    </button>
    
    <!-- Контейнер самой картинки с защитой от вылета за границы экрана -->
    <div class="flex items-center justify-center p-4 max-w-[95vw] max-h-[85vh]">
        <img id="js-fm-preview-image" src="" class="max-w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl border border-slate-200/10 bg-slate-950/20" alt="Просмотр">
    </div>
</div>

<!-- ОКНО ИЗМЕНЕНИЯ ПРАВ ДОСТУПА (CHMOD MODAL) -->
<div id="js-fm-chmod-modal" class="modal-backdrop-fixed animate-fade-in">
    <div class="modal-content-card flex-col">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <div>
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-1.5">
                    <span class="icon-shield text-[var(--primary-color)]"></span> Права доступа (CHMOD)
                </h3>
                <p id="js-chmod-filename" class="text-xs text-slate-500 font-mono mt-1 text-emerald-600 truncate max-w-xs"></p>
            </div>
            <button type="button" onclick="closeFmChmodModal()" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all cursor-pointer">
                <span class="icon-x text-lg"></span>
            </button>
        </div>
        
        <form id="js-chmod-form" onsubmit="submitFmChmodForm(event)" class="p-5 flex flex-col gap-4 text-sm">
            <!-- Сетка чекбоксов построена на классах matrix-container и matrix-label из config.css -->
            <div class="border border-slate-100 rounded-xl p-4 bg-slate-50/50">
                <div class="grid grid-cols-4 gap-2 text-center text-xs font-bold text-slate-700 mb-2 border-b border-slate-100 pb-2">
                    <div class="text-left font-medium text-slate-400">Роли:</div>
                    <div class="text-emerald-400">Вл.</div>
                    <div class="text-indigo-500">Гр.</div>
                    <div class="text-amber-500">Все</div>
                </div>

                <!-- Чтение -->
                <div class="grid grid-cols-4 gap-2 text-center items-center">
                    <span class="text-left text-xs font-semibold text-slate-500">Чтение (r)</span>
                    <label class="matrix-label justify-center"><input type="checkbox" id="chmod-u-r" value="4" onchange="calculateFmChmod()"></label>
                    <label class="matrix-label justify-center"><input type="checkbox" id="chmod-g-r" value="4" onchange="calculateFmChmod()"></label>
                    <label class="matrix-label justify-center"><input type="checkbox" id="chmod-o-r" value="4" onchange="calculateFmChmod()"></label>
                </div>

                <!-- Запись -->
                <div class="grid grid-cols-4 gap-2 text-center items-center">
                    <span class="text-left text-xs font-semibold text-slate-500">Запись (w)</span>
                    <label class="matrix-label justify-center"><input type="checkbox" id="chmod-u-w" value="2" onchange="calculateFmChmod()"></label>
                    <label class="matrix-label justify-center"><input type="checkbox" id="chmod-g-w" value="2" onchange="calculateFmChmod()"></label>
                    <label class="matrix-label justify-center"><input type="checkbox" id="chmod-o-w" value="2" onchange="calculateFmChmod()"></label>
                </div>

                <!-- Выполнение -->
                <div class="grid grid-cols-4 gap-2 text-center items-center">
                    <span class="text-left text-xs font-semibold text-slate-500">Запуск (x)</span>
                    <label class="matrix-label justify-center"><input type="checkbox" id="chmod-u-x" value="1" onchange="calculateFmChmod()"></label>
                    <label class="matrix-label justify-center"><input type="checkbox" id="chmod-g-x" value="1" onchange="calculateFmChmod()"></label>
                    <label class="matrix-label justify-center"><input type="checkbox" id="chmod-o-x" value="1" onchange="calculateFmChmod()"></label>
                </div>
            </div>

            <!-- Числовое значение маски -->
            <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100">
                <span class="text-xs text-slate-600 font-bold">Значение маски:</span>
                <input type="text" id="js-chmod-octal" value="0644" readonly class="w-20 text-center font-mono font-bold bg-white border border-slate-200 rounded-lg text-sm text-slate-800 py-1 focus:outline-none">
            </div>

            <!-- Кнопки управления -->
            <div class="flex justify-end gap-2 text-xs font-bold pt-2">
                <button type="button" onclick="closeFmChmodModal()" class="px-4 py-2.5 border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition cursor-pointer">Отмена</button>
                <button type="submit" class="px-5 py-2.5 bg-[var(--primary-color)] text-white rounded-xl hover:bg-[var(--primary-dark)] transition shadow-md cursor-pointer">Применить</button>
            </div>
        </form>
    </div>
</div>

<!-- ОКНО СОЗДАНИЯ ЭЛЕМЕНТА (CREATE MODAL) -->
<div id="js-fm-create-modal" class="modal-backdrop-fixed animate-fade-in hidden">
    <div class="modal-content-card flex-col" style="max-width: 380px !important;">
        
        <!-- Шапка окна -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <h3 id="js-create-title" class="text-base font-bold text-slate-800 flex items-center gap-1.5">
                <span class="icon-folder-plus text-amber-500"></span> Новая папка
            </h3>
            <button type="button" onclick="closeFmCreateModal()" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all cursor-pointer">
                <span class="icon-x text-lg"></span>
            </button>

        </div>
        
        <!-- Форма оформления -->
        <form id="js-create-form" onsubmit="submitFmCreateForm(event)" class="p-5 flex flex-col gap-4 text-sm">
            <input type="hidden" id="js-create-type" value="dir">
            
            <!-- Поле ввода имени -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-500">Название:</label>
                <input type="text" id="js-create-name" required placeholder="Введите имя..." class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)] transition-all">
            </div>

            <!-- ИНТЕРАКТИВНЫЙ БЛОК ПРЕДУПРЕЖДЕНИЙ И ТРАНСЛИТЕРАЦИИ -->
            <div id="js-create-warning-zone" class="p-4 bg-slate-50 border border-slate-100 rounded-xl" style="display: none !important;">
                <div id="js-create-warning-text" class="text-xs font-semibold text-slate-500">
                    Внимание: Кириллица и пробелы в именах файлов могут усложнить их вызов по прямым ссылкам в браузере.
                </div>
                <div id="js-create-translit-wrapper" class="flex items-center gap-2">
                    <button type="button" onclick="triggerInModalTranslit()" class="w-full py-2 bg-white border border-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
                        Транслитировать в латиницу
                    </button>
                </div>
            </div>

            <!-- Кнопки действий -->
            <div class="flex justify-end gap-2 text-xs font-bold pt-2">
                <button type="button" onclick="closeFmCreateModal()" class="px-4 py-2.5 border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition cursor-pointer">Отмена</button>
                <button type="submit" id="js-create-submit-btn" class="px-5 py-2.5 bg-[var(--primary-color)] text-white rounded-xl hover:bg-[var(--primary-dark)] transition shadow-md cursor-pointer">Создать</button>
            </div>

        </form>
    </div>
</div>

<script src="js/ace/ace.js" type="text/javascript" charset="utf-8"></script>
<script src="js/ace/ext-language_tools.js" type="text/javascript" charset="utf-8"></script>
<script src="js/filemanager.js?v=<?php echo filemtime('js/filemanager.js'); ?>" type="text/javascript"></script>
