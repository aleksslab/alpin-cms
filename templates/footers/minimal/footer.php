<?php
// templates/footers/minimal/footer.php
?>

<footer class="w-full bg-[#1A1E23] py-8 border-t border-slate-800">
    <div class="container mx-auto px-6 lg:px-20">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <?php if (!empty($site_logo)): ?>
                    <img src="<?php echo e($site_logo); ?>" alt="<?php echo e($siteNamePlain); ?>" class="h-8 w-auto">
                <?php else: ?>
                    <span class="text-xl font-black tracking-tight text-white"><?php echo $site_name; ?></span>
                <?php endif; ?>
            </div>
            
            <p class="text-sm text-slate-400 text-center md:text-right">
                &copy; <?php echo date('Y'); ?> <?php echo $site_name; ?><?php echo !empty($copyrightText) ? '. ' . $copyrightText : ''; ?>
            </p>
        </div>
    </div>
</footer>