<?php
/**
 * Модуль: Отзывы клиентов — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$cols = $moduleData['cols'] ?? 3;
$style = $moduleData['style'] ?? 'classic';
$moduleClass = $moduleData['settings']['class'] ?? '';

if (!is_array($items) || empty($items)) {
    return;
}

$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);

// Определяем класс сетки
if ($cols == 2) {
    $gridClass = 'grid-cols-1 md:grid-cols-2';
} elseif ($cols == 4) {
    $gridClass = 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4';
} else {
    $gridClass = 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3';
}

// Определяем класс стиля
$styleClass = $style == 'centered' ? 'testimonial-centered' : 'testimonial-classic';
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

    <div class="grid <?php echo $gridClass; ?> gap-6 md:gap-8 <?php echo $styleClass; ?>">
        <?php foreach ($items as $item): 
            $photo = $item['photo'] ?? '';
            $name = $item['name'] ?? '';
            $position = $item['position'] ?? '';
            $text = $item['text'] ?? '';
            $video = $item['video'] ?? '';
            $rating = $item['rating'] ?? 5;
            $hasPhoto = !empty($photo);
        ?>
        <div class="testimonial-card bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow duration-300 flex flex-col">
            <?php if ($style == 'centered'): ?>
                <!-- Центрированный стиль (фото сверху) -->
                <div class="flex flex-col items-center text-center">
                    <!-- Фото -->
                    <div class="w-24 h-24 rounded-full bg-slate-200 flex items-center justify-center text-slate-400 mb-4 overflow-hidden flex-shrink-0">
                        <?php if ($hasPhoto): ?>
                        <img src="<?php echo e($photo); ?>" alt="<?php echo e($name); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                        <span class="icon-user text-4xl"></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Имя -->
                    <?php if (!empty($name)): ?>
                    <div class="font-bold text-slate-800 text-lg"><?php echo e($name); ?></div>
                    <?php endif; ?>
                    
                    <!-- Должность -->
                    <?php if (!empty($position)): ?>
                    <div class="text-sm text-slate-500 mb-3"><?php echo e($position); ?></div>
                    <?php endif; ?>
                    
                    <!-- Рейтинг -->
                    <div class="flex items-center gap-1 mb-4">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="text-lg <?php echo $i <= $rating ? 'text-yellow-400' : 'text-slate-200'; ?>">★</span>
                        <?php endfor; ?>
                    </div>

                    <!-- Текст -->
                    <?php if (!empty($text)): ?>
                    <p class="text-slate-600 text-sm leading-relaxed flex-1 mb-4">"<?php echo e($text); ?>"</p>
                    <?php endif; ?>

                    <!-- Кнопка видео -->
                    <?php if (!empty($video)): ?>
                    <button type="button" class="testimonial-play-btn px-5 py-2 rounded-full bg-[var(--primary-color)] text-white text-sm font-medium hover:bg-[var(--primary-dark)] transition-colors flex items-center gap-2" data-video="<?php echo e($video); ?>">
                        <span class="icon-play text-sm"></span> Смотреть видео
                    </button>
                    <?php else: ?>
                    <div class="h-10"></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Классический стиль (фото слева) -->
                <div class="flex flex-col h-full">
                    <!-- Рейтинг -->
                    <div class="flex items-center gap-1 mb-4">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="text-lg <?php echo $i <= $rating ? 'text-yellow-400' : 'text-slate-200'; ?>">★</span>
                        <?php endfor; ?>
                    </div>

                    <!-- Текст -->
                    <?php if (!empty($text)): ?>
                    <p class="text-slate-600 text-sm leading-relaxed flex-1 mb-4">"<?php echo e($text); ?>"</p>
                    <?php endif; ?>

                    <!-- Автор -->
                    <div class="flex items-center gap-3 pt-4 border-t border-slate-100 mt-auto">
                        <?php if ($hasPhoto): ?>
                        <img src="<?php echo e($photo); ?>" alt="<?php echo e($name); ?>" class="w-12 h-12 rounded-full object-cover flex-shrink-0">
                        <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-slate-200 flex items-center justify-center text-slate-400 flex-shrink-0">
                            <span class="icon-user text-xl"></span>
                        </div>
                        <?php endif; ?>
                        <div class="min-w-0 flex-1">
                            <?php if (!empty($name)): ?>
                            <div class="font-bold text-slate-800 text-sm"><?php echo e($name); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($position)): ?>
                            <div class="text-xs text-slate-500"><?php echo e($position); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($video)): ?>
                        <button type="button" class="testimonial-play-btn ml-auto w-10 h-10 rounded-full bg-[var(--primary-color)] text-white flex items-center justify-center hover:bg-[var(--primary-dark)] transition-colors flex-shrink-0" data-video="<?php echo e($video); ?>">
                            <span class="icon-play text-sm"></span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Lightbox для видео -->
<div id="testimonial-lightbox" class="fixed inset-0 bg-black/80 z-50 hidden items-center justify-center p-4" onclick="closeTestimonialLightbox(event)">
    <div class="relative w-full max-w-3xl aspect-video" onclick="event.stopPropagation()">
        <button type="button" class="absolute -top-12 right-0 text-white text-3xl hover:text-slate-300 transition-colors" onclick="closeTestimonialLightbox()">✕</button>
        <div class="w-full h-full bg-black rounded-xl overflow-hidden">
            <iframe id="testimonial-video-iframe" class="w-full h-full" src="" frameborder="0" allowfullscreen></iframe>
        </div>
    </div>
</div>