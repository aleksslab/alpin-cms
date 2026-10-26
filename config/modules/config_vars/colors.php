<?php
/**
 * Секция настроек: Цветовая схема.
 * Ожидает в области видимости: $settingsData
 */
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$themePrimaryColor = $settingsData['theme']['primary_color'] ?? '#10B981';
$themePrimaryDark  = $settingsData['theme']['primary_dark'] ?? '#059669';
$themeDefault      = $settingsData['theme_default'] ?? 'light';

// Получаем палитры с учетом fallback (если старая структура settings.json)
$paletteLight = getThemePalette($settingsData, 'light');
$paletteDark  = getThemePalette($settingsData, 'dark');
?>

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
        <div class="space-y-6">

            <!-- Группа 1: Брендовые цвета -->
            <div>
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Брендовые цвета</h4>
                <div class="grid grid-cols-1 lg:grid-cols-3! gap-3">
                    <div class="editor-field color-picker-sync">
                        <label class="block text-[9px] font-medium text-slate-500 mb-1">Основной цвет</label>
                        <div class="flex gap-2 items-center">
                            <input type="color" name="theme_primary_color" value="<?php echo e($themePrimaryColor); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                            <input type="text" name="theme_primary_color_text" value="<?php echo e($themePrimaryColor); ?>" placeholder="#10B981" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                        </div>
                    </div>
                    <div class="editor-field color-picker-sync">
                        <label class="block text-[9px] font-medium text-slate-500 mb-1">Цвет при наведении (hover)</label>
                        <div class="flex gap-2 items-center">
                            <input type="color" name="theme_primary_dark" value="<?php echo e($themePrimaryDark); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                            <input type="text" name="theme_primary_dark_text" value="<?php echo e($themePrimaryDark); ?>" placeholder="#059669" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Тема по умолчанию -->
            <div class="border-t border-slate-200/60 pt-4">
                <div class="editor-field max-w-xs">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Тема по умолчанию</label>
                    <select name="theme_default" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                        <option value="light" <?php echo $themeDefault === 'light' ? 'selected' : ''; ?>>Светлая</option>
                        <option value="dark" <?php echo $themeDefault === 'dark' ? 'selected' : ''; ?>>Тёмная</option>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Какую тему увидят пользователи без сохранённого выбора.</p>
                </div>
            </div>

            <!-- Табы переключения палитр -->
            <div class="border-t border-slate-200/60 pt-4">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Настройка палитры</h4>
                    
                    <!-- Табы -->
                    <div class="flex p-1 bg-slate-100 rounded-xl w-full max-w-[240px]">
                        <button type="button" class="theme-tab-btn flex-1 py-1.5 text-xs font-bold rounded-lg transition-all bg-white text-slate-800 shadow-sm cursor-pointer flex items-center justify-center gap-1.5" data-theme-tab="light">
                            <span class="icon-sun text-sm"></span> Светлая
                        </button>
                        <button type="button" class="theme-tab-btn flex-1 py-1.5 text-xs font-bold rounded-lg transition-all text-slate-500 hover:text-slate-700 cursor-pointer flex items-center justify-center gap-1.5" data-theme-tab="dark">
                            <span class="icon-moon text-sm"></span> Тёмная
                        </button>
                    </div>
                </div>

                <!-- Блок: Светлая тема -->
                <div id="theme-palette-light" class="theme-palette-block">
                    <!-- Группа: Фоны -->
                    <div class="mb-4">
                        <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Фоны</h5>
                        <div class="grid grid-cols-1 lg:grid-cols-3! gap-3">
                            <?php 
                            $bgFields = [
                                'bg_main' => 'Фон страницы',
                                'bg_card' => 'Фон карточек',
                                'bg_section' => 'Фон секций'
                            ];
                            foreach ($bgFields as $key => $label): 
                                $val = $paletteLight[$key] ?? '#FFFFFF';
                            ?>
                            <div class="editor-field color-picker-sync">
                                <label class="block text-[9px] font-medium text-slate-500 mb-1"><?php echo $label; ?></label>
                                <div class="flex gap-2 items-center">
                                    <input type="color" name="theme_light_<?php echo $key; ?>" value="<?php echo e($val); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                                    <input type="text" name="theme_light_<?php echo $key; ?>_text" value="<?php echo e($val); ?>" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Группа: Текст и границы -->
                    <div>
                        <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Текст и границы</h5>
                        <div class="grid grid-cols-1 lg:grid-cols-3! gap-3">
                            <?php 
                            $textFields = [
                                'text_main' => 'Основной текст',
                                'text_muted' => 'Второстепенный текст',
                                'border_color' => 'Цвет границ'
                            ];
                            foreach ($textFields as $key => $label): 
                                $val = $paletteLight[$key] ?? '#334155';
                            ?>
                            <div class="editor-field color-picker-sync">
                                <label class="block text-[9px] font-medium text-slate-500 mb-1"><?php echo $label; ?></label>
                                <div class="flex gap-2 items-center">
                                    <input type="color" name="theme_light_<?php echo $key; ?>" value="<?php echo e($val); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                                    <input type="text" name="theme_light_<?php echo $key; ?>_text" value="<?php echo e($val); ?>" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Блок: Тёмная тема (скрыт по умолчанию) -->
                <div id="theme-palette-dark" class="theme-palette-block hidden">
                    <!-- Группа: Фоны -->
                    <div class="mb-4">
                        <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Фоны</h5>
                        <div class="grid grid-cols-1 lg:grid-cols-3! gap-3">
                            <?php 
                            foreach ($bgFields as $key => $label): 
                                $val = $paletteDark[$key] ?? '#0F172A';
                            ?>
                            <div class="editor-field color-picker-sync">
                                <label class="block text-[9px] font-medium text-slate-500 mb-1"><?php echo $label; ?></label>
                                <div class="flex gap-2 items-center">
                                    <input type="color" name="theme_dark_<?php echo $key; ?>" value="<?php echo e($val); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                                    <input type="text" name="theme_dark_<?php echo $key; ?>_text" value="<?php echo e($val); ?>" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Группа: Текст и границы -->
                    <div>
                        <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Текст и границы</h5>
                        <div class="grid grid-cols-1 lg:grid-cols-3! gap-3">
                            <?php 
                            foreach ($textFields as $key => $label): 
                                $val = $paletteDark[$key] ?? '#F1F5F9';
                            ?>
                            <div class="editor-field color-picker-sync">
                                <label class="block text-[9px] font-medium text-slate-500 mb-1"><?php echo $label; ?></label>
                                <div class="flex gap-2 items-center">
                                    <input type="color" name="theme_dark_<?php echo $key; ?>" value="<?php echo e($val); ?>" class="w-10 h-9 p-0.5 border border-slate-200 rounded cursor-pointer flex-shrink-0">
                                    <input type="text" name="theme_dark_<?php echo $key; ?>_text" value="<?php echo e($val); ?>" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] font-mono">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <p class="text-[10px] text-slate-400 mt-3">Пустые поля используют значения по умолчанию.</p>
            </div>

        </div>
    </div>
</div>

<!-- JS для переключения табов палитры -->
<script>
(function() {
    'use strict';
    function initColorTabs() {
        var tabs = document.querySelectorAll('.theme-tab-btn');
        var blocks = document.querySelectorAll('.theme-palette-block');
        
        if (!tabs.length || !blocks.length) return;

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var target = this.getAttribute('data-theme-tab');
                
                tabs.forEach(function(t) {
                    t.classList.remove('bg-white', 'text-slate-800', 'shadow-sm');
                    t.classList.add('text-slate-500', 'hover:text-slate-700');
                });
                this.classList.remove('text-slate-500', 'hover:text-slate-700');
                this.classList.add('bg-white', 'text-slate-800', 'shadow-sm');
                
                blocks.forEach(function(block) {
                    block.classList.add('hidden');
                });
                var activeBlock = document.getElementById('theme-palette-' + target);
                if (activeBlock) {
                    activeBlock.classList.remove('hidden');
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initColorTabs);
    } else {
        initColorTabs();
    }
})();
</script>