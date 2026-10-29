<!-- СЕКЦИЯ: Open Graph & Twitter -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Open Graph & Twitter</span>
            <span class="text-[10px] text-slate-400">(соцсети)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">OG Title</label>
                    <input type="text" name="set_og_title" value="<?php echo e($ogTitle); ?>" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" placeholder="Название компании - слоган" />
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">OG URL</label>
                    <input type="text" name="set_og_url" value="<?php echo e($ogUrl); ?>" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" placeholder="https://company.com" />
                </div>
            </div>

            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">OG Description</label>
                    <textarea name="set_og_description" rows="2" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" placeholder="Описание для соцсетей"><?php echo e($ogDesc); ?></textarea>
                </div>
            </div>

            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">OG Image</label>
                    <div class="flex gap-2 items-center js-media-input-group">
                        <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview">
                            <?php if (!empty($ogImage)): ?>
                                <img src="/<?php echo e($ogImage); ?>" class="js-slide-preview-img w-full h-full object-contain" alt="Превью" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                                <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 hidden">
                                    <span class="icon-image text-lg"></span>
                                </div>
                            <?php else: ?>
                                <img src="" class="js-slide-preview-img w-full h-full object-contain hidden" alt="Превью">
                                <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400">
                                    <span class="icon-image text-lg"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="text" name="set_og_image" value="<?php echo e($ogImage); ?>" 
                               class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                               placeholder="/images/og-image.jpg" 
                               oninput="updatePreviewOnManualInput(this)">
                        <button type="button" onclick="openMediaModal(this, true)" 
                                class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-bold text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                            <span class="icon-log-out rotate-90 text-sm"></span> Фото
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Рекомендуемый размер: 1200x630px</p>
                </div>
            </div>

            <div class="border-t border-slate-200/60 pt-4 mt-2">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Twitter Card</h4>
                
                <div class="editor-row">
                    <div class="editor-field">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Twitter Title</label>
                        <input type="text" name="set_twitter_title" value="<?php echo e($twitterTitle); ?>" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" placeholder="Название компании - слоган" />
                    </div>
                </div>

                <div class="editor-row">
                    <div class="editor-field">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Twitter Description</label>
                        <textarea name="set_twitter_description" rows="2" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" placeholder="Описание для Twitter"><?php echo e($twitterDesc); ?></textarea>
                    </div>
                </div>

                <div class="editor-row">
                    <div class="editor-field">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Twitter Image</label>
                        <div class="flex gap-2 items-center js-media-input-group">
                            <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview">
                                <?php if (!empty($twitterImage)): ?>
                                    <img src="/<?php echo e($twitterImage); ?>" class="js-slide-preview-img w-full h-full object-contain" alt="Превью" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                                    <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 hidden">
                                        <span class="icon-image text-lg"></span>
                                    </div>
                                <?php else: ?>
                                    <img src="" class="js-slide-preview-img w-full h-full object-contain hidden" alt="Превью">
                                    <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400">
                                        <span class="icon-image text-lg"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="text" name="set_twitter_image" value="<?php echo e($twitterImage); ?>" 
                                   class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                                   placeholder="/images/twitter-card.jpg" 
                                   oninput="updatePreviewOnManualInput(this)">
                            <button type="button" onclick="openMediaModal(this, true)" 
                                    class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-bold text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                                <span class="icon-log-out rotate-90 text-sm"></span> Фото
                            </button>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Рекомендуемый размер: 1200x675px (оптимально для Twitter)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>