<?php
/**
 * Модуль: Яндекс.Карты — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$centerLat = $moduleData['center_lat'] ?? '55.7558';
$centerLng = $moduleData['center_lng'] ?? '37.6176';

$zoom = intval($moduleData['zoom'] ?? 15);
if ($zoom < 1) $zoom = 1;
if ($zoom > 21) $zoom = 21;

$height = intval($moduleData['height'] ?? 400);
if ($height < 100) $height = 100;
if ($height > 2000) $height = 2000;

$apiKey = $moduleData['api_key'] ?? '';
$enableClustering = $moduleData['enable_clustering'] ?? true;
$activeMarker = $moduleData['active_marker'] ?? -1;

if (!is_array($items) || empty($items)) {
    return;
}

$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);
$mapId = 'map-' . md5(json_encode($items) . $centerLat . $centerLng . $zoom);
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

<!-- Карта -->
<div id="<?php echo $mapId; ?>" class="map-container" style="height: <?php echo e($height); ?>px; width: 100%; border-radius: 1rem; overflow: hidden; background: #f1f5f9;" 
     data-center-lat="<?php echo e($centerLat); ?>"
     data-center-lng="<?php echo e($centerLng); ?>"
     data-zoom="<?php echo e($zoom); ?>"
     data-api-key="<?php echo e($apiKey); ?>"
     data-enable-clustering="<?php echo $enableClustering ? 'true' : 'false'; ?>"
     data-active-marker="<?php echo e($activeMarker); ?>"
     data-items='<?php echo json_encode($items); ?>'>
    <div class="map-loading flex items-center justify-center h-full text-slate-400">
        <span class="icon-loader animate-spin text-2xl mr-2"></span> Загрузка карты...
    </div>
</div>

<!-- Список меток под картой -->
<?php if (count($items) > 1): ?>
<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($items as $idx => $item): 
        $name = $item['name'] ?? '';
        $address = $item['address'] ?? '';
        $phone = $item['phone'] ?? '';
        $email = $item['email'] ?? '';
        $workHours = $item['work_hours'] ?? '';
        $icon = $item['icon'] ?? '';
    ?>
    <div class="map-placemark-item flex items-start gap-3 p-3 bg-white rounded-xl border border-slate-100 hover:shadow-md transition-shadow" data-index="<?php echo $idx; ?>">
        <?php if (!empty($icon)): ?>
        <div class="text-2xl text-[var(--primary-color)] mt-0.5">
            <span class="<?php echo e($icon); ?>"></span>
        </div>
        <?php endif; ?>
        <div class="min-w-0 flex-1">
            <?php if (!empty($name)): ?>
            <div class="font-bold text-slate-800 text-sm"><?php echo e($name); ?></div>
            <?php endif; ?>
            <?php if (!empty($address)): ?>
            <div class="text-xs text-slate-500 mt-0.5"><span class="icon-map-pin map-contact-icon"></span><span class="map-contact-text"><?php echo e($address); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($phone)): ?>
            <div class="text-xs text-slate-500 mt-0.5"><span class="icon-phone map-contact-icon"></span><span class="map-contact-text"><?php echo e($phone); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($email)): ?>
            <div class="text-xs text-slate-500 mt-0.5"><span class="icon-mail map-contact-icon"></span><span class="map-contact-text"><?php echo e($email); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($workHours)): ?>
            <div class="text-xs text-slate-500 mt-0.5"><span class="icon-clock map-contact-icon"></span><span class="map-contact-text"><?php echo e($workHours); ?></span></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>