<?php
/**
 * Модалка истории изменений страницы.
 *
 * Ожидаемые переменные:
 * - $historyPageId — ID страницы
 */

if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$historyPageId = $historyPageId ?? '';
if (!preg_match('/^[a-z0-9\-_]+$/i', $historyPageId)) {
    return;
}

// Читаем историю страницы через текущий файл
$currentPage = loadPageById($historyPageId);
$historyKey  = $currentPage['history_key'] ?? '';

$historyList  = ($historyKey !== '' && preg_match('/^[a-f0-9]{16}$/i', $historyKey))
    ? getPageHistory($historyKey)
    : [];
$historyCount = count($historyList);
$token        = $_SESSION['csrf_token'] ?? '';
?>

<div id="history-modal" class="modal-backdrop-fixed !z-9996">
    <div class="modal-content-card" style="max-width: 800px; max-height: 90vh;">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <div>
                <h3 class="text-base font-bold text-slate-800">История изменений</h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Сохранённые версии страницы. Всего: <?php echo $historyCount; ?>.
                </p>
            </div>
            <button type="button" onclick="window.closeHistoryModal()"
                    class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all">
                <span class="icon-x text-xl"></span>
            </button>
        </div>

        <div class="p-4 overflow-y-auto flex-1" style="max-height: calc(90vh - 180px);">
            <?php if (empty($historyList)): ?>
                <div class="text-center py-16 text-slate-400">
                    <span class="icon-history text-3xl block mb-3 text-slate-300"></span>
                    <p class="text-sm font-semibold text-slate-600">История пуста</p>
                    <p class="text-xs mt-1">Версии появятся здесь после первого изменения и сохранения страницы.</p>
                </div>
            <?php else: ?>
                <div class="space-y-2" id="history-list">
                    <?php foreach ($historyList as $item): ?>
                        <div class="history-item flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl hover:border-slate-300 transition-all">
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-slate-800">
                                    <?php echo date('d.m.Y H:i', $item['snapshot_at']); ?>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-400 mt-1">
                                    <?php if (!empty($item['saved_by'])): ?>
                                        <span>Кем: <?php echo e($item['saved_by']); ?></span>
                                    <?php endif; ?>
                                    <span>Размер: <?php echo e(formatSize($item['size_bytes'])); ?></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button type="button"
                                        onclick="window.restoreHistoryVersion(<?php echo (int)$item['timestamp']; ?>)"
                                        class="px-3 py-2 bg-[var(--primary-color)] text-white text-xs font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all cursor-pointer flex items-center gap-1.5">
                                    <span class="icon-rotate-ccw text-sm"></span> Откатить
                                </button>
                                <button type="button"
                                        onclick="window.deleteHistoryVersion(<?php echo (int)$item['timestamp']; ?>)"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center border border-rose-100 text-rose-500 hover:text-rose-800 bg-white hover:bg-rose-50 transition-all cursor-pointer"
                                        title="Удалить версию">
                                    <span class="icon-trash-2 text-sm"></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="p-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50 flex-shrink-0">
            <p class="text-[10px] text-slate-400 max-w-md">
                Откат восстанавливает: заголовок, содержимое, SEO-настройки, шаблон, хедер/футер.
                Статус публикации, URL (slug) и preview-ссылка не изменятся.
            </p>
            <div class="flex items-center gap-2">
                <button type="button"
                        onclick="window.clearPageHistory()"
                        class="px-4 py-2 border border-rose-200 text-rose-600 text-sm font-bold rounded-xl hover:bg-rose-50 transition-all cursor-pointer flex items-center gap-2 <?php echo empty($historyList) ? 'opacity-40 cursor-not-allowed' : ''; ?>"
                        <?php echo empty($historyList) ? 'disabled' : ''; ?>>
                    <span class="icon-trash-2 text-sm"></span> Очистить всю
                </button>
                <button type="button"
                        onclick="window.closeHistoryModal()"
                        class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-bold rounded-xl hover:bg-slate-50 transition-all cursor-pointer">
                    Закрыть
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Скрытая форма для POST-действий -->
<form id="history-action-form" method="POST" action="?tab=pages&amp;action=edit&amp;id=<?php echo e($historyPageId); ?>" class="hidden">
    <input type="hidden" name="csrf_token" value="<?php echo e($token); ?>">
    <input type="hidden" name="page_id" value="<?php echo e($historyPageId); ?>">
    <input type="hidden" name="version_ts" id="history-form-ts" value="">

    <!-- JS добавит сюда один из action-полей -->
</form>

<script src="js/history_modal.js?v=<?php echo filemtime(APP_ROOT . '/config/js/history_modal.js'); ?>"></script>