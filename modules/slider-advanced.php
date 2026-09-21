<?php
/**
 * Модуль: Слайдер продвинутый — сайт
 */

$slides = $moduleData['slides'] ?? [];
$autoplay = $moduleData['autoplay'] ?? true;
$interval = $moduleData['interval'] ?? 5000;
$effect = $moduleData['effect'] ?? 'fade';
$speed = $moduleData['speed'] ?? 600;
$pauseOnHover = $moduleData['pause_on_hover'] ?? true;
$showArrows = $moduleData['show_arrows'] ?? true;
$showDots = $moduleData['show_dots'] ?? true;
$swipe = $moduleData['swipe'] ?? true;
$moduleClass = $moduleData['settings']['class'] ?? '';

if (empty($slides)) {
    return;
}
?>

<div class="carousel-module <?php echo $moduleClass; ?>">
    <div class="relative w-full h-[500px] md:h-[600px] overflow-hidden group" 
         data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>" 
         data-interval="<?php echo $interval; ?>"
         data-effect="<?php echo $effect; ?>"
         data-speed="<?php echo $speed; ?>"
         data-pause-on-hover="<?php echo $pauseOnHover ? 'true' : 'false'; ?>"
         data-show-arrows="<?php echo $showArrows ? 'true' : 'false'; ?>"
         data-show-dots="<?php echo $showDots ? 'true' : 'false'; ?>"
         data-swipe="<?php echo $swipe ? 'true' : 'false'; ?>">
        
        <?php 
        $dots_html = '';
        
        foreach ($slides as $i => $slide) { 
            $activeClass = ($i === 0) ? 'opacity-100 z-10' : 'opacity-0 z-0';
            $dotClass = ($i === 0) ? 'h-2 w-8 bg-white/80' : 'h-2 w-2 bg-white/50 hover:bg-white/80';
            $dots_html .= '<button class="carousel-dot rounded-full transition-all duration-300 ' . $dotClass . '" data-index="' . $i . '"></button>';
            ?>
            <div class="carousel-slide absolute inset-0 transition-opacity duration-1000 ease-in-out <?php echo $activeClass; ?>" data-index="<?php echo $i; ?>">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-slate-900/20 to-transparent z-10"></div>
                <?php if (!empty($slide['image'])): ?>
                    <img src="<?php echo e($slide['image']); ?>" alt="<?php echo e($slide['title'] ?? 'Слайд'); ?>" class="w-full h-full object-cover" />
                <?php endif; ?>
                
                <?php if (!empty($slide['title']) || !empty($slide['subtitle'])): ?>
                <div class="absolute inset-0 z-20 flex flex-col items-center justify-end pb-20 px-4 text-center">
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-8 rounded-3xl max-w-2xl">
                        <?php if (!empty($slide['title'])): ?>
                            <h3 class="text-3xl md:text-4xl font-bold mb-3 text-white"><?php echo e($slide['title']); ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($slide['subtitle'])): ?>
                            <p class="text-lg text-white/90"><?php echo e($slide['subtitle']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($slide['link']) && $slide['link'] !== '#'): ?>
                            <a href="<?php echo e($slide['link']); ?>" class="mt-4 inline-block px-6 py-2 bg-white text-slate-800 font-bold rounded-full hover:bg-slate-100 transition-all shadow-lg">
                                Подробнее
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php } ?>
        
        <!-- Кнопки управления -->
        <button class="carousel-prev absolute left-6 top-1/2 -translate-y-1/2 z-30 w-14 h-14 bg-white/20 hover:bg-white/40 rounded-full flex items-center justify-center text-white backdrop-blur-md border border-white/30 transition-all opacity-0 group-hover:opacity-100 hover:scale-110 cursor-pointer <?php echo $showArrows ? '' : 'hidden'; ?>">
            <span class="icon-chevron-left text-2xl"></span>
        </button>
        <button class="carousel-next absolute right-6 top-1/2 -translate-y-1/2 z-30 w-14 h-14 bg-white/20 hover:bg-white/40 rounded-full flex items-center justify-center text-white backdrop-blur-md border border-white/30 transition-all opacity-0 group-hover:opacity-100 hover:scale-110 cursor-pointer <?php echo $showArrows ? '' : 'hidden'; ?>">
            <span class="icon-chevron-right text-2xl"></span>
        </button>
        
        <!-- Точки -->
        <?php if ($showDots && count($slides) > 1): ?>
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-30 flex gap-3 bg-black/20 backdrop-blur-sm px-4 py-2 rounded-full">
            <?php echo $dots_html; ?>
        </div>
        <?php endif; ?>
    </div>
</div>