<?php
// --- config/modules/modules.php (Управление модулями) ---
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$modules = getModulesData();
$token = $_SESSION['csrf_token'] ?? '';

// Доступные для установки модули и обновления из кеша каталога
$cacheFile = DATA_DIR . 'updates_cache.json';
$newModules = [];
$moduleUpdates = [];

if (file_exists($cacheFile)) {
    $cache = json_decode(file_get_contents($cacheFile), true);
    $newModules = $cache['data']['new'] ?? [];
    $moduleUpdates = $cache['data']['updates'] ?? [];

    // Оставляем только модули
    $newModules = array_filter($newModules, function ($item) {
        return ($item['type'] ?? 'plugin') === 'module';
    });
    $moduleUpdates = array_filter($moduleUpdates, function ($item) {
        return ($item['type'] ?? 'plugin') === 'module';
    });
}

// Сортируем по имени
uasort($modules, function ($a, $b) {
    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
});
?>

<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Управление модулями</h1>
        <p class="text-slate-500 text-sm">Установка, удаление и обновление модулей системы.</p>
    </div>
    <div class="flex flex-row gap-2 w-full lg:w-auto">
        <button type="button" onclick="refreshModulesCatalog(this)"
                class="flex-1 lg:flex-initial px-4 py-3 bg-slate-100 border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-all flex items-center justify-center gap-2 text-sm whitespace-nowrap"
                title="Обновить список доступных модулей">
            <span class="icon-refresh-cw text-base"></span>
            <span class="hidden lg:inline">Обновить каталог</span>
        </button>

        <button type="button" onclick="openModuleUploadModal()"
                class="flex-1 lg:flex-initial px-4 py-3 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all shadow-lg shadow-[var(--primary-color)]/20 flex items-center justify-center gap-2 text-sm whitespace-nowrap">
            <span class="icon-plus text-base"></span>
            <span class="lg:hidden">Установить</span>
            <span class="hidden lg:inline">Установить модуль</span>
        </button>
    </div>
</div>

<?php echo renderFlash(); ?>

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
            $hasArchive = file_exists(getModuleArchivePath($id));
            $autoUnpack = $mod['auto_unpack'] ?? false;
            $usedIn = findModuleUsage($id);
            $hasUsage = !empty($usedIn);
            $updateInfo = $moduleUpdates[$id] ?? null;

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
                        <div class="text-[10px] font-mono text-slate-400">
                            <?php echo e($id); ?> · v<?php echo e($mod['version'] ?? '1.0.0'); ?>
                        </div>
                    </div>
                </div>
                <div class="flex gap-1 items-center flex-shrink-0">
                    <?php if ($updateInfo): ?>
                        <span class="text-[8px] bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full">
                            v<?php echo e($updateInfo['current'] ?? '?'); ?> → v<?php echo e($updateInfo['latest'] ?? '?'); ?>
                        </span>
                    <?php elseif ($isInstalled): ?>
                        <span class="text-[8px] bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-full">распакован</span>
                    <?php elseif ($hasArchive): ?>
                        <button type="button" onclick="unpackModule('<?php echo escapeJsString($id); ?>', this)"
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
            <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between gap-2 min-h-[40px]">
                <span class="text-[9px] text-slate-400">
                    <?php if ($hasUsage): ?>
                        <span class="text-rose-400">используется на <?php echo count($usedIn); ?> стр.</span>
                    <?php else: ?>
                        не используется
                    <?php endif; ?>
                </span>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <?php if ($updateInfo): ?>
                        <button type="button" onclick="installModuleFromCatalog('<?php echo escapeJsString($updateInfo['url'] ?? ''); ?>', '<?php echo escapeJsString($id); ?>', this)"
                                class="text-[10px] text-amber-600 hover:text-amber-800 transition-colors cursor-pointer flex items-center gap-1 font-bold">
                            <span class="icon-download text-xs"></span> Обновить
                        </button>
                    <?php elseif ($isInstalled): ?>
                        <button type="button" onclick="rebuildModuleMin('<?php echo escapeJsString($id); ?>')"
                                class="text-[10px] text-slate-500 hover:text-[var(--primary-color)] transition-colors cursor-pointer flex items-center gap-1">
                            <span class="icon-refresh-cw text-xs"></span> Пересобрать
                        </button>
                    <?php endif; ?>

                    <?php if (!$hasUsage && $isInstalled): ?>
                        <form method="POST" action="index.php?tab=modules" class="inline-block" onsubmit="return confirm('Удалить код модуля &quot;<?php echo e($mod['name'] ?? $id); ?>&quot;? Архив и запись в каталоге останутся.')">
                            <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                            <input type="hidden" name="module_action" value="delete">
                            <input type="hidden" name="module_id" value="<?php echo e($id); ?>">
                            <button type="submit" class="text-[10px] text-rose-500 hover:text-rose-700 transition-colors cursor-pointer flex items-center gap-1" title="Удалить только распакованный код">
                                <span class="icon-trash-2"></span> Удалить код
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if (!$hasUsage): ?>
                        <form method="POST" action="index.php?tab=modules" class="inline-block" onsubmit="return confirm('Удалить модуль &quot;<?php echo e($mod['name'] ?? $id); ?>&quot; полностью? Будут удалены код, архив и запись.')">
                            <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                            <input type="hidden" name="module_action" value="purge">
                            <input type="hidden" name="module_id" value="<?php echo e($id); ?>">
                            <button type="submit" class="text-[10px] text-slate-400 hover:text-rose-700 transition-colors cursor-pointer flex items-center gap-1" title="Удалить код, архив и запись">
                                <span class="icon-circle-x"></span> Удалить полностью
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Доступные для установки из каталога -->
<?php if (!empty($newModules)): ?>
    <div class="mt-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-slate-800">Доступные для установки</h2>
            <span class="text-xs text-slate-400">Из каталога GitHub</span>
        </div>

        <!-- ДЕСКТОП: Таблица -->
        <div class="w-full desktop-only">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50">
                            <th class="py-4 px-4">Название</th>
                            <th class="py-4">Описание</th>
                            <th class="py-4">Версия</th>
                            <th class="py-4 text-right pr-4">Действие</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700">
                        <?php foreach ($newModules as $id => $module): ?>
                            <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100">
                                <td class="py-3 px-4 font-semibold text-slate-800">
                                    <?php echo e($module['name'] ?? $id); ?>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5"><?php echo e($id); ?></div>
                                </td>
                                <td class="py-3 text-slate-500 text-xs max-w-xs truncate">
                                    <?php echo e($module['description'] ?? ''); ?>
                                </td>
                                <td class="py-3 font-mono text-xs text-slate-500">
                                    v<?php echo e($module['version'] ?? '1.0.0'); ?>
                                </td>
                                <td class="py-3 text-right px-4">
                                    <button type="button" onclick="installModuleFromCatalog('<?php echo escapeJsString($module['url'] ?? ''); ?>', '<?php echo escapeJsString($id); ?>', this)" class="px-3 py-1.5 bg-[var(--primary-color)] text-white text-xs font-bold rounded-lg hover:bg-[var(--primary-dark)] transition-all flex items-center gap-1 ml-auto">
                                        <span class="icon-download text-sm"></span> Установить
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- МОБИЛЬНАЯ ВЕРСИЯ: Карточки -->
        <div class="mobile-only space-y-4">
            <?php foreach ($newModules as $id => $module): ?>
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex items-start justify-between border-b border-slate-100 pb-3 mb-3">
                        <div>
                            <span class="text-base font-bold text-slate-800"><?php echo e($module['name'] ?? $id); ?></span>
                            <div class="text-[10px] text-slate-400 font-mono mt-0.5"><?php echo e($id); ?> (v<?php echo e($module['version'] ?? '1.0.0'); ?>)</div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mb-3"><?php echo e($module['description'] ?? ''); ?></p>

                    <button type="button" onclick="installModuleFromCatalog('<?php echo escapeJsString($module['url'] ?? ''); ?>', '<?php echo escapeJsString($id); ?>', this)" class="w-full h-10 bg-[var(--primary-color)] text-white rounded-lg text-xs font-bold flex items-center justify-center gap-1 transition-all">
                        <span class="icon-download text-sm"></span> Установить
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php include APP_ROOT . '/config/core/media_modal.php';?>

<script>
/**
 * Распаковка модуля из локального архива
 */
function unpackModule(moduleId, btn) {
    if (!confirm('Распаковать модуль "' + moduleId + '"?')) {
        return;
    }

    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Распаковка...';

    fetch('index.php?tab=modules', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'unpack_module=1&module_id=' + encodeURIComponent(moduleId) + '&csrf_token=' + '<?php echo escapeJsString($token); ?>'
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

/**
 * Установка или обновление модуля из каталога
 */
function installModuleFromCatalog(url, id, btn) {
    if (!confirm('Установить или обновить модуль "' + id + '"?')) return;

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="icon-loader animate-spin text-xs"></span> Установка...';

    const formData = new FormData();
    formData.append('url', url);
    formData.append('id', id);
    formData.append('csrf_token', '<?php echo escapeJsString($token); ?>');

    fetch('index.php?tab=modules&ajax=module_install_from_url', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(() => {
        alert('Ошибка соединения с сервером.');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

/**
 * Обновление каталога модулей
 */
function refreshModulesCatalog(btn) {
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="icon-loader animate-spin text-base"></span> Обновление...';

    fetch('index.php?tab=modules&ajax=refresh_catalog')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.error);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(() => {
            alert('Ошибка соединения.');
            btn.disabled = false;
            btn.innerHTML = originalText;
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
