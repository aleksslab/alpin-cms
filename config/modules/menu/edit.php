<?php
// --- config/modules/menu/edit.php (Редактирование/создание меню) ---
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

// Определяем режим
$isEdit = isset($_GET['id']) && !empty($_GET['id']);
$menuId = $isEdit ? $_GET['id'] : '';

// Загружаем данные меню
$menuData = $isEdit ? loadMenu($menuId) : null;

// Если редактирование и меню не найдено — редирект
if ($isEdit && !$menuData) {
    setFlash('Меню не найдено.', 'error');
    header('Location: ?tab=menu');
    exit;
}

// Данные по умолчанию
$menuName = $menuData['name'] ?? '';
$menuItems = $menuData['items'] ?? [];

// Получаем ID главного меню
$mainMenuId = getMainMenuId();

// Типы пунктов меню (для отображения в таблице)
$itemTypeLabels = [
    'page' => 'Страница',
    'section' => 'Раздел',
    'custom' => 'Ссылка',
    'dropdown' => 'Выпадающее',
    'divider' => 'Разделитель'
];

// Получаем список страниц для селекта в модалках
$pagesList = getPagesList();
$pageOptions = [];
foreach ($pagesList as $page) {
    $pageOptions[] = [
        'url' => $page['slug'] === '' ? '/' : '/' . $page['slug'],
        'label' => $page['title'] . ($page['slug'] === '' ? ' (главная)' : '')
    ];
}
if (empty($pageOptions)) {
    $pageOptions[] = ['url' => '/', 'label' => 'Главная страница'];
}

$token = $_SESSION['csrf_token'] ?? '';
?>

<div class="flex items-center gap-4 mb-6">
    <a href="?tab=menu" class="w-10 h-10 rounded-xl border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 hover:border-slate-300 transition-all">
        <span class="icon-arrow-left text-lg"></span>
    </a>
    <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">
            <?php echo $isEdit ? 'Редактирование меню' : 'Создание меню'; ?>
        </h1>
        <p class="text-slate-500 text-sm">
            <?php echo $isEdit ? 'Редактирование "' . e($menuName) . '"' : 'Создание нового меню'; ?>
        </p>
    </div>
</div>

<?php renderFlash() ?>

<form method="POST" action="index.php?tab=menu" id="menu-edit-form" class="space-y-6">
    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
    <input type="hidden" name="save_menu" value="1">
    <input type="hidden" name="menu_id" value="<?php echo e($menuId); ?>">
    
    <!-- ===== JSON ДАННЫХ ===== -->
    <input type="hidden" name="menu_items_json" id="menu-items-json-input" value='<?php echo htmlspecialchars(json_encode($menuItems, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>'>
    
    <!-- БЛОК 1: Основная информация -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <h3 class="text-sm font-bold text-slate-800 border-b border-slate-200/60 pb-3">Основная информация</h3>

        <div class="editor-row !items-start">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Название меню *</label>
                <input type="text" name="menu_name" id="menu-name-input" value="<?php echo e($menuName); ?>" required 
                       class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]" 
                       placeholder="Например: Главное меню">
                <p class="text-[10px] text-slate-400 mt-1">Введите название меню</p>
            </div>
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">ID меню <span class="text-rose-500">*</span></label>
                <input type="text" name="menu_id" id="menu-id-input" 
                       value="<?php echo $isEdit ? e($menuId) : ''; ?>" 
                       placeholder="avtomaticheski" 
                       class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:border-[var(--primary-color)]">
                <div class="text-[10px] text-slate-400 mt-1">Только латиница, цифры, дефис и подчёркивание. Будет использован в URL: <span id="menu-id-preview" class="text-[var(--primary-color)] font-semibold">/<?php echo $isEdit ? e($menuId) : ''; ?></span></div>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="menu_id_original" value="<?php echo e($menuId); ?>">
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isEdit && $menuId === $mainMenuId): ?>
            <div class="flex items-center gap-2 text-sm text-amber-600 bg-amber-50 px-4 py-2 rounded-xl border border-amber-200">
                <span class="icon-star text-amber-500"></span>
                Это главное меню сайта (сворачивается в бургер)
            </div>
        <?php endif; ?>
    </div>

    <!-- БЛОК 2: Список пунктов меню -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="icon-list text-[var(--primary-color)]"></span>
                <h3 class="text-sm font-bold text-slate-800">Пункты меню</h3>
                <span class="text-xs text-slate-400" id="items-count"><?php echo count($menuItems); ?> пунктов</span>
            </div>
            <button type="button" onclick="openAddItemModal()" class="px-3 py-1.5 bg-[var(--primary-color)] text-white text-xs font-bold rounded-lg hover:bg-[var(--primary-dark)] transition-all flex items-center gap-1">
                <span class="icon-plus text-xs"></span> Добавить пункт
            </button>
        </div>

        <div id="menu-items-container" class="p-4">
            <!-- ДЕСКТОП: Таблица -->
            <div class="w-full desktop-only">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                            <th class="py-3 px-3">№</th>
                            <th class="py-3">Название</th>
                            <th class="py-3">Тип</th>
                            <th class="py-3 text-right pr-3">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700" id="menu-items-tbody">
                        <?php if (!empty($menuItems)): ?>
                            <?php foreach ($menuItems as $index => $item): 
                                $itemId = $item['id'] ?? 'item_' . uniqid();
                                $type = $item['type'] ?? 'page';
                                $label = $item['label'] ?? '';
                                $url = $item['url'] ?? '';
                                $pageId = $item['page_id'] ?? '';
                                $children = $item['children'] ?? [];
                                $typeLabel = $itemTypeLabels[$type] ?? $type;
                                $childrenJson = json_encode($children, JSON_UNESCAPED_UNICODE);
                            ?>
                                <tr class="menu-item hover:bg-slate-50 transition-colors group border-b border-slate-100" 
                                    data-id="<?php echo $itemId; ?>"
                                    data-label="<?php echo e($label); ?>"
                                    data-type="<?php echo $type; ?>"
                                    data-url="<?php echo e($url); ?>"
                                    data-page-id="<?php echo e($pageId); ?>"
                                    data-children='<?php echo htmlspecialchars($childrenJson, ENT_QUOTES, 'UTF-8'); ?>'>
                                    <td class="py-3 px-3 font-mono text-xs text-slate-400"><?php echo $index + 1; ?></td>
                                    <td class="py-3 font-semibold text-slate-800">
                                        <?php if ($type === 'dropdown'): ?>
                                            <span class="inline-flex items-center gap-1">
                                                <span class="icon-chevron-down text-xs text-slate-400"></span>
                                                <?php echo e($label); ?>
                                                <?php if (!empty($children)): ?>
                                                    <span class="text-[10px] text-slate-400 ml-1">(<?php echo count($children); ?>)</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <?php echo e($label); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3">
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold text-slate-600 bg-slate-100">
                                            <?php echo $typeLabel; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 text-right px-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" onclick="openEditItemModal('<?php echo $itemId; ?>')" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:text-slate-800 bg-white hover:bg-slate-50 transition-all" title="Редактировать">
                                                <span class="icon-edit text-sm"></span>
                                            </button>
                                            <button type="button" onclick="moveMenuItem(this, 'up')" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-400 hover:text-slate-600 bg-white hover:bg-slate-50 transition-all" title="Вверх">
                                                <span class="icon-chevron-up text-xs"></span>
                                            </button>
                                            <button type="button" onclick="moveMenuItem(this, 'down')" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-400 hover:text-slate-600 bg-white hover:bg-slate-50 transition-all" title="Вниз">
                                                <span class="icon-chevron-down text-xs"></span>
                                            </button>
                                            <button type="button" onclick="removeMenuItem(this)" class="w-8 h-8 rounded-lg flex items-center justify-center border border-rose-100 text-rose-500 hover:text-rose-800 bg-white hover:bg-rose-50 transition-all" title="Удалить">
                                                <span class="icon-trash-2 text-sm"></span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- МОБИЛЬНАЯ ВЕРСИЯ: Карточки -->
            <div class="mobile-only space-y-4" id="menu-items-mobile">
                <?php if (!empty($menuItems)): ?>
                    <?php foreach ($menuItems as $index => $item): 
                        $itemId = $item['id'] ?? 'item_' . uniqid();
                        $type = $item['type'] ?? 'page';
                        $label = $item['label'] ?? '';
                        $url = $item['url'] ?? '';
                        $pageId = $item['page_id'] ?? '';
                        $children = $item['children'] ?? [];
                        $typeLabel = $itemTypeLabels[$type] ?? $type;
                        $childrenJson = json_encode($children, JSON_UNESCAPED_UNICODE);
                    ?>
                        <div class="menu-item bg-white p-4 rounded-2xl border border-slate-100 shadow-sm" 
                             data-id="<?php echo $itemId; ?>"
                             data-label="<?php echo e($label); ?>"
                             data-type="<?php echo $type; ?>"
                             data-url="<?php echo e($url); ?>"
                             data-page-id="<?php echo e($pageId); ?>"
                             data-children='<?php echo htmlspecialchars($childrenJson, ENT_QUOTES, 'UTF-8'); ?>'>
                            <div class="flex items-start justify-between border-b border-slate-100 pb-3 mb-3">
                                <div>
                                    <span class="text-base font-bold text-slate-800">
                                        <?php if ($type === 'dropdown'): ?>
                                            <span class="inline-flex items-center gap-1">
                                                <span class="icon-chevron-down text-xs text-slate-400"></span>
                                                <?php echo e($label); ?>
                                                <?php if (!empty($children)): ?>
                                                    <span class="text-[10px] text-slate-400 ml-1">(<?php echo count($children); ?>)</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <?php echo e($label); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold text-slate-600 bg-slate-100 flex-shrink-0">
                                    <?php echo $typeLabel; ?>
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 mb-3">
                                <div>
                                    <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">№</span>
                                    <span class="font-mono font-medium text-slate-700"><?php echo $index + 1; ?></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                                <button type="button" onclick="openEditItemModal('<?php echo $itemId; ?>')" class="flex-1 h-10 bg-white border border-slate-200 text-slate-600 rounded-lg text-xs font-bold flex items-center justify-center gap-1 transition-all cursor-pointer hover:bg-slate-50">
                                    <span class="icon-edit text-xs"></span> Редактировать
                                </button>
                                <button type="button" onclick="moveMenuItem(this, 'up')" class="w-10 h-10 flex-shrink-0 bg-white border border-slate-200 text-slate-400 rounded-lg flex items-center justify-center transition-all cursor-pointer hover:bg-slate-50" title="Вверх">
                                    <span class="icon-chevron-up text-xs"></span>
                                </button>
                                <button type="button" onclick="moveMenuItem(this, 'down')" class="w-10 h-10 flex-shrink-0 bg-white border border-slate-200 text-slate-400 rounded-lg flex items-center justify-center transition-all cursor-pointer hover:bg-slate-50" title="Вниз">
                                    <span class="icon-chevron-down text-xs"></span>
                                </button>
                                <button type="button" onclick="removeMenuItem(this)" class="w-10 h-10 flex-shrink-0 bg-white border border-rose-100 text-rose-500 rounded-lg flex items-center justify-center transition-all cursor-pointer hover:bg-rose-50" title="Удалить">
                                    <span class="icon-trash-2 text-sm"></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Сообщение, если нет пунктов -->
            <?php if (empty($menuItems)): ?>
                <div class="text-center py-8 text-sm text-slate-400 border-2 border-dashed border-slate-200 rounded-xl" id="empty-menu-message">
                    Нет пунктов в меню. Нажмите "Добавить пункт".
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Кнопки сохранения -->
    <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
        <button type="submit" class="px-8 py-3 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all shadow-lg shadow-[var(--primary-color)]/20 flex items-center gap-2">
            <span class="icon-save text-lg"></span> Сохранить меню
        </button>
        <a href="?tab=menu" class="px-6 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all">
            Отмена
        </a>
        <?php if ($isEdit && $menuId !== $mainMenuId): ?>
            <button type="submit" 
                    form="menu-delete-form" 
                    class="px-6 py-3 bg-rose-50 text-rose-600 font-bold rounded-xl hover:bg-rose-100 transition-all ml-auto">
                <span class="icon-trash-2 text-sm mr-1"></span> Удалить
            </button>
        <?php endif; ?>
    </div>
</form>

<?php if ($isEdit && $menuId !== $mainMenuId): ?>
<form id="menu-delete-form" method="POST" action="index.php?tab=menu" 
      onsubmit="return confirm('Удалить меню &quot;<?php echo e($menuName); ?>&quot; навсегда? Это действие необратимо.')" 
      style="display:none">
    <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
    <input type="hidden" name="menu_delete" value="1">
    <input type="hidden" name="id" value="<?php echo e($menuId); ?>">
</form>
<?php endif; ?>

<!-- Модалка -->
<?php include __DIR__ . '/modals.php'; ?>

<script src="js/main.js?v=<?php echo filemtime('js/main.js'); ?>" type="text/javascript"></script>
<script src="js/menu.js?v=<?php echo filemtime('js/menu.js'); ?>" type="text/javascript"></script>