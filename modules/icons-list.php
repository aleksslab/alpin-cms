<?php
/**
 * Модуль: Список с иконками — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? 'Наши преимущества';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];

if (empty($items)) {
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

<!-- Сетка иконок -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach ($items as $item): ?>
        <div class="bg-white p-8 rounded-[2rem] shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-slate-100 hover:-translate-y-1 transition-transform duration-300 group flex flex-col items-center text-center">
            <?php if (!empty($item['icon'])): ?>
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mb-6 text-[var(--primary-color)] group-hover:bg-[var(--primary-color)] group-hover:text-white transition-colors duration-300">
                    <span class="<?php echo e($item['icon']); ?> text-3xl"></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($item['title'])): ?>
                <h3 class="text-xl font-bold text-slate-800 mb-4"><?php echo e($item['title']); ?></h3>
            <?php endif; ?>
            
            <?php if (!empty($item['text'])): ?>
                <p class="text-slate-600 leading-relaxed"><?php echo e($item['text']); ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>