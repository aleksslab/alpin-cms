<!-- СЕКЦИЯ: Настройки футера -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Настройки футера</span>
            <span class="text-[10px] text-slate-400">(вариант, копирайт)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">
            <!-- Выбор варианта футера -->
            <?php if (empty($footerVariants)): ?>
                <div class="text-center py-6 text-slate-400 text-sm">
                    <span class="icon-alert-triangle text-2xl block mb-2 text-slate-300"></span>
                    Нет доступных вариантов футера.<br>
                    Добавьте папку в <span class="font-mono text-xs bg-white px-1 py-0.5 rounded border border-slate-200">/templates/footers/</span>
                </div>
            <?php else: ?>
                <?php 
                $currentVariant = null;
                foreach ($footerVariants as $v) {
                    if ($v['id'] === $currentFooter) {
                        $currentVariant = $v;
                        break;
                    }
                }
                ?>
                <div class="flex items-start gap-2 footer-preview-container">
                    <div class="w-[42px] h-[42px] border border-slate-200 bg-slate-100 rounded-xl cursor-pointer flex items-center justify-center text-slate-400 hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-colors" 
                         onclick="zoomCarouselImage(this)" 
                         title="Предпросмотр футера">
                        <?php if ($currentVariant && !empty($currentVariant['preview'])): ?>
                            <img src="<?php echo $currentVariant['preview']; ?>" class="js-slide-preview-img !w-0">
                        <?php endif; ?>
                        <span class="icon-eye<?php echo ($currentVariant && !empty($currentVariant['preview'])) ? '' : '-off'; ?> text-2xl"></span>
                    </div>
                    <div class="flex-1">
                        <select name="footer_variant" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                            <?php foreach ($footerVariants as $variant): ?>
                                <option value="<?php echo $variant['id']; ?>" <?php echo $currentFooter === $variant['id'] ? 'selected' : ''; ?> data-preview="<?php echo $variant['preview'] ?? ''; ?>">
                                    <?php echo $variant['name']; ?>
                                    <?php if (!empty($variant['author'])): ?>
                                        (by <?php echo $variant['author']; ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Добавьте новый вариант в <span class="font-mono text-[10px] bg-white px-1 py-0.5 rounded border border-slate-200">/templates/footers/</span></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Настройки копирайта -->
            <div class="border-t border-slate-200/60 pt-4 mt-2">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Нижняя панель (копирайт)</h4>

                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Текст копирайта (поддерживает HTML)</label>
                    <input type="text" name="footer_copyright" value="<?php echo e($footerCopyright); ?>" 
                           class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" 
                           placeholder="Все права защищены.">
                    <p class="text-[10px] text-slate-400 mt-2">
                        Можно использовать HTML. Если оставить пустым — будет выводиться стандартный текст: 
                        <span class="font-mono text-xs bg-white px-1 py-0.5 rounded border border-slate-200">&copy; <?php echo date('Y'); ?> <?php echo getPlainText($settingsData['name']); ?>. Все права защищены.</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>