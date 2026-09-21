<?php
/**
 * Модуль: Портфолио — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$categories = $moduleData['categories'] ?? '';
$cols = $moduleData['cols'] ?? 3;
$perPage = $moduleData['per_page'] ?? 6;
$showAll = $moduleData['show_all'] ?? false;
$showTags = $moduleData['show_tags'] ?? true;
$showAllFilter = $moduleData['show_all_filter'] ?? true;
$enableLightbox = $moduleData['enable_lightbox'] ?? true;

if (!is_array($items) || empty($items)) {
    return;
}

// Приводим категории к массиву
if (is_string($categories)) {
    $categories = explode('|', $categories);
}
if (!is_array($categories)) {
    $categories = ['Все'];
}
$categories = array_map('trim', $categories);
if (empty($categories)) {
    $categories = ['Все'];
}

$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);
$filteredCategories = array_filter($categories, function($cat) { return $cat !== 'Все'; });

// Определяем класс сетки
if ($cols == 2) {
    $gridClass = 'grid-cols-1 sm:grid-cols-2';
} elseif ($cols == 4) {
    $gridClass = 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';
} else {
    $gridClass = 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
}

$uniqueId = 'portfolio-' . md5(json_encode($items) . $perPage);
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

<!-- Фильтры -->
<?php if (!empty($filteredCategories)): ?>
<div class="flex flex-wrap justify-center gap-2 md:gap-3 mb-10" id="<?php echo $uniqueId; ?>-filters">
    <?php if ($showAllFilter): ?>
    <button class="portfolio-filter-btn px-5 py-2 rounded-full text-sm font-bold transition-all bg-[var(--primary-color)] text-white shadow-lg shadow-[var(--primary-color)]/20" data-filter="all">Все</button>
    <?php endif; ?>
    <?php 
    $first = true;
    foreach ($filteredCategories as $cat): 
        $activeClass = (!$showAllFilter && $first) ? 'bg-[var(--primary-color)] text-white shadow-lg shadow-[var(--primary-color)]/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200';
        $first = false;
    ?>
        <button class="portfolio-filter-btn px-5 py-2 rounded-full text-sm font-bold transition-all <?php echo $activeClass; ?>" data-filter="<?php echo e($cat); ?>"><?php echo e($cat); ?></button>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Сетка -->
<div class="portfolio-grid grid <?php echo $gridClass; ?> gap-6" id="<?php echo $uniqueId; ?>-grid" 
     data-show-all="<?php echo $showAll ? 'true' : 'false'; ?>" 
     data-per-page="<?php echo $perPage; ?>"
     data-lightbox="<?php echo $enableLightbox ? 'true' : 'false'; ?>">
    
    <?php foreach ($items as $item): 
        $image = $item['image'] ?? '';
        $title = $item['title'] ?? '';
        $category = $item['category'] ?? '';
        $link = $item['link'] ?? '';
        $description = $item['description'] ?? '';
    ?>
    <div class="portfolio-item group relative rounded-xl overflow-hidden bg-slate-100 aspect-[4/3] shadow-sm hover:shadow-xl transition-all duration-300" data-category="<?php echo e($category); ?>">
        <?php if (!empty($image)): ?>
        <img src="<?php echo e($image); ?>" alt="<?php echo e($title); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
        <?php endif; ?>
        
        <!-- Оверлей -->
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
            <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                <?php if (!empty($title)): ?>
                <h3 class="text-white text-lg font-bold"><?php echo e($title); ?></h3>
                <?php endif; ?>
                
                <?php if (!empty($description)): ?>
                <p class="text-white/80 text-sm mt-1"><?php echo e($description); ?></p>
                <?php endif; ?>
                
                <?php if ($showTags && !empty($category)): ?>
                <span class="inline-block mt-2 px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-xs font-medium rounded-full border border-white/30">
                    <?php echo e($category); ?>
                </span>
                <?php endif; ?>
                
                <?php if (!empty($link) && $link !== '#'): ?>
                <a href="<?php echo e($link); ?>" target="_blank" class="inline-block mt-3 px-5 py-2 bg-white text-slate-800 font-bold text-sm rounded-full hover:bg-slate-100 transition-all shadow-lg" onclick="event.stopPropagation();">
                    Смотреть
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Кнопка "Загрузить еще" -->
<div class="text-center mt-10" id="<?php echo $uniqueId; ?>-loadmore-wrap" style="display: none;">
    <button class="portfolio-load-more px-8 py-3 bg-slate-100 text-slate-700 font-bold rounded-full hover:bg-slate-200 transition-all text-sm" data-loadmore="<?php echo $uniqueId; ?>">
        Загрузить еще
    </button>
</div>

<!-- Lightbox для изображений (только если включен) -->
<?php if ($enableLightbox): ?>
<div id="portfolio-lightbox" class="fixed inset-0 bg-black/90 z-50 hidden items-center justify-center p-4" onclick="closePortfolioLightbox(event)">
    <div class="relative max-w-5xl max-h-[90vh]" onclick="event.stopPropagation()">
        <button type="button" class="absolute -top-12 right-0 text-white text-3xl hover:text-slate-300 transition-colors" onclick="closePortfolioLightbox()">✕</button>
        <img id="portfolio-lightbox-image" src="" alt="" class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl">
        <div id="portfolio-lightbox-caption" class="absolute bottom-0 left-0 right-0 text-center text-white text-sm bg-black/50 py-3 px-4 rounded-b-xl"></div>
    </div>
</div>
<?php endif; ?>