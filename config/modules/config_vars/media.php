<?php
/**
 * Секция настроек: Медиа.
 * Ожидает в области видимости: $settingsData
 */
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$mediaLazyEnabled = !empty($settingsData['media_lazy_enabled']);
$mediaQuality     = max(1, min(100, (int)($settingsData['media_quality'] ?? 82)));

$webpSupported = isWebpSupported();
$webpEngine = '';
if ($webpSupported) {
    if (extension_loaded('gd') && function_exists('imagewebp')) {
        $webpEngine = 'GD';
    } elseif (extension_loaded('imagick')) {
        $webpEngine = 'Imagick';
    }
}
$webpEnabled = $webpSupported && !empty($settingsData['media_webp_enabled']);
?>

<!-- СЕКЦИЯ: Медиа -->
<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Медиа</span>
            <span class="text-[10px] text-slate-400">(WebP, lazy loading)</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <div class="space-y-5">

            <!-- LAZY -->
            <div class="editor-field">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="media_lazy_enabled" value="0">
                    <input type="checkbox" name="media_lazy_enabled" value="1"
                           <?php echo $mediaLazyEnabled ? 'checked' : ''; ?>
                           class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                    <span class="text-sm font-medium text-slate-700">Lazy loading изображений</span>
                </label>
                <p class="text-[10px] text-slate-400 mt-1">
                    Добавляет <code class="font-mono bg-white px-1 rounded border border-slate-200">loading="lazy"</code> и
                    <code class="font-mono bg-white px-1 rounded border border-slate-200">decoding="async"</code>
                    к контентным изображениям. Не применяется к hero-секциям и логотипу в хедере.
                </p>
            </div>

            <!-- WEBP -->
            <div class="border-t border-slate-200/60 pt-4 mt-2">
                <?php if (!$webpSupported): ?>
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 mb-3">
                        <span class="icon-alert-triangle inline mr-1"></span>
                        WebP-конвертация <strong>не поддерживается на этом сервере</strong>.
                        Нужно расширение <strong>GD</strong> (с поддержкой WebP) или <strong>Imagick</strong>.
                    </div>
                    <?php if (!empty($settingsData['media_webp_enabled'])): ?>
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 mb-3">
                            <span class="icon-info inline mr-1"></span>
                            Настройка была включена, но сейчас не работает.
                            При следующем <strong>сохранении настроек</strong> она будет автоматически отключена.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="editor-field">
                    <label class="flex items-center gap-2 cursor-pointer <?php echo !$webpSupported ? 'opacity-50' : ''; ?>">
                        <input type="hidden" name="media_webp_enabled" value="0">
                        <input type="checkbox" name="media_webp_enabled" value="1"
                               <?php echo $webpEnabled ? 'checked' : ''; ?>
                               <?php echo !$webpSupported ? 'disabled' : ''; ?>
                               class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                        <span class="text-sm font-medium text-slate-700">Конвертировать в WebP при загрузке</span>
                    </label>
                    <p class="text-[10px] text-slate-400 mt-1">
                        При загрузке jpg/png создаётся копия в .webp рядом с оригиналом. Оригинал сохраняется.
                    </p>
                </div>

                <?php if ($webpSupported): ?>
                    <div class="editor-field mt-3">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Качество WebP</label>
                        <input type="number" name="media_quality" value="<?php echo $mediaQuality; ?>" min="1" max="100"
                               class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                        <p class="text-[10px] text-slate-400 mt-1">
                            От 1 до 100. По умолчанию 82. Движок: <strong><?php echo e($webpEngine); ?></strong>.
                        </p>
                    </div>

                    <?php
                    $webpStats = getWebpStats();
                    $webpStats['size_formatted'] = formatSize($webpStats['size_bytes']);
                    ?>
                    <div class="border-t border-slate-200/60 pt-4 mt-4">
                        <div class="text-[10px] text-slate-400 p-3 bg-white rounded-xl border border-slate-200 mb-3">
                            <span class="font-semibold">Статистика .webp:</span><br>
                            Файлов: <strong id="webp-stat-count"><?php echo $webpStats['count']; ?></strong><br>
                            Объём: <strong id="webp-stat-size"><?php echo e($webpStats['size_formatted']); ?></strong>
                        </div>
                        <button type="button" onclick="window.deleteAllWebp()"
                                class="px-4 py-2 text-sm border border-rose-200 text-rose-600 font-bold rounded-xl hover:bg-rose-50 transition-all cursor-pointer flex items-center gap-2 <?php echo $webpStats['count'] === 0 ? 'opacity-40 cursor-not-allowed' : ''; ?>"
                                <?php echo $webpStats['count'] === 0 ? 'disabled' : ''; ?>>
                            <span class="icon-trash-2 text-sm"></span> Удалить все .webp
                        </button>
                        <p class="text-[10px] text-slate-400 mt-1">
                            Удаляет только .webp, у которых есть парный jpg/png. Оригиналы не трогает.
                        </p>

                        <?php $withoutWebp = countImagesWithoutWebp(); ?>
                        <?php if ($withoutWebp > 0): ?>
                        <div class="border-t border-slate-200/60 pt-4 mt-4">
                            <div class="text-[10px] text-slate-400 p-3 bg-white rounded-xl border border-slate-200 mb-3">
                                Без .webp: <strong><?php echo $withoutWebp; ?></strong> jpg/png
                            </div>
                            <button type="button" onclick="window.convertExistingImages()"
                                    class="px-4 py-2 text-sm border border-emerald-200 text-emerald-600 font-bold rounded-xl hover:bg-emerald-50 transition-all cursor-pointer flex items-center gap-2">
                                <span class="icon-refresh-cw text-sm"></span> Конвертировать <?php echo $withoutWebp; ?> существующих
                            </button>
                            <p class="text-[10px] text-slate-400 mt-1">
                                Обрабатывает существующие jpg/png шагами. Оригиналы сохраняются.
                            </p>

                            <div id="webp-convert-progress" class="hidden mt-3 bg-white border border-slate-200 p-3 rounded-xl">
                                <div class="flex justify-between text-xs font-bold text-slate-700 mb-2">
                                    <span id="webp-convert-status">Сканирование...</span>
                                    <span id="webp-convert-percent" class="font-mono">0%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div id="webp-convert-bar" class="bg-emerald-500 h-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>