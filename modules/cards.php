<?php
/**
 * Модуль: Карточки — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
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

<!-- Сетка карточек -->
<div class="flex flex-wrap justify-center gap-8 w-full">
    <?php foreach ($items as $item): ?>
        <?php 
        $visible = isset($item['visible']) ? filter_var($item['visible'], FILTER_VALIDATE_BOOLEAN) : true;
        if (!$visible) {
            continue;
        }
        ?>

        <div class="flex flex-col rounded-[2rem] border border-slate-200 overflow-hidden hover:shadow-[0_20px_40px_rgb(0,0,0,0.06)] transition-all duration-300 relative group bg-white w-full sm:w-[calc(50%-16px)] md:w-[calc(33.333%-22px)] max-w-sm shrink-0">
            <div class="absolute top-0 left-0 right-0 h-2 bg-[var(--primary-color)] opacity-0 group-hover:opacity-100 transition-opacity z-10"></div>

            <?php 
            $cardImage = $item['image'] ?? '';
            if ($cardImage !== '' && !preg_match('~^/[a-z0-9\-_./]+$|^https?://[a-z0-9\-_./]+$~i', $cardImage)) {
                $cardImage = '';
            }
            ?>
            <?php if (!empty($cardImage)): ?>
                <div class="h-40 w-full bg-cover bg-center bg-no-repeat bg-slate-100 border-b border-slate-200" style="background-image: url('<?php echo e($cardImage); ?>')"></div>
            <?php else: ?>
                <div class="h-40 w-full bg-slate-100 border-b border-slate-200 flex items-center justify-center text-slate-300">
                    <span class="icon-image text-4xl"></span>
                </div>
            <?php endif; ?>

            <div class="px-8 pt-2 pb-6 bg-slate-50 border-b border-slate-200 relative">
                <div class="w-14 h-14 rounded-full bg-white flex items-center justify-center text-[var(--primary-color)] shadow-[0_4px_10px_rgba(0,0,0,0.08)] absolute -top-7 left-8">
                    <span class="<?php echo e($item['icon'] ?? 'icon-star'); ?> text-2xl"></span>
                </div>
                <h3 class="text-2xl font-bold text-slate-800 mb-2 mt-6"><?php echo e($item['name'] ?? ''); ?></h3>
                <?php if (!empty($item['class_name'])): ?>
                    <div class="text-[var(--primary-color)] font-bold text-lg"><?php echo e($item['class_name']); ?></div>
                <?php endif; ?>
            </div>

            <div class="p-8 flex-grow flex flex-col justify-between">
                <?php if (!empty($item['features']) && is_array($item['features'])): ?>
                    <ul class="space-y-4 mb-8">
                        <?php foreach ($item['features'] as $feature): ?>
                            <li class="flex items-start gap-3 text-slate-600">
                                <span class="icon-check text-[var(--primary-color)] mt-1 flex-shrink-0"></span>
                                <span><?php echo e($feature); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($item['link']) && $item['link'] !== '#'): ?>
                    <a href="<?php echo e(safeUrl($item['link'])); ?>" class="inline-flex items-center justify-center w-full py-3 rounded-xl border border-[var(--primary-color)] text-[var(--primary-color)] font-semibold hover:bg-[var(--primary-color)] hover:text-white transition-colors">
                        Подробнее
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>