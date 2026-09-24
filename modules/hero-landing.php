<?php
/**
 * Модуль: Обложка-лендинг (Hero-Landing) — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';

$layout = $moduleData['layout'] ?? 'centered';

$backgroundType = $moduleData['background_type'] ?? 'color';
$backgroundColor = $moduleData['background_color'] ?? '#1a1e23';
$backgroundImage = $moduleData['background_image'] ?? '';

$image = $moduleData['image'] ?? '';
$imagePosition = $moduleData['image_position'] ?? 'right';
$imageStyle = $moduleData['image_style'] ?? 'browser';

$buttonText = $moduleData['button_text'] ?? '';
$buttonLink = safeUrl($moduleData['button_link'] ?? '');

$buttonText2 = $moduleData['button_text_2'] ?? '';
$buttonLink2 = safeUrl($moduleData['button_link_2'] ?? '');

$features = $moduleData['features'] ?? [];

// === ВАЛИДАЦИЯ ===
// Хелпер: нормализует путь к изображению.
// Относительный путь → добавляет ведущий "/".
// Безопасный URL → оставляет.
// Опасный → пустая строка.
$normalizeImagePath = function($path) {
    $path = trim($path);
    if ($path === '') return '';

    // Уже абсолютный или http(s) — проверяем на безопасность
    if (preg_match('~^https?://[a-z0-9\-_./]+$~i', $path)) {
        return $path;
    }
    if (preg_match('~^/[a-z0-9\-_./]+$~i', $path)) {
        return $path;
    }

    // Относительный — добавляем ведущий /
    if (preg_match('~^[a-z0-9\-_./]+$~i', $path)) {
        return '/' . $path;
    }

    return '';
};

$backgroundImage = $normalizeImagePath($backgroundImage);
$image = $normalizeImagePath($image);

if ($backgroundColor !== '' && !preg_match('~^#[0-9a-f]{3,8}$|^rgba?\([0-9,\.\s]+\)$|^[a-z]+$~i', $backgroundColor)) {
    $backgroundColor = '';
}

// Если совсем нет фона — не рендерим
if (empty($backgroundImage) && empty($backgroundColor)) {
    return;
}

$hasContent = !empty($title) || !empty($subtitle) || !empty($description) || !empty($buttonText) || !empty($buttonText2) || !empty($features);
if (!$hasContent) {
    return;
}

// Стиль фона
$bgStyle = '';
if ($backgroundType === 'image' && !empty($backgroundImage)) {
    $bgStyle = 'background-image: url(' . e($backgroundImage) . '); background-size: cover; background-position: center;';
} elseif ($backgroundType === 'color' && !empty($backgroundColor)) {
    $bgStyle = 'background-color: ' . e($backgroundColor) . ';';
}

// Высота — от класса
$settingsClass = $moduleClass ?? '';
$heroMinHeight = (strpos($settingsClass, 'hero-compact') !== false) ? 'auto' : '100vh';

// Раскладка
$isSplit = ($layout === 'split') && !empty($image);

// Порядок картинки в split
$imageOrderClass = ($imagePosition === 'left') ? 'lg:order-first' : 'lg:order-last';
$textOrderClass = ($imagePosition === 'left') ? 'lg:order-last' : 'lg:order-first';

// Стиль картинки
$imageWrapperClass = '';
if ($imageStyle === 'shadow') {
    $imageWrapperClass = 'shadow-2xl rounded-2xl';
} elseif ($imageStyle === 'browser') {
    $imageWrapperClass = 'shadow-2xl rounded-2xl overflow-hidden border border-white/10';
}

// Иконки/текст: для тёмного фона — светлый, для светлого — тёмный
// Определим яркость фона (грубо): если background_color тёмный — white, иначе slate
$isDarkBg = true;
if ($backgroundType === 'color' && !empty($backgroundColor)) {
    // Грубая оценка: #1a1e23, #0F172A и т.п. — тёмные. Простой тест: R+G+B < 3*128
    if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/i', $backgroundColor, $m)) {
        $r = hexdec($m[1]);
        $g = hexdec($m[2]);
        $b = hexdec($m[3]);
        $isDarkBg = (($r + $g + $b) < 384);
    }
}
$textColorClass = $isDarkBg ? 'text-white' : 'text-slate-800';
$mutedTextColorClass = $isDarkBg ? 'text-slate-300' : 'text-slate-600';
$subtitleColorClass = $isDarkBg ? 'text-[var(--primary-color)]' : 'text-[var(--primary-color)]';
?>

<div class="hero-landing-module relative overflow-hidden" style="<?php echo $bgStyle; ?>; min-height: <?php echo $heroMinHeight; ?>; display: flex; align-items: center;">

    <?php if ($backgroundType === 'image'): ?>
    <div class="absolute inset-0 bg-black/50"></div>
    <?php endif; ?>

    <div class="container mx-auto px-6 relative z-10 py-12 md:py-16">
        <?php if ($isSplit): ?>
            <?php // === SPLIT LAYOUT === ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <?php // Текстовая колонка ?>
                <div class="<?php echo $textOrderClass; ?>">
                    <?php if (!empty($subtitle)): ?>
                        <p class="<?php echo $subtitleColorClass; ?> font-bold tracking-wider uppercase text-sm mb-3"><?php echo e($subtitle); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($title)): ?>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold <?php echo $textColorClass; ?> mb-4 leading-tight"><?php echo e($title); ?></h1>
                    <?php endif; ?>

                    <?php if (!empty($description)): ?>
                        <p class="<?php echo $mutedTextColorClass; ?> text-lg mb-8 max-w-xl"><?php echo e($description); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($buttonText) || !empty($buttonText2)): ?>
                        <div class="flex flex-wrap gap-3 mb-8">
                            <?php if (!empty($buttonText)): ?>
                                <a href="<?php echo e($buttonLink); ?>" class="px-7 py-3.5 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all shadow-lg shadow-[var(--primary-color)]/20 text-base">
                                    <?php echo e($buttonText); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($buttonText2)): ?>
                                <a href="<?php echo e($buttonLink2); ?>" class="px-7 py-3.5 border-2 <?php echo $isDarkBg ? 'border-slate-600 text-white hover:border-[var(--primary-color)] hover:text-[var(--primary-color)]' : 'border-slate-300 text-slate-800 hover:border-[var(--primary-color)] hover:text-[var(--primary-color)]'; ?> font-bold rounded-xl transition-all text-base">
                                    <?php echo e($buttonText2); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($features)): ?>
                        <div class="flex flex-wrap gap-x-6 gap-y-3">
                            <?php foreach ($features as $feature): ?>
                                <?php 
                                $fIcon = $feature['icon'] ?? '';
                                $fText = $feature['text'] ?? '';
                                if (empty($fText)) continue;
                                ?>
                                <div class="flex items-center gap-2 <?php echo $mutedTextColorClass; ?>">
                                    <?php if (!empty($fIcon)): ?>
                                        <span class="<?php echo e($fIcon); ?> text-[var(--primary-color)] text-lg"></span>
                                    <?php endif; ?>
                                    <span class="text-sm font-medium"><?php echo e($fText); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php // Картинка ?>
                <?php if (!empty($image)): ?>
                    <div class="<?php echo $imageOrderClass; ?>">
                        <?php if ($imageStyle === 'browser'): ?>
                            <?php // Стиль браузера — рамка с точками ?>
                            <div class="<?php echo $imageWrapperClass; ?> max-w-[500px] mx-auto">
                                <div class="flex items-center gap-1.5 px-4 py-2.5 bg-slate-800/90 border-b border-white/10">
                                    <div class="w-2.5 h-2.5 rounded-full bg-rose-500"></div>
                                    <div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                                </div>
                                <img src="<?php echo e($image); ?>" alt="<?php echo e($title); ?>" class="w-full h-auto block">
                            </div>
                        <?php else: ?>
                            <div class="<?php echo $imageWrapperClass; ?> max-w-[500px] mx-auto">
                                <img src="<?php echo e($image); ?>" alt="<?php echo e($title); ?>" class="w-full h-auto block rounded-2xl">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <?php // === CENTERED LAYOUT === ?>
            <div class="max-w-3xl mx-auto text-center">
                <?php if (!empty($subtitle)): ?>
                    <p class="<?php echo $subtitleColorClass; ?> font-bold tracking-wider uppercase text-sm mb-3"><?php echo e($subtitle); ?></p>
                <?php endif; ?>

                <?php if (!empty($title)): ?>
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold <?php echo $textColorClass; ?> mb-4 leading-tight"><?php echo e($title); ?></h1>
                <?php endif; ?>

                <?php if (!empty($description)): ?>
                    <p class="<?php echo $mutedTextColorClass; ?> text-lg mb-8 max-w-2xl mx-auto"><?php echo e($description); ?></p>
                <?php endif; ?>

                <?php if (!empty($buttonText) || !empty($buttonText2)): ?>
                    <div class="flex flex-wrap justify-center gap-3 mb-8">
                        <?php if (!empty($buttonText)): ?>
                            <a href="<?php echo e($buttonLink); ?>" class="px-7 py-3.5 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all shadow-lg shadow-[var(--primary-color)]/20 text-base">
                                <?php echo e($buttonText); ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($buttonText2)): ?>
                            <a href="<?php echo e($buttonLink2); ?>" class="px-7 py-3.5 border-2 <?php echo $isDarkBg ? 'border-slate-600 text-white hover:border-[var(--primary-color)] hover:text-[var(--primary-color)]' : 'border-slate-300 text-slate-800 hover:border-[var(--primary-color)] hover:text-[var(--primary-color)]'; ?> font-bold rounded-xl transition-all text-base">
                                <?php echo e($buttonText2); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($features)): ?>
                    <div class="flex flex-wrap justify-center gap-x-6 gap-y-3">
                        <?php foreach ($features as $feature): ?>
                            <?php 
                            $fIcon = $feature['icon'] ?? '';
                            $fText = $feature['text'] ?? '';
                            if (empty($fText)) continue;
                            ?>
                            <div class="flex items-center gap-2 <?php echo $mutedTextColorClass; ?>">
                                <?php if (!empty($fIcon)): ?>
                                    <span class="<?php echo e($fIcon); ?> text-[var(--primary-color)] text-lg"></span>
                                <?php endif; ?>
                                <span class="text-sm font-medium"><?php echo e($fText); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>