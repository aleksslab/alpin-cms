<?php
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$token = $_SESSION['csrf_token'] ?? '';
$settings = getSettingsData();
$period = (int)($settings['updates_period'] ?? 7); // Дни

// Проверка окружения (используем функцию ядра)
$env = get_download_environment();
$canCheck = $env['curl'] || $env['fopen'];

// Загружаем кеш
$cacheFile = DATA_DIR . 'updates_cache.json';
$cache = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : null;
$lastCheck = $cache['last_check'] ?? 0;
$updateData = $cache['data'] ?? null;
?>

<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Обновления</h1>
        <p class="text-slate-500 text-sm">Проверка новых версий AlPin CMS, модулей и плагинов.</p>
    </div>
    <div class="flex flex-row gap-2 w-full lg:w-auto">
        <button type="button" onclick="checkUpdates()" <?php echo !$canCheck ? 'disabled' : ''; ?> class="flex-1 lg:flex-initial px-4 py-3 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all shadow-lg flex items-center justify-center gap-2 text-sm whitespace-nowrap <?php echo !$canCheck ? 'opacity-50 cursor-not-allowed' : ''; ?>">
            <span class="icon-refresh-cw text-base"></span>
            <span>Проверить сейчас</span>
        </button>
    </div>
</div>

<?php if (!$canCheck): ?>
<div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 flex items-start gap-3">
    <span class="icon-alert-triangle text-amber-500 text-xl flex-shrink-0 mt-0.5"></span>
    <div>
        <h4 class="text-sm font-bold text-amber-800">Проверка обновлений недоступна</h4>
        <p class="text-xs text-amber-700 mt-1">На вашем сервере отключены расширение <strong>cURL</strong> и директива <strong>allow_url_fopen</strong>. Плагин не может скачать каталог обновлений с GitHub.</p>
    </div>
</div>
<?php endif; ?>

<?php renderFlash() ?>

<form method="POST" action="index.php?tab=config_vars" class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm mb-6">
    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
    <input type="hidden" name="save_settings" value="1">
    <input type="hidden" name="return_url" value="index.php?tab=updates">
    <input type="hidden" name="settings_source" value="плагина">

    <div class="flex items-center justify-between border-b border-slate-200/60 pb-3 mb-4">
        <h3 class="text-sm font-bold text-slate-800">Настройки проверки</h3>
        <button type="submit" class="text-xs bg-[var(--primary-color)] text-white px-4 py-2 rounded-lg hover:bg-[var(--primary-dark)] transition-colors font-bold shadow-sm">
            Сохранить настройки
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2! gap-8 mb-2">
        <!-- Левая колонка: Период и URL -->
        <div class="space-y-5">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Период проверки</label>
                <select name="updates_period" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="1" <?php echo $period == 1 ? 'selected' : ''; ?>>Каждый день</option>
                    <option value="3" <?php echo $period == 3 ? 'selected' : ''; ?>>Раз в 3 дня</option>
                    <option value="7" <?php echo $period == 7 ? 'selected' : ''; ?>>Раз в неделю</option>
                    <option value="30" <?php echo $period == 30 ? 'selected' : ''; ?>>Раз в месяц</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">URL каталога обновлений</label>
                <input type="text" name="updates_catalog_url" value="<?php echo e(!empty($settings['updates_catalog_url']) ? $settings['updates_catalog_url'] : UPDATES_DEFAULT_CATALOG_URL); ?>" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:border-[var(--primary-color)]">
                <p class="text-[10px] text-slate-400 mt-1">
                    Ссылка на JSON-файл с версиями.
                    <?php if ($canCheck): ?>
                        <span class="text-emerald-600 ml-1">(Метод: <?php echo $env['curl'] ? 'cURL' : 'file_get_contents'; ?>)</span>
                    <?php else: ?>
                        <span class="text-rose-500 ml-1">(Методы недоступны)</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Правая колонка: Чекбоксы -->
        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Что проверять</label>
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="updates_check_core" value="0">
                    <input type="checkbox" name="updates_check_core" value="1" <?php echo !empty($settings['updates_check_core']) ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm font-medium text-slate-700">Ядро (система)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="updates_check_modules" value="0">
                    <input type="checkbox" name="updates_check_modules" value="1" <?php echo !empty($settings['updates_check_modules']) ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm font-medium text-slate-700">Модули контента</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="updates_check_plugins" value="0">
                    <input type="checkbox" name="updates_check_plugins" value="1" <?php echo !empty($settings['updates_check_plugins']) ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm font-medium text-slate-700">Плагины (расширения)</span>
                </label>
            </div>
        </div>
    </div>
</form>

<div id="updates-result" class="space-y-6">
    <?php if ($updateData !== null): ?>
        <?php echo render_updates_results($updateData); ?>
    <?php else: ?>
        <div class="text-center py-12 text-slate-400">
            <span class="icon-cloud-download text-4xl mb-4 block"></span>
            <p>Нажмите «Проверить сейчас», чтобы получить список обновлений.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function checkUpdates() {
    const btn = document.querySelector('button[onclick="checkUpdates()"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="icon-loader animate-spin text-base"></span> Проверка...';

    fetch('index.php?tab=updates&ajax=check_updates')
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.error);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert('Ошибка соединения');
        });
}

function installUpdate(url, type, id) {
    if (!confirm('Установить обновление? Файлы будут перезаписаны.')) return;

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="icon-loader animate-spin"></span> Загрузка...';

    const formData = new FormData();
    formData.append('url', url);
    formData.append('type', type);
    formData.append('id', id);
    formData.append('csrf_token', '<?php echo $token; ?>');

    fetch('index.php?tab=updates&ajax=download_update', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
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