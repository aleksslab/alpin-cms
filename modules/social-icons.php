<?php
/**
 * Модуль: Социальные иконки — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$moduleClass = $moduleData['settings']['class'] ?? '';

// Валидация CSS-классов (защита от XSS через сломанный атрибут class)
$iconSize = $moduleData['icon_size'] ?? 'w-6 h-6';
if (!preg_match('~^[a-z0-9\-\_\.\:\[\]\(\)\/\s]+$~i', $iconSize)) {
    $iconSize = 'w-6 h-6';
}

$iconColor = $moduleData['icon_color'] ?? 'text-slate-600';
if (!preg_match('~^[a-z0-9\-\_\.\:\[\]\(\)\/\s]+$~i', $iconColor)) {
    $iconColor = 'text-slate-600';
}

$iconHoverColor = $moduleData['icon_hover_color'] ?? 'hover:text-[var(--primary-color)]';
if (!preg_match('~^[a-z0-9\-\_\.\:\[\]\(\)\/\s]+$~i', $iconHoverColor)) {
    $iconHoverColor = 'hover:text-[var(--primary-color)]';
}

$showLabels = $moduleData['show_labels'] ?? false;

$socials = getSavedSocials();

if (empty($socials)) {
    return;
}

$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);
?>

<?php if ($hasHeader): ?>
<div class="text-center max-w-3xl mx-auto mb-12">
    <?php if (!empty($subtitle)): ?>
    <span class="text-[var(--primary-color)] font-bold tracking-wider uppercase text-sm block mb-2"><?php echo e($subtitle); ?></span>
    <?php endif; ?>

    <?php if (!empty($title)): ?>
    <h2 class="text-3xl md:text-4xl font-bold text-slate-800 mb-4"><?php echo e($title); ?></h2>
    <?php endif; ?>

    <?php if (!empty($description)): ?>
    <p class="text-[var(--text-muted)] max-w-2xl mx-auto text-lg"><?php echo e($description); ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="flex flex-wrap justify-center items-center gap-4 md:gap-6 <?php echo e($moduleClass); ?>">
    <?php foreach ($socials as $social): ?>
        <?php if (empty($social['url'])) continue; ?>
        <!-- К значению ховера динамически добавляется префикс hover: -->
        <a href="<?php echo e(safeUrl($social['url'])); ?>" 
           target="_blank" 
           rel="noopener noreferrer"
           class="flex flex-col items-center gap-1 transition-all hover:scale-110 <?php echo e($iconColor); ?> hover:<?php echo e($iconHoverColor); ?>"
           aria-label="<?php echo e($social['label'] ?? ''); ?>">
            <span class="<?php echo e($iconSize); ?> transition-colors">
                <svg class="w-full h-full">
                    <use href="/fonts/brands.svg#<?php echo e($social['id']); ?>"/>
                </svg>
            </span>
            <?php if ($showLabels && !empty($social['label'])): ?>
                <span class="text-xs transition-colors"><?php echo e($social['label']); ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
