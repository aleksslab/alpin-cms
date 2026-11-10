<?php
/**
 * Секция настроек: Цветовая схема (светлая тема).
 * Ожидает в области видимости: $settingsData
 */
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$themePrimaryColor = $settingsData['theme']['primary_color'] ?? '#10B981';
$themePrimaryDark  = $settingsData['theme']['primary_dark'] ?? '#059669';
$paletteLight = getThemePalette($settingsData, 'light');
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

            <!-- Внедрение ДО палитры (например, табы и селект темы) -->
            <?php render_plugin_settings('colors', 'before'); ?>

            <!-- Группа 2: Светлая палитра (ядро) -->
            <div class="border-t border-slate-200/60 pt-4">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Палитра (светлая тема)</h4>
                
                <!-- Фоны -->
                <div class="mb-4">
                    <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Фоны</h5>
                    <div class="grid grid-cols-1 lg:grid-cols-3! gap-3">
                        <?php 
                        $bgFields = ['bg_main' => 'Фон страницы', 'bg_card' => 'Фон карточек', 'bg_section' => 'Фон секций'];
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

                <!-- Текст и границы -->
                <div>
                    <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Текст и границы</h5>
                    <div class="grid grid-cols-1 lg:grid-cols-3! gap-3">
                        <?php 
                        $textFields = ['text_main' => 'Основной текст', 'text_muted' => 'Второстепенный текст', 'border_color' => 'Цвет границ'];
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

            <!-- Внедрение ПОСЛЕ палитры (например, тёмная тема и скрипты) -->
            <?php render_plugin_settings('colors', 'after'); ?>

            <p class="text-[10px] text-slate-400 mt-3">Пустые поля используют значения по умолчанию.</p>
        </div>
    </div>
</div>