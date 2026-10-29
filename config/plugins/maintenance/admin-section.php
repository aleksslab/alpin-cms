<?php
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$settings = getSettingsData();
$maintenanceEnabled = !empty($settings['maintenance_enabled']);
$maintenanceMessage = (string)($settings['maintenance_message'] ?? '');
$maintenanceToken   = (string)($settings['maintenance_bypass_token'] ?? '');
$maintenanceUntil   = !empty($settings['maintenance_until']) ? (int)$settings['maintenance_until'] : 0;
$untilValue = $maintenanceUntil > 0 ? date('Y-m-d\TH:i', $maintenanceUntil) : '';

$bypassUrl = '';
if ($maintenanceToken !== '') {
    $bypassUrl = getBaseUrl() . '/?bypass=' . $maintenanceToken;
}
?>

<div class="border border-slate-200 rounded-2xl overflow-hidden mb-5">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Технические работы</span>
            <span class="text-[10px] text-slate-400">(заглушка для посетителей)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">
            <div class="editor-field">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="maintenance_enabled" value="0">
                    <input type="checkbox" name="maintenance_enabled" value="1" <?php echo $maintenanceEnabled ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm font-medium text-slate-700">Включить режим технических работ</span>
                </label>
                <p class="text-[10px] text-slate-400 mt-1">Все посетители увидят заглушку с кодом 503.</p>
            </div>

            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Сообщение</label>
                <textarea name="maintenance_message" rows="5" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:border-[var(--primary-color)]" placeholder="Мы проводим технические работы."><?php echo e($maintenanceMessage); ?></textarea>
            </div>

            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Снять автоматически</label>
                <input type="datetime-local" name="maintenance_until" value="<?php echo e($untilValue); ?>" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>

            <div class="editor-field">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="maintenance_show_timer" value="0">
                    <input type="checkbox" name="maintenance_show_timer" value="1" <?php echo !empty($settings['maintenance_show_timer']) ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm font-medium text-slate-700">Показывать таймер до открытия</span>
                </label>
            </div>

            <div class="border-t border-slate-200/60 pt-4 mt-2">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Bypass-ссылка</label>
                <div class="flex flex-col lg:flex-row gap-2 mb-2">
                    <input type="text" readonly id="maintenance-token-display" value="<?php echo e($maintenanceToken); ?>" placeholder="(токен не сгенерирован)" class="w-full min-w-0 px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-mono text-slate-600 select-all focus:outline-none">
                    <button type="button" onclick="window.regenerateMaintenanceToken()" class="w-full lg:w-auto lg:flex-shrink-0 px-4 py-2.5 bg-rose-50 border border-rose-200 text-rose-600 font-bold text-sm rounded-xl hover:bg-rose-100 transition-all cursor-pointer flex items-center justify-center gap-1.5 whitespace-nowrap">
                        <span class="icon-refresh-cw text-sm"></span><span>Перегенерировать</span>
                    </button>
                </div>
                <input type="hidden" id="maintenance-token-hidden" name="maintenance_bypass_token" value="<?php echo e($maintenanceToken); ?>">
                <div class="flex flex-col lg:flex-row gap-2 mt-2">
                    <input type="text" readonly id="maintenance-bypass-url" value="<?php echo e($bypassUrl); ?>" placeholder="(сгенерируется при включении)" class="w-full min-w-0 px-4 py-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-mono text-emerald-800 select-all focus:outline-none">
                    <button type="button" onclick="window.copyMaintenanceBypassUrl(this)" class="w-full lg:w-auto lg:flex-shrink-0 px-4 py-2.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-bold rounded-xl hover:bg-emerald-100 transition-all whitespace-nowrap flex items-center justify-center gap-2">
                        <span class="icon-copy text-sm"></span> Копировать
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>