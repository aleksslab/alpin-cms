<?php
// --- config/modules/backups.php (Финальная рабочая нативная разметка) ---
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) { 
    die('Доступ запрещен'); 
}

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'admin_controller.php';

// Загружаем РЕАЛЬНЫЕ живые настройки из файла backup_config.json
$backupSettings = getBackupSettingsData();

// Считываем РЕАЛЬНЫЙ список созданных ZIP-архивов из папки хранения
$realStorageDir = getCmsBackupStorageDir();

// Сканируем папку на наличие реальных ZIP-файлов для вывода в таблицу
$realBackups = [];
if ($realStorageDir && is_dir($realStorageDir)) {
    $files = scandir($realStorageDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
            $filePath = $realStorageDir . DIRECTORY_SEPARATOR . $file;
            
            $realBackups[] = [
                'date' => date('d.m.Y H:i', filemtime($filePath)),
                'file' => $file,
                'size' => round(filesize($filePath) / 1024, 1) . ' KB',
                'encrypted' => (strpos($file, '_enc_') !== false)
            ];
        }
    }
    // Сортируем бэкапы: свежие всегда сверху таблицы
    usort($realBackups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>

<h1 class="text-3xl font-bold text-slate-800 mb-2">Резервное копирование</h1>
<p class="text-slate-500 text-sm mb-8">Создание резервных копий контента и медиафайлов системы, управление расписанием автоматической архивации.</p>

<?php renderFlash() ?>

<!-- ФОРМА ОТПРАВЛЯЕТСЯ КЛАССИЧЕСКИМ POST-МЕТОДОМ НА ТЕКУЩИЙ ТАБ -->
<form id="js-backup-form" method="POST" action="index.php?tab=backups" class="mb-6">
    <!-- КРИПТОЗАЩИТА ФОРМЫ БЭКАПОВ -->
    <input type="hidden" id="js-backup-csrf-token" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

    <!-- БЛОК 1: НАСТРОЕК АРХИВА -->
    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 mb-6">
        <h3 class="text-lg font-bold text-slate-800 border-b border-slate-200/60 pb-3 mb-4">Настройка резервной копии</h3>
        
        <div class="editor-row">
            <div class="editor-field mb-2">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Режим копирования</label>
                <div class="flex items-center gap-4 py-1">
                    <label class="flex items-center gap-2 cursor-pointer text-sm font-semibold text-slate-700">
                        <input type="radio" name="backup_type" value="full" <?php echo ($backupSettings['backup_type'] ?? 'full') === 'full' ? 'checked' : ''; ?> onchange="toggleFmBackupFields()"> Полный бэкап
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm font-semibold text-slate-700">
                        <input type="radio" name="backup_type" value="custom" <?php echo ($backupSettings['backup_type'] ?? '') === 'custom' ? 'checked' : ''; ?> onchange="toggleFmBackupFields()"> Выборочно
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Полностью динамический вывод папок на базе CSS-классов .matrix-container -->
        <div id="js-backup-targets-zone" class="editor-field <?php echo ($backupSettings['backup_type'] ?? 'full') === 'full' ? 'hidden' : ''; ?>">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Объекты резервного копирования</label>
            
            <div class="matrix-container bg-slate-50 p-5 rounded-2xl border border-slate-100 max-h-[500px] overflow-hidden-safe">
                <?php
                $rootItems = scandir(APP_ROOT);
                $savedTargets = $backupSettings['targets'] ?? [];
                $storageDirName = basename($realStorageDir);

                // Разделяем на папки и файлы
                $folders = [];
                $files = [];

                foreach ($rootItems as $item):
                    if ($item === '.' || $item === '..') continue;

                    $absolutePath = APP_ROOT . DIRECTORY_SEPARATOR . $item;

                    // Пропускаем папку бэкапов
                    if (is_dir($absolutePath) && $item === $storageDirName) continue;

                    if (is_dir($absolutePath)) {
                        $folders[] = $item;
                    } else {
                        $files[] = $item;
                    }
                endforeach;

                // Сортируем каждую группу по алфавиту
                sort($folders, SORT_NATURAL | SORT_FLAG_CASE);
                sort($files, SORT_NATURAL | SORT_FLAG_CASE);

                // Объединяем: сначала папки, потом файлы
                $sortedItems = array_merge($folders, $files);
                $isFull = ($backupSettings['backup_type'] ?? 'full') === 'full';

                foreach ($sortedItems as $item):
                    $absolutePath = APP_ROOT . DIRECTORY_SEPARATOR . $item;
                    $isDir = is_dir($absolutePath);
                    $isChecked = $isFull || in_array($item, $savedTargets);
                    $icon = $isDir ? 'icon-folder' : 'icon-file-text';
                ?>
                    <label class="matrix-label select-none">
                        <input type="checkbox" name="targets[]" value="<?php echo e($item); ?>" class="js-backup-target-cb" <?php echo $isChecked ? 'checked' : ''; ?>>
                        <span class="<?php echo $icon; ?> text-slate-400"></span>
                        <span class="truncate" title="<?php echo e($item); ?>"><?php echo e($item); ?><?php echo $isDir ? '/' : ''; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>


        <div class="editor-row mt-4">
            <div class="editor-field">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Путь к папке бэкапов на сервере</label>
                <input type="text" name="storage_path" value="<?php echo e($backupSettings['storage_path'] ?? 'config/backups/'); ?>" required class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-base text-slate-800 font-mono focus:outline-none focus:border-[var(--primary-color)]" />
                <p class="text-[10px] text-slate-400 mt-2">Можно указать путь выше корня сайта (например, <span class="font-mono bg-slate-100 px-1 rounded">../backups_storage/</span>) для абсолютной безопасности.</p>
            </div>
        </div>

        <div class="editor-row border-t border-slate-200/40 pt-4 mt-4">
            <div class="editor-field mb-2">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Шифрование данных</label>
                <label class="matrix-label">
                    <input type="checkbox" id="js-backup-encrypt-toggle" name="encrypt_archive" <?php echo !empty($backupSettings['encrypt_archive']) ? 'checked' : ''; ?> onchange="toggleFmBackupFields()"> Зашифровать архив паролем (AES-256)
                </label>
            </div>

            <div id="js-backup-password-zone" class="editor-field<?php echo empty($backupSettings['encrypt_archive']) ? ' hidden' : ''; ?>">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Пароль для архивов</label>
                <input type="password" id="js-backup-password-input" name="archive_password" placeholder="........" class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" />
            </div>
        </div>
    </div>

    <!-- БЛОК 2: АВТОМАТИЗАЦИЯ -->
    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 mb-6">
        <h3 class="text-lg font-bold text-slate-800 border-b border-slate-200/60 pb-3 mb-4">Автоматическое копирование (Планировщик)</h3>

        <div class="editor-row">
            <div class="editor-field mb-2">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Режим триггеров</label>
                <div class="flex flex-col gap-2">
                    <!-- Добавлены вызовы onchange="toggleFmBackupFields()" на оба чекбокса -->
                    <label class="matrix-label"><input type="checkbox" name="cron_enabled" <?php echo !empty($backupSettings['cron_enabled']) ? 'checked' : ''; ?> onchange="toggleFmBackupFields()"> Включить системный Крон по расписанию</label>
                    <label class="matrix-label"><input type="checkbox" name="cron_virtual" <?php echo !empty($backupSettings['cron_virtual']) ? 'checked' : ''; ?> onchange="toggleFmBackupFields()"> Включить Виртуальный Web Cron при входе админа</label>
                </div>
            </div>

            <!-- ИСПРАВЛЕНО: Добавлен ID и класс hidden через PHP для селекта периодичности вирт. крона -->
            <div id="js-backup-cron-period-zone" class="editor-field <?php echo empty($backupSettings['cron_virtual']) ? 'hidden' : ''; ?>">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Периодичность авто-бэкапа</label>
                <select name="cron_period" class="w-full px-5 py-3 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)] font-semibold text-slate-700 cursor-pointer">
                    <option value="24" <?php echo ($backupSettings['cron_period'] ?? 24) == 24 ? 'selected' : ''; ?>>Каждый день (Каждые 24 часа)</option>
                    <option value="168" <?php echo ($backupSettings['cron_period'] ?? 24) == 168 ? 'selected' : ''; ?>>Раз в неделю (Каждые 7 дней)</option>
                </select>
            </div>
        </div>

        <!-- ИСПРАВЛЕНО: Добавлен ID и класс hidden через PHP для строки ссылки системного крона -->
        <div class="editor-row mt-4">
            <div id="js-backup-cron-link-zone" class="editor-field <?php echo empty($backupSettings['cron_enabled']) ? 'hidden' : ''; ?>">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Ссылка для планировщика хостинга (Cron URL)</label>
                <input type="text" readonly value="https://<?php echo $_SERVER['HTTP_HOST']; ?>/config/cron_backup.php?token=<?php echo $backupSettings['cron_token'] ?? ''; ?>" class="w-full px-5 py-3 bg-slate-100 border border-slate-200 rounded-xl text-xs font-mono text-slate-600 select-all focus:outline-none" />
            </div>
        </div>
    </div>


    <!-- КНОПКА ЗАПУСКА НАСТРОЕК -->
    <div class="flex justify-center pt-2 mt-4">
        <button type="submit" name="save_backup_settings" class="disabled:opacity-40 w-full md:max-w-md bg-slate-800 text-white font-bold h-10 rounded-xl hover:bg-slate-700 transition-all cursor-pointer text-xs shadow-md select-none">
            Сохранить настройки конфигурации
        </button>
    </div>
</form>

<!-- НЕЗАВИСИМЫЙ БЛОК НЕПОСРЕДСТВЕННОГО ЗАПУСКА АРХИВАЦИИ -->
<div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 mt-6 mb-6">
    <!-- Изменено: все элементы выстроены по вертикали и отцентрированы -->
    <div class="flex flex-col items-center text-center gap-4">
        <div>
            <h4 class="text-sm font-bold text-slate-800">Мгновенная точка восстановления</h4>
            <p class="text-slate-400 text-xs mt-1">Запуск создания свежего ZIP-архива на основе текущих сохраненных параметров системы.</p>
        </div>
        
        <!-- КНОПКА 2: НЕПОСРЕДСТВЕННЫЙ СТАРТ СБОРКИ (Отцентрирована, класс w-64) -->
        <button type="button" onclick="startFmArchiveGeneration()" class="disabled:opacity-40 w-full md:max-w-md bg-[var(--primary-color)] text-white font-bold h-10 rounded-xl hover:bg-[var(--primary-dark)] transition-all cursor-pointer text-xs shadow-lg shadow-[var(--primary-color)]/20 flex items-center justify-center gap-2 select-none flex-shrink-0">
            <span class="icon-database-backup text-sm"></span> Запустить резервное копирование
        </button>
    </div>

    <!-- ИНТЕРАКТИВНЫЙ PROGRESS BAR (Изначально скрыт display: none) -->
    <div id="js-backup-progress-zone" class="hidden bg-white border border-slate-200 p-5 rounded-xl mt-6 animate-fade-in">
        <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-2">
            <span class="flex items-center gap-1.5 text-amber-600">
                <span class="icon-refresh-cw inline-block animate-spin text-sm"></span> 
                <span id="js-backup-status-text">Сканирование директорий и подсчет объектов...</span>
            </span>
            <span id="js-backup-percent-text" class="font-mono text-sm font-black text-[var(--primary-color)]">0%</span>
        </div>
        <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden-safe border border-slate-200">
            <div id="js-backup-progress-bar" class="bg-amber-500 h-full transition-all duration-300" style="width: 0%;"></div>
        </div>
    </div>
</div>

<!-- ТАБЛИЦА АРХИВОВ -->
<div class="bg-white p-6 rounded-2xl border border-slate-100 mt-8 shadow-sm">
    <div class="mb-4">
        <h3 class="text-lg font-bold text-slate-800">Архив резервных копий</h3>
        <p class="text-slate-400 text-xs mt-1">Список сохраненных точек восстановления файловой системы контента сайта.</p>
    </div>

    <div class="overflow-hidden-safe">
        <!-- А) ДЕСKТОПНАЯ ВЕРСИЯ ТАБЛИЦЫ -->
        <table class="w-full text-left border-collapse desktop-only">
            <thead>
                <tr class="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    <th class="pb-3 pl-2">Дата создания</th>
                    <th class="pb-3">Имя файла архива</th>
                    <th class="pb-3 text-center">Защита</th>
                    <th class="pb-3">Размер</th>
                    <th class="pb-3 text-right pr-2">Действие</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm text-slate-700">
                <?php if (empty($realBackups)): ?>
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400 italic">Резервные копии еще не создавались. Архив пуст.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($realBackups as $backup): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 pl-2 font-mono font-medium text-slate-800"><?php echo e($backup['date']); ?></td>
                            <td class="py-3 font-mono text-xs text-slate-600 truncate max-w-xs" title="<?php echo e($backup['file']); ?>"><?php echo e($backup['file']); ?></td>
                            <td class="py-3 text-center">
                                <?php if ($backup['encrypted']): ?>
                                    <span class="icon-lock text-amber-500 text-sm" title="Зашифрован"></span>
                                <?php else: ?>
                                    <span class="icon-unlock text-slate-300 text-sm" title="Открытый"></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 font-mono text-xs text-slate-500 font-bold"><?php echo e($backup['size']); ?></td>
                            <td class="py-3 text-right pr-2">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" onclick="downloadFmBackup('<?php echo e($backup['file']); ?>')" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:text-slate-800 bg-white hover:bg-slate-50 transition-all cursor-pointer"><span class="icon-download text-sm"></span></button>
                                    <button type="button" onclick="restoreFmBackup('<?php echo e($backup['file']); ?>', <?php echo $backup['encrypted'] ? 'true' : 'false'; ?>)" class="w-8 h-8 rounded-lg flex items-center justify-center border border-slate-200 text-emerald-400 hover:text-slate-800 bg-white hover:bg-slate-50 transition-all cursor-pointer"><span class="icon-rotate-ccw text-sm"></span></button>
                                    <button type="button" onclick="deleteFmBackup('<?php echo e($backup['file']); ?>')" class="w-8 h-8 rounded-lg flex items-center justify-center border border-rose-100 text-rose-500 hover:text-rose-800 bg-white hover:bg-slate-50 transition-all cursor-pointer"><span class="icon-trash-2 text-sm"></span></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Б) МОБИЛЬНАЯ ВЕРСИЯ КАРТОЧЕК (mobile-only) -->
        <div class="mobile-only mt-2">
            <?php if (empty($realBackups)): ?>
                <div class="py-4 text-center text-slate-400 italic text-sm">Резервные копии отсутствуют.</div>
            <?php else: ?>
                <?php foreach ($realBackups as $backup): ?>
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl mb-4">
                        <!-- Шапка карточки: Имя файла и пиктограмма защиты как на десктопе -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 gap-2">
                            <span class="font-mono font-bold text-slate-800 text-xs truncate" title="<?php echo e($backup['file']); ?>"><?php echo e($backup['file']); ?></span>
                            <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 bg-white border border-slate-200 rounded-md shadow-sm">
                                <?php if ($backup['encrypted']): ?>
                                    <span class="icon-lock text-amber-500 text-xs" title="Зашифрован"></span>
                                <?php else: ?>
                                    <span class="icon-unlock text-slate-300 text-xs" title="Открытый"></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Данные архива -->
                        <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 mt-2 mb-3">
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1">Дата создания</span>
                                <span class="font-medium text-slate-700"><?php echo e($backup['date']); ?></span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1">Размер архива</span>
                                <span class="font-mono font-bold text-slate-700"><?php echo e($backup['size']); ?></span>
                            </div>
                        </div>

                        <!-- Нижний ряд кнопок управления на родных классах -->
                        <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100">
                            <button type="button" onclick="downloadFmBackup('<?php echo e($backup['file']); ?>')" class="flex-1 h-10 bg-white border border-slate-200 text-slate-600 rounded-lg text-xs font-bold flex items-center justify-center gap-1 transition-all cursor-pointer">
                                <span class="icon-download text-xs"></span> Скачать
                            </button>
                            <button type="button" onclick="restoreFmBackup('<?php echo e($backup['file']); ?>', <?php echo $backup['encrypted'] ? 'true' : 'false'; ?>)" class="flex-1 h-10 bg-white border border-slate-200 text-emerald-400 rounded-lg text-xs font-bold flex items-center justify-center gap-1 transition-all cursor-pointer">
                                <span class="icon-rotate-ccw text-xs"></span> Восстановить
                            </button>
                            <button type="button" onclick="deleteFmBackup('<?php echo e($backup['file']); ?>')" class="w-10 h-10 flex-shrink-0 bg-white border border-rose-100 text-rose-500 rounded-lg flex items-center justify-center transition-all cursor-pointer" title="Удалить архив">
                                <span class="icon-trash-2 text-sm"></span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php 
// Подсчёт общего размера
$totalSize = 0;
foreach ($realBackups as $backup) {
    // Размер хранится в формате "XXX KB", парсим
    $sizeStr = str_replace(' KB', '', $backup['size']);
    $totalSize += floatval($sizeStr);
}

$totalSizeFormatted = $totalSize > 1024 ? round($totalSize / 1024, 1) . ' MB' : round($totalSize, 1) . ' KB';
$totalCount = count($realBackups);
?>

<!-- Строка статистики -->
<div class="flex flex-wrap items-center justify-between gap-4 mt-4 pt-4 border-t border-slate-200 text-xs text-slate-500">
    <div>
        <span class="font-semibold text-slate-700"><?php echo $totalCount; ?></span> архивов
    </div>
    <div>
        Общий размер: <span class="font-bold text-slate-700"><?php echo $totalSizeFormatted; ?></span>
    </div>
    <div>
        <span class="inline-flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span class="text-slate-600">Свободно:</span>
            <span class="font-bold text-slate-700">
                <?php 
                $freeSpace = disk_free_space($realStorageDir);
                if ($freeSpace > 1024 * 1024 * 1024) {
                    echo round($freeSpace / 1024 / 1024 / 1024, 1) . ' GB';
                } else {
                    echo round($freeSpace / 1024 / 1024, 1) . ' MB';
                }
                ?>
            </span>
        </span>
    </div>
</div>

<script src="js/backup_manager.js?v=<?php echo filemtime(APP_ROOT.'/config/js/backup_manager.js'); ?>" type="text/javascript"></script>
