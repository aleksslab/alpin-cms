<?php
// --- config/modules/menu.php (Список меню) ---
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

// Определяем режим
$action = $_GET['action'] ?? 'list';

// Если это редактирование или создание — подключаем menu_edit.php
if ($action === 'create' || ($action === 'edit' && !empty($_GET['id']))) {
    include __DIR__ . '/menu/edit.php';
    exit;
}

// Получаем список всех меню
$menusList = getMenusList();

// Получаем ID главного меню из настроек
$mainMenuId = getMainMenuId();
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Управление меню</h1>
        <p class="text-slate-500 text-sm">Создание, редактирование и управление меню сайта.</p>
    </div>
    
    <a href="?tab=menu&action=create" class="w-full md:w-auto px-6 py-3 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all shadow-lg shadow-[var(--primary-color)]/20 flex items-center justify-center gap-2 text-sm">
        <span class="icon-plus text-base"></span> Создать меню
    </a>
</div>

<?php renderFlash() ?>

<!-- ДЕСКТОПНАЯ ВЕРСИЯ: Таблица -->
<div class="w-full desktop-only">
    <?php if (empty($menusList)): ?>
        <div class="bg-white rounded-2xl border border-slate-100 p-12 text-center text-slate-400 shadow-sm">
            <span class="icon-menu text-4xl block mb-4 text-slate-300"></span>
            <p class="text-lg font-semibold text-slate-600">Меню отсутствуют</p>
            <p class="text-sm mt-2">Создайте первое меню, нажав кнопку выше.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50">
                        <th class="py-4 px-4">ID</th>
                        <th class="py-4">Название</th>
                        <th class="py-4 text-right pr-4">Действия</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-slate-700">
                    <?php foreach ($menusList as $menu): ?>
                        <?php 
                        $isMain = ($menu['id'] === $mainMenuId);
                        $starClass = $isMain ? 'text-amber-500' : 'text-slate-300 hover:text-amber-500';
                        $starTitle = $isMain ? 'Главное меню' : 'Сделать главным';
                        $onclick = "return confirm('" . ($isMain ? 'Снять статус главного меню с "' . e($menu['name']) . '"?' : 'Сделать меню "' . e($menu['name']) . '" главным?') . "')";
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100">
                            <td class="py-3 px-4 font-mono text-xs text-slate-400"><?php echo e($menu['id']); ?></td>
                            <td class="py-3 font-semibold text-slate-800">
                                <span class="inline-flex items-center gap-1">
                                    <?php echo e($menu['name']); ?>
                                    <button type="submit" 
                                            form="menu-actions-<?php echo e($menu['id']); ?>" 
                                            name="menu_set_main" 
                                            value="1" 
                                            class="<?php echo $starClass; ?> icon-star text-sm cursor-pointer bg-transparent border-0 p-0" 
                                            title="<?php echo $starTitle; ?>" 
                                            onclick="<?php echo $onclick; ?>"></button>
                                </span>
                            </td>
                            <td class="py-3 text-right px-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="?tab=menu&action=edit&id=<?php echo e($menu['id']); ?>" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:text-slate-800 bg-white hover:bg-slate-50 transition-all" title="Редактировать">
                                        <span class="icon-edit text-sm"></span>
                                    </a>
                                    <form id="menu-actions-<?php echo e($menu['id']); ?>" method="POST" action="index.php?tab=menu" class="flex items-center gap-1.5">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                                        <input type="hidden" name="id" value="<?php echo e($menu['id']); ?>">
                                        <?php if (!$isMain): ?>
                                            <button type="submit" name="menu_delete" value="1" class="w-8 h-8 rounded-lg flex items-center justify-center border border-rose-100 text-rose-500 hover:text-rose-800 bg-white hover:bg-rose-50 transition-all" title="Удалить" onclick="return confirm('Удалить меню &quot;<?php echo e($menu['name']); ?>&quot;? Это действие необратимо.')">
                                                <span class="icon-trash-2 text-sm"></span>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-400 bg-slate-50 cursor-not-allowed opacity-40" disabled>
                                                <span class="icon-trash-2 text-sm"></span>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- МОБИЛЬНАЯ ВЕРСИЯ: Карточки -->
<div class="mobile-only space-y-4">
    <?php if (empty($menusList)): ?>
        <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center text-slate-400 shadow-sm">
            <span class="icon-menu text-3xl block mb-3 text-slate-300"></span>
            <p class="text-sm font-semibold text-slate-600">Меню отсутствуют</p>
            <p class="text-xs mt-1">Создайте первое меню.</p>
        </div>
    <?php else: ?>
        <?php foreach ($menusList as $menu): ?>
            <?php 
            $isMain = ($menu['id'] === $mainMenuId);
            $starClass = $isMain ? 'text-amber-500' : 'text-slate-300';
            ?>
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                <div class="flex items-start justify-between border-b border-slate-100 pb-3 mb-3">
                    <div>
                        <span class="inline-flex items-center gap-1">
                            <span class="text-base font-bold text-slate-800"><?php echo e($menu['name']); ?></span>
                            <button type="submit" 
                                    form="menu-actions-mobile-<?php echo e($menu['id']); ?>" 
                                    name="menu_set_main" 
                                    value="1" 
                                    class="<?php echo $starClass; ?> icon-star text-sm cursor-pointer bg-transparent border-0 p-0" 
                                    title="<?php echo $isMain ? 'Главное меню' : 'Сделать главным'; ?>" 
                                    onclick="return confirm('<?php echo $isMain ? 'Снять статус главного меню с "' . e($menu['name']) . '"? ' : 'Сделать меню "' . e($menu['name']) . '" главным? '; ?>')"></button>
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 mb-3">
                    <div>
                        <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">ID</span>
                        <span class="font-mono font-medium text-slate-700"><?php echo e($menu['id']); ?></span>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                    <a href="?tab=menu&action=edit&id=<?php echo e($menu['id']); ?>" class="flex-1 h-10 bg-white border border-slate-200 text-slate-600 rounded-lg text-xs font-bold flex items-center justify-center gap-1 transition-all cursor-pointer hover:bg-slate-50">Редактировать</a>
                    <form id="menu-actions-mobile-<?php echo e($menu['id']); ?>" method="POST" action="index.php?tab=menu" class="flex items-center gap-2 flex-shrink-0">
                        <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                        <input type="hidden" name="id" value="<?php echo e($menu['id']); ?>">
                        <?php if (!$isMain): ?>
                            <button type="submit" name="menu_delete" value="1" class="w-10 h-10 flex-shrink-0 bg-white border border-rose-100 text-rose-500 rounded-lg flex items-center justify-center transition-all cursor-pointer hover:bg-rose-50" title="Удалить" onclick="return confirm('Удалить меню &quot;<?php echo e($menu['name']); ?>&quot;? Это действие необратимо.')">
                                <span class="icon-trash-2 text-sm"></span>
                            </button>
                        <?php else: ?>
                            <button type="button" class="w-10 h-10 flex-shrink-0 bg-white border border-slate-200 text-slate-400 rounded-lg flex items-center justify-center cursor-not-allowed opacity-40"  disabled title="Главное меню нельзя удалить">
                                <span class="icon-trash-2 text-sm"></span>
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>