<!-- СЕКЦИЯ: Custom CSS/JS -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Произвольный код</span>
            <span class="text-[10px] text-slate-400">(CSS, JS)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Custom CSS</label>
                    <textarea name="set_custom_css" rows="4" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base font-mono text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" placeholder="/* Добавьте свои CSS стили */"><?php echo e($customCss); ?></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Будет вставлен в &lt;head&gt; сайта</p>
                </div>
            </div>

            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Custom JS</label>
                    <textarea name="set_custom_js" rows="4" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base font-mono text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" placeholder="// Добавьте свои JavaScript скрипты"><?php echo e($customJs); ?></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Будет вставлен перед &lt;/body&gt; сайта</p>
                </div>
            </div>
        </div>
    </div>
</div>