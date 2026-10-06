<?php
/**
 * Общая модалка корзины для страниц и меню.
 *
 * Ожидаемые переменные:
 * - $trashContext  'page' | 'menu'
 * - $trashReturnUrl URL для редиректа после POST
 */

if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$trashContext   = $trashContext   ?? 'page';
$trashReturnUrl = $trashReturnUrl ?? '?tab=pages';

$trashList  = getTrashList($trashContext);
$trashCount = count($trashList);
$token      = $_SESSION['csrf_token'] ?? '';
$contextLabel = ($trashContext === 'page') ? 'страницы' : 'меню';
?>

<!-- Кнопка «Корзина» для вставки в шапку -->
<!-- ВНИМАНИЕ: кнопка выводится отдельно, в шапке pages.php/menu.php -->
<!-- Здесь только модалка + JS -->

<!-- Модалка корзины -->
<div id="trash-modal" class="modal-backdrop-fixed !z-9996">
    <div class="modal-content-card" style="max-width: 800px; max-height: 90vh;">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <div>
                <h3 class="text-base font-bold text-slate-800">Корзина</h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Удалённые <?php echo e($contextLabel); ?>. Хранятся до ручной очистки.
                </p>
            </div>
            <button type="button" onclick="window.closeTrashModal()" 
                    class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all">
                <span class="icon-x text-xl"></span>
            </button>
        </div>

        <div class="p-4 overflow-y-auto flex-1" style="max-height: calc(90vh - 180px);">
            <?php if (empty($trashList)): ?>
                <div class="text-center py-16 text-slate-400">
                    <span class="icon-trash-2 text-3xl block mb-3 text-slate-300"></span>
                    <p class="text-sm font-semibold text-slate-600">Корзина пуста</p>
                    <p class="text-xs mt-1">Удалённые <?php echo e($contextLabel); ?> появятся здесь.</p>
                </div>
            <?php else: ?>
                <!-- Шапка: выбрать все -->
                <div class="flex items-center gap-3 px-3 py-2 mb-2 border-b border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="trash-select-all" class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Выбрать все</span>
                    </label>
                    <span class="text-xs text-slate-400 ml-auto">
                        <span id="trash-selected-count">0</span> выбрано из <?php echo $trashCount; ?>
                    </span>
                </div>

                <!-- Список -->
                <div class="space-y-2" id="trash-list">
                    <?php foreach ($trashList as $item): ?>
                        <label class="trash-item flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl hover:border-slate-300 transition-all cursor-pointer"
                               data-file="<?php echo e($item['file']); ?>">
                            <input type="checkbox" class="trash-item-checkbox w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 flex-shrink-0">
                            
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-slate-800 truncate" title="<?php echo e($item['title']); ?>">
                                    <?php echo e($item['title']); ?>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-400 mt-1">
                                    <span class="font-mono">ID: <?php echo e($item['original_id']); ?></span>
                                    <span>Удалено: <?php echo date('d.m.Y H:i', $item['deleted_at']); ?></span>
                                    <?php if (!empty($item['deleted_by'])): ?>
                                        <span>Кем: <?php echo e($item['deleted_by']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="p-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50 flex-shrink-0">
            <button type="button" 
                    onclick="window.trashClearAll()" 
                    class="px-4 py-2 border border-rose-200 text-rose-600 text-sm font-bold rounded-xl hover:bg-rose-50 transition-all <?php echo empty($trashList) ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'; ?>"
                    <?php echo empty($trashList) ? 'disabled' : ''; ?>>
                <span class="icon-trash-2 text-sm mr-1"></span> Очистить корзину
            </button>

            <div class="flex items-center gap-2">
                <button type="button" 
                        onclick="window.trashRestoreSelected()" 
                        class="px-4 py-2 bg-[var(--primary-color)] text-white text-sm font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all cursor-pointer disabled:opacity-40"
                        id="trash-restore-btn" disabled>
                    <span class="icon-rotate-ccw text-sm mr-1"></span> Восстановить
                </button>
                <button type="button" 
                        onclick="window.trashDeleteSelected()" 
                        class="px-4 py-2 bg-rose-500 text-white text-sm font-bold rounded-xl hover:bg-rose-600 transition-all cursor-pointer disabled:opacity-40"
                        id="trash-delete-btn" disabled>
                    <span class="icon-x text-sm mr-1"></span> Удалить выбранные
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Скрытая форма для POST-действий -->
<form id="trash-action-form" method="POST" action="<?php echo e($trashReturnUrl); ?>" class="hidden">
    <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
    <input type="hidden" name="trash_return_url" value="<?php echo e($trashReturnUrl); ?>">
    <input type="hidden" name="trash_context" value="<?php echo e($trashContext); ?>">
    <div id="trash-form-files"></div>
    <input type="hidden" name="trash_action" id="trash-form-action" value="">
</form>

<script src="js/trash_modal.js?v=<?php echo filemtime(APP_ROOT . '/config/js/trash_modal.js'); ?>"></script>