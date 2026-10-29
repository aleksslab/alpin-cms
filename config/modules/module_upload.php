<?php
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$token = $_SESSION['csrf_token'] ?? '';
?>

<!-- Модалка загрузки модуля -->
<div id="module-upload-modal" class="modal-backdrop-fixed">
    <div class="modal-content-card" style="max-width: 550px; max-height: 90vh; display: flex; flex-direction: column;">
        
        <!-- Шапка -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <div>
                <h3 class="text-base font-bold text-slate-800">Установка модуля</h3>
                <p class="text-xs text-slate-400 mt-1">Загрузите архив модуля (.zip)</p>
            </div>
            <button type="button" onclick="closeModuleUploadModal()" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all cursor-pointer">
                <span class="icon-x text-xl"></span>
            </button>
        </div>
        
        <form id="module-upload-form" method="POST" action="index.php?tab=modules" enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0 p-4">
            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
            <input type="hidden" name="module_action" value="upload">
            
            <!-- Drag-and-Drop зона -->
            <div class="flex-1 min-h-0">
                <div id="module-drop-zone" class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer hover:border-[var(--primary-color)] transition-all relative h-full max-h-[220px] flex items-center justify-center">
                    <input type="file" name="module_archive" accept=".zip" id="module-file-input" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    
                    <div id="module-drop-content" class="pointer-events-none">
                        <span class="icon-upload-cloud text-3xl text-slate-300 block mb-2"></span>
                        <p class="text-sm font-medium text-slate-600">Перетащите архив или нажмите для выбора</p>
                        <p class="text-xs text-slate-400 mt-0.5">.zip до 10 МБ</p>
                    </div>
                    
                    <div id="module-file-info" class="hidden pointer-events-none">
                        <span class="icon-file-archive text-2xl text-emerald-500 block mb-1"></span>
                        <p id="module-file-name" class="text-sm font-medium text-slate-700"></p>
                        <p id="module-file-size" class="text-xs text-slate-400"></p>
                    </div>
                </div>
                
                <div id="module-upload-error" class="hidden mt-2 p-2 text-sm text-rose-800 bg-rose-50 border border-rose-200 rounded-xl"></div>
                <div id="module-upload-progress" class="hidden mt-2">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1">
                        <span>Загрузка...</span>
                        <span id="module-upload-percent">0%</span>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        <div id="module-upload-bar" class="bg-[var(--primary-color)] h-full transition-all duration-300" style="width: 0%;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Кнопки -->
            <div class="mt-4 pt-4 border-t border-slate-100 flex justify-end gap-2 flex-shrink-0">
                <button type="button" onclick="closeModuleUploadModal()" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition cursor-pointer">Отмена</button>
                <button type="submit" id="module-upload-submit" class="px-4 py-2 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition shadow-lg shadow-[var(--primary-color)]/20 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    <span class="icon-upload-cloud mr-2"></span> Установить
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Модалка загрузки модуля
 */
function openModuleUploadModal() {
    document.getElementById('module-upload-modal').classList.add('active');
    resetModuleUpload();
}

function closeModuleUploadModal() {
    document.getElementById('module-upload-modal').classList.remove('active');
    resetModuleUpload();
}

function resetModuleUpload(e) {
    if (e) e.stopPropagation();
    
    const input = document.getElementById('module-file-input');
    const dropZone = document.getElementById('module-drop-zone');
    const content = document.getElementById('module-drop-content');
    const fileInfo = document.getElementById('module-file-info');
    const errorEl = document.getElementById('module-upload-error');
    const progressEl = document.getElementById('module-upload-progress');
    const submitBtn = document.getElementById('module-upload-submit');
    
    if (input) input.value = '';
    if (content) content.classList.remove('hidden');
    if (fileInfo) fileInfo.classList.add('hidden');
    if (errorEl) errorEl.classList.add('hidden');
    if (progressEl) progressEl.classList.add('hidden');
    if (submitBtn) submitBtn.disabled = true;
    if (dropZone) dropZone.classList.remove('border-[var(--primary-color)]', 'bg-emerald-50/20');
}

/**
 * Форматирует размер файла в человекочитаемый вид
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Б';
    
    const k = 1024;
    const sizes = ['Б', 'КБ', 'МБ', 'ГБ'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    const size = (bytes / Math.pow(k, i)).toFixed(i === 0 ? 0 : 1);
    
    return size + ' ' + sizes[i];
}

// Drag-and-Drop
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('module-drop-zone');
    const input = document.getElementById('module-file-input');
    const content = document.getElementById('module-drop-content');
    const fileInfo = document.getElementById('module-file-info');
    const fileName = document.getElementById('module-file-name');
    const fileSize = document.getElementById('module-file-size');
    const errorEl = document.getElementById('module-upload-error');
    const submitBtn = document.getElementById('module-upload-submit');
    
    if (!dropZone || !input) return;
    
    // Drag Events
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-[var(--primary-color)]', 'bg-emerald-50/20');
    });
    
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('border-[var(--primary-color)]', 'bg-emerald-50/20');
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-[var(--primary-color)]', 'bg-emerald-50/20');
        
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            handleFileSelect(input.files[0]);
        }
    });
    
    // Change Event
    input.addEventListener('change', function() {
        if (this.files.length) {
            handleFileSelect(this.files[0]);
        }
    });
    
    function handleFileSelect(file) {
        // Проверка расширения
        const ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'zip') {
            errorEl.textContent = 'Допустимы только ZIP-архивы.';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = true;
            return;
        }
        
        // Проверка размера (10 МБ)
        if (file.size > 10 * 1024 * 1024) {
            errorEl.textContent = 'Размер файла превышает 10 МБ.';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = true;
            return;
        }
        
        errorEl.classList.add('hidden');
        
        // Показываем информацию о файле с человекочитаемым размером
        content.classList.add('hidden');
        fileInfo.classList.remove('hidden');
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        
        submitBtn.disabled = false;
    }
});

// Отправка формы с прогрессом
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('module-upload-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const errorEl = document.getElementById('module-upload-error');
        const progressEl = document.getElementById('module-upload-progress');
        const bar = document.getElementById('module-upload-bar');
        const percent = document.getElementById('module-upload-percent');
        const submitBtn = document.getElementById('module-upload-submit');
        
        errorEl.classList.add('hidden');
        progressEl.classList.remove('hidden');
        submitBtn.disabled = true;
        
        const formData = new FormData(this);
        
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 100);
                bar.style.width = pct + '%';
                percent.textContent = pct + '%';
            }
        });
        
        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    } else {
                        errorEl.textContent = response.error || 'Ошибка установки модуля';
                        errorEl.classList.remove('hidden');
                        progressEl.classList.add('hidden');
                        submitBtn.disabled = false;
                    }
                } catch (e) {
                    errorEl.textContent = 'Ошибка обработки ответа сервера';
                    errorEl.classList.remove('hidden');
                    progressEl.classList.add('hidden');
                    submitBtn.disabled = false;
                }
            } else {
                errorEl.textContent = 'Ошибка сервера (HTTP ' + xhr.status + ')';
                errorEl.classList.remove('hidden');
                progressEl.classList.add('hidden');
                submitBtn.disabled = false;
            }
        });
        
        xhr.addEventListener('error', function() {
            errorEl.textContent = 'Ошибка сети. Проверьте подключение.';
            errorEl.classList.remove('hidden');
            progressEl.classList.add('hidden');
            submitBtn.disabled = false;
        });
        
        xhr.open('POST', form.action);
        xhr.send(formData);
    });
});
</script>