<?php
// --- config/modules/modules.php (Управление модулями) ---
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$modules = getModulesData();
$token = $_SESSION['csrf_token'] ?? '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['module_action'])) {
    $action = $_POST['module_action'];
    $moduleId = $_POST['module_id'] ?? '';
    
    // Проверка CSRF
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $token) {
        $error = 'Ошибка безопасности: CSRF-токен невалиден';
    } elseif (!preg_match('/^[a-z0-9\-_]+$/i', $moduleId)) {
        // Валидация ID модуля (защита от path traversal)
        $error = 'Невалидный ID модуля';
        logAction('module_delete', 'Попытка удаления модуля с невалидным ID: ' . $moduleId, 'ERROR');
    } elseif ($action === 'delete' && $moduleId) {
        // Проверяем использование
        $usedIn = findModuleUsage($moduleId);
        if (!empty($usedIn)) {
            $error = 'Модуль используется на страницах: ' . implode(', ', $usedIn) . '. Удалите модуль со страниц, чтобы удалить его.';
        } else {
            // Удаляем распакованные файлы
            
            // 1. Удаляем папку модуля в админке
            $adminDir = APP_ROOT . '/config/modules/' . $moduleId;
            if (is_dir($adminDir)) {
                $files = array_diff(scandir($adminDir), ['.', '..']);
                foreach ($files as $file) {
                    @unlink($adminDir . '/' . $file);
                }
                @rmdir($adminDir);
            }
            
            // 2. Удаляем файлы на сайте
            $siteFiles = [
                APP_ROOT . '/modules/' . $moduleId . '.php',
                APP_ROOT . '/css/' . $moduleId . '.css',
                APP_ROOT . '/js/' . $moduleId . '.js'
            ];
            foreach ($siteFiles as $file) {
                if (file_exists($file)) @unlink($file);
            }
            
            $success = 'Модуль "' . $moduleId . '" удалён.';
        }
    } elseif ($action === 'upload') {
        // Обработка загрузки архива (будет позже)
        $success = 'Загрузка архива... (в разработке)';
    }
}
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Управление модулями</h1>
        <p class="text-slate-500 text-sm">Установка, удаление и обновление модулей системы.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="p-4 mb-6 text-sm text-emerald-800 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center gap-3 animate-fade-in">
        <span class="icon-shield-check text-lg text-[var(--primary-color)]"></span> <?php echo e($success); ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="p-4 mb-6 text-sm text-rose-800 bg-rose-50 border border-rose-100 rounded-xl flex items-center gap-3 animate-fade-in">
        <span class="icon-x text-lg"></span> <?php echo e($error); ?>
    </div>
<?php endif; ?>

<!-- Форма загрузки архива -->
<div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h3 class="text-lg font-bold text-slate-800">Установка нового модуля</h3>
            <p class="text-sm text-slate-500">Загрузите архив модуля (.zip) для установки в систему.</p>
        </div>
        <button type="button" onclick="openModuleUploadModal()" class="px-6 py-2.5 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all shadow-lg shadow-[var(--primary-color)]/20 text-sm flex items-center gap-2 flex-shrink-0">
            <span class="icon-upload-cloud text-base"></span> Установить модуль
        </button>
    </div>
</div>

<!-- Подключаем модалку загрузки -->
<?php include __DIR__ . '/module_upload.php'; ?>

<!-- Список модулей -->
<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
    <h3 class="text-lg font-bold text-slate-800 mb-4">Доступные модули</h3>
    
    <?php if (empty($modules)): ?>
        <div class="text-center py-12 text-slate-400">
            <span class="icon-box text-4xl block mb-3 text-slate-300"></span>
            <p class="text-sm">Нет доступных модулей.</p>
            <p class="text-xs mt-1">Загрузите архив модуля выше.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2! xl:grid-cols-3! gap-4 auto-rows-fr">
        <?php foreach ($modules as $id => $mod): 
            $isInstalled = isModuleInstalled($id);
            $hasArchive = file_exists(APP_ROOT . '/config/modules/archives/' . $id . '.zip');
            $autoUnpack = $mod['auto_unpack'] ?? false;
            $usedIn = findModuleUsage($id);
            $hasUsage = !empty($usedIn);
    
            // Превью модуля
            $previewPath = '/config/modules/previews/' . $id . '.png';
            $hasPreview = file_exists(APP_ROOT . $previewPath);
        ?>
        <div class="border border-slate-200 rounded-xl p-4 hover:shadow-md transition-all flex flex-col h-full">
            <!-- Верхняя часть: иконка + название + статус -->
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <!-- иконка/превью -->
                    <?php if ($hasPreview): ?>
                        <div onclick="zoomCarouselImage(this)" 
                             class="carousel-inline-preview" 
                             style="width:40px !important;height:40px !important;min-width:40px !important;">
                            <img src="<?php echo e($previewPath); ?>" 
                                 class="js-slide-preview-img w-full h-full object-cover" 
                                 alt="Превью модуля">
                        </div>
                    <?php else: ?>
                        <span class="<?php echo e($mod['icon'] ?? 'icon-box'); ?> text-2xl text-slate-400"></span>
                    <?php endif; ?>
                    <div>
                        <div class="font-bold text-slate-800">
                            <?php echo e($mod['name'] ?? $id); ?>
                            <?php if ($autoUnpack): ?>
                                <span class="text-[8px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full ml-1">авто</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-[10px] font-mono text-slate-400"><?php echo e($id); ?></div>
                    </div>
                </div>
                <div class="flex gap-1 items-center flex-shrink-0">
                    <?php if ($isInstalled): ?>
                        <span class="text-[8px] bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-full">распакован</span>
                    <?php elseif ($hasArchive): ?>
                        <button onclick="unpackModule('<?php echo escapeJsString($id); ?>')" 
                                class="text-[8px] bg-[var(--primary-color)] text-white px-2 py-1 rounded-full hover:bg-[var(--primary-dark)] transition-all cursor-pointer">
                            Распаковать
                        </button>
                    <?php else: ?>
                        <span class="text-[8px] bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full">в архиве</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Описание (растягивается) -->
            <div class="my-2 text-xs text-slate-500 flex-1">
                <?php echo e($mod['description'] ?? ''); ?>
                <?php if (!empty($mod['has_settings'])): ?>
                    <span class="text-[8px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded-full ml-1">настраиваемый</span>
                <?php endif; ?>
            </div>

            <!-- Панель кнопок с фиксированной высотой -->
            <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between h-10">
                <span class="text-[9px] text-slate-400">
                    <?php if ($hasUsage): ?>
                        <span class="text-rose-400">используется на <?php echo count($usedIn); ?> стр.</span>
                    <?php else: ?>
                        не используется
                    <?php endif; ?>
                </span>

                <div class="flex items-center gap-2">
                    <?php if ($isInstalled): ?>
                        <button onclick="rebuildModuleMin('<?php echo escapeJsString($id); ?>')" 
                                class="text-[10px] text-slate-500 hover:text-[var(--primary-color)] transition-colors cursor-pointer flex items-center gap-1">
                            <span class="icon-refresh-cw text-xs"></span> Пересобрать
                        </button>

                        <?php if (!$hasUsage): ?>
                            <form method="POST" action="index.php?tab=modules" onsubmit="return confirm('Удалить модуль &quot;<?php echo e($mod['name'] ?? $id); ?>&quot;?')">
                                <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                <input type="hidden" name="module_action" value="delete">
                                <input type="hidden" name="module_id" value="<?php echo e($id); ?>">
                                <button type="submit" class="text-[10px] text-rose-500 hover:text-rose-700 transition-colors cursor-pointer flex items-center gap-1">
                                    <span class="icon-trash-2"></span> Удалить
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div> 

<?php include APP_ROOT . '/config/core/media_modal.php';?>

<script>
/**
 * Распаковка модуля
 */
function unpackModule(moduleId) {
    if (!confirm('Распаковать модуль "' + moduleId + '"?')) {
        return;
    }
    
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Распаковка...';
    
    fetch('index.php?tab=modules', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'unpack_module=1&module_id=' + encodeURIComponent(moduleId) + '&csrf_token=' + '<?php echo $token; ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Ошибка распаковки');
            btn.disabled = false;
            btn.textContent = originalText;
        }
    })
    .catch(() => {
        alert('Ошибка соединения');
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

function rebuildModuleMin(moduleId) {
    if (!confirm('Пересобрать .min файлы для модуля "' + moduleId + '"?')) {
        return;
    }

    var token = document.querySelector('input[name="csrf_token"]');
    if (!token) {
        alert('Ошибка: CSRF-токен не найден');
        return;
    }

    var body = 'rebuild_min=1&module_id=' + encodeURIComponent(moduleId) + '&csrf_token=' + encodeURIComponent(token.value);
    fetch('index.php?tab=modules', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            alert(data.message || 'Пересборка завершена');
            location.reload();
        } else {
            alert(data.error || 'Ошибка');
        }
    })
    .catch(function() {
        alert('Ошибка соединения');
    });
}
</script>