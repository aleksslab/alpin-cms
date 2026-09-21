<?php
// Получаем пункты меню по ID из актуального JSON-конфига настроек сайта
$mainMenuId = getMainMenuId();
$menuItems = $mainMenuId ? getMenuItems($mainMenuId) : [];

// Вызываем новые лаконичные функции ядра, которые мы вынесли в config/core/functions.php
$mainMenuHTML = renderMainMenu($menuItems);
$burgerMenuHTML = renderMobileMenu($menuItems);
?>
<header class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-xl border-b border-slate-100 shadow-sm h-20">
    <div class="container mx-auto px-6 h-20 flex items-center justify-between">
        <!-- Блок Логотипа -->
        <a href="/" class="flex items-center gap-3 group">
            <?php if (!empty($site_logo)): ?>
                <img src="<?php echo e($site_logo); ?>" alt="<?php echo sanitizeHtml(e($site_name)); ?>" class="h-10 w-auto">
            <?php else: ?>
                <span class="text-2xl font-black tracking-tight text-slate-800 pr-4"><?php echo $site_name; ?></span>
            <?php endif; ?>
        </a>

        <!-- Основная навигация сайта для ПК (Скрывается на мобильных экранах) -->
        <nav class="hidden md:flex items-center gap-4 lg:gap-8 h-20">
            <?php echo $mainMenuHTML; ?>            
        </nav>

        <!-- Блок Контактов для ПК -->
        <div class="hidden lg:flex items-center gap-4">
            <div class="text-right">
                <a href="tel:<?php echo e($phone); ?>" class="block text-sm font-bold text-slate-800"><?php echo formatPhone($phone); ?></a>
                <a href="mailto:<?php echo e($email); ?>" class="text-xs text-slate-500 hover:text-[var(--primary-color)] transition-colors"><?php echo e($email); ?></a>
            </div>
        </div>

        <!-- Кнопка мобильного бургера -->
        <?php if (isBurgerEnabled()): ?>
            <div class="<?php echo getBurgerBreakpoint() === 'md' ? 'md:hidden' : 'lg:hidden'; ?>">
                <button onclick="toggleMobileMenu()" class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                    <span id="burger-icon" class="icon-menu text-xl"></span>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Мобильное выпадающее меню -->
    <?php if (isBurgerEnabled()): ?>
        <div class="<?php echo getBurgerBreakpoint() === 'md' ? 'md:hidden' : 'lg:hidden'; ?>">
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
        </div>
    <?php endif; ?>
</header>