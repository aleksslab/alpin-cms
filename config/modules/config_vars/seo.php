<!-- СЕКЦИЯ: SEO настройки -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">SEO настройки</span>
            <span class="text-[10px] text-slate-400">(meta-теги)</span>
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
        </div>
    </div>
</div>