<?php
// templates/headers/minimal/header.php

$mainMenuId = getMainMenuId();
$menuItems = $mainMenuId ? getMenuItems($mainMenuId) : [];

$burgerMenuHTML = renderMobileMenu($menuItems);
?>
<header class="<?php echo $headerFixed ? 'sticky top-0 z-50 ' : ''; ?>bg-white/90 backdrop-blur-xl border-b border-slate-100 shadow-sm h-20">
    <div class="container mx-auto px-6 h-20 flex items-center justify-between">
        <!-- Логотип слева -->
        <a href="/" class="flex items-center gap-3 group">
            <?php if (!empty($site_logo)): ?>
                <img src="<?php echo e($site_logo); ?>" alt="<?php echo e($siteNamePlain); ?>" class="h-10 w-auto">
            <?php else: ?>
                <span class="text-2xl font-black tracking-tight text-slate-800"><?php echo $site_name; ?></span>
            <?php endif; ?>
        </a>

        <!-- Бургер справа -->
        <button onclick="toggleMobileMenu()" class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
            <span id="burger-icon" class="icon-menu text-xl"></span>
        </button>
    </div>

    <!-- Мобильное меню -->
    <div id="mobile-menu" class="hidden fixed inset-0 top-20 h-[calc(100vh-80px)] w-screen z-50 flex-col">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity cursor-pointer" onclick="toggleMobileMenu()"></div>
        <div class="relative z-10 bg-white border-b border-slate-100 shadow-xl py-4 px-6 flex flex-col max-h-[80vh] overflow-y-auto w-full animate-fade-in space-y-2">

            <?php echo $burgerMenuHTML; ?>

            <div class="mt-2 pt-4 border-t border-slate-100 flex flex-col gap-2">
                <a href="tel:<?php echo e($phone); ?>" class="flex items-center gap-3 text-slate-800 font-bold">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-[var(--primary-color)]">
                        <span class="icon-phone text-sm"></span>
                    </div>
                    <?php echo formatPhone($phone); ?>
                </a>
                <a href="mailto:<?php echo e($email); ?>" class="flex items-center gap-3 text-slate-600 hover:text-[var(--primary-color)] transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-[var(--primary-color)]">
                        <span class="icon-mail text-sm"></span>
                    </div>
                    <?php echo e($email); ?>
                </a>
            </div>

        </div>
    </div>
</header>