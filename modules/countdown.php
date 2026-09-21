<?php
/**
 * Модуль: Таймер обратного отсчета — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$startDate = $moduleData['start_date'] ?? '';
$endDate = $moduleData['end_date'] ?? '';
$expiredText = $moduleData['expired_text'] ?? 'Событие наступило!';
$showSeconds = $moduleData['show_seconds'] ?? true;
$displayType = $moduleData['display_type'] ?? 'blocks';

// Если нет даты окончания — ничего не выводим
if (empty($endDate)) {
    return;
}

// Определяем, нужно ли показывать заголовочную часть
$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);
?>

<div <?php if ($displayType == 'progress') { ?>
     data-countdown-start="<?php echo e($startDate); ?>" 
     <?php } ?>
     data-countdown-end="<?php echo e($endDate); ?>" 
     data-show-seconds="<?php echo $showSeconds ? 'true' : 'false'; ?>" 
     data-display-type="<?php echo e($displayType); ?>" 
     data-expired-text="<?php echo e($expiredText); ?>">
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

    <!-- Блок таймера -->
    <div id="countdown-<?php echo md5($endDate . $moduleClass); ?>" class="countdown-container">
        <?php if ($displayType == 'blocks'): ?>
        <!-- Отображение блоками -->
        <div class="countdown-blocks flex justify-center gap-4 md:gap-8">
            <div class="countdown-block text-center">
                <div class="countdown-value text-4xl md:text-6xl lg:text-7xl font-black text-slate-800" data-unit="days">--</div>
                <div class="countdown-label text-sm md:text-base text-slate-500 font-semibold mt-2">Дней</div>
            </div>
            <div class="countdown-block text-center">
                <div class="countdown-value text-4xl md:text-6xl lg:text-7xl font-black text-slate-800" data-unit="hours">--</div>
                <div class="countdown-label text-sm md:text-base text-slate-500 font-semibold mt-2">Часов</div>
            </div>
            <div class="countdown-block text-center">
                <div class="countdown-value text-4xl md:text-6xl lg:text-7xl font-black text-slate-800" data-unit="minutes">--</div>
                <div class="countdown-label text-sm md:text-base text-slate-500 font-semibold mt-2">Минут</div>
            </div>
            <?php if ($showSeconds): ?>
            <div class="countdown-block text-center">
                <div class="countdown-value text-4xl md:text-6xl lg:text-7xl font-black text-slate-800" data-unit="seconds">--</div>
                <div class="countdown-label text-sm md:text-base text-slate-500 font-semibold mt-2">Секунд</div>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Отображение прогресс-баром -->
        <div class="countdown-progress max-w-2xl mx-auto">
            <!-- Время сверху -->
            <div class="countdown-time-left text-center text-2xl md:text-3xl font-bold text-slate-800 mb-4"></div>
            
            <!-- Прогресс-бар -->
            <div class="w-full h-4 bg-slate-200 rounded-full overflow-hidden">
                <div class="countdown-progress-bar h-full bg-[var(--primary-color)] transition-all duration-300 rounded-full" style="width: 0%"></div>
            </div>
            
            <!-- Проценты снизу -->
            <div class="text-center text-sm text-slate-500 mt-2">
                <span class="countdown-percentage font-bold text-[var(--primary-color)]">0%</span> выполнено
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>