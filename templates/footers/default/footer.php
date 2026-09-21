<?php
// --- Получаем все меню для футера ---
$footerMenus = [
    'footer_about' => getMenuItems('footer_about'),
    'footer_help' => getMenuItems('footer_help'),
    'brands' => getMenuItems('brands')
];

// Убираем пустые меню
$footerMenus = array_filter($footerMenus, function($items) {
    return !empty($items);
});

// Загружаем названия меню из файлов
$menusData = [];
foreach (array_keys($footerMenus) as $menuId) {
    $menu = loadMenu($menuId);
    if ($menu && !empty($menu['name'])) {
        $menusData[$menuId] = $menu['name'];
    } else {
        $menusData[$menuId] = $menuId;
    }
}
?>

<footer class="w-full bg-[#1A1E23] pt-16 pb-8 border-t border-slate-800">
    <div class="container mx-auto px-6 lg:px-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12 mb-12">
            
            <?php // КОЛОНКА 1: Логотип и краткое описание ?>
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-2xl font-black tracking-tight text-white"><?php echo $site_name; ?></span>
                </div>
                <p class="text-sm text-slate-300 leading-relaxed"><?php echo $site_slogan; ?></p>
            </div>
            
            <?php 
            // Выводим меню по порядку: footer_about, footer_help, brands
            $menuOrder = ['footer_about', 'footer_help', 'brands'];
            foreach ($menuOrder as $menuId): 
                if (empty($footerMenus[$menuId])) continue;
                $items = $footerMenus[$menuId];
                $menuTitle = $menusData[$menuId] ?? $menuId;
            ?>
            <div>
                <h4 class="text-lg font-bold text-white mb-6"><?php echo e($menuTitle); ?></h4>
                <ul class="space-y-3 text-sm text-slate-300">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <a href="<?php echo e(safeUrl($item['url'] ?? '#')); ?>" class="hover:text-white transition-colors cursor-pointer">
                                <?php echo e($item['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
            
            <?php // КОЛОНКА 4: Контакты ?>
            <div>
                <h4 class="text-lg font-bold text-white mb-6">Контакты</h4>
                <ul class="space-y-4 text-sm text-slate-300">
                    <li class="flex items-center gap-3">
                        <span class="icon-mail text-white"></span>
                        <a href="mailto:<?php echo e($email); ?>"><?php echo e($email); ?></a>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="icon-phone text-white"></span>
                        <a href="tel:<?php echo e($phone); ?>"><?php echo formatPhone($phone); ?></a>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="icon-map-pin text-white mt-0.5"></span>
                        <span><?php echo e($address); ?></span>
                    </li>
                </ul>
            </div>
            
        </div>
        
        <?php // НИЖНЯЯ ПАНЕЛЬ: Политики и копирайт ?>
        <div class="border-t border-slate-700 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-400">
            <div class="flex gap-6">
                <button onclick="openPolicy('privacy')" class="hover:text-white cursor-pointer">Политика конфиденциальности</button>
                <button onclick="openPolicy('cookie')" class="hover:text-white cursor-pointer">Политика cookie</button>
            </div>
            
            <p>&copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>. <?php echo $copyrightText; ?></p>
            
            <?php 
            $socialData = [
                'icon_size' => 'w-5 h-5',
                'icon_color' => 'text-slate-300',
                'icon_hover_color' => 'text-white',
                'show_labels' => false
            ];
            echo render_module('social-icons', $socialData, ['class' => 'footer-social-icons justify-center text-slate-300']);
            ?>
        </div>
    </div>
</footer>