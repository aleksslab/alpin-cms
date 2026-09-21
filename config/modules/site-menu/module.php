<?php
/**
 * Модуль: Меню сайта — панель управления
 */
if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

// Данные приходят напрямую в $moduleData
// Структура: { "menu_id": "header_main", "menu_style": "vertical", "settings": { "class": "" } }
$selectedMenuId = $moduleData['menu_id'] ?? '';
$menuStyle = $moduleData['menu_style'] ?? 'horizontal';
$settingsClass = $moduleData['settings']['class'] ?? '';

// Получаем актуальный список всех созданных в CMS меню для селекта
$allMenus = getMenusList();
?>

<div class="space-y-4">
    <!-- БЛОК 1: Выбор меню и стиля -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Выберите меню *</label>
            <select name="menu_id" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" required>
                <option value="" disabled <?php echo empty($selectedMenuId) ? 'selected' : ''; ?>>-- Выберите меню --</option>
                <?php foreach ($allMenus as $menu): ?>
                    <option value="<?php echo e($menu['id']); ?>" <?php echo $selectedMenuId === $menu['id'] ? 'selected' : ''; ?>>
                        <?php echo e($menu['name']); ?> (<?php echo e($menu['id']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-[10px] text-slate-400 mt-1">Какое из созданных меню отобразить в этом блоке</p>
        </div>

        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Стиль отображения</label>
            <select name="menu_style" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                <option value="horizontal" <?php echo $menuStyle === 'horizontal' ? 'selected' : ''; ?>>Горизонтальная строка (как в шапке)</option>
                <option value="vertical" <?php echo $menuStyle === 'vertical' ? 'selected' : ''; ?>>Вертикальный список (для футера / сайдбара)</option>
            </select>
            <p class="text-[10px] text-slate-400 mt-1">Определяет ориентацию ссылок в блоке</p>
        </div>
    </div>

    <!-- БЛОК 2: Системные настройки стилей оформления -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-1">Настройки внешнего вида</h4>
        <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Дополнительные CSS классы</label>
            <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="gap-6, my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            <p class="text-[10px] text-slate-400 mt-1">Вы можете добавить стандартные классы Tailwind для кастомизации отступов или сеток</p>
        </div>
    </div>
</div>