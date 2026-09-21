<!-- СЕКЦИЯ: Цветовая схема -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Цветовая схема</span>
            <span class="text-[10px] text-slate-400">(основные цвета сайта)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">

        <!-- Группа 1: Брендовые цвета -->
        <div class="mb-4">
            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Брендовые цвета</h4>
            <div class="grid grid-cols-1 lg:grid-cols-3! gap-3">
                <div class="editor-field color-picker-sync">
                    <label class="block text-[9px] font-medium text-slate-500 mb-1">Основной цвет</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="theme_primary_color" value="<?php echo e($settingsData['theme']['primary_color'] ?? '#10B981'); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                        <input type="text" name="theme_primary_color_text" value="<?php echo e($settingsData['theme']['primary_color'] ?? '#10B981'); ?>" placeholder="#10B981" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                    </div>
                </div>
                <div class="editor-field color-picker-sync">
                    <label class="block text-[9px] font-medium text-slate-500 mb-1">Цвет при наведении (hover)</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="theme_primary_dark" value="<?php echo e($settingsData['theme']['primary_dark'] ?? '#059669'); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                        <input type="text" name="theme_primary_dark_text" value="<?php echo e($settingsData['theme']['primary_dark'] ?? '#059669'); ?>" placeholder="#059669" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                    </div>
                </div>
            </div>
        </div>

        <!-- Группа 2: Фоны -->
        <div class="mb-4">
            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Фоны</h4>
            <div class="grid grid-cols-1 lg:grid-cols-3! gap-3">
                <div class="editor-field color-picker-sync">
                    <label class="block text-[9px] font-medium text-slate-500 mb-1">Фон страницы</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="theme_bg_main" value="<?php echo e($settingsData['theme']['bg_main'] ?? '#FFFFFF'); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                        <input type="text" name="theme_bg_main_text" value="<?php echo e($settingsData['theme']['bg_main'] ?? '#FFFFFF'); ?>" placeholder="#FFFFFF" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                    </div>
                </div>
                <div class="editor-field color-picker-sync">
                    <label class="block text-[9px] font-medium text-slate-500 mb-1">Фон карточек</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="theme_bg_card" value="<?php echo e($settingsData['theme']['bg_card'] ?? '#FFFFFF'); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                        <input type="text" name="theme_bg_card_text" value="<?php echo e($settingsData['theme']['bg_card'] ?? '#FFFFFF'); ?>" placeholder="#FFFFFF" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                    </div>
                </div>
                <div class="editor-field color-picker-sync">
                    <label class="block text-[9px] font-medium text-slate-500 mb-1">Фон секций</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="theme_bg_section" value="<?php echo e($settingsData['theme']['bg_section'] ?? '#F8FAFC'); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                        <input type="text" name="theme_bg_section_text" value="<?php echo e($settingsData['theme']['bg_section'] ?? '#F8FAFC'); ?>" placeholder="#F8FAFC" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                    </div>
                </div>
            </div>
        </div>

        <!-- Группа 3: Текст и границы -->
        <div>
            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Текст и границы</h4>
            <div class="grid grid-cols-1 lg:grid-cols-3! gap-3">
                <div class="editor-field color-picker-sync">
                    <label class="block text-[9px] font-medium text-slate-500 mb-1">Основной текст</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="theme_text_main" value="<?php echo e($settingsData['theme']['text_main'] ?? '#334155'); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                        <input type="text" name="theme_text_main_text" value="<?php echo e($settingsData['theme']['text_main'] ?? '#334155'); ?>" placeholder="#334155" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                    </div>
                </div>
                <div class="editor-field color-picker-sync">
                    <label class="block text-[9px] font-medium text-slate-500 mb-1">Второстепенный текст</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="theme_text_muted" value="<?php echo e($settingsData['theme']['text_muted'] ?? '#64748B'); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                        <input type="text" name="theme_text_muted_text" value="<?php echo e($settingsData['theme']['text_muted'] ?? '#64748B'); ?>" placeholder="#64748B" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                    </div>
                </div>
                <div class="editor-field color-picker-sync">
                    <label class="block text-[9px] font-medium text-slate-500 mb-1">Цвет границ</label>
                    <div class="flex gap-2 items-center">
                        <input type="color" name="theme_border_color" value="<?php echo e($settingsData['theme']['border_color'] ?? '#F1F5F9'); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                        <input type="text" name="theme_border_color_text" value="<?php echo e($settingsData['theme']['border_color'] ?? '#F1F5F9'); ?>" placeholder="#F1F5F9" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                    </div>
                </div>
            </div>
        </div>

        <p class="text-[10px] text-slate-400 mt-3">Пустые поля используют значения по умолчанию.</p>
    </div>
</div>