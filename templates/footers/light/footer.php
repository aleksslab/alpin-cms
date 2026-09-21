<?php
// templates/footers/light/footer.php

$footerMenus = [
    'footer_about' => getMenuItems('footer_about'),
    'footer_help' => getMenuItems('footer_help'),
    'brands' => getMenuItems('brands')
];

$footerMenus = array_filter($footerMenus, function($items) {
    return !empty($items);
});

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

<footer class="w-full bg-white pt-16 pb-8 border-t border-slate-200">
    <div class="container mx-auto px-6 lg:px-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12 mb-12">
            
            <!-- Колонка 1: Логотип и описание -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <?php if (!empty($site_logo)): ?>
                        <img src="<?php echo e($site_logo); ?>" alt="<?php echo e($siteNamePlain); ?>" class="h-10 w-auto">
                    <?php else: ?>
                        <span class="text-2xl font-black tracking-tight text-slate-800"><?php echo $site_name; ?></span>
                    <?php endif; ?>
                </div>
                <p class="text-sm text-slate-600 leading-relaxed"><?php echo $site_slogan; ?></p>
            </div>
            
            <?php 
            $menuOrder = ['footer_about', 'footer_help', 'brands'];
            foreach ($menuOrder as $menuId): 
                if (empty($footerMenus[$menuId])) continue;
                $items = $footerMenus[$menuId];
                $menuTitle = $menusData[$menuId] ?? $menuId;
            ?>
            <div>
                <h4 class="text-lg font-bold text-slate-800 mb-6"><?php echo e($menuTitle); ?></h4>
                <ul class="space-y-3 text-sm text-slate-600">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <a href="<?php echo e(safeUrl($item['url'] ?? '#')); ?>" class="hover:text-[var(--primary-color)] transition-colors cursor-pointer">
                                <?php echo e($item['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
            
            <!-- Колонка 4: Контакты -->
            <div>
                <h4 class="text-lg font-bold text-slate-800 mb-6">Контакты</h4>
                <ul class="space-y-4 text-sm text-slate-600">
                    <li class="flex items-center gap-3">
                        <span class="icon-mail text-[var(--primary-color)]"></span>
                        <a href="mailto:<?php echo e($email); ?>" class="hover:text-slate-800 transition-colors"><?php echo e($email); ?></a>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="icon-phone text-[var(--primary-color)]"></span>
                        <a href="tel:<?php echo e($phone); ?>" class="hover:text-slate-800 transition-colors"><?php echo formatPhone($phone); ?></a>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="icon-map-pin text-[var(--primary-color)] mt-0.5"></span>
                        <span class="hover:text-slate-800 transition-colors"><?php echo e($address); ?></span>
                    </li>
                </ul>
            </div>
            
        </div>
        
        <!-- Нижняя панель -->
        <div class="border-t border-slate-200 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div class="flex gap-6">
                <button onclick="openPolicy('privacy')" class="hover:text-slate-800 cursor-pointer">Политика конфиденциальности</button>
                <button onclick="openPolicy('cookie')" class="hover:text-slate-800 cursor-pointer">Политика cookie</button>
            </div>
            
            <p>&copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>. <?php echo $copyrightText; ?></p>
            
            <?php 
            $socialData = [
                'icon_size' => 'w-5 h-5',
                'icon_color' => 'text-slate-500',
                'icon_hover_color' => 'text-[var(--primary-color)]',
                'show_labels' => false
            ];
            echo render_module('social-icons', $socialData, ['class' => 'footer-social-icons justify-center']);
            ?>
        </div>
    </div>
</footer>