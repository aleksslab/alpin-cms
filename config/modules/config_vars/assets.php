<!-- Секция: Минификация -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Минификация</span>
            <span class="text-[10px] text-slate-400">(CSS, JS)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">
            <div class="editor-row !items-start">
                <div class="editor-field">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="assets_minify" value="0">
                        <input type="checkbox" name="assets_minify" value="1" <?php echo $assetsMinify ? 'checked' : ''; ?> 
                               class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                        <span class="text-sm font-medium text-slate-700">Включить минификацию CSS/JS модулей</span>
                    </label>
                    <p class="text-[10px] text-slate-400 mt-1">При включении будут созданы .min версии файлов модулей.</p>
                </div>

                <div class="editor-field">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="assets_combine" value="0">
                        <input type="checkbox" name="assets_combine" value="1" <?php echo ($settingsData['assets_combine'] ?? false) ? 'checked' : ''; ?> 
                               class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                        <span class="text-sm font-medium text-slate-700">Объединение</span>
                    </label>
                    <p class="text-[10px] text-slate-400 mt-1">Собирать все CSS/JS модулей страницы в один файл.</p>
                </div>
            </div>
            <!-- СТАТИСТИКА МИНИФИКАЦИИ -->
            <?php 
            $stats = $minifyStats;
            ?>
            <div id="minify-stats-block" class="grid grid-cols-1 sm:grid-cols-2! gap-3 p-3 bg-white rounded-xl border border-slate-200">
                <div class="text-center">
                    <div class="text-xs font-medium text-slate-500">CSS</div>
                    <div class="text-sm font-bold text-slate-800">
                        <span id="minify-css-minified"><?php echo $stats['css']['minified']; ?></span> / <span id="minify-css-total"><?php echo $stats['css']['total']; ?></span>
                        <span id="minify-css-ratio" class="text-[10px] font-normal text-slate-400">
                            <?php if ($stats['css']['total'] > 0): ?>
                                (<?php echo round($stats['css']['ratio'] * 100); ?>%)
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="text-[9px] text-slate-400">
                        <?php if ($stats['css']['total'] > 0): ?>
                            <span id="minify-css-original"><?php echo formatSize($stats['css']['original_size']); ?></span> → <span id="minify-css-minified-size"><?php echo formatSize($stats['css']['minified_size']); ?></span>
                        <?php else: ?>
                            нет файлов
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-xs font-medium text-slate-500">JS</div>
                    <div class="text-sm font-bold text-slate-800">
                        <span id="minify-js-minified"><?php echo $stats['js']['minified']; ?></span> / <span id="minify-js-total"><?php echo $stats['js']['total']; ?></span>
                        <span id="minify-js-ratio" class="text-[10px] font-normal text-slate-400">
                            <?php if ($stats['js']['total'] > 0): ?>
                                (<?php echo round($stats['js']['ratio'] * 100); ?>%)
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="text-[9px] text-slate-400">
                        <?php if ($stats['js']['total'] > 0): ?>
                            <span id="minify-js-original"><?php echo formatSize($stats['js']['original_size']); ?></span> → <span id="minify-js-minified-size"><?php echo formatSize($stats['js']['minified_size']); ?></span>
                        <?php else: ?>
                            нет файлов
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" onclick="window.openMinifyExceptions()" 
                        class="px-4 py-2 text-sm border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-all cursor-pointer flex items-center gap-2">
                    <span class="icon-settings text-sm"></span> Исключения
                </button>
                
                <button type="button" onclick="window.rebuildAllMinFiles()" 
                        class="px-4 py-2 text-sm border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-all cursor-pointer flex items-center gap-2">
                    <span class="icon-refresh-cw text-sm"></span> Пересобрать все .min
                </button>
                
                <button type="button" onclick="window.deleteAllMinFiles()" 
                        class="px-4 py-2 text-sm border border-rose-200 text-rose-600 font-bold rounded-xl hover:bg-rose-50 transition-all cursor-pointer flex items-center gap-2">
                    <span class="icon-trash-2 text-sm"></span> Удалить все .min
                </button>
            </div>
            <p class="text-[10px] text-slate-400 mt-1">
                <span class="text-rose-500">Удалить все .min</span> — удаляет все минифицированные файлы. Затем можно пересобрать заново.
            </p>

        </div>
    </div>
</div>