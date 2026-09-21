<!-- СЕКЦИЯ: Кеширование страниц -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Кеширование страниц</span>
            <span class="text-[10px] text-slate-400">(ускорение загрузки)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">

            <div class="editor-field">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="cache_enabled" value="0">
                    <input type="checkbox" name="cache_enabled" value="1" <?php echo ($settingsData['cache_enabled'] ?? false) ? 'checked' : ''; ?> 
                           class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm font-medium text-slate-700">Включить кеширование страниц</span>
                </label>
                <p class="text-[10px] text-slate-400 mt-1">Готовые HTML-страницы будут сохраняться в кеш для ускорения загрузки.</p>
            </div>

            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Время жизни кеша</label>
                <select name="cache_ttl" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="3600" <?php echo ($settingsData['cache_ttl'] ?? 3600) == 3600 ? 'selected' : ''; ?>>1 час</option>
                    <option value="21600" <?php echo ($settingsData['cache_ttl'] ?? 3600) == 21600 ? 'selected' : ''; ?>>6 часов</option>
                    <option value="86400" <?php echo ($settingsData['cache_ttl'] ?? 3600) == 86400 ? 'selected' : ''; ?>>24 часа</option>
                    <option value="604800" <?php echo ($settingsData['cache_ttl'] ?? 3600) == 604800 ? 'selected' : ''; ?>>7 дней</option>
                    <option value="0" <?php echo ($settingsData['cache_ttl'] ?? 3600) == 0 ? 'selected' : ''; ?>>Бессрочно (до изменения страницы)</option>
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Время, через которое кеш считается устаревшим.</p>
            </div>

            <div class="border-t border-slate-200/60 pt-4 mt-2">
                <button type="button" onclick="window.clearAllCache()" 
                        class="px-4 py-2 text-sm border border-rose-200 text-rose-600 font-bold rounded-xl hover:bg-rose-50 transition-all cursor-pointer flex items-center gap-2">
                    <span class="icon-trash-2 text-sm"></span> Очистить весь кеш
                </button>
                <p class="text-[10px] text-slate-400 mt-1">Удаляет все закешированные HTML-страницы.</p>
            </div>

            <div class="text-[10px] text-slate-400 mt-2 p-3 bg-white rounded-xl border border-slate-200">
                <span class="font-semibold">Статистика кеша:</span><br>
                <?php 
                $cacheDir = APP_ROOT . '/cache/pages/';
                $cacheFiles = is_dir($cacheDir) ? glob($cacheDir . '*.html') : [];
                $cacheCount = count($cacheFiles);
                $cacheSize = 0;
                foreach ($cacheFiles as $file) {
                    $cacheSize += filesize($file);
                }
                ?>
                Страниц в кеше: <strong id="cache-count"><?php echo $cacheCount; ?></strong><br>
                Объём кеша: <strong id="cache-size"><?php echo formatSize($cacheSize); ?></strong>
            </div>

        </div>
    </div>
</div>