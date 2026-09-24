<?php
/**
 * Модуль: Обложка (Hero) — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$backgroundType = $moduleData['background_type'] ?? 'image';
$backgroundImage = $moduleData['background_image'] ?? '';
$backgroundColor = $moduleData['background_color'] ?? '';
$buttonText = $moduleData['button_text'] ?? '';
$buttonLink = $moduleData['button_link'] ?? '#';
$buttonLink = safeUrl($buttonLink);

// === ВАЛИДАЦИЯ CSS-ЗНАЧЕНИЙ ===
if ($backgroundImage !== '') {
    if (!preg_match('~^/[a-z0-9\-_./]+$|^https?://[a-z0-9\-_./]+$~i', $backgroundImage)) {
        $backgroundImage = '';
    }
}

// Цвет: #hex, rgba(), rgb(), именованные цвета
if ($backgroundColor !== '') {
    if (!preg_match('~^#[0-9a-f]{3,8}$|^rgba?\([0-9,\.\s]+\)$|^[a-z]+$~i', $backgroundColor)) {
        $backgroundColor = '';
    }
}

if (empty($backgroundImage) && empty($backgroundColor)) {
    return;
}

$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);
$hasContent = !empty($title) || !empty($subtitle) || !empty($buttonText) || !empty($description);

$bgStyle = '';
if ($backgroundType === 'image' && !empty($backgroundImage)) {
    $bgStyle = 'background-image: url(' . e($backgroundImage) . '); background-size: cover; background-position: center;';
} else if ($backgroundType === 'color' && !empty($backgroundColor)) {
    $bgStyle = 'background-color: ' . e($backgroundColor) . ';';
}

// === ВЫСОТА HERO ===
// По умолчанию — на весь экран (100vh).
// Если в settings.class есть 'hero-compact' — высота по контенту.
$heroMinHeight = (strpos($moduleClass, 'hero-compact') !== false) ? 'auto' : '100vh';
?>

<div class="hero-module relative overflow-hidden" style="<?php echo $bgStyle; ?>; min-height: <?php echo $heroMinHeight; ?>; display: flex; align-items: center;">
    <?php if ($backgroundType === 'image'): ?>
    <div class="absolute inset-0 bg-black/40"></div>
    <?php endif; ?>
    
    <?php if ($hasContent): ?>
    <div class="container mx-auto px-6 relative z-10 py-20">
        <div class="max-w-3xl mx-auto text-center">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-8 rounded-3xl max-w-2xl mx-auto">
                <?php if (!empty($subtitle)): ?>
                <p class="text-lg text-white/90"><?php echo e($subtitle); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($title)): ?>
                <h3 class="text-3xl md:text-4xl font-bold mb-3 text-white"><?php echo e($title); ?></h3>
                <?php endif; ?>
                
                <?php if (!empty($description)): ?>
                <p class="text-white/80 text-lg"><?php echo e($description); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($buttonText)): ?>
                <a href="<?php echo e($buttonLink); ?>" class="mt-4 inline-block px-6 py-2 bg-white text-slate-800 font-bold rounded-full hover:bg-slate-100 transition-all shadow-lg">
                    <?php echo e($buttonText); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>