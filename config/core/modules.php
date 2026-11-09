<?php
/**
 * Установка, обновление и удаление модулей.
 *
 * Файл подключается из functions.php и доступен и на фронте, и в админке.
 * Это нужно, чтобы render_module() мог распаковать модуль из архива без
 * загрузки admin_controller.php.
 *
 * Источник истины для доступных модулей — config/data/modules.json,
 * архивы — config/modules/archives/{id}.zip.
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

/**
 * Сохраняет список модулей в config/data/modules.json
 *
 * @param array $data Массив с данными модулей
 * @return bool true при успешной записи, false при ошибке
 */
function saveModulesData(array $data): bool {
    $path = APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'modules.json';
    $jsonString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return safeFileWrite($path, $jsonString);
}

/**
 * Рекурсивно удаляет папку.
 *
 * @param string $dir Путь к папке
 * @return void
 */
function _moduleRemoveDir(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }

    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            _moduleRemoveDir($path);
        } else {
            @unlink($path);
        }
    }

    @rmdir($dir);
}

/**
 * Рекурсивно копирует папку.
 *
 * @param string $source Исходная папка
 * @param string $target Целевая папка
 * @return void
 */
function _moduleCopyDir(string $source, string $target): void {
    if (!is_dir($source)) {
        return;
    }

    if (!is_dir($target)) {
        mkdir($target, 0755, true);
    }

    $items = array_diff(scandir($source), ['.', '..']);
    foreach ($items as $item) {
        $src = $source . DIRECTORY_SEPARATOR . $item;
        $dst = $target . DIRECTORY_SEPARATOR . $item;
        if (is_dir($src)) {
            _moduleCopyDir($src, $dst);
        } else {
            copy($src, $dst);
        }
    }
}

/**
 * Собирает запись реестра модулей из manifest.json.
 *
 * @param array  $manifest Данные манифеста
 * @param string $moduleId Идентификатор модуля
 * @return array
 */
function _moduleRegistryEntry(array $manifest, string $moduleId): array {
    return [
        'name' => $manifest['name'] ?? $moduleId,
        'icon' => $manifest['icon'] ?? 'icon-box',
        'description' => $manifest['description'] ?? '',
        'has_settings' => $manifest['has_settings'] ?? false,
        'auto_unpack' => $manifest['auto_unpack'] ?? false,
        'version' => $manifest['version'] ?? '1.0.0',
        'author' => $manifest['author'] ?? ''
    ];
}

/**
 * Распаковывает ZIP-архив модуля в общие папки.
 *
 * @param string $zipPath  Путь к ZIP-архиву
 * @param string $moduleId Идентификатор модуля
 * @return array ['success' => bool, 'message' => string]
 */
function unpackModuleArchive(string $zipPath, string $moduleId): array {
    if (!isValidId($moduleId)) {
        return ['success' => false, 'message' => 'Невалидный ID модуля'];
    }

    if (!file_exists($zipPath)) {
        return ['success' => false, 'message' => 'Архив модуля не найден'];
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['success' => false, 'message' => 'Не удалось открыть архив'];
    }

    // === ЗАЩИТА ОТ ZIP SLIP ===
    $zipSlipEntry = detectZipSlip($zip);
    if ($zipSlipEntry !== null) {
        $zip->close();
        if (function_exists('logAction')) {
            logAction('module_unpack', 'ZIP Slip заблокирован: ' . $zipSlipEntry, 'ERROR');
        }
        return ['success' => false, 'message' => 'Архив содержит небезопасные имена файлов. Установка отменена.'];
    }
    // === КОНЕЦ ЗАЩИТЫ ===

    // Создаём временную папку для распаковки
    $tempDir = APP_ROOT . '/config/modules/_temp/' . $moduleId;
    _moduleRemoveDir($tempDir);
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    // Распаковываем архив во временную папку
    $zip->extractTo($tempDir);
    $zip->close();

    // Копируем файлы из admin/ в config/modules/{id}/
    $adminSource = $tempDir . '/admin';
    if (is_dir($adminSource)) {
        _moduleCopyDir($adminSource, APP_ROOT . '/config/modules/' . $moduleId);
    }

    // Копируем файлы из site/ в общие папки
    $siteSource = $tempDir . '/site';
    if (is_dir($siteSource)) {
        // site/module.php → modules/{id}.php
        if (file_exists($siteSource . '/module.php')) {
            copy($siteSource . '/module.php', APP_ROOT . '/modules/' . $moduleId . '.php');
        }
        // site/style.css → css/{id}.css
        if (file_exists($siteSource . '/style.css')) {
            copy($siteSource . '/style.css', APP_ROOT . '/css/' . $moduleId . '.css');
        }
        // site/script.js → js/{id}.js
        if (file_exists($siteSource . '/script.js')) {
            copy($siteSource . '/script.js', APP_ROOT . '/js/' . $moduleId . '.js');
        }
    }

    // Удаляем временную папку
    _moduleRemoveDir($tempDir);

    // ===== СОЗДАЁМ .min ФАЙЛЫ, ЕСЛИ МИНИФИКАЦИЯ ВКЛЮЧЕНА =====
    if (function_exists('getSettingsData') && function_exists('createMinFilesForModule')) {
        $settings = getSettingsData();
        if (!empty($settings['assets_minify'])) {
            $exceptions = $settings['minify_exceptions'] ?? [];
            // Проверяем, не в исключениях ли модуль
            if (empty($exceptions[$moduleId]['css']) || empty($exceptions[$moduleId]['js'])) {
                createMinFilesForModule($moduleId);
            }
        }
    }

    if (function_exists('clearPageCache')) {
        clearPageCache();
    }

    return ['success' => true, 'message' => 'Модуль успешно распакован'];
}

/**
 * Распаковывает модуль из архива в общие папки.
 *
 * @param string $moduleId Идентификатор модуля
 * @return array ['success' => bool, 'message' => string]
 */
function unpackModule(string $moduleId): array {
    if (!isValidId($moduleId)) {
        return ['success' => false, 'message' => 'Невалидный ID модуля'];
    }

    $archivePath = getModuleArchivePath($moduleId);

    if (!file_exists($archivePath)) {
        return ['success' => false, 'message' => 'Архив модуля не найден'];
    }

    // Проверяем, распакован ли уже
    if (isModuleInstalled($moduleId)) {
        return ['success' => true, 'message' => 'Модуль уже распакован'];
    }

    return unpackModuleArchive($archivePath, $moduleId);
}

/**
 * Удаляет распакованные файлы модуля (архив и реестр остаются).
 *
 * @param string $moduleId Идентификатор модуля
 * @return array ['success' => bool, 'message' => string, 'deleted' => int]
 */
function removeModuleFiles(string $moduleId): array {
    $deleted = 0;

    if (!isValidId($moduleId)) {
        return ['success' => false, 'message' => 'Невалидный ID модуля', 'deleted' => $deleted];
    }

    // 1. Удаляем папку модуля в админке
    $adminDir = APP_ROOT . '/config/modules/' . $moduleId;
    if (is_dir($adminDir)) {
        $files = array_diff(scandir($adminDir), ['.', '..']);
        foreach ($files as $file) {
            if (unlink($adminDir . '/' . $file)) {
                $deleted++;
            }
        }
        if (rmdir($adminDir)) {
            $deleted++;
        }
    }

    // 2. Удаляем файлы на сайте
    $siteFiles = [
        APP_ROOT . '/modules/' . $moduleId . '.php',
        APP_ROOT . '/css/' . $moduleId . '.css',
        APP_ROOT . '/css/' . $moduleId . '.min.css',
        APP_ROOT . '/js/' . $moduleId . '.js',
        APP_ROOT . '/js/' . $moduleId . '.min.js'
    ];

    foreach ($siteFiles as $file) {
        if (file_exists($file) && unlink($file)) {
            $deleted++;
        }
    }

    if (function_exists('clearPageCache')) {
        clearPageCache();
    }

    return [
        'success' => true,
        'message' => 'Удалено файлов: ' . $deleted,
        'deleted' => $deleted
    ];
}

/**
 * Удаляет модуль полностью: код, архив, превью и запись в modules.json.
 *
 * @param string $moduleId Идентификатор модуля
 * @return array ['success' => bool, 'message' => string, 'deleted' => int]
 */
function purgeModule(string $moduleId): array {
    if (!isValidId($moduleId)) {
        return ['success' => false, 'message' => 'Невалидный ID модуля', 'deleted' => 0];
    }

    $result = removeModuleFiles($moduleId);
    $deleted = $result['deleted'];

    // Архив
    $archivePath = getModuleArchivePath($moduleId);
    if (file_exists($archivePath) && @unlink($archivePath)) {
        $deleted++;
    }

    // Превью
    $previewPath = getModulePreviewPath($moduleId);
    if (file_exists($previewPath) && @unlink($previewPath)) {
        $deleted++;
    }

    // Запись в реестре
    $modules = getModulesData();
    if (isset($modules[$moduleId])) {
        unset($modules[$moduleId]);
        saveModulesData($modules);
    }

    if (function_exists('clearPageCache')) {
        clearPageCache();
    }

    return [
        'success' => true,
        'message' => 'Модуль удалён полностью. Удалено файлов: ' . $deleted,
        'deleted' => $deleted
    ];
}

/**
 * Обновляет модуль (удаляет старые файлы и распаковывает заново).
 *
 * @param string $moduleId Идентификатор модуля
 * @return array ['success' => bool, 'message' => string]
 */
function reinstallModule(string $moduleId): array {
    // Удаляем старые файлы
    removeModuleFiles($moduleId);

    // Распаковываем заново
    return unpackModule($moduleId);
}

/**
 * Скачивает ZIP-архив модуля по URL во временный файл.
 *
 * @param string $url URL архива
 * @param string $id  ID модуля (для имени файла)
 * @return string|null Путь к временному файлу или null
 */
function downloadModuleZip(string $url, string $id): ?string {
    if (!isValidId($id)) {
        return null;
    }

    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return null;
    }

    $maxSize = 10 * 1024 * 1024; // 10 МБ

    $tempFile = DATA_DIR . 'module_' . $id . '.zip';
    $success = false;

    if (function_exists('curl_init')) {
        $fp = fopen($tempFile, 'w');
        if ($fp === false) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_MAXFILESIZE, $maxSize);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AlPin-CMS-Updater/1.0');
        $success = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    } elseif (ini_get('allow_url_fopen') == '1') {
        $success = @copy($url, $tempFile);
    }

    if (!$success || !file_exists($tempFile) || filesize($tempFile) === 0 || filesize($tempFile) > $maxSize) {
        @unlink($tempFile);
        return null;
    }

    return $tempFile;
}

/**
 * Проверяет ZIP-архив модуля перед установкой.
 *
 * @param string $zipPath  Путь к ZIP-архиву
 * @param string $moduleId Ожидаемый идентификатор модуля
 * @return array ['success' => bool, 'message' => string, 'manifest' => array]
 */
function validateModuleArchive(string $zipPath, string $moduleId): array {
    if (!isValidId($moduleId)) {
        return ['success' => false, 'message' => 'Невалидный ID модуля.', 'manifest' => []];
    }

    if (!file_exists($zipPath)) {
        return ['success' => false, 'message' => 'Архив модуля не найден.', 'manifest' => []];
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['success' => false, 'message' => 'Не удалось открыть ZIP-архив.', 'manifest' => []];
    }

    // ZIP Slip
    $zipSlip = detectZipSlip($zip);
    if ($zipSlip !== null) {
        $zip->close();
        return ['success' => false, 'message' => 'Архив содержит небезопасные имена файлов (' . e($zipSlip) . ').', 'manifest' => []];
    }

    // Модуль не должен быть плагином
    if ($zip->locateName('plugin.json') !== false) {
        $zip->close();
        return ['success' => false, 'message' => 'Архив является плагином, а не модулем.', 'manifest' => []];
    }

    // Обязательные файлы
    if ($zip->locateName('manifest.json') === false) {
        $zip->close();
        return ['success' => false, 'message' => 'В архиве отсутствует manifest.json.', 'manifest' => []];
    }

    if ($zip->locateName('site/module.php') === false) {
        $zip->close();
        return ['success' => false, 'message' => 'В архиве отсутствует site/module.php.', 'manifest' => []];
    }

    $manifestJson = $zip->getFromName('manifest.json');
    $zip->close();

    $manifest = json_decode((string)$manifestJson, true);
    if (!is_array($manifest)) {
        return ['success' => false, 'message' => 'manifest.json имеет неверный формат.', 'manifest' => []];
    }

    $archiveId = $manifest['id'] ?? '';
    if (!isValidId((string)$archiveId) || $archiveId !== $moduleId) {
        return ['success' => false, 'message' => 'ID модуля в manifest.json не совпадает с ожидаемым.', 'manifest' => []];
    }

    // Запрет даунгрейда
    $modules = getModulesData();
    $installedVersion = $modules[$moduleId]['version'] ?? '';
    $newVersion = (string)($manifest['version'] ?? '1.0.0');
    if ($installedVersion !== '' && version_compare($newVersion, (string)$installedVersion, '<')) {
        return [
            'success' => false,
            'message' => 'Версия в архиве (' . $newVersion . ') ниже установленной (' . $installedVersion . ').',
            'manifest' => $manifest
        ];
    }

    return ['success' => true, 'message' => '', 'manifest' => $manifest];
}

/**
 * Устанавливает или обновляет модуль из ZIP-архива.
 *
 * @param string $zipPath Путь к ZIP-архиву
 * @param string $id      Идентификатор модуля
 * @param bool   $replace true для обновления уже установленного модуля
 * @return array ['success' => bool, 'message' => string, 'version' => string]
 */
function installModuleFromZip(string $zipPath, string $id, bool $replace = false): array {
    if (!isValidId($id)) {
        return ['success' => false, 'message' => 'Невалидный ID модуля.', 'version' => ''];
    }

    $validation = validateModuleArchive($zipPath, $id);
    if (!$validation['success']) {
        return ['success' => false, 'message' => $validation['message'], 'version' => ''];
    }

    if (isModuleInstalled($id) && !$replace) {
        return ['success' => false, 'message' => 'Модуль уже установлен. Для обновления используйте обновление.', 'version' => ''];
    }

    // При обновлении сначала убираем старый код, иначе останутся файлы,
    // которых нет в новой версии (например, удалённый admin/script.js).
    if (isModuleInstalled($id)) {
        removeModuleFiles($id);
    }

    $unpackResult = unpackModuleArchive($zipPath, $id);
    if (!$unpackResult['success']) {
        return ['success' => false, 'message' => $unpackResult['message'], 'version' => ''];
    }

    // Сохраняем архив в библиотеку
    $archiveDir = dirname(getModuleArchivePath($id));
    if (!is_dir($archiveDir)) {
        mkdir($archiveDir, 0755, true);
    }
    if (!copy($zipPath, getModuleArchivePath($id))) {
        return ['success' => false, 'message' => 'Модуль распакован, но архив не сохранён.', 'version' => ''];
    }

    // Сохраняем превью из архива
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === true) {
        $previewData = $zip->getFromName('preview.png');
        $zip->close();
        if ($previewData !== false && $previewData !== '') {
            $previewDir = dirname(getModulePreviewPath($id));
            if (!is_dir($previewDir)) {
                mkdir($previewDir, 0755, true);
            }
            file_put_contents(getModulePreviewPath($id), $previewData);
        }
    }

    // Обновляем реестр
    $manifest = $validation['manifest'];
    $modules = getModulesData();
    $modules[$id] = _moduleRegistryEntry($manifest, $id);
    saveModulesData($modules);

    $version = (string)($manifest['version'] ?? '1.0.0');
    if (function_exists('logAction')) {
        logAction('module_install', 'Модуль "' . $id . '" установлен/обновлён до версии ' . $version, 'INFO');
    }

    return [
        'success' => true,
        'message' => 'Модуль "' . ($manifest['name'] ?? $id) . '" установлен.',
        'version' => $version
    ];
}

/**
 * Синхронизирует config/data/modules.json с архивами в config/modules/archives/.
 *
 * Добавляет новые архивы (по manifest.json), обновляет версии и удаляет
 * записи, для которых больше нет архива. Порядок существующих записей
 * сохраняется.
 *
 * @return array Актуальный список модулей
 */
function syncModulesData(): array {
    $existing = getModulesData();

    // Собираем актуальные записи из архивов
    $entries = [];
    $archives = glob(APP_ROOT . '/config/modules/archives/*.zip') ?: [];

    foreach ($archives as $archivePath) {
        $id = basename($archivePath, '.zip');
        if (!isValidId($id)) {
            continue;
        }

        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            continue;
        }
        $manifestJson = $zip->getFromName('manifest.json');
        $zip->close();

        $manifest = json_decode((string)$manifestJson, true);
        if (!is_array($manifest)) {
            continue;
        }

        $entries[$id] = _moduleRegistryEntry($manifest, $id);
    }

    $result = [];
    $changed = false;

    // Сначала сохраняем существующий порядок и обновляем записи на месте
    foreach ($existing as $id => $data) {
        if (!isset($entries[$id])) {
            // Архива больше нет — запись удаляется
            $changed = true;
            continue;
        }

        $merged = $data;
        foreach ($entries[$id] as $key => $value) {
            if (!array_key_exists($key, $merged) || $merged[$key] !== $value) {
                $merged[$key] = $value;
                $changed = true;
            }
        }
        $result[$id] = $merged;
        unset($entries[$id]);
    }

    // Новые архивы добавляем в конец
    foreach ($entries as $id => $entry) {
        $result[$id] = $entry;
        $changed = true;
    }

    if ($changed) {
        saveModulesData($result);
    }

    return $result;
}
