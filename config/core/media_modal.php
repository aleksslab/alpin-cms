<?php
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) { die('Доступ запрещен'); }

$targetSubFolder = preg_replace('/[^a-z0-9_-]/i', '', $mediaTargetFolder ?? '');
$scanRoot = isset($scanRoot) && $scanRoot === true;

if ($scanRoot) {
    $imagesDir = APP_ROOT;
    $baseUrl = '';
    $displayPath = 'корень сайта';
} else {
    $imagesDir = APP_ROOT . DIRECTORY_SEPARATOR . 'images';
    if (!empty($targetSubFolder)) { 
        $imagesDir .= DIRECTORY_SEPARATOR . $targetSubFolder; 
    }
    $baseUrl = 'images/' . (!empty($targetSubFolder) ? $targetSubFolder . '/' : '');
    $displayPath = '/images/' . (!empty($targetSubFolder) ? $targetSubFolder . '/' : '');
}

$serverImages = [];
if (!is_dir($imagesDir)) { mkdir($imagesDir, 0755, true); }

$files = scandir($imagesDir);
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'ico'])) {
        $serverImages[] = $baseUrl . $file;
    }
}
?>

<!-- Окно полного просмотра (ZOOM) -->
<div id="carousel-zoom-modal" class="zoom-modal-backdrop animate-fade-in" onclick="closeZoomModal()">
    <img id="zoom-modal-target-img" class="zoom-modal-img" src="" alt="Увеличенное изображение">
</div>

<!-- Контейнер основного медиа-менеджера -->
<div id="media-modal" class="modal-backdrop-fixed animate-fade-in" 
     data-folder="<?php echo e($targetSubFolder); ?>"      
     data-root="<?php echo $scanRoot ? '1' : '0'; ?>">
    <div class="modal-content-card flex-col">
        
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <div>
                <h3 class="text-base font-bold text-slate-800">Выбор изображения</h3>
                <p class="text-xs text-slate-400 my-1">Выберите файл из галереи сервера или загрузите новый</p>
            </div>
            <button type="button" onclick="closeMediaModal()" class="w-12 h-12 flex items-center justify-center bg-white border border-slate-200 text-slate-400 rounded-lg hover:text-slate-600 transition-all cursor-pointer">
                <span class="icon-x text-xl"></span>
            </button>
        </div>

        <div class="p-4 border-b border-slate-100 bg-slate-50/20 shrink-0">
            <div class="px-4 py-2 border border-dashed border-slate-200 rounded-xl bg-white modal-upload-inline-bar">
                <div class="flex items-center gap-3">
                    <div class="modal-upload-icon-box">
                        <span class="icon-plus text-base"></span>
                    </div>
                    <div class="modal-upload-text-block">
                        <p class="text-xs font-bold text-slate-700 my-1">Перетащите изображение сюда или нажмите</p>
                        <p class="text-[10px] text-slate-400 my-1" style="margin-bottom: .25rem;">Разрешенные типы файлов: JPG, PNG, WEBP, SVG, ICO</p>
                    </div>
                </div>
                <label class="modal-upload-btn-action">
                    Обзор...
                    <input type="file" id="modal-file-input" onchange="uploadImageFromDevice()" class="hidden" accept="image/*">
                </label>
            </div>
            <div id="upload-error-msg" class="hidden mt-2 p-2 text-sm font-semibold text-rose-800 bg-rose-50 border border-rose-200 rounded-xl animate-fade-in"></div>
        </div>

        <div class="media-scroll-zone">
            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                Доступные файлы (<?php echo $scanRoot ? 'корень сайта' : $displayPath; ?>)
            </h4>
            <div id="media-grid" class="media-preview-grid">
                <?php if (empty($serverImages)): ?>
                    <p id="empty-grid-msg" class="text-slate-400 text-xs col-span-full py-12 text-center w-full block">Изображений в этой папке пока нет.</p>
                <?php else: ?>
                    <?php foreach ($serverImages as $imgUrl):
                        $ext = strtolower(pathinfo($imgUrl, PATHINFO_EXTENSION));
                        $displayExt = strtoupper($ext);
                    ?>
                        <div class="media-preview-item group js-media-item" data-url="<?php echo e($imgUrl); ?>">
                            <img src="<?php echo $scanRoot ? '/' : '../'; ?><?php echo e($imgUrl); ?>" class="w-full h-full object-contain group-hover:scale-105 transition-all duration-300">
                            <!-- Плашка с расширением -->
                            <div class="absolute bottom-2 right-2 bg-black/70 text-white text-[9px] font-bold px-2 py-0.5 rounded-md backdrop-blur-sm">
                                <?php echo $displayExt; ?>
                            </div>
                            <div class="absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center">
                                <span class="px-2 py-1 bg-white text-slate-800 text-[10px] font-bold rounded-lg shadow border border-slate-100">Выбрать</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="js/media_modal.js?v=<?php echo filemtime('js/media_modal.js'); ?>" type="text/javascript"></script>