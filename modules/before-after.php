<?php
/**
 * Модуль: До и После — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$beforeImage = $moduleData['before_image'] ?? '';
$afterImage = $moduleData['after_image'] ?? '';
$orientation = $moduleData['orientation'] ?? 'horizontal';
$defaultPosition = $moduleData['default_position'] ?? 50;
$moduleClass = $moduleData['settings']['class'] ?? '';

if (empty($beforeImage) || empty($afterImage)) {
    return;
}

$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);
$isVertical = $orientation == 'vertical';
$uniqueId = 'ba-' . md5($beforeImage . $afterImage);
?>

<div class="<?php echo e($moduleClass); ?>">
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

    <div class="before-after-container <?php echo $isVertical ? 'ba-vertical' : 'ba-horizontal'; ?>" id="<?php echo $uniqueId; ?>" data-default="<?php echo e($defaultPosition); ?>">
        <div class="ba-wrapper">
            <!-- После (нижний слой, задает пропорции всего модуля) -->
            <div class="ba-after-full">
                <img src="<?php echo e($afterImage); ?>" alt="После" class="ba-img-base">
                <div class="ba-label ba-label-after">После</div>
            </div>
            
            <!-- До (верхний слой, плавно обрезается) -->
            <div class="ba-before-clip">
                <img src="<?php echo e($beforeImage); ?>" alt="До" class="ba-img-overlay">
                <div class="ba-label ba-label-before">До</div>
            </div>
            
            <!-- Разделитель -->
            <div class="ba-handle">
                <div class="ba-line"></div>
                <div class="ba-arrows">
                    <span class="ba-arrow ba-arrow-left">
                        <svg xmlns="http://w3.org" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </span>
                    <span class="ba-arrow ba-arrow-right">
                        <svg xmlns="http://w3.org" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
