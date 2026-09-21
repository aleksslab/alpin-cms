<?php
// --- config/modules/pages.php (Список страниц) ---
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

// Определяем режим
$action = $_GET['action'] ?? 'list';

// Если это редактирование или создание — переключаемся на pages_edit.php
if ($action === 'create' || ($action === 'edit' && !empty($_GET['id']))) {
    include __DIR__ . '/pages/edit.php';
    exit;
}

// Пагинация
$allowedPerPage = [10, 25, 50, 100, 200];

if (!empty($_GET['per_page']) && in_array((int)$_GET['per_page'], $allowedPerPage)) {
    $perPage = (int)$_GET['per_page'];
    $_SESSION['pages_per_page'] = $perPage;
} elseif (!empty($_SESSION['pages_per_page']) && in_array((int)$_SESSION['pages_per_page'], $allowedPerPage)) {
    $perPage = (int)$_SESSION['pages_per_page'];
} else {
    $perPage = 25;
}

$currentPage = max(1, intval($_GET['page'] ?? 1));

$paginated = getPagesListPaginated($currentPage, $perPage);
$pagesList = $paginated['pages'];
$totalPages = $paginated['totalPages'];
$totalPagesCount = $paginated['total'];
$currentPage = $paginated['page'];
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Управление страницами</h1>
        <p class="text-slate-500 text-sm">Создание, редактирование и управление страницами сайта.</p>
    </div>
    
    <a href="?tab=pages&action=create" class="w-full md:w-auto px-6 py-3 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all shadow-lg shadow-[var(--primary-color)]/20 flex items-center justify-center gap-2 text-sm">
        <span class="icon-plus text-base"></span> Создать страницу
    </a>
</div>

<?php echo renderFlash(); ?>

<!-- ДЕСКТОПНАЯ ВЕРСИЯ: Таблица -->
<div class="w-full desktop-only">
    <?php if (empty($pagesList)): ?>
        <div class="bg-white rounded-2xl border border-slate-100 p-12 text-center text-slate-400 shadow-sm">
            <span class="icon-file-text text-4xl block mb-4 text-slate-300"></span>
            <p class="text-lg font-semibold text-slate-600">Страницы отсутствуют</p>
            <p class="text-sm mt-2">Создайте первую страницу, нажав кнопку выше.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50">
                        <th class="py-4 px-4">ID</th>
                        <th class="py-4">Заголовок</th>
                        <th class="py-4">ЧПУ (Slug)</th>
                        <th class="py-4">Шаблон</th>
                        <th class="py-4">Статус</th>
                        <th class="py-4 text-right pr-4">Действия</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-slate-700">
                    <?php foreach ($pagesList as $page): ?>
                        <?php 
                        $isHome = ($page['slug'] === '');
                        $statusClass = ($page['status'] === 'published') ? 'text-emerald-800 bg-emerald-50' : 'text-amber-600 bg-amber-50';
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors group border-b border-slate-100">
                            <td class="py-3 px-4 font-mono text-xs text-slate-400"><?php echo e($page['id']); ?></td>
                            <td class="py-3 font-semibold text-slate-800">
                                <?php if ($page['id'] !== getHomePageId()): ?>
                                    <span class="inline-flex items-center gap-1">
                                        <?php echo e($page['title']); ?>
                                        <button type="submit" form="page-actions-<?php echo e($page['id']); ?>" name="page_set_home" value="1" class="icon-star text-slate-300 text-sm hover:text-amber-500 transition-colors cursor-pointer" title="Сделать главной" onclick="return confirm('Сделать страницу &quot;<?php echo e($page['title']); ?>&quot; главной?')"></button>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1">
                                        <?php echo e($page['title']); ?>
                                        <span class="text-amber-500 icon-star text-sm" title="Главная страница"></span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 font-mono text-xs text-slate-500">
                                <?php echo $isHome ? '/' : '/' . e($page['slug']); ?>
                            </td>
                            <td class="py-3 text-slate-600"><?php echo e($page['template'] ?? 'full-width'); ?></td>
                            <td class="py-3">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo $statusClass; ?>">
                                    <?php echo $page['status'] === 'published' ? 'Опубликована' : 'Черновик'; ?>
                                </span>
                            </td>
                            <td class="py-3 text-right px-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="?tab=pages&action=edit&id=<?php echo e($page['id']); ?>" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:text-slate-800 bg-white hover:bg-slate-50 transition-all" title="Редактировать">
                                        <span class="icon-edit text-sm"></span>
                                    </a>

                                    <form id="page-actions-<?php echo e($page['id']); ?>" method="POST" action="index.php?tab=pages" class="flex items-center gap-1.5">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                                        <input type="hidden" name="id" value="<?php echo e($page['id']); ?>">

                                        <button type="submit" name="page_clone" value="1" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:text-slate-800 bg-white hover:bg-slate-50 transition-all cursor-pointer" title="Клонировать" onclick="return confirm('Клонировать страницу &quot;<?php echo e($page['title']); ?>&quot;?')">
                                            <span class="icon-copy text-sm"></span>
                                        </button>

                                        <?php if (!$isHome): ?>
                                            <button type="submit" name="page_delete" value="1" class="w-8 h-8 rounded-lg flex items-center justify-center border border-rose-100 text-rose-500 hover:text-rose-800 bg-white hover:bg-rose-50 transition-all cursor-pointer" title="Удалить" onclick="return confirm('Удалить страницу &quot;<?php echo e($page['title']); ?>&quot;? Это действие необратимо.')">
                                                <span class="icon-trash-2 text-sm"></span>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-400 bg-slate-50 cursor-not-allowed opacity-40" title="Главную страницу нельзя удалить" disabled>
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
    <?php if (empty($pagesList)): ?>
        <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center text-slate-400 shadow-sm">
            <span class="icon-file-text text-3xl block mb-3 text-slate-300"></span>
            <p class="text-sm font-semibold text-slate-600">Страницы отсутствуют</p>
            <p class="text-xs mt-1">Создайте первую страницу.</p>
        </div>
    <?php else: ?>
        <?php foreach ($pagesList as $page): ?>
            <?php 
            $isHome = ($page['slug'] === '');
            $statusClass = ($page['status'] === 'published') ? 'text-emerald-800 bg-emerald-50' : 'text-amber-600 bg-amber-50';
            ?>
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
                <div class="flex items-start justify-between border-b border-slate-100 pb-3 mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <?php if ($page['id'] !== getHomePageId()): ?>
                                <span class="inline-flex items-center gap-1">
                                    <span class="text-base font-bold text-slate-800"><?php echo e($page['title']); ?></span>
                                    <button type="submit" form="page-actions-mobile-<?php echo e($page['id']); ?>" name="page_set_home" value="1" class="icon-star text-slate-300 text-sm hover:text-amber-500 transition-colors cursor-pointer" title="Сделать главной" onclick="return confirm('Сделать страницу &quot;<?php echo e($page['title']); ?>&quot; главной?')"></button>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1">
                                    <span class="text-base font-bold text-slate-800"><?php echo e($page['title']); ?></span>
                                    <span class="text-amber-500 icon-star text-sm" title="Главная страница"></span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo $statusClass; ?> flex-shrink-0">
                        <?php echo $page['status'] === 'published' ? 'Опубликована' : 'Черновик'; ?>
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 mb-3">
                    <div>
                        <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">ЧПУ</span>
                        <span class="font-mono font-medium text-slate-700"><?php echo $isHome ? '/' : '/' . e($page['slug']); ?></span>
                    </div>
                    <div>
                        <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">Шаблон</span>
                        <span class="font-medium text-slate-700"><?php echo e($page['template'] ?? 'full-width'); ?></span>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                    <a href="?tab=pages&action=edit&id=<?php echo e($page['id']); ?>" class="flex-1 h-10 bg-white border border-slate-200 text-slate-600 rounded-lg text-xs font-bold flex items-center justify-center gap-1 transition-all cursor-pointer hover:bg-slate-50">
                        <span class="icon-edit text-xs"></span> Редактировать
                    </a>

                    <form id="page-actions-mobile-<?php echo e($page['id']); ?>" method="POST" action="index.php?tab=pages" class="flex items-center gap-2 flex-1">
                        <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
                        <input type="hidden" name="id" value="<?php echo e($page['id']); ?>">

                        <button type="submit" name="page_clone" value="1" class="flex-1 h-10 bg-white border border-slate-200 text-slate-600 rounded-lg text-xs font-bold flex items-center justify-center gap-1 transition-all cursor-pointer hover:bg-slate-50" onclick="return confirm('Клонировать страницу &quot;<?php echo e($page['title']); ?>&quot;?')">
                            <span class="icon-copy text-xs"></span> Клонировать
                        </button>

                        <?php if (!$isHome): ?>
                            <button type="submit" name="page_delete" value="1" class="w-10 h-10 flex-shrink-0 bg-white border border-rose-100 text-rose-500 rounded-lg flex items-center justify-center transition-all cursor-pointer hover:bg-rose-50" title="Удалить" onclick="return confirm('Удалить страницу &quot;<?php echo e($page['title']); ?>&quot;? Это действие необратимо.')">
                                <span class="icon-trash-2 text-sm"></span>
                            </button>
                        <?php else: ?>
                            <button type="button" class="w-10 h-10 flex-shrink-0 bg-white border border-slate-200 text-slate-400 rounded-lg flex items-center justify-center cursor-not-allowed opacity-40" disabled title="Главную страницу нельзя удалить">
                                <span class="icon-trash-2 text-sm"></span>
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ПАГИНАЦИЯ -->
<?php if (!empty($pagesList)): ?>
    <div class="mt-6 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
        
        <!-- Левая часть: инфа + селект -->
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
            <span>
                Показано <strong class="text-slate-700"><?php echo count($pagesList); ?></strong> 
                из <strong class="text-slate-700"><?php echo $totalPagesCount; ?></strong>
            </span>
            
            <span class="text-slate-300">|</span>
            
            <label class="flex items-center gap-2 cursor-pointer">
                <span>Показывать:</span>
                <select onchange="window.location.href='?tab=pages&page=1&per_page=' + this.value" 
                        class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs text-slate-700 focus:outline-none focus:border-[var(--primary-color)] cursor-pointer">
                    <?php foreach ([10, 25, 50, 100, 200] as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo $perPage === $opt ? 'selected' : ''; ?>>
                            <?php echo $opt; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        
        <!-- Правая часть: навигация -->
        <?php if ($totalPages > 1): ?>
            <div class="flex items-center gap-1">
                <?php if ($currentPage > 1): ?>
                    <a href="?tab=pages&page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $perPage; ?>" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 transition-all">
                        <span class="icon-chevron-left text-sm"></span>
                    </a>
                <?php endif; ?>

                <?php
                $start = max(1, $currentPage - 2);
                $end = min($totalPages, $currentPage + 2);

                if ($start > 1): ?>
                    <a href="?tab=pages&page=1&per_page=<?php echo $perPage; ?>" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 transition-all text-xs font-bold">1</a>
                    <?php if ($start > 2): ?>
                        <span class="text-slate-300 text-xs px-1">…</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?tab=pages&page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?>" class="w-8 h-8 rounded-lg flex items-center justify-center border <?php echo $i === $currentPage ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]' : 'border-slate-200 text-slate-500 hover:bg-slate-50'; ?> transition-all text-xs font-bold">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <span class="text-slate-300 text-xs px-1">…</span>
                    <?php endif; ?>
                    <a href="?tab=pages&page=<?php echo $totalPages; ?>&per_page=<?php echo $perPage; ?>" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 transition-all text-xs font-bold"><?php echo $totalPages; ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?tab=pages&page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $perPage; ?>" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 transition-all">
                        <span class="icon-chevron-right text-sm"></span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    </div>
<?php endif; ?>