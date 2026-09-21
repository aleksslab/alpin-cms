<?php
/**
 * Модуль: Счетчики (Статистика) — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$stats = $moduleData['stats'] ?? [];
$cols = $moduleData['cols'] ?? 4;
$animation = $moduleData['animation'] ?? true;
$duration = $moduleData['duration'] ?? 1.5;
$moduleClass = $moduleData['settings']['class'] ?? '';

// Если нет данных — ничего не выводим
if (empty($stats)) {
    return;
}

// Определяем класс сетки в зависимости от количества колонок
if ($cols == 2) {
    $gridClass = 'grid-cols-1 sm:grid-cols-2';
} elseif ($cols == 3) {
    $gridClass = 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
} else {
    $gridClass = 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';
}

// Определяем, нужно ли показывать заголовочную часть
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

<div class="grid <?php echo $gridClass; ?> gap-8 md:gap-12">
    <?php foreach ($stats as $stat): 
        $icon = $stat['icon'] ?? '';
        $value = $stat['value'] ?? '';
        $label = $stat['label'] ?? '';
    ?>
    <div class="text-center stat-item">
        <?php if (!empty($icon)): ?>
        <div class="text-4xl text-[var(--primary-color)] mb-4">
            <span class="<?php echo e($icon); ?>"></span>
        </div>
        <?php endif; ?>

        <div class="stat-value text-4xl md:text-5xl lg:text-6xl font-black text-slate-800" 
             data-target="<?php echo e($value); ?>"
             data-duration="<?php echo e($duration); ?>"
             <?php echo $animation ? '' : 'data-no-animation="true"'; ?>>
            <?php echo e($value); ?>
        </div>

        <?php if (!empty($label)): ?>
        <div class="text-sm md:text-base text-slate-500 font-semibold mt-2"><?php echo e($label); ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>