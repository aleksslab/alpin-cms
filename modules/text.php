<?php
/**
 * Модуль: Произвольный HTML/текст — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$content = $moduleData['content'] ?? '';

$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);

if (empty($content) && !$hasHeader) {
    return;
}
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

<?php if (!empty($content)): ?>
<div class="text-module-content">
    <?php echo $content; ?>
</div>
<?php endif; ?>