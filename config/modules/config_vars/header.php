<!-- СЕКЦИЯ: Настройки хедера -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Настройки хедера</span>
            <span class="text-[10px] text-slate-400">(вариант, фиксация, бургер)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">
            <!-- Выбор варианта хедера -->
            <?php if (empty($headerVariants)): ?>
                <div class="text-center py-6 text-slate-400 text-sm">
                    <span class="icon-alert-triangle text-2xl block mb-2 text-slate-300"></span>
                    Нет доступных вариантов хедера.<br>
                    Добавьте папку в <span class="font-mono text-xs bg-white px-1 py-0.5 rounded border border-slate-200">/templates/headers/</span>
                </div>
            <?php else: ?>
                <?php 
                $currentVariant = null;
                foreach ($headerVariants as $v) {
                    if ($v['id'] === $currentHeader) {
                        $currentVariant = $v;
                        break;
                    }
                }
                ?>
                <div class="flex items-start gap-2 header-preview-container">
                    <div class="w-[42px] h-[42px] border border-slate-200 bg-slate-100 rounded-xl cursor-pointer flex items-center justify-center text-slate-400 hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-colors" 
                         onclick="zoomCarouselImage(this)" 
                         title="Предпросмотр хедера">
                        <?php if ($currentVariant && !empty($currentVariant['preview'])): ?>
                            <img src="<?php echo $currentVariant['preview']; ?>" class="js-slide-preview-img !w-0">
                        <?php endif; ?>
                        <span class="icon-eye<?php echo ($currentVariant && !empty($currentVariant['preview'])) ? '' : '-off'; ?> text-2xl"></span>
                    </div>
                    <div class="flex-1">
                        <select name="header_variant" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                            <?php foreach ($headerVariants as $variant): ?>
                                <option value="<?php echo $variant['id']; ?>" <?php echo $currentHeader === $variant['id'] ? 'selected' : ''; ?> data-preview="<?php echo $variant['preview'] ?? ''; ?>">
                                    <?php echo $variant['name']; ?>
                                    <?php if (!empty($variant['author'])): ?>
                                        (by <?php echo $variant['author']; ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Добавьте новый вариант в <span class="font-mono text-[10px] bg-white px-1 py-0.5 rounded border border-slate-200">/templates/headers/</span></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Настройки фиксации хедера -->
            <div class="border-t border-slate-200/60 pt-4 mt-2">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Фиксация хедера</h4>

                <div class="editor-field">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="header_fixed" value="0">
                        <input type="checkbox" name="header_fixed" value="1" <?php echo ($settingsData['header_fixed'] ?? true) ? 'checked' : ''; ?> 
                               class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                        <span class="text-sm font-medium text-slate-700">Прилипающий хедер (sticky)</span>
                    </label>
                    <p class="text-[10px] text-slate-400 mt-1">Хедер прилипает к верху при скролле</p>
                </div>
            </div>

            <!-- Настройки бургера -->
            <div class="border-t border-slate-200/60 pt-4 mt-2">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Мобильное меню (бургер)</h4>
                
                <div class="editor-row !items-center">
                    <div class="editor-field">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="burger_enabled" value="0">
                            <input type="checkbox" name="burger_enabled" value="1" <?php echo $burgerEnabled ? 'checked' : ''; ?> 
                                   class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                            <span class="text-sm font-medium text-slate-700">Включить бургер-меню</span>
                        </label>
                        <p class="text-[10px] text-slate-400 mt-1">На мобильных устройствах меню сворачивается в бургер</p>
                    </div>
                    <div class="editor-field">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Брекпоинт</label>
                        <select name="burger_breakpoint" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                            <option value="sm" <?php echo $burgerBreakpoint === 'sm' ? 'selected' : ''; ?>>sm (640px)</option>
                            <option value="md" <?php echo $burgerBreakpoint === 'md' ? 'selected' : ''; ?>>md (768px)</option>
                            <option value="lg" <?php echo $burgerBreakpoint === 'lg' ? 'selected' : ''; ?>>lg (1024px)</option>
                            <option value="xl" <?php echo $burgerBreakpoint === 'xl' ? 'selected' : ''; ?>>xl (1280px)</option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">На этом брекпоинте и меньше меню сворачивается в бургер</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>