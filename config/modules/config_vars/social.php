<!-- СЕКЦИЯ: Социальные сети -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Социальные сети</span>
            <span class="text-[10px] text-slate-400">(ссылки на соцсети)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">
            <div id="social-extra-container" class="space-y-2 grid grid-cols-1 xl:grid-cols-2!">
                <?php 
                $allNetworks = getSocialNetworks();
                $savedSocials = getSavedSocials();
                $primaryIds = getPrimarySocialIds();

                if (empty($savedSocials)) {
                    foreach ($primaryIds as $id) {
                        $savedSocials[] = ['id' => $id, 'url' => ''];
                    }
                }

                $savedIds = array_column($savedSocials, 'id');
                ?>

                <?php foreach ($savedSocials as $social): 
                    $id = $social['id'];
                    $isPrimary = in_array($id, $primaryIds);
                    $label = $allNetworks[$id]['label'] ?? $id;
                ?>
                    <div class="js-social-row flex items-center gap-2 bg-white p-2.5 rounded-xl border border-slate-200" 
                         data-social-id="<?php echo $id; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
                            <use href="/fonts/brands.svg#<?php echo $id; ?>"/>
                        </svg>
                        <span class="text-xs font-medium text-slate-500 w-28 flex-shrink-0"><?php echo $label; ?></span>
                        <input type="text" name="socials[<?php echo $id; ?>]" value="<?php echo e($social['url']); ?>" 
                               class="w-full px-2 py-1.5 bg-transparent border-0 focus:ring-0 text-sm text-slate-800 placeholder-slate-400" 
                               placeholder="<?php echo $label; ?>">
                        <?php if (!$isPrimary): ?>
                            <button type="button" onclick="removeSocialRow(this)" class="text-slate-300 hover:text-rose-500 transition-colors flex-shrink-0">
                                <span class="icon-x text-sm"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Дропдаун -->
            <div class="flex items-center gap-3 relative" id="js-social-dropdown-wrapper">
                <button type="button" id="js-social-dropdown-btn" 
                        class="px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 hover:border-[var(--primary-color)] transition-all cursor-pointer flex items-center gap-2 min-w-[200px] justify-between">
                    <span id="js-social-dropdown-selected" class="flex items-center gap-2">Выберите соцсеть</span>
                    <span class="icon-chevron-down text-xs text-slate-400 flex-shrink-0"></span>
                </button>

                <div id="js-social-dropdown-menu" 
                     class="hidden absolute top-full left-0 mt-1 min-w-[200px] bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-[200px] overflow-y-auto py-1">
                    <?php foreach ($allNetworks as $id => $data): 
                        if (in_array($id, $primaryIds)) continue;
                        if (in_array($id, $savedIds)) continue;
                    ?>
                        <div class="js-social-dropdown-item flex items-center gap-2 px-3 py-2 hover:bg-slate-50 cursor-pointer transition-colors" 
                             data-id="<?php echo $id; ?>">
                            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
                                <use href="/fonts/brands.svg#<?php echo $id; ?>"/>
                            </svg>
                            <span class="text-sm text-slate-700"><?php echo $data['label']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" onclick="addSocialRowFromDropdown()" 
                        class="px-4 py-2.5 bg-[var(--primary-color)] text-white font-bold text-sm rounded-xl hover:bg-[var(--primary-dark)] transition-all cursor-pointer flex items-center gap-1.5 shadow-lg shadow-[var(--primary-color)]/20">
                    <span class="icon-plus text-sm"></span><span class="hidden lg:flex">Добавить</span>
                </button>
            </div>

            <p class="text-[10px] text-slate-400 mt-2">Заполните только нужные поля. Пустые поля не будут выводиться на сайте.</p>
        </div>
    </div>
</div>