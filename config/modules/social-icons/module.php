<?php
/**
 * Модуль: Социальные иконки — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$settingsClass = $moduleData['settings']['class'] ?? '';
$iconSize = $moduleData['icon_size'] ?? 'w-6 h-6';
$iconColor = $moduleData['icon_color'] ?? 'text-[var(--text-main)]';
$iconHoverColor = $moduleData['icon_hover_color'] ?? 'text-[var(--primary-color)]';
$showLabels = $moduleData['show_labels'] ?? false;

$socials = getSavedSocials();
$hasSocials = !empty($socials);

// Варианты размеров иконок
$sizeOptions = [
    'w-4 h-4' => 'Маленькие (16px)',
    'w-5 h-5' => 'Средние (20px)',
    'w-6 h-6' => 'Стандартные (24px)',
    'w-8 h-8' => 'Крупные (32px)',
    'w-10 h-10' => 'Очень крупные (40px)',
];

// Варианты цветов из CSS-переменных (только названия)
$colorOptions = [
    'text-[var(--primary-color)]' => 'Основной бренд',
    'text-[var(--primary-dark)]'  => 'Основной тёмный',
    'text-[var(--text-main)]'     => 'Основной текст',
    'text-[var(--text-muted)]'    => 'Серый текст',
    'text-[var(--bg-main)]'       => 'Цвет фона',
];
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Мы в соцсетях" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Подписывайтесь на нас" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание блока..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки отображения -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки отображения</h4>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field flex items-end">
                <label class="flex items-center gap-2 cursor-pointer pb-2">
                    <input type="hidden" name="show_labels" value="0">
                    <input type="checkbox" name="show_labels" id="show_labels" value="1" <?php echo $showLabels ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm text-slate-600 font-medium">Показывать названия</span>
                </label>
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Размер иконок</label>
                <select name="icon_size" id="icon-size-select" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <?php foreach ($sizeOptions as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $iconSize == $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="editor-field">
                <!-- Пусто -->
            </div>
        </div>

        <!-- Цвет и ховер -->
        <div class="editor-row">
            <!-- Поле: Основной цвет -->
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Цвет иконок</label>
                <div class="flex gap-2 items-center">
                    <select name="icon_color" id="icon-color-select" class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                        <?php foreach ($colorOptions as $value => $name): ?>
                            <option value="<?php echo $value; ?>" <?php echo $iconColor == $value ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                        <option value="__custom__" <?php echo !array_key_exists($iconColor, $colorOptions) ? 'selected' : ''; ?>>Свой цвет</option>
                    </select>
                    <input type="color" id="icon-color-picker" 
                           value="<?php echo !array_key_exists($iconColor, $colorOptions) && !empty($iconColor) ? str_replace(['text-[', ']', 'text-'], '', $iconColor) : '#64748b'; ?>" 
                           class="w-12 h-10 p-1 border border-slate-200 rounded-lg cursor-pointer flex-shrink-0 <?php echo array_key_exists($iconColor, $colorOptions) ? 'hidden' : ''; ?>">
                    <input type="hidden" name="icon_color" id="icon-color-final" value="<?php echo e($iconColor); ?>">
                </div>
            </div>

            <!-- Поле: Цвет при наведении -->
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Цвет при наведении</label>
                <div class="flex gap-2 items-center">
                    <select name="icon_hover_color" id="icon-hover-select" class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                        <?php foreach ($colorOptions as $value => $name): ?>
                            <option value="<?php echo $value; ?>" <?php echo $iconHoverColor == $value ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                        <option value="__custom__" <?php echo !array_key_exists($iconHoverColor, $colorOptions) ? 'selected' : ''; ?>>Свой цвет</option>
                    </select>
                    <input type="color" id="icon-hover-picker" 
                           value="<?php echo !array_key_exists($iconHoverColor, $colorOptions) && !empty($iconHoverColor) ? str_replace(['text-[', ']', 'text-'], '', $iconHoverColor) : '#10b981'; ?>" 
                           class="w-12 h-10 p-1 border border-slate-200 rounded-lg cursor-pointer flex-shrink-0 <?php echo array_key_exists($iconHoverColor, $colorOptions) ? 'hidden' : ''; ?>">
                    <input type="hidden" name="icon_hover_color" id="icon-hover-final" value="<?php echo e($iconHoverColor); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- БЛОК 3: Превью соцсетей -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200">
        <h4 class="text-sm font-bold text-slate-700 mb-3">Предпросмотр</h4>
        
        <?php if ($hasSocials): ?>
            <div id="social-preview" class="flex flex-wrap justify-center items-center gap-4 md:gap-6 p-4 bg-white rounded-lg border border-slate-200">
                <?php foreach (array_slice($socials, 0, 5) as $social): ?>
                    <?php if (empty($social['url'])) continue; ?>
                    <div class="flex flex-col items-center gap-1" data-label="<?php echo e($social['label'] ?? ''); ?>">
                        <span class="<?php echo $iconSize; ?> transition-colors" style="color: <?php echo $iconColor; ?>;">
                            <svg class="w-full h-full" viewBox="0 0 24 24">
                                <use href="/fonts/brands.svg#<?php echo e($social['id']); ?>"/>
                            </svg>
                        </span>
                        <?php if ($showLabels && !empty($social['label'])): ?>
                            <span class="text-xs text-slate-500"><?php echo e($social['label']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (count($socials) > 5): ?>
                    <span id="total" class="text-xs text-slate-400">+<?php echo count($socials) - 5; ?></span>
                <?php endif; ?>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 text-center">Изменения применяются в реальном времени</p>
        <?php else: ?>
            <div class="text-center py-4 text-sm text-slate-400">
                Социальные сети не настроены.
            </div>
        <?php endif; ?>
    </div>
</div>
