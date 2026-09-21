<?php
/**
 * Модуль: Временная шкала — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$orientation = $moduleData['orientation'] ?? 'vertical';

if (!is_array($items) || empty($items)) {
    return;
}

$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);
$isHorizontal = $orientation == 'horizontal';
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

<div class="timeline <?php echo $isHorizontal ? 'timeline-horizontal' : 'timeline-vertical'; ?>">
    <?php foreach ($items as $index => $item): 
        $date = $item['date'] ?? '';
        $title = $item['title'] ?? '';
        $description = $item['description'] ?? '';
        $icon = $item['icon'] ?? '';
        $completed = !empty($item['completed']);
        $isLast = $index == count($items) - 1;
    ?>
    <div class="timeline-item <?php echo $isLast ? 'timeline-item-last' : ''; ?> <?php echo $completed ? 'timeline-item-completed' : ''; ?>" data-index="<?php echo $index; ?>">
        <div class="timeline-icon-wrapper">
            <div class="timeline-icon">
                <?php if (!empty($icon)): ?>
                <span class="<?php echo e($icon); ?>"></span>
                <?php else: ?>
                <span class="icon-circle"></span>
                <?php endif; ?>
            </div>
            <?php if ($completed): ?>
            <div class="timeline-check">
                <span class="icon-check-circle"></span>
            </div>
            <?php endif; ?>
        </div>
        <div class="timeline-content">
            <?php if (!empty($date)): ?>
            <div class="timeline-date <?php echo $completed ? 'timeline-date-completed' : ''; ?>"><?php echo e($date); ?></div>
            <?php endif; ?>
            <?php if (!empty($title)): ?>
            <h3 class="timeline-title <?php echo $completed ? 'timeline-title-completed' : ''; ?>"><?php echo e($title); ?></h3>
            <?php endif; ?>
            <?php if (!empty($description)): ?>
            <p class="timeline-description <?php echo $completed ? 'timeline-description-completed' : ''; ?>"><?php echo e($description); ?></p>
            <?php endif; ?>
        </div>
        <?php if (!$isLast): ?>
        <div class="timeline-line"></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>