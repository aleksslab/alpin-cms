<?php
$mainMenuId = getMainMenuId();
$menuItems = $mainMenuId ? getMenuItems($mainMenuId) : [];

$mainMenuHTML = renderMainMenu($menuItems);
$burgerMenuHTML = renderMobileMenu($menuItems);

$breakpoint = getBurgerBreakpoint();

$hasContacts = !empty($phone) || !empty($email);

// CTA — заменяем на «Скачать» для CMS
$ctaLabel = 'Скачать';
$ctaUrl   = '/download';
?>
<header class="<?php echo $headerFixed ? 'sticky top-0 z-50 ' : ''; ?>bg-white/90 backdrop-blur-xl border-b border-slate-100 shadow-sm h-20">
    <div class="container mx-auto px-6 h-20 flex items-center justify-between">
        <!-- Логотип -->
        <a href="/" class="flex items-center gap-3 group flex-shrink-0">
            <?php if (!empty($site_logo)): ?>
                <img src="<?php echo e($site_logo); ?>" alt="<?php echo e($siteNamePlain); ?>" class="h-10 w-auto">
            <?php else: ?>
                <span class="text-2xl font-black tracking-tight text-slate-800"><?php echo $site_name; ?></span>
            <?php endif; ?>
        </a>

        <!-- Меню -->
        <nav class="hidden <?php echo $breakpoint; ?>:flex items-center gap-4 lg:gap-8 h-20">
            <?php echo $mainMenuHTML; ?>
        </nav>

        <!-- Контакты + CTA -->
        <div class="hidden lg:flex items-center gap-6 flex-shrink-0">
            <?php if ($hasContacts): ?>
            <div class="text-right">
                <?php if (!empty($phone)): ?>
                    <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $phone)); ?>" class="block text-sm font-bold text-slate-800"><?php echo formatPhone($phone); ?></a>
                <?php endif; ?>
                <?php if (!empty($email)): ?>
                    <a href="mailto:<?php echo e($email); ?>" class="text-xs text-slate-500 hover:text-[var(--primary-color)] transition-colors"><?php echo e($email); ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <a href="<?php echo e($ctaUrl); ?>" class="px-5 py-2.5 bg-[var(--primary-color)] text-white font-bold text-sm rounded-xl hover:bg-[var(--primary-dark)] transition-all shadow-lg shadow-[var(--primary-color)]/20 flex-shrink-0">
                <?php echo e($ctaLabel); ?>
            </a>
        </div>

        <!-- Бургер -->
        <?php if (isBurgerEnabled()): ?>
            <div class="<?php echo $breakpoint; ?>:hidden">
                <button onclick="toggleMobileMenu()" class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                    <span id="burger-icon" class="icon-menu text-xl"></span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Мобильное меню -->
    <?php if (isBurgerEnabled()): ?>
        <div class="<?php echo $breakpoint; ?>:hidden">
            <div id="mobile-menu" class="hidden fixed inset-0 top-20 h-[calc(100vh-80px)] w-screen z-50 flex-col">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity cursor-pointer" onclick="toggleMobileMenu()"></div>
                <div class="relative z-10 bg-white border-b border-slate-100 shadow-xl py-4 px-6 flex flex-col max-h-[80vh] overflow-y-auto w-full animate-fade-in space-y-2">
                    
                    <?php echo $burgerMenuHTML; ?>
                    
                    <?php if ($hasContacts): ?>
                    <div class="mt-2 pt-4 border-t border-slate-100 flex flex-col gap-2">
                        <?php if (!empty($phone)): ?>
                            <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $phone)); ?>" class="flex items-center gap-3 text-slate-800 font-bold">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-[var(--primary-color)]">
                                    <span class="icon-phone text-sm"></span>
                                </div>
                                <?php echo formatPhone($phone); ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($email)): ?>
                            <a href="mailto:<?php echo e($email); ?>" class="flex items-center gap-3 text-slate-600 hover:text-[var(--primary-color)] transition-colors">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-[var(--primary-color)]">
                                    <span class="icon-mail text-sm"></span>
                                </div>
                                <?php echo e($email); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <a href="<?php echo e($ctaUrl); ?>" class="w-full text-center px-5 py-3 bg-[var(--primary-color)] text-white font-bold text-sm rounded-xl hover:bg-[var(--primary-dark)] transition-all mt-2">
                        <?php echo e($ctaLabel); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</header>