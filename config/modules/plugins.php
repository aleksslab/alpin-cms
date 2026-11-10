<?php
// --- config/modules/plugins.php (Управление плагинами) ---
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

// Получаем список доступных для установки плагинов из кеша
$cacheFile = DATA_DIR . 'updates_cache.json';
$newExtensions = [];

if (file_exists($cacheFile)) {
    $cache = json_decode(file_get_contents($cacheFile), true);
    $newExtensions = $cache['data']['new'] ?? [];
    
    // Фильтруем только плагины (на всякий случай)
    $newExtensions = array_filter($newExtensions, function($item) {
        return ($item['type'] ?? 'plugin') === 'plugin';
    });
}

// Синхронизируем папку с JSON перед выводом
$plugins = syncPluginsData();
$token = $_SESSION['csrf_token'] ?? '';

// Сортируем по имени
uasort($plugins, function($a, $b) {
    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
});
?>

<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Плагины</h1>
        <p class="text-slate-500 text-sm">Расширения системных функций ядра.</p>
    </div>
    <div class="flex flex-row gap-2 w-full lg:w-auto">
        <!-- Кнопка обновления каталога -->
        <button type="button" onclick="refreshPluginsCatalog(this)"
                class="flex-1 lg:flex-initial px-4 py-3 bg-slate-100 border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-all flex items-center justify-center gap-2 text-sm whitespace-nowrap"
                title="Обновить список доступных плагинов">
            <span class="icon-refresh-cw text-base"></span>
            <span class="hidden lg:inline">Обновить каталог</span>
        </button>

        <button type="button" onclick="window.openPluginUploadModal()"
                class="flex-1 lg:flex-initial lg:w-[140px] px-4 py-3 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all shadow-lg shadow-[var(--primary-color)]/20 flex items-center justify-center gap-2 text-sm whitespace-nowrap">
            <span class="icon-plus text-base"></span>
            <span>Установить</span>
        </button>
    </div>
</div>

<?php renderFlash() ?>

<div class="w-full desktop-only">
    <?php if (empty($plugins)): ?>
        <div class="bg-white rounded-2xl border border-slate-100 p-12 text-center text-slate-400 shadow-sm">
            <span class="icon-puzzle text-4xl block mb-4 text-slate-300"></span>
            <p class="text-lg font-semibold text-slate-600">Плагины отсутствуют</p>
            <p class="text-sm mt-2">Установите первый плагин, нажав кнопку выше.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50">
                        <th class="py-4 px-4">Название</th>
                        <th class="py-4">Описание</th>
                        <th class="py-4">Версия</th>
                        <th class="py-4 text-right pr-4">Статус</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-slate-700">
                    <?php foreach ($plugins as $id => $plugin): ?>
                        <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100">
                            <td class="py-3 px-4 font-semibold text-slate-800">
                                <?php echo e($plugin['name'] ?? $id); ?>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5"><?php echo e($id); ?></div>
                            </td>
                            <td class="py-3 text-slate-500 text-xs max-w-xs truncate">
                                <?php echo e($plugin['description'] ?? ''); ?>
                            </td>
                            <td class="py-3 font-mono text-xs text-slate-500">
                                v<?php echo e($plugin['version'] ?? '1.0.0'); ?>
                            </td>
                            <td class="py-3 text-right px-4">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="index.php?tab=plugins" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                                        <input type="hidden" name="plugin_action" value="toggle">
                                        <input type="hidden" name="plugin_id" value="<?php echo e($id); ?>">
                                        <input type="hidden" name="plugin_active" value="<?php echo empty($plugin['active']) ? '1' : '0'; ?>">
                                        
                                        <?php if (!empty($plugin['active'])): ?>
                                            <button type="submit" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200 hover:bg-emerald-100 transition-all flex items-center gap-1">
                                                <span class="icon-check-circle text-sm"></span> Включен
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="px-3 py-1.5 bg-slate-50 text-slate-500 text-xs font-bold rounded-lg border border-slate-200 hover:bg-slate-100 transition-all flex items-center gap-1">
                                                <span class="icon-x-circle text-sm"></span> Выключен
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    
                                    <form method="POST" action="index.php?tab=plugins" class="inline-block" onsubmit="return confirm('Удалить плагин &quot;<?php echo e($plugin['name'] ?? $id); ?>&quot;?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                                        <input type="hidden" name="plugin_action" value="delete">
                                        <input type="hidden" name="plugin_id" value="<?php echo e($id); ?>">
                                        <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center border border-rose-100 text-rose-500 hover:text-rose-800 bg-white hover:bg-rose-50 transition-all" title="Удалить">
                                            <span class="icon-trash-2 text-sm"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Мобильная версия -->
<div class="mobile-only space-y-4">
    <?php foreach ($plugins as $id => $plugin): ?>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="flex items-start justify-between border-b border-slate-100 pb-3 mb-3">
                <div>
                    <span class="text-base font-bold text-slate-800"><?php echo e($plugin['name'] ?? $id); ?></span>
                    <div class="text-[10px] text-slate-400 font-mono mt-0.5"><?php echo e($id); ?> (v<?php echo e($plugin['version'] ?? '1.0.0'); ?>)</div>
                </div>
            </div>
            <p class="text-xs text-slate-500 mb-3"><?php echo e($plugin['description'] ?? ''); ?></p>
            
            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                <form method="POST" action="index.php?tab=plugins" class="flex-1">
                    <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                    <input type="hidden" name="plugin_action" value="toggle">
                    <input type="hidden" name="plugin_id" value="<?php echo e($id); ?>">
                    <input type="hidden" name="plugin_active" value="<?php echo empty($plugin['active']) ? '1' : '0'; ?>">
                    
                    <?php if (!empty($plugin['active'])): ?>
                        <button type="submit" class="w-full h-10 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-xs font-bold flex items-center justify-center gap-1 transition-all">
                            <span class="icon-check-circle text-sm"></span> Включен
                        </button>
                    <?php else: ?>
                        <button type="submit" class="w-full h-10 bg-slate-50 border border-slate-200 text-slate-500 rounded-lg text-xs font-bold flex items-center justify-center gap-1 transition-all">
                            <span class="icon-x-circle text-sm"></span> Выключен
                        </button>
                    <?php endif; ?>
                </form>
                
                <form method="POST" action="index.php?tab=plugins" class="flex-shrink-0" onsubmit="return confirm('Удалить плагин &quot;<?php echo e($plugin['name'] ?? $id); ?>&quot;?');">
                    <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                    <input type="hidden" name="plugin_action" value="delete">
                    <input type="hidden" name="plugin_id" value="<?php echo e($id); ?>">
                    <button type="submit" class="w-10 h-10 bg-white border border-rose-100 text-rose-500 rounded-lg flex items-center justify-center transition-all cursor-pointer hover:bg-rose-50" title="Удалить">
                        <span class="icon-trash-2 text-sm"></span>
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Блок доступных для установки -->
<?php if (!empty($newExtensions)): ?>
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
                        <?php foreach ($newExtensions as $id => $plugin): ?>
                            <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100">
                                <td class="py-3 px-4 font-semibold text-slate-800">
                                    <?php echo e($plugin['name']); ?>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5"><?php echo e($id); ?></div>
                                </td>
                                <td class="py-3 text-slate-500 text-xs max-w-xs truncate">
                                    <?php echo e($plugin['description'] ?? ''); ?>
                                </td>
                                <td class="py-3 font-mono text-xs text-slate-500">
                                    v<?php echo e($plugin['version']); ?>
                                </td>
                                <td class="py-3 text-right px-4">
                                    <button type="button" onclick="installPluginFromCatalog('<?php echo e($plugin['url']); ?>', '<?php echo e($id); ?>', this)" class="px-3 py-1.5 bg-[var(--primary-color)] text-white text-xs font-bold rounded-lg hover:bg-[var(--primary-dark)] transition-all flex items-center gap-1 ml-auto">
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
            <?php foreach ($newExtensions as $id => $plugin): ?>
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex items-start justify-between border-b border-slate-100 pb-3 mb-3">
                        <div>
                            <span class="text-base font-bold text-slate-800"><?php echo e($plugin['name']); ?></span>
                            <div class="text-[10px] text-slate-400 font-mono mt-0.5"><?php echo e($id); ?> (v<?php echo e($plugin['version']); ?>)</div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mb-3"><?php echo e($plugin['description'] ?? ''); ?></p>
                    
                    <button type="button" onclick="installPluginFromCatalog('<?php echo e($plugin['url']); ?>', '<?php echo e($id); ?>', this)" class="w-full h-10 bg-[var(--primary-color)] text-white rounded-lg text-xs font-bold flex items-center justify-center gap-1 transition-all">
                        <span class="icon-download text-sm"></span> Установить
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Модалка загрузки (ZIP) -->
<?php 
include __DIR__ . '/plugin_upload.php'; 
?>
</div>

<script>
function installPluginFromCatalog(url, id, btn) {
    if (!confirm('Установить плагин "' + id + '"?')) return;
    
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="icon-loader animate-spin text-xs"></span> Установка...';
    
    const formData = new FormData();
    formData.append('url', url);
    formData.append('id', id);
    formData.append('csrf_token', '<?php echo e($token); ?>');
    
    fetch('index.php?tab=plugins&ajax=plugin_install_from_url', {
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

function refreshPluginsCatalog(btn) {
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="icon-loader animate-spin text-base"></span> Обновление...';
    
    // Отправляем AJAX-запрос на обновление каталога
    // (Создадим этот эндпоинт ниже)
    fetch('index.php?tab=plugins&ajax=refresh_catalog')
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
</script>