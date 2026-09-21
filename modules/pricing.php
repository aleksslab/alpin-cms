<?php
/**
 * Модуль: Тарифная сетка — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$cols = $moduleData['cols'] ?? 3;
$currency = $moduleData['currency'] ?? '$';

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

<!-- Переключатель -->
<div class="flex justify-center mb-10">
    <div class="bg-slate-100 p-1 rounded-full inline-flex relative">
        <button type="button" class="pricing-toggle-btn px-6 py-2.5 text-sm font-bold rounded-full transition-all text-white bg-[var(--primary-color)] shadow-lg" data-period="month">Месячная</button>
        <button type="button" class="pricing-toggle-btn px-6 py-2.5 text-sm font-bold rounded-full transition-all text-slate-600" data-period="year">Годовая</button>
    </div>
</div>

<!-- Тарифы -->
<div class="grid <?php echo $gridClass; ?> gap-6 md:gap-8">
    <?php foreach ($items as $item): 
        $name = $item['name'] ?? '';
        $description = $item['description'] ?? '';
        $priceMonth = $item['price_month'] ?? '';
        $priceYear = $item['price_year'] ?? '';
        $icon = $item['icon'] ?? '';
        $popular = !empty($item['popular']);
        $buttonText = $item['button_text'] ?? 'Выбрать';
        $buttonLink = $item['button_link'] ?? '#';
        
        $features = [];
        if (!empty($item['features'])) {
            if (is_array($item['features'])) {
                $features = $item['features'];
            } elseif (is_string($item['features'])) {
                $features = explode("\n", trim($item['features']));
                $features = array_filter($features, function($line) {
                    return trim($line) !== '';
                });
            }
        }
    ?>
    <div class="pricing-card bg-white rounded-2xl p-6 shadow-sm border <?php echo $popular ? 'border-[var(--primary-color)] shadow-lg ring-2 ring-[var(--primary-color)]/20' : 'border-slate-100'; ?> hover:shadow-md transition-all duration-300 flex flex-col items-center text-center relative">
        <?php if ($popular): ?>
        <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[var(--primary-color)] text-white text-xs font-bold px-4 py-1 rounded-full">Популярный</div>
        <?php endif; ?>

        <!-- Иконка -->
        <?php if (!empty($icon)): ?>
        <div class="text-3xl text-[var(--primary-color)] mb-3">
            <span class="<?php echo e($icon); ?>"></span>
        </div>
        <?php endif; ?>

        <!-- Название -->
        <?php if (!empty($name)): ?>
        <h3 class="text-xl font-bold text-slate-800"><?php echo e($name); ?></h3>
        <?php endif; ?>

        <!-- Описание -->
        <?php if (!empty($description)): ?>
        <p class="text-sm text-slate-500 mt-1"><?php echo e($description); ?></p>
        <?php endif; ?>

        <!-- Цена -->
        <div class="mt-4 mb-6">
            <div class="pricing-price-month" data-period="month">
                <span class="text-3xl font-black text-slate-900"><?php echo e($currency); ?><?php echo e($priceMonth); ?></span>
                <span class="text-sm text-slate-500">/мес</span>
            </div>
            <div class="pricing-price-year hidden" data-period="year">
                <span class="text-3xl font-black text-slate-900"><?php echo e($currency); ?><?php echo e($priceYear); ?></span>
                <span class="text-sm text-slate-500">/год</span>
                <?php if (!empty($priceMonth) && !empty($priceYear) && is_numeric($priceMonth) && is_numeric($priceYear) && $priceYear > 0): ?>
                <div class="text-xs text-emerald-500 font-semibold mt-1">Экономия <?php echo round((1 - $priceYear / ($priceMonth * 12)) * 100); ?>%</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Особенности -->
        <?php if (!empty($features)): ?>
        <ul class="space-y-2 mb-6 flex-1 w-full text-left">
            <?php foreach ($features as $feature): ?>
                <?php if (!empty(trim($feature))): ?>
                <li class="flex items-start gap-2 text-sm text-slate-600">
                    <span class="text-[var(--primary-color)] mt-0.5">✓</span>
                    <?php echo e(trim($feature)); ?>
                </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <!-- Кнопка -->
        <?php if (!empty($buttonLink)): ?>
        <a href="<?php echo e($buttonLink); ?>" class="block text-center px-4 py-3 rounded-full font-bold text-sm transition-all w-full <?php echo $popular ? 'bg-[var(--primary-color)] text-white hover:bg-[var(--primary-dark)] shadow-lg shadow-[var(--primary-color)]/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'; ?>">
            <?php echo e($buttonText); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>