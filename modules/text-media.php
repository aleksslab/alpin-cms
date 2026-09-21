<?php
/**
 * Модуль: Текст + Медиа — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$image = $moduleData['image'] ?? '';
$imagePosition = $moduleData['image_position'] ?? 'left';
$badgeText = $moduleData['badge_text'] ?? '';
$badgeLabel = $moduleData['badge_label'] ?? '';
$features = $moduleData['features'] ?? [];
$stats = $moduleData['stats'] ?? [];

if (empty($subtitle) && empty($title) && empty($description) && empty($image)) {
    return;
}
?>

<div class="flex flex-col lg:flex-row items-center gap-16">            
    <?php if ($imagePosition === 'left'): ?>
    <div class="lg:w-1/2 lg:pl-12 mb-16 lg:mb-0 px-4 md:px-0">
        <div class="relative w-full max-w-lg mx-auto lg:mx-0 mt-4 md:mt-0">
            <div class="absolute inset-0 bg-[#e8f6f0] rounded-[2.5rem] transform rotate-2 translate-x-2 translate-y-2 md:translate-x-4 md:translate-y-4"></div>

            <div class="relative z-10 rounded-[2rem] overflow-hidden shadow-sm bg-white border border-slate-100">
                <?php if (!empty($image)): ?>
                <img src="<?php echo e($image); ?>" alt="<?php echo e($title); ?>" class="w-full h-[300px] sm:h-[400px] lg:h-[500px] object-cover hover:scale-105 transition-transform duration-700" />
                <?php else: ?>
                <div class="w-full h-[300px] sm:h-[400px] lg:h-[500px] bg-slate-100 flex items-center justify-center text-slate-400">
                    <span class="icon-image text-6xl"></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($badgeText)): ?>
            <div class="absolute -bottom-8 left-4 right-4 sm:right-auto md:-left-12 md:bottom-8 lg:-bottom-8 z-20 bg-white rounded-2xl py-4 px-6 shadow-[0_15px_40px_rgb(0,0,0,0.08)] flex items-center justify-center sm:justify-start gap-4 border border-slate-50">
                <div class="w-12 h-12 bg-[#e8f6f0] rounded-full flex items-center justify-center shrink-0 text-[var(--primary-color)]">
                    <span class="icon-shield-check text-2xl"></span>
                </div>
                <div>
                    <div class="text-2xl font-black text-slate-900 leading-none mb-1"><?php echo e($badgeText); ?></div>
                    <?php if (!empty($badgeLabel)): ?>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-widest"><?php echo e($badgeLabel); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="lg:w-1/2 <?php echo $imagePosition === 'left' ? '' : 'lg:order-first'; ?>">
        <?php if (!empty($subtitle)): ?>
        <span class="text-[var(--primary-color)] font-bold tracking-wider uppercase text-sm mb-4 block"><?php echo e($subtitle); ?></span>
        <?php endif; ?>

        <?php if (!empty($title)): ?>
        <h2 class="text-4xl font-bold text-slate-800 mb-6"><?php echo e($title); ?></h2>
        <?php endif; ?>

        <?php if (!empty($description)): ?>
        <p class="text-lg text-slate-600 mb-8 leading-relaxed"><?php echo e($description); ?></p>
        <?php endif; ?>

        <?php if (!empty($features)): ?>
        <div class="space-y-6">
            <?php foreach ($features as $feature): ?>
            <div class="flex items-start gap-4">
                <?php if (!empty($feature['icon'])): ?>
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-[var(--primary-color)] shrink-0 mt-1">
                    <span class="<?php echo e($feature['icon']); ?> text-xl"></span>
                </div>
                <?php endif; ?>
                <div>
                    <?php if (!empty($feature['title'])): ?>
                    <h4 class="text-xl font-bold text-slate-800 mb-1"><?php echo e($feature['title']); ?></h4>
                    <?php endif; ?>
                    <?php if (!empty($feature['desc'])): ?>
                    <p class="text-slate-600"><?php echo e($feature['desc']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($stats)): ?>
        <div class="mt-10 pt-8 border-t border-slate-100 grid grid-cols-3 gap-6">
            <?php foreach ($stats as $stat): ?>
            <div class="flex flex-col items-center text-center">
                <?php if (!empty($stat['value'])): ?>
                <div class="text-3xl lg:text-4xl font-black text-[var(--primary-dark)]"><?php echo e($stat['value']); ?></div>
                <?php endif; ?>
                <?php if (!empty($stat['label'])): ?>
                <div class="text-sm font-semibold text-slate-500 mt-1"><?php echo e($stat['label']); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($imagePosition === 'right'): ?>
    <div class="lg:w-1/2 lg:pl-12 mb-16 lg:mb-0 px-4 md:px-0">
        <div class="relative w-full max-w-lg mx-auto lg:mx-0 mt-4 md:mt-0">
            <div class="absolute inset-0 bg-[#e8f6f0] rounded-[2.5rem] transform rotate-2 translate-x-2 translate-y-2 md:translate-x-4 md:translate-y-4"></div>

            <div class="relative z-10 rounded-[2rem] overflow-hidden shadow-sm bg-white border border-slate-100">
                <?php if (!empty($image)): ?>
                <img src="<?php echo e($image); ?>" alt="<?php echo e($title); ?>" class="w-full h-[300px] sm:h-[400px] lg:h-[500px] object-cover hover:scale-105 transition-transform duration-700" />
                <?php else: ?>
                <div class="w-full h-[300px] sm:h-[400px] lg:h-[500px] bg-slate-100 flex items-center justify-center text-slate-400">
                    <span class="icon-image text-6xl"></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($badgeText)): ?>
            <div class="absolute -bottom-8 left-4 right-4 sm:right-auto md:-left-12 md:bottom-8 lg:-bottom-8 z-20 bg-white rounded-2xl py-4 px-6 shadow-[0_15px_40px_rgb(0,0,0,0.08)] flex items-center justify-center sm:justify-start gap-4 border border-slate-50">
                <div class="w-12 h-12 bg-[#e8f6f0] rounded-full flex items-center justify-center shrink-0 text-[var(--primary-color)]">
                    <span class="icon-shield-check text-2xl"></span>
                </div>
                <div>
                    <div class="text-2xl font-black text-slate-900 leading-none mb-1"><?php echo e($badgeText); ?></div>
                    <?php if (!empty($badgeLabel)): ?>
                    <div class="text-xs font-bold text-slate-500 uppercase tracking-widest"><?php echo e($badgeLabel); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>