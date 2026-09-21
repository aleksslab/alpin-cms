<?php
/**
 * Модуль: Меню сайта — сайт
 */
if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

if (!function_exists('renderMenuItem')) {    
    /**
     * Рендерит пункт меню (общая логика для всех стилей)
     */
    function renderMenuItem(array $item, string $style = 'horizontal'): string {
        $type = $item['type'] ?? 'page';
        $label = $item['label'] ?? '';
        $url = $type === 'page' ? ($item['page_id'] ?? '/') : ($item['url'] ?? '#');

        if ($type === 'divider') {
            return '<div class="border-t border-slate-100 my-2"></div>';
        }

        if ($type === 'dropdown') {
            $children = $item['children'] ?? [];
            if (empty($children)) {
                return '';
            }

            if ($style === 'horizontal') {
                return renderHorizontalDropdown($label, $children);
            } else {
                return renderVerticalDropdown($label, $children);
            }
        }

        // Обычный пункт
        return '<a href="' . $url . '" class="text-sm font-semibold tracking-wide text-slate-600 hover:text-[var(--primary-color)] transition-colors cursor-pointer">' . e($label) . '</a>';
    }

    /**
     * Рендерит горизонтальный dropdown
     */
    function renderHorizontalDropdown(string $label, array $children): string {
        $html = '<div class="relative group">';
        $html .= '<button type="button" class="inline-flex items-center gap-1 text-sm font-semibold tracking-wide text-slate-600 hover:text-[var(--primary-color)] transition-colors cursor-pointer">';
        $html .= e($label) . '<span class="icon-chevron-down text-[10px] text-slate-400 group-hover:rotate-180 transition-transform"></span>';
        $html .= '</button>';
        $html .= '<div class="absolute top-full left-0 hidden group-hover:flex flex-col bg-white border border-slate-100 shadow-xl rounded-xl py-2 min-w-[200px] animate-fade-in z-10">';

        foreach ($children as $child) {
            $childUrl = $child['type'] === 'page' ? ($child['page_id'] ?? '/') : ($child['url'] ?? '#');
            $html .= '<a href="' . $childUrl . '" class="px-4 py-2 text-sm text-slate-600 hover:text-[var(--primary-color)] hover:bg-slate-50 transition-colors">' . e($child['label']) . '</a>';
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Рендерит вертикальный dropdown (аккордеон)
     */
    function renderVerticalDropdown(string $label, array $children): string {
        $dropdownId = 'vert_drop_' . uniqid();

        $html = '<div class="flex flex-col">';
        $html .= '<button type="button" onclick="toggleMobileDropdown(\'' . $dropdownId . '\', this)" class="text-sm w-full text-left font-semibold text-slate-600 hover:text-[var(--primary-color)] flex items-center justify-between cursor-pointer transition-colors">';
        $html .= e($label);
        $html .= '<span class="icon-chevron-right text-xs text-slate-400 transition-transform"></span>';
        $html .= '</button>';
        $html .= '<div id="' . $dropdownId . '" class="hidden flex flex-col pl-4 border-l border-slate-100 mt-1 mb-2 space-y-2">';

        foreach ($children as $child) {
            $childUrl = $child['type'] === 'page' ? ($child['page_id'] ?? '/') : ($child['url'] ?? '#');
            $html .= '<a href="' . $childUrl . '" class="text-left text-sm font-medium text-slate-500 hover:text-[var(--primary-color)] py-1.5 transition-colors">' . e($child['label']) . '</a>';
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Рендерит горизонтальное меню
     */
    function renderHorizontalMenu(array $items): string {
        $html = '';
        foreach ($items as $item) {
            $html .= renderMenuItem($item, 'horizontal');
        }
        return $html;
    }

    /**
     * Рендерит вертикальное меню
     */
    function renderVerticalMenu(array $items): string {
        $html = '';
        foreach ($items as $item) {
            $html .= renderMenuItem($item, 'vertical');
        }
        return $html;
    }
}
// --- Основная логика модуля ---

// Данные приходят напрямую из $moduleData
$targetMenuId = $moduleData['menu_id'] ?? '';
$menuStyle = $moduleData['menu_style'] ?? 'horizontal';

if (empty($targetMenuId)) {
    return;
}

$menuItems = getMenuItems($targetMenuId);

if (empty($menuItems)) {
    return;
}
?>

<?php if ($menuStyle === 'horizontal'): ?>
    <nav class="flex flex-wrap items-center gap-4 lg:gap-8 h-auto">
        <?php echo renderHorizontalMenu($menuItems); ?>
    </nav>
<?php else: ?>
    <nav class="flex flex-col space-y-2.5">
        <?php echo renderVerticalMenu($menuItems); ?>
    </nav>
<?php endif; ?>