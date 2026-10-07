<!-- СЕКЦИЯ: SEO настройки -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">SEO настройки</span>
            <span class="text-[10px] text-slate-400">(meta-теги, sitemap, robots)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Meta Title</label>
                    <input type="text" name="set_meta_title" value="<?php echo e($metaTitle); ?>" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" placeholder="Название компании - описание" />
                    <p class="text-[10px] text-slate-400 mt-1">Рекомендуемая длина: 50-60 символов</p>
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Meta Keywords</label>
                    <input type="text" name="set_meta_keywords" value="<?php echo e($metaKeywords); ?>" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" placeholder="ключевое слово 1, ключевое слово 2" />
                    <p class="text-[10px] text-slate-400 mt-1">Ключевые слова через запятую</p>
                </div>
            </div>

            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Meta Description</label>
                    <textarea name="set_meta_description" rows="3" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" placeholder="Краткое описание сайта для поисковых систем"><?php echo e($metaDesc); ?></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Рекомендуемая длина: 150-160 символов</p>
                </div>
            </div>

            <!-- ТЕХНИЧЕСКИЙ SEO -->
            <?php
            $seoSitemapEnabled  = $settingsData['seo_sitemap_enabled'] ?? true;
            $seoRobotsEnabled   = $settingsData['seo_robots_enabled'] ?? true;
            $seoAutoRegenerate  = $settingsData['seo_auto_regenerate'] ?? false;
            $lastSeoRegenerate  = (int)($settingsData['last_seo_regenerate'] ?? 0);

            $sitemapFile = APP_ROOT . '/sitemap.xml';
            $robotsFile  = APP_ROOT . '/robots.txt';
            $sitemapExists = file_exists($sitemapFile);
            $robotsExists  = file_exists($robotsFile);
            ?>

            <div class="border-t border-slate-200/60 pt-4 mt-2">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Технический SEO</h4>

                <div class="space-y-3">
                    <!-- Sitemap -->
                    <label class="matrix-label">
                        <input type="hidden" name="seo_sitemap_enabled" value="0">
                        <input type="checkbox" name="seo_sitemap_enabled" value="1" <?php echo $seoSitemapEnabled ? 'checked' : ''; ?>>
                        <span class="text-sm font-medium text-slate-700">Генерировать sitemap.xml</span>
                    </label>

                    <!-- Robots -->
                    <label class="matrix-label">
                        <input type="hidden" name="seo_robots_enabled" value="0">
                        <input type="checkbox" name="seo_robots_enabled" value="1" <?php echo $seoRobotsEnabled ? 'checked' : ''; ?>>
                        <span class="text-sm font-medium text-slate-700">Генерировать robots.txt</span>
                    </label>

                    <!-- Автообновление -->
                    <label class="matrix-label">
                        <input type="hidden" name="seo_auto_regenerate" value="0">
                        <input type="checkbox" name="seo_auto_regenerate" value="1" <?php echo $seoAutoRegenerate ? 'checked' : ''; ?>>
                        <span class="text-sm font-medium text-slate-700">Автообновлять при изменении страниц</span>
                    </label>
                    <p class="text-[10px] text-slate-400 ml-6 -mt-2">
                        Если выключено — файлы обновляются только вручную или через cron.
                    </p>
                </div>

                <!-- Статус и ссылки -->
                <div class="mt-4 p-3 bg-white rounded-xl border border-slate-200">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-xs text-slate-500">
                            <?php if ($lastSeoRegenerate > 0): ?>
                                Последняя генерация: <strong class="text-slate-700"><?php echo date('d.m.Y H:i', $lastSeoRegenerate); ?></strong>
                            <?php else: ?>
                                <span class="text-slate-400 italic">Последняя генерация: не выполнялась</span>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <?php if ($sitemapExists): ?>
                                <a href="/sitemap.xml" target="_blank" class="text-xs text-[var(--primary-color)] hover:underline font-semibold flex items-center gap-1">
                                    <span class="icon-external-link text-sm"></span> sitemap.xml
                                </a>
                            <?php endif; ?>
                            <?php if ($robotsExists): ?>
                                <a href="/robots.txt" target="_blank" class="text-xs text-[var(--primary-color)] hover:underline font-semibold flex items-center gap-1">
                                    <span class="icon-external-link text-sm"></span> robots.txt
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <button type="button" onclick="window.regenerateSeoFiles()"
                                class="px-4 py-2 bg-slate-100 border border-slate-200 text-slate-700 text-sm font-bold rounded-xl hover:bg-slate-200 transition-all cursor-pointer flex items-center gap-2">
                            <span class="icon-refresh-cw text-sm"></span> 
                            <?php if ($lastSeoRegenerate > 0): ?>
                                Пересобрать
                            <?php else: ?>
                                Генерировать
                            <?php endif; ?>
                        </button>
                        <p class="text-[10px] text-slate-400 mt-2">
                            Файлы создаются в корне сайта: <span class="font-mono">sitemap.xml</span>, <span class="font-mono">robots.txt</span>.
                        </p>
                    </div>
                    <!-- URL крона -->
                    <div class="mt-3 pt-3 border-t border-slate-100">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                            URL для планировщика хостинга (cron)
                        </label>
                        <div class="flex flex-col lg:flex-row gap-2">
                            <input type="text"
                                   readonly
                                   value="https://<?php echo e($_SERVER['HTTP_HOST'] ?? 'localhost'); ?>/config/cron/seo.php?token=<?php echo e(getCronToken()); ?>"
                                   class="w-full min-w-0 px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-[11px] font-mono text-slate-600 select-all focus:outline-none">
                            <button type="button"
                                    onclick="copyToClipboard(this)"
                                    class="w-full lg:w-auto lg:flex-initial lg:w-[140px] px-4 py-2.5 bg-slate-100 border border-slate-200 text-slate-700 text-sm font-bold rounded-xl hover:bg-slate-200 transition-all whitespace-nowrap flex items-center justify-center gap-2">
                                <span class="icon-copy text-sm"></span> Копировать
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-2">
                            Рекомендуемая частота: 1 раз в сутки. Использует общий токен крона.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>