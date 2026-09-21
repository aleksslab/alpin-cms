<?php
/**
 * Модуль: Бегущая лента — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];

$speed = intval($moduleData['speed'] ?? 15);
if ($speed < 1) $speed = 1;
if ($speed > 120) $speed = 120;

$direction = $moduleData['direction'] ?? 'left';

$logoHeight = intval($moduleData['logo_height'] ?? 80);
if ($logoHeight < 20) $logoHeight = 20;
if ($logoHeight > 400) $logoHeight = 400;

$moduleClass = $moduleData['settings']['class'] ?? '';

if (empty($items)) {
    return;
}

$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);
$directionClass = $direction == 'left' ? 'marquee-left' : 'marquee-right';
$uniqueId = 'marquee-' . md5(json_encode($items) . $speed);
?>

<div>
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

    <div class="marquee-wrapper" id="<?php echo $uniqueId; ?>" style="--logo-height: <?php echo e($logoHeight); ?>px; --speed: <?php echo e($speed); ?>s;">
        <div class="marquee-track <?php echo $directionClass; ?>">
            <?php foreach ($items as $item): ?>
                <?php if (!empty($item['image'])): ?>
                    <?php if (!empty($item['url'])): ?>
                    <a href="<?php echo e(safeUrl($item['url'])); ?>" target="_blank" rel="noopener" class="marquee-item">
                        <img src="<?php echo e($item['image']); ?>" alt="<?php echo e($item['title'] ?? 'Логотип'); ?>" class="marquee-logo h-[var(--logo-height)] w-auto object-contain grayscale hover:grayscale-0 transition-all duration-300">
                    </a>
                    <?php else: ?>
                    <div class="marquee-item">
                        <img src="<?php echo e($item['image']); ?>" alt="<?php echo e($item['title'] ?? 'Логотип'); ?>" class="marquee-logo h-[var(--logo-height)] w-auto object-contain grayscale hover:grayscale-0 transition-all duration-300">
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>