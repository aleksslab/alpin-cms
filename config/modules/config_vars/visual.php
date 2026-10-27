<!-- СЕКЦИЯ: Визуальные элементы -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Визуальные элементы</span>
            <span class="text-[10px] text-slate-400">(логотип, фавикон)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="editor-row">
            <!-- Логотип -->
            <div class="editor-field js-media-module-container">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Логотип сайта</label>
                <div class="flex gap-2 items-center js-media-input-group">
                    <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview">
                        <?php if (!empty($logo)): ?>
                            <img src="/<?php echo e($logo); ?>" class="js-slide-preview-img w-full h-full object-contain" alt="Превью" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
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
                    <input type="text" name="set_logo" value="<?php echo e($logo); ?>" 
                           class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                           placeholder="/logo.png" 
                           oninput="updatePreviewOnManualInput(this)">
                    <button type="button" onclick="openMediaModal(this, true)" 
                            class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-bold text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                        <span class="icon-log-out rotate-90 text-sm"></span> Фото
                    </button>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Рекомендуемый размер: 200x60px (PNG, SVG)</p>
            </div>

            <!-- Favicon -->
            <div class="editor-field js-media-module-container">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Favicon</label>
                <div class="flex gap-2 items-center js-media-input-group">
                    <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview">
                        <?php if (!empty($favicon)): ?>
                            <img src="/<?php echo e($favicon); ?>" class="js-slide-preview-img w-full h-full object-contain" alt="Превью" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
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
                    <input type="text" name="set_favicon" value="<?php echo e($favicon); ?>" 
                           class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                           placeholder="/favicon.png" 
                           oninput="updatePreviewOnManualInput(this)">
                    <button type="button" onclick="openMediaModal(this, true)" 
                            class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-bold text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                        <span class="icon-log-out rotate-90 text-sm"></span> Фото
                    </button>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Рекомендуемый размер: 32x32px или 64x64px (PNG, ICO, SVG)</p>
            </div>
        </div>

        <!-- Группа: Хлебные крошки -->
        <div class="border-t border-slate-200/60 pt-4 mt-2">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Хлебные крошки</h4>
            
            <!-- Ряд 1: Чекбоксы (3 в ряд) -->
            <div class="grid grid-cols-1 lg:grid-cols-3! gap-4 mb-4 items-center">
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="breadcrumbs_enabled" value="0">
                        <input type="checkbox" name="breadcrumbs_enabled" value="1" <?php echo !empty($settingsData['breadcrumbs_enabled']) ? 'checked' : ''; ?> 
                               class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                        <span class="text-sm font-medium text-slate-700">Включить крошки</span>
                    </label>
                </div>
                
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="breadcrumbs_show_on_home" value="0">
                        <input type="checkbox" name="breadcrumbs_show_on_home" value="1" <?php echo !empty($settingsData['breadcrumbs_show_on_home']) ? 'checked' : ''; ?> 
                               class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                        <span class="text-sm font-medium text-slate-700">Показывать на главной</span>
                    </label>
                </div>

                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="breadcrumbs_show_home_root" value="0">
                        <input type="checkbox" name="breadcrumbs_show_home_root" value="1" <?php echo !empty($settingsData['breadcrumbs_show_home_root']) ? 'checked' : ''; ?> 
                               class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                        <span class="text-sm font-medium text-slate-700">Показывать корень</span>
                    </label>
                </div>
            </div>

            <!-- Ряд 2: Поля (Текст + Селект) -->
            <div class="editor-row mb-2">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Текст корня</label>
                    <input type="text" name="breadcrumbs_home_label" value="<?php echo e($settingsData['breadcrumbs_home_label'] ?? 'Главная'); ?>" 
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                           placeholder="Главная">
                </div>
                
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Разделитель</label>
                    <select name="breadcrumbs_separator" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                        <option value="/" <?php echo ($settingsData['breadcrumbs_separator'] ?? '/') === '/' ? 'selected' : ''; ?>>/ (слеш)</option>
                        <option value=">" <?php echo ($settingsData['breadcrumbs_separator'] ?? '/') === '>' ? 'selected' : ''; ?>> > (стрелка)</option>
                        <option value="→" <?php echo ($settingsData['breadcrumbs_separator'] ?? '/') === '→' ? 'selected' : ''; ?>> → (стрелка вправо)</option>
                        <option value="•" <?php echo ($settingsData['breadcrumbs_separator'] ?? '/') === '•' ? 'selected' : ''; ?>> • (точка)</option>
                        <option value="|" <?php echo ($settingsData['breadcrumbs_separator'] ?? '/') === '|' ? 'selected' : ''; ?>> | (черта)</option>
                    </select>
                </div>
            </div>
            
            <p class="text-[10px] text-slate-400 mt-1">Если страница не найдена в главном меню, будет выведено: Корень → Заголовок страницы.</p>
        </div>
    </div>
</div>