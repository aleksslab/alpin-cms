<!-- СЕКЦИЯ: Контактная информация -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-down text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Контактная информация</span>
            <span class="text-[10px] text-slate-400">(название, телефон, email, адрес)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50">
        <div class="space-y-5">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Название компании / Сайта</label>
                <input type="text" name="set_name" value="<?php echo e($settingsData['name'] ?? ''); ?>" 
                       class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" 
                       placeholder="Название компании">
                <p class="text-[10px] text-slate-400 mt-2">
                    Можно использовать HTML. Пример: <span class="font-mono text-xs bg-white px-1 py-0.5 rounded border border-slate-200">&lt;span class="text-lg font-black"&gt;Название&lt;/span&gt;</span>
                </p>
            </div>
            
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Слоган сайта</label>
                <textarea name="set_slogan" rows="3" 
                          class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" 
                          placeholder="Краткий слоган компании"><?php echo e(trim($settingsData['slogan'] ?? '')); ?></textarea>
                <p class="text-[10px] text-slate-400 mt-2">
                    Можно использовать HTML. Пример: <span class="font-mono text-xs bg-white px-1 py-0.5 rounded border border-slate-200">&lt;span class="text-lg font-black"&gt;Слоган сайта&lt;/span&gt;</span>
                </p>
            </div>

            <div class="editor-row mb-5">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Контактный телефон</label>
                    <input type="text" name="set_phone" value="<?php echo e(formatPhone($settingsData['phone'] ?? '')); ?>" 
                           class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" 
                           placeholder="+7 (900) 000-00-00" />
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Email организации</label>
                    <input type="email" name="set_email" value="<?php echo e($settingsData['email'] ?? ''); ?>" 
                           class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" 
                           placeholder="info@company.com" />
                </div>
            </div>

            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Физический адрес компании</label>
                <input type="text" name="set_address" value="<?php echo e($settingsData['address'] ?? ''); ?>" 
                       class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" 
                       placeholder="г. Москва, ул. Тверская, д. 1" />
            </div>
        </div>
    </div>
</div>