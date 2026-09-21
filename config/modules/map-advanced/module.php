<?php
/**
 * Модуль: Яндекс.Карты — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$centerLat = $moduleData['center_lat'] ?? '55.7558';
$centerLng = $moduleData['center_lng'] ?? '37.6176';
$zoom = $moduleData['zoom'] ?? 15;
$height = $moduleData['height'] ?? 400;
$apiKey = $moduleData['api_key'] ?? '';
$enableClustering = $moduleData['enable_clustering'] ?? true;
$activeMarker = $moduleData['active_marker'] ?? -1;
$settingsClass = $moduleData['settings']['class'] ?? '';

if (!is_array($items) || empty($items)) {
    $items = [
        ['lat' => '55.7558', 'lng' => '37.6176', 'name' => '', 'address' => '', 'phone' => '', 'email' => '', 'work_hours' => '', 'icon' => '']
    ];
}
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Наши офисы" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Мы рядом с вами" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки карты -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки карты</h4>
        
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">API ключ Яндекс.Карт</label>
            <input type="text" name="api_key" value="<?php echo e($apiKey); ?>" placeholder="Введите API ключ..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            <p class="text-[10px] text-slate-400 mt-1">
                Получить ключ можно в <a href="https://developer.tech.yandex.ru/" target="_blank" class="text-[var(--primary-color)] hover:underline">кабинете разработчика</a>
            </p>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Центр (широта)</label>
                <input type="text" name="center_lat" value="<?php echo e($centerLat); ?>" placeholder="55.7558" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Центр (долгота)</label>
                <input type="text" name="center_lng" value="<?php echo e($centerLng); ?>" placeholder="37.6176" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Зум</label>
                <input type="number" name="zoom" value="<?php echo e($zoom); ?>" min="5" max="19" placeholder="15" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Высота карты (px)</label>
                <input type="number" name="height" value="<?php echo e($height); ?>" min="200" max="800" placeholder="400" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Кластеризация</label>
                <select name="enable_clustering" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="1" <?php echo $enableClustering ? 'selected' : ''; ?>>Включена</option>
                    <option value="0" <?php echo !$enableClustering ? 'selected' : ''; ?>>Выключена</option>
                </select>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Активная метка</label>
                <select name="active_marker" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <option value="-1" <?php echo $activeMarker == -1 ? 'selected' : ''; ?>>Не открывать</option>
                    <?php foreach ($items as $idx => $item): ?>
                        <option value="<?php echo $idx; ?>" <?php echo $activeMarker == $idx ? 'selected' : ''; ?>>
                            <?php echo e($item['name'] ?: 'Метка #' . ($idx + 1)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <!-- Пусто -->
            </div>
        </div>
    </div>

    <!-- БЛОК 3: Метки -->
    <div id="map-items-container" class="space-y-3">
        <?php 
        $itemTemplate = '
        <div class="map-item-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Метка #{INDEX}</span>
                <button type="button" class="js-remove-map-item w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить метку">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Широта</label>
                    <input type="text" name="items[{INDEX}][lat]" value="{LAT}" placeholder="55.7558" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Долгота</label>
                    <input type="text" name="items[{INDEX}][lng]" value="{LNG}" placeholder="37.6176" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Название</label>
                    <input type="text" name="items[{INDEX}][name]" value="{NAME}" placeholder="Главный офис" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Адрес</label>
                    <input type="text" name="items[{INDEX}][address]" value="{ADDRESS}" placeholder="Москва, ул. Тверская, 1" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Телефон</label>
                    <input type="text" name="items[{INDEX}][phone]" value="{PHONE}" placeholder="+7 (495) 123-45-67" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Email</label>
                    <input type="text" name="items[{INDEX}][email]" value="{EMAIL}" placeholder="info@site.ru" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Часы работы</label>
                    <input type="text" name="items[{INDEX}][work_hours]" value="{WORK_HOURS}" placeholder="Пн-Пт: 9:00-18:00" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Иконка</label>
                    <div class="flex gap-2 items-center js-icon-input-group">
                        <div class="icon-preview-wrapper">
                            <span class="js-icon-preview-icon {ICON}"></span>
                        </div>
                        <input type="text" name="items[{INDEX}][icon]" value="{ICON}" placeholder="icon-building" 
                               class="js-icon-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                        <button type="button" onclick="openIconModal(this)" 
                                class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                            <span class="icon-search text-sm"></span> Иконка
                        </button>
                    </div>
                </div>
            </div>
        </div>';
        ?>
        
        <?php foreach ($items as $idx => $item): ?>
            <?php 
            $num = $idx + 1;
            $icon = $item['icon'] ?? '';
            if (!empty($icon) && strpos($icon, 'icon-') !== 0) {
                $icon = 'icon-' . $icon;
            }
            
            echo str_replace(
                ['{INDEX}', '{LAT}', '{LNG}', '{NAME}', '{ADDRESS}', '{PHONE}', '{EMAIL}', '{WORK_HOURS}', '{ICON}'],
                [$num, e($item['lat'] ?? ''), e($item['lng'] ?? ''), e($item['name'] ?? ''), e($item['address'] ?? ''), e($item['phone'] ?? ''), e($item['email'] ?? ''), e($item['work_hours'] ?? ''), e($icon)],
                $itemTemplate
            );
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-map-item">
        <?php echo str_replace(
            ['{INDEX}', '{LAT}', '{LNG}', '{NAME}', '{ADDRESS}', '{PHONE}', '{EMAIL}', '{WORK_HOURS}', '{ICON}'],
            ['__INDEX__', '', '', '', '', '', '', '', 'icon-building'],
            $itemTemplate
        ); ?>
    </template>

    <button type="button" id="add-map-item-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить метку
    </button>
</div>

<?php include APP_ROOT . '/config/core/icon_modal.php'; ?>