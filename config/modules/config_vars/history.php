<?php
/**
 * Секция настроек: История изменений.
 * Ожидает в области видимости: $settingsData
 */
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$historyEnabled = !empty($settingsData['history_enabled']);
$historyLimit   = max(1, min(100, intval($settingsData['history_limit'] ?? 20)));

$historyStats   = getHistoryStats();
$historyStats['size_formatted'] = formatSize($historyStats['size_bytes']);
?>

<!-- СЕКЦИЯ: История изменений -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">История изменений</span>
            <span class="text-[10px] text-slate-400">(версии страниц, откат)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">

            <div class="editor-field">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="history_enabled" value="0">
                    <input type="checkbox" name="history_enabled" value="1" <?php echo $historyEnabled ? 'checked' : ''; ?>
                           class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm font-medium text-slate-700">Хранить историю изменений страниц</span>
                </label>
                <p class="text-[10px] text-slate-400 mt-1">
                    При каждом сохранении страницы создаётся снимок предыдущей версии. Откат — из формы редактирования страницы.
                </p>
            </div>

            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Количество хранимых версий</label>
                <input type="number" name="history_limit" value="<?php echo $historyLimit; ?>" min="1" max="100"
                       class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                <p class="text-[10px] text-slate-400 mt-1">
                    От 1 до 100. Старые версии удаляются автоматически при превышении лимита.
                </p>
            </div>

            <div class="border-t border-slate-200/60 pt-4 mt-2">
                <div class="text-[10px] text-slate-400 p-3 bg-white rounded-xl border border-slate-200">
                    <span class="font-semibold">Статистика истории:</span><br>
                    Страниц: <strong id="history-stat-pages"><?php echo $historyStats['pages']; ?></strong><br>
                    Версий: <strong id="history-stat-versions"><?php echo $historyStats['versions']; ?></strong><br>
                    Объём: <strong id="history-stat-size"><?php echo e($historyStats['size_formatted']); ?></strong>
                </div>
            </div>

            <div class="border-t border-slate-200/60 pt-4 mt-2">
                <button type="button" onclick="window.clearAllHistory()"
                        class="px-4 py-2 text-sm border border-rose-200 text-rose-600 font-bold rounded-xl hover:bg-rose-50 transition-all cursor-pointer flex items-center gap-2">
                    <span class="icon-trash-2 text-sm"></span> Очистить всю историю
                </button>
                <p class="text-[10px] text-slate-400 mt-1">
                    Удаляет все сохранённые версии. Текущее содержимое страниц не затрагивается.
                </p>
            </div>

        </div>
    </div>
</div>