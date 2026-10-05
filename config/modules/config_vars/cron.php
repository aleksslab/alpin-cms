<?php
$cronProtocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$cronHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$cronBaseUrl = $cronProtocol . '://' . $cronHost;
$currentToken = getCronToken();
?>
<!-- СЕКЦИЯ: Интеграция с планировщиком -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Интеграция с планировщиком</span>
            <span class="text-[10px] text-slate-400">(токен, URL кронов)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">

            <!-- Токен -->
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Токен крона</label>

                <div class="flex flex-col lg:flex-row gap-2">
                    <input type="text"
                           id="cron-token-display"
                           readonly
                           value="<?php echo e($currentToken); ?>"
                           class="w-full min-w-0 px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-mono text-slate-600 select-all focus:outline-none">
                    <button type="button"
                            onclick="regenerateCronToken()"
                            class="w-full lg:w-auto lg:flex-shrink-0 px-4 py-2.5 bg-rose-50 border border-rose-200 text-rose-600 font-bold text-sm rounded-xl hover:bg-rose-100 transition-all cursor-pointer flex items-center justify-center gap-1.5 whitespace-nowrap">
                        <span class="icon-refresh-cw text-sm"></span>
                        <span>Перегенерировать</span>
                    </button>
                </div>

                <input type="hidden"
                       id="cron-token-hidden"
                       name="cron_token"
                       value="<?php echo e($currentToken); ?>">

                <p class="text-[10px] text-slate-400 mt-1">
                    Один токен используется и для бэкапов, и для автопубликации страниц.
                    Новый токен вступит в силу <strong>после нажатия «Сохранить настройки»</strong>.
                    После этого все ранее настроенные крон-задачи сломаются.
                </p>
            </div>

            <!-- URL кронов -->
            <div class="border-t border-slate-200/60 pt-4 mt-2">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Ссылки для планировщика</h4>

                <div class="editor-field mb-3">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Резервное копирование</label>
                    <input type="text"
                           id="cron-backup-url"
                           readonly
                           value="<?php echo e($cronBaseUrl); ?>/config/cron/backup.php?token=<?php echo e($currentToken); ?>"
                           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-[11px] font-mono text-slate-600 select-all focus:outline-none">
                </div>

                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Автопубликация страниц</label>
                    <input type="text"
                           id="cron-publish-url"
                           readonly
                           value="<?php echo e($cronBaseUrl); ?>/config/cron/publish.php?token=<?php echo e($currentToken); ?>"
                           class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-[11px] font-mono text-slate-600 select-all focus:outline-none">
                </div>
            </div>

        </div>
    </div>
</div>