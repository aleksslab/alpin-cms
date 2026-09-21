<?php
/**
 * Модуль: Тарифная сетка — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$cols = $moduleData['cols'] ?? 3;
$currency = $moduleData['currency'] ?? '$';
$settingsClass = $moduleData['settings']['class'] ?? '';

// Преобразуем features из массива в строку для textarea
if (!empty($items)) {
    foreach ($items as $key => $item) {
        if (isset($item['features']) && is_array($item['features'])) {
            $items[$key]['features'] = implode("\n", $item['features']);
        }
    }
}

if (!is_array($items) || empty($items)) {
    $items = [
        ['name' => '', 'description' => '', 'price_month' => '', 'price_year' => '', 'icon' => '', 'popular' => false, 'button_text' => 'Выбрать', 'button_link' => '#', 'features' => ''],
        ['name' => '', 'description' => '', 'price_month' => '', 'price_year' => '', 'icon' => '', 'popular' => false, 'button_text' => 'Выбрать', 'button_link' => '#', 'features' => ''],
        ['name' => '', 'description' => '', 'price_month' => '', 'price_year' => '', 'icon' => '', 'popular' => false, 'button_text' => 'Выбрать', 'button_link' => '#', 'features' => '']
    ];
}

$colsOptions = [2, 3, 4];
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Выберите свой тариф" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Цены на наши услуги" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки отображения</h4>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Колонок</label>
                <select name="cols" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                    <?php foreach ($colsOptions as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $cols == $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
        </div>

        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Валюта</label>
            <div class="flex gap-1 items-center flex-wrap">
                <input type="text" name="currency" value="<?php echo e($currency); ?>" placeholder="$" class="w-20 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] text-center" id="currency-input">
                <button type="button" class="currency-preset px-3 py-2 border border-slate-200 rounded-lg text-sm hover:bg-slate-50 transition-all" data-currency="$">$</button>
                <button type="button" class="currency-preset px-3 py-2 border border-slate-200 rounded-lg text-sm hover:bg-slate-50 transition-all" data-currency="€">€</button>
                <button type="button" class="currency-preset px-3 py-2 border border-slate-200 rounded-lg text-sm hover:bg-slate-50 transition-all" data-currency="£">£</button>
                <button type="button" class="currency-preset px-3 py-2 border border-slate-200 rounded-lg text-sm hover:bg-slate-50 transition-all" data-currency="₽">₽</button>
                <button type="button" class="currency-preset px-3 py-2 border border-slate-200 rounded-lg text-sm hover:bg-slate-50 transition-all" data-currency="₴">₴</button>
                <button type="button" class="currency-preset px-3 py-2 border border-slate-200 rounded-lg text-sm hover:bg-slate-50 transition-all" data-currency="₸">₸</button>
                <button type="button" class="currency-preset px-3 py-2 border border-slate-200 rounded-lg text-sm hover:bg-slate-50 transition-all" data-currency="¥">¥</button>
            </div>
            <p class="text-[10px] text-slate-400 mt-1">Нажмите на символ для быстрой вставки или введите свой</p>
        </div>
    </div>

    <!-- БЛОК 3: Тарифы -->
    <div id="pricing-container" class="space-y-3">
        <?php 
        $itemTemplate = '
        <div class="pricing-item-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Тариф #{INDEX}</span>
                <button type="button" class="js-remove-pricing w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить тариф">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Название</label>
                    <input type="text" name="items[{INDEX}][name]" value="{NAME}" placeholder="Базовый" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
                    <input type="text" name="items[{INDEX}][description]" value="{DESCRIPTION}" placeholder="Для начинающих" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Цена (мес)</label>
                    <input type="text" name="items[{INDEX}][price_month]" value="{PRICE_MONTH}" placeholder="10" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Цена (год)</label>
                    <input type="text" name="items[{INDEX}][price_year]" value="{PRICE_YEAR}" placeholder="100" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Иконка</label>
                    <div class="flex gap-2 items-center js-icon-input-group">
                        <div class="icon-preview-wrapper">
                            <span class="js-icon-preview-icon {ICON}"></span>
                        </div>
                        <input type="text" name="items[{INDEX}][icon]" value="{ICON}" placeholder="icon-star" 
                               class="js-icon-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
                        <button type="button" onclick="openIconModal(this)" 
                                class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
                            <span class="icon-search text-sm"></span> Иконка
                        </button>
                    </div>
                </div>
                <div class="editor-field flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer pb-2">
                        <input type="hidden" name="items[{INDEX}][popular]" value="0">
                        <input type="checkbox" name="items[{INDEX}][popular]" value="1" {POPULAR_CHECKED} class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                        <span class="text-sm text-slate-600 font-medium">Популярный</span>
                    </label>
                </div>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Текст кнопки</label>
                    <input type="text" name="items[{INDEX}][button_text]" value="{BUTTON_TEXT}" placeholder="Выбрать" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ссылка</label>
                    <input type="text" name="items[{INDEX}][button_link]" value="{BUTTON_LINK}" placeholder="https://..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Особенности (по одной на строку)</label>
                <textarea name="items[{INDEX}][features]" rows="4" placeholder="5 проектов&#10;10 ГБ хранилища&#10;Поддержка 24/7" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)] resize-y">{FEATURES}</textarea>
                <p class="text-[10px] text-slate-400 mt-1">Каждая особенность на новой строке</p>
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
            $popularChecked = !empty($item['popular']) ? 'checked' : '';
            // features уже строка (преобразовали выше)
            $features = $item['features'] ?? '';
            
            echo str_replace(
                ['{INDEX}', '{NAME}', '{DESCRIPTION}', '{PRICE_MONTH}', '{PRICE_YEAR}', '{ICON}', '{POPULAR_CHECKED}', '{BUTTON_TEXT}', '{BUTTON_LINK}', '{FEATURES}'],
                [$num, e($item['name'] ?? ''), e($item['description'] ?? ''), e($item['price_month'] ?? ''), e($item['price_year'] ?? ''), e($icon), $popularChecked, e($item['button_text'] ?? 'Выбрать'), e($item['button_link'] ?? '#'), e($features)],
                $itemTemplate
            );
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-pricing-item">
        <?php echo str_replace(
            ['{INDEX}', '{NAME}', '{DESCRIPTION}', '{PRICE_MONTH}', '{PRICE_YEAR}', '{ICON}', '{POPULAR_CHECKED}', '{BUTTON_TEXT}', '{BUTTON_LINK}', '{FEATURES}'],
            ['__INDEX__', '', '', '', '', 'icon-star', '', 'Выбрать', '#', ''],
            $itemTemplate
        ); ?>
    </template>

    <button type="button" id="add-pricing-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить тариф
    </button>
</div>

<?php include APP_ROOT . '/config/core/icon_modal.php'; ?>