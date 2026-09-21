<?php
/**
 * Модалки для конструктора (вынесены из формы)
 */
$modules = getModulesData();
$token = $_SESSION['csrf_token'] ?? '';
// Подключаем медиа-модалку для zoomCarouselImage
include APP_ROOT . '/config/core/media_modal.php';
?>

<!-- Модалка выбора модуля -->
<div id="module-select-modal" class="modal-backdrop-fixed !z-9995">
    <div class="modal-content-card" style="max-width: 500px;">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h3 class="text-base font-bold text-slate-800">Выбор модуля</h3>
            <button type="button" onclick="window.closeModuleModal()" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all">
                <span class="icon-x text-xl"></span>
            </button>
        </div>
        <div class="p-4 max-h-[400px] overflow-y-auto">
            <div class="space-y-2">
                <?php $previewPath = '/config/modules/previews/'; ?>
                <?php foreach ($modules as $key => $mod): ?>
                    <?php 
                    $previewImg = $previewPath . $key . '.png';
                    $hasPreview = file_exists(APP_ROOT . $previewImg);
                    ?>

                    <button type="button" onclick="window.selectModule('<?php echo e($key); ?>')" 
                            class="w-full p-3 bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-200 rounded-xl text-left transition-all flex items-center gap-3">

                        <?php if ($hasPreview): ?>
                            <div onclick="event.stopPropagation(); zoomCarouselImage(this)" 
                                 class="carousel-inline-preview cursor-pointer" 
                                 title="Кликните для увеличения" 
                                 style="width:40px !important;height:40px !important;min-width:40px !important;">
                                <img src="<?php echo $previewImg; ?>" 
                                     class="js-slide-preview-img w-full h-full object-cover" 
                                     alt="Превью модуля">
                            </div>
                        <?php else: ?>
                            <span class="<?php echo e($mod['icon'] ?? 'icon-box'); ?> text-lg text-slate-400 w-[40px] text-center"></span>
                        <?php endif; ?>

                        <div>
                            <div class="font-semibold text-slate-700"><?php echo e($mod['name']); ?></div>
                            <div class="text-xs text-slate-400"><?php echo e($mod['description'] ?? ''); ?></div>
                        </div>
                        <?php if (empty($mod['has_settings'])): ?>
                            <span class="ml-auto text-xs text-slate-400">без настроек</span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Модалка настроек модуля -->
<div id="module-settings-modal" class="modal-backdrop-fixed !z-9995">
    <div class="modal-content-card" style="max-width: 700px; max-height: 90vh;">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <h3 class="text-base font-bold text-slate-800">
                Настройки модуля «<span id="settings-module-name"></span>»
            </h3>
            <button type="button" onclick="window.closeModuleSettings()" class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all">
                <span class="icon-x text-xl"></span>
            </button>
        </div>
        <form id="module-settings-form" method="POST" action="#" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
            <div class="p-4 overflow-y-auto" id="module-settings-content" style="max-height: calc(90vh - 180px);">
                <div class="text-center py-8 text-slate-400">
                    <span class="icon-refresh-cw animate-spin inline-block mr-2"></span> 
                    Загрузка настроек...
                </div>
            </div>
        </form>
        <div class="p-4 border-t border-slate-100 flex justify-end gap-2 flex-shrink-0">
            <button type="button" onclick="window.closeModuleSettings()" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition">Отмена</button>
            <button type="button" onclick="window.saveModuleSettings()" class="px-4 py-2 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition">
                Сохранить
            </button>
        </div>
    </div>
</div>