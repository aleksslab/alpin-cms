<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. РУБЕЖ ЗАЩИТЫ: Проверяем константу ядра и валидность сессии админа
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Доступ запрещен. Требуется авторизация.']);
    exit;
}

// 2. ФИКСИРУЕМ КОРЕНЬ
define('FM_SITE_ROOT', APP_ROOT);

/**
 * Безопасно нормализует и проверяет относительный путь.
 * Возвращает нормализованный путь или null при небезопасном значении.
 *
 * @param string $path Путь из запроса
 * @return string|null
 */
function fmSafePath(string $path): ?string {
    $path = str_replace('\\', '/', $path);
    $path = trim($path, '/');

    if ($path === '') {
        return '';
    }

    // Whitelist символов
    if (!preg_match('#^[a-zA-Z0-9\-_./]+$#', $path)) {
        return null;
    }

    // Запрет на .., //, начало с /
    if (strpos($path, '..') !== false || strpos($path, '//') !== false) {
        return null;
    }

    return $path;
}

/**
 * Безопасно собирает абсолютный путь внутри FM_SITE_ROOT.
 * Возвращает абсолютный путь или null при выходе за пределы.
 *
 * @param string $relativePath Нормализованный относительный путь
 * @return string|null
 */
function fmSafeAbsolute(string $relativePath): ?string {
    $realRoot = realpath(FM_SITE_ROOT);
    if ($realRoot === false) {
        return null;
    }

    $full = FM_SITE_ROOT;
    if ($relativePath !== '') {
        $full .= DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    // Проверяем, что итоговый путь внутри FM_SITE_ROOT
    $check = file_exists($full) ? realpath($full) : realpath(dirname($full));

    if ($check === false) {
        return null;
    }

    // На Windows регистр не важен — нормализуем
    if (DIRECTORY_SEPARATOR === '\\') {
        if (stripos($check, $realRoot) !== 0) {
            return null;
        }
    } else {
        if (strpos($check, $realRoot) !== 0) {
            return null;
        }
    }

    return $full;
}

/**
 * Проверяет, попадает ли путь в blacklist (config/data, config/backups, config/core).
 *
 * @param string $relativePath Нормализованный относительный путь
 * @return bool true — путь запрещён
 */
function fmIsBlacklisted(string $relativePath): bool {
    $blacklist = ['config/data', 'config/backups', 'config/core', 'data/logs'];
    $normalized = str_replace('\\', '/', $relativePath);

    foreach ($blacklist as $blocked) {
        if ($normalized === $blocked || strpos($normalized, $blocked . '/') === 0) {
            return true;
        }
    }

    return false;
}

/**
 * Проверяет расширение файла по whitelist (для get_code/save_code).
 *
 * @param string $fileName Имя файла
 * @return bool true — расширение разрешено
 */
function fmIsAllowedExtension(string $fileName): bool {
    $allowed = ['php', 'css', 'js', 'json', 'html', 'htm', 'txt', 'md', 'xml', 'svg', 'htaccess'];

    if ($fileName === '.htaccess') {
        return true;
    }

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    return in_array($ext, $allowed, true);
}

/**
 * Проверяет, является ли файл чувствительным (по имени).
 *
 * @param string $fileName Имя файла
 * @return bool true — файл запрещён
 */
function fmIsSensitiveFile(string $fileName): bool {
    $sensitive = ['.pepper', 'credentials.json', 'login_attempts.json', 'backup_config.json'];
    return in_array($fileName, $sensitive, true);
}

// Получаем текущий подкаталог из запроса
$requestedDir = fmSafePath($_REQUEST['dir'] ?? '');

if ($requestedDir === null) {
    logAction('file_list', 'Path traversal: ' . ($_REQUEST['dir'] ?? ''), 'ERROR');
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Недопустимый путь']);
    exit;
}

$currentFullPath = fmSafeAbsolute($requestedDir);
if ($currentFullPath === null) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Недопустимый путь']);
    exit;
}

// Страховка: если папка не существует, сбрасываем в корень сайта
if (!file_exists($currentFullPath) || !is_dir($currentFullPath)) {
    $currentFullPath = FM_SITE_ROOT;
    $requestedDir = '';
}

$action = $_GET['action'] ?? 'list';
header('Content-Type: application/json; charset=utf-8');

// =========================================================================
// 4. СКАНИРОВАНИЕ
// =========================================================================
if ($action === 'list') {
    $foldersList = [];
    $filesList = [];

    $items = scandir($currentFullPath);
    $jsonParentPath = '/' . (!empty($requestedDir) ? $requestedDir . '/' : '');

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $itemFullPath = $currentFullPath . DIRECTORY_SEPARATOR . $item;

        if (is_dir($itemFullPath)) {
            $modifiedStr = date('d.m.Y H:i', filemtime($itemFullPath));
            $permsStr = substr(sprintf('%o', fileperms($itemFullPath)), -4);

            $foldersList[] = [
                'name'     => $item,
                'path'     => $jsonParentPath,
                'size'     => '—',
                'modified' => $modifiedStr,
                'perms'    => $permsStr
            ];
        } else if (is_file($itemFullPath)) {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            $bytes = filesize($itemFullPath);

            if ($bytes >= 1073741824) {
                $sizeStr = round($bytes / 1073741824, 2) . ' GB';
            } elseif ($bytes >= 1048576) {
                $sizeStr = round($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                $sizeStr = round($bytes / 1024, 2) . ' KB';
            } else {
                $sizeStr = $bytes . ' B';
            }

            $modifiedStr = date('d.m.Y H:i', filemtime($itemFullPath));
            $permsStr = substr(sprintf('%o', fileperms($itemFullPath)), -4);

            $filesList[] = [
                'name' => $item,
                'path' => $jsonParentPath,
                'ext' => $ext,
                'size' => $sizeStr,
                'modified' => $modifiedStr,
                'perms' => $permsStr
            ];
        }
    }

    echo json_encode([
        'currentDir' => $requestedDir,
        'folders' => $foldersList,
        'files' => $filesList
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// =========================================================================
// 5. УДАЛЕНИЕ
// =========================================================================
if ($action === 'delete') {
    $itemName = $_POST['name'] ?? '';
    $itemPathRaw = $_POST['path'] ?? '';

    // Валидация itemName — только имя файла, без слэшей
    if (!preg_match('#^[a-zA-Z0-9\-_.]+$#', $itemName)) {
        logAction('file_delete', 'Невалидное имя: ' . $itemName, 'ERROR');
        echo json_encode(['error' => 'Недопустимое имя файла']);
        exit;
    }

    $itemPath = fmSafePath($itemPathRaw);
    if ($itemPath === null) {
        logAction('file_delete', 'Path traversal: ' . $itemPathRaw, 'ERROR');
        echo json_encode(['error' => 'Недопустимый путь']);
        exit;
    }

    // Blacklist
    $fullRelative = ($itemPath !== '' ? $itemPath . '/' : '') . $itemName;
    if (fmIsBlacklisted($fullRelative)) {
        logAction('file_delete', 'Попытка доступа к защищённой папке: ' . $fullRelative, 'ERROR');
        echo json_encode(['error' => 'Доступ к этой папке запрещён']);
        exit;
    }

    $basePath = fmSafeAbsolute($itemPath);
    if ($basePath === null) {
        echo json_encode(['error' => 'Недопустимый путь']);
        exit;
    }

    $fullPath = $basePath . DIRECTORY_SEPARATOR . $itemName;

    if (!file_exists($fullPath)) {
        echo json_encode(['error' => 'Файл или папка не найдены на сервере']);
        exit;
    }

    if (is_dir($fullPath)) {
        $dirEntries = scandir($fullPath);
        if (count($dirEntries) > 2) {
            echo json_encode(['error' => 'Нельзя удалить папку, пока внутри неё есть файлы или другие подкаталоги!']);
            exit;
        }

        if (@rmdir($fullPath)) {
            echo json_encode(['success' => 'Папка успешно удалена']);
        } else {
            echo json_encode(['error' => 'Не удалось удалить папку. Проверьте права доступа на сервере.']);
        }
        exit;
    }

    if (is_file($fullPath)) {
        if (@unlink($fullPath)) {
            logAction('file_delete', 'Файл "' . $itemName . '" удалён (путь: ' . $itemPath . ')', 'INFO');
            echo json_encode(['success' => 'Файл успешно удален']);
        } else {
            logAction('file_delete', 'Ошибка удаления файла "' . $itemName . '"', 'ERROR');
            echo json_encode(['error' => 'Не удалось удалить файл.']);
        }
        exit;
    }
}

// =========================================================================
// 6. ПЕРЕИМЕНОВАНИЕ
// =========================================================================
if ($action === 'rename') {
    $oldName = $_POST['old_name'] ?? '';
    $newName = $_POST['new_name'] ?? '';
    $itemPathRaw = $_POST['path'] ?? '';

    if (!preg_match('#^[a-zA-Z0-9\-_.]+$#', $oldName) || !preg_match('#^[a-zA-Z0-9\-_.]+$#', $newName)) {
        echo json_encode(['error' => 'Недопустимое имя файла']);
        exit;
    }

    $itemPath = fmSafePath($itemPathRaw);
    if ($itemPath === null) {
        logAction('file_rename', 'Path traversal: ' . $itemPathRaw, 'ERROR');
        echo json_encode(['error' => 'Недопустимый путь']);
        exit;
    }

    $fullRelative = ($itemPath !== '' ? $itemPath . '/' : '') . $oldName;
    if (fmIsBlacklisted($fullRelative)) {
        logAction('file_rename', 'Попытка доступа к защищённой папке: ' . $fullRelative, 'ERROR');
        echo json_encode(['error' => 'Доступ к этой папке запрещён']);
        exit;
    }

    $basePath = fmSafeAbsolute($itemPath);
    if ($basePath === null) {
        echo json_encode(['error' => 'Недопустимый путь']);
        exit;
    }

    $sourcePath = $basePath . DIRECTORY_SEPARATOR . $oldName;
    $targetPath = $basePath . DIRECTORY_SEPARATOR . $newName;

    if (!file_exists($sourcePath)) {
        echo json_encode(['error' => 'Исходный элемент не найден на сервере']);
        exit;
    }

    if (file_exists($targetPath)) {
        echo json_encode(['error' => 'Элемент с таким именем уже существует в этой папке!']);
        exit;
    }

    if (@rename($sourcePath, $targetPath)) {
        logAction('file_rename', 'Файл "' . $oldName . '" переименован в "' . $newName . '"', 'INFO');
        echo json_encode(['success' => 'Элемент успешно переименован']);
    } else {
        logAction('file_rename', 'Ошибка переименования "' . $oldName . '"', 'ERROR');
        echo json_encode(['error' => 'Не удалось переименовать элемент.']);
    }
    exit;
}

// =========================================================================
// 7. ЧТЕНИЕ КОДА
// =========================================================================
if ($action === 'get_code') {
    // CSRF-проверка
    $clientToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken) || !hash_equals($sessionToken, $clientToken)) {
        logAction('file_access', 'get_code без CSRF', 'ERROR');
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'CSRF-токен невалиден']);
        exit;
    }

    $fileName = $_POST['name'] ?? '';
    $filePathRaw = $_POST['path'] ?? '';

    // Валидация имени файла
    if (!preg_match('#^[a-zA-Z0-9\-_.]+$#', $fileName) && $fileName !== '.htaccess') {
        echo json_encode(['error' => 'Недопустимое имя файла']);
        exit;
    }

    // Whitelist расширений
    if (!fmIsAllowedExtension($fileName)) {
        logAction('file_access', 'Запрещённое расширение: ' . $fileName, 'ERROR');
        echo json_encode(['error' => 'Редактирование файлов этого типа запрещено']);
        exit;
    }

    // Blacklist имён
    if (fmIsSensitiveFile($fileName)) {
        logAction('file_access', 'Попытка доступа к чувствительному файлу: ' . $fileName, 'ERROR');
        echo json_encode(['error' => 'Доступ к этому файлу запрещён']);
        exit;
    }

    $itemPath = fmSafePath($filePathRaw);
    if ($itemPath === null) {
        logAction('file_access', 'Path traversal: ' . $filePathRaw, 'ERROR');
        echo json_encode(['error' => 'Недопустимый путь']);
        exit;
    }

    $fullRelative = ($itemPath !== '' ? $itemPath . '/' : '') . $fileName;
    if (fmIsBlacklisted($fullRelative)) {
        logAction('file_access', 'Защищённая папка: ' . $fullRelative, 'ERROR');
        echo json_encode(['error' => 'Доступ к этой папке запрещён']);
        exit;
    }

    $basePath = fmSafeAbsolute($itemPath);
    if ($basePath === null) {
        echo json_encode(['error' => 'Недопустимый путь']);
        exit;
    }

    $fullPath = $basePath . DIRECTORY_SEPARATOR . $fileName;

    if (file_exists($fullPath) && is_file($fullPath)) {
        $codeContent = file_get_contents($fullPath);
        echo json_encode([
            'success' => true,
            'code' => $codeContent
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['error' => 'Файл не найден на сервере']);
    }
    exit;
}

// =========================================================================
// 8. СОХРАНЕНИЕ КОДА
// =========================================================================
if ($action === 'save_code') {
    $fileName = $_POST['name'] ?? '';
    $filePathRaw = $_POST['path'] ?? '';
    $codeData = $_POST['code'] ?? '';

    // Валидация имени
    if (!preg_match('#^[a-zA-Z0-9\-_.]+$#', $fileName) && $fileName !== '.htaccess') {
        echo json_encode(['error' => 'Недопустимое имя файла']);
        exit;
    }

    // Whitelist расширений
    if (!fmIsAllowedExtension($fileName)) {
        logAction('file_edit', 'Запрещённое расширение: ' . $fileName, 'ERROR');
        echo json_encode(['error' => 'Сохранение файлов этого типа запрещено']);
        exit;
    }

    // Blacklist имён
    if (fmIsSensitiveFile($fileName)) {
        logAction('file_edit', 'Попытка записи в чувствительный файл: ' . $fileName, 'ERROR');
        echo json_encode(['error' => 'Запись в этот файл запрещена']);
        exit;
    }

    $itemPath = fmSafePath($filePathRaw);
    if ($itemPath === null) {
        logAction('file_edit', 'Path traversal: ' . $filePathRaw, 'ERROR');
        echo json_encode(['error' => 'Недопустимый путь']);
        exit;
    }

    $fullRelative = ($itemPath !== '' ? $itemPath . '/' : '') . $fileName;
    if (fmIsBlacklisted($fullRelative)) {
        logAction('file_edit', 'Защищённая папка: ' . $fullRelative, 'ERROR');
        echo json_encode(['error' => 'Доступ к этой папке запрещён']);
        exit;
    }

    $basePath = fmSafeAbsolute($itemPath);
    if ($basePath === null) {
        echo json_encode(['error' => 'Недопустимый путь']);
        exit;
    }

    $fullPath = $basePath . DIRECTORY_SEPARATOR . $fileName;

    // Если файла нет — создаём
    $isNew = !file_exists($fullPath);

    if (file_put_contents($fullPath, $codeData) !== false) {
        if ($isNew) {
            logAction('file_edit', 'Создан новый файл "' . $fileName . '" (путь: ' . $itemPath . ')', 'INFO');
            echo json_encode(['success' => 'Файл успешно создан на сервере!']);
        } else {
            logAction('file_edit', 'Файл "' . $fileName . '" сохранён (путь: ' . $itemPath . ')', 'INFO');
            echo json_encode(['success' => 'Файл успешно сохранен на сервере!']);
        }
    } else {
        logAction('file_edit', 'Ошибка записи файла "' . $fileName . '"', 'ERROR');
        echo json_encode(['error' => 'Не удалось сохранить файл. Проверьте права доступа.']);
    }
    exit;
}

// =========================================================================
// 9. ЗАГРУЗКА ФАЙЛОВ
// =========================================================================
if ($action === 'upload') {
    if (!isset($_FILES['filemanager_files'])) {
        echo json_encode(['success' => false, 'error' => 'Файлы для загрузки не найдены.']);
        exit;
    }

    if (!is_dir($currentFullPath)) {
        echo json_encode(['success' => false, 'error' => 'Целевая директория не существует.']);
        exit;
    }

    // Blacklist для текущей папки
    if (fmIsBlacklisted($requestedDir)) {
        logAction('file_upload', 'Загрузка в защищённую папку: ' . $requestedDir, 'ERROR');
        echo json_encode(['success' => false, 'error' => 'Загрузка в эту папку запрещена']);
        exit;
    }

    $uploadedFiles = [];
    if (is_array($_FILES['filemanager_files']['name'])) {
        foreach ($_FILES['filemanager_files']['name'] as $index => $name) {
            $uploadedFiles[] = [
                'name'     => $_FILES['filemanager_files']['name'][$index],
                'tmp_name' => $_FILES['filemanager_files']['tmp_name'][$index],
                'error'    => $_FILES['filemanager_files']['error'][$index],
                'size'     => $_FILES['filemanager_files']['size'][$index],
            ];
        }
    } else {
        $uploadedFiles[] = $_FILES['filemanager_files'];
    }

    $allowedExtensions = ['php', 'css', 'js', 'json', 'html', 'htaccess', 'txt', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    $successCount = 0;

    foreach ($uploadedFiles as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) continue;

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            echo json_encode(['success' => false, 'error' => "Файл \"{$file['name']}\" превышает лимит (" . (MAX_UPLOAD_SIZE / 1024 / 1024) . " МБ)."]);
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (empty($ext) && strpos($file['name'], '.') === 0) {
            $ext = strtolower(substr($file['name'], 1));
        }

        if (!in_array($ext, $allowedExtensions, true)) {
            echo json_encode(['success' => false, 'error' => "Формат файла \"{$file['name']}\" заблокирован!"]);
            exit;
        }

        // Проверка mime для картинок
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (stripos($mime, 'image/') !== 0) {
                echo json_encode(['success' => false, 'error' => "Файл \"{$file['name']}\" не является валидным изображением."]);
                exit;
            }
        }

        // Именование
        if ($file['name'] === '.htaccess') {
            $finalName = '.htaccess';
        } else {
            $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
            $translitName = cms_transliterate($originalName);
            $cleanName = preg_replace('/[^a-z0-9._-]/i', '', $translitName);
            $cleanName = preg_replace('/_+/', '_', $cleanName);
            $cleanName = trim($cleanName, '_');

            if ($cleanName === '' || mb_strlen($cleanName) > 50) {
                $cleanName = 'file';
            }

            $potentialName = $cleanName . '.' . $ext;
            $checkPath = $currentFullPath . DIRECTORY_SEPARATOR . $potentialName;

            if (!file_exists($checkPath)) {
                $finalName = $potentialName;
            } else {
                $randomSalt = bin2hex(random_bytes(2));
                $potentialName = $cleanName . '_' . $randomSalt . '.' . $ext;
                $checkPath = $currentFullPath . DIRECTORY_SEPARATOR . $potentialName;

                if (file_exists($checkPath)) {
                    $randomSalt = bin2hex(random_bytes(2));
                    $potentialName = $cleanName . '_' . $randomSalt . '.' . $ext;
                }
                $finalName = $potentialName;
            }
        }

        $targetPath = $currentFullPath . DIRECTORY_SEPARATOR . $finalName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $successCount++;
        } else {
            logAction('file_upload', 'Ошибка загрузки в папку ' . $requestedDir, 'ERROR');
            echo json_encode(['success' => false, 'error' => "Не удалось сохранить файл \"{$file['name']}\"."]);
            exit;
        }
    }
    logAction('file_upload', 'Загружено ' . $successCount . ' файлов в папку ' . $requestedDir, 'INFO');
    echo json_encode(['success' => true, 'message' => "Успешно загружено файлов: {$successCount}"]);
    exit;
}

// =========================================================================
// 10. CHMOD
// =========================================================================
if ($action === 'chmod') {
    $itemName = $_POST['name'] ?? '';
    $itemPathRaw = $_POST['path'] ?? '';
    $modeInput = $_POST['mode'] ?? '';

    if (!preg_match('#^[a-zA-Z0-9\-_.]+$#', $itemName)) {
        echo json_encode(['error' => 'Недопустимое имя файла']);
        exit;
    }

    if (!preg_match('#^0?[0-7]{3}$#', $modeInput)) {
        echo json_encode(['error' => 'Недопустимая маска прав']);
        exit;
    }

    if (empty($itemName)) {
        echo json_encode(['error' => 'Не указано имя объекта']);
        exit;
    }

    $itemPath = fmSafePath($itemPathRaw);
    if ($itemPath === null) {
        logAction('file_chmod', 'Path traversal: ' . $itemPathRaw, 'ERROR');
        echo json_encode(['error' => 'Недопустимый путь']);
        exit;
    }

    $fullRelative = ($itemPath !== '' ? $itemPath . '/' : '') . $itemName;
    if (fmIsBlacklisted($fullRelative)) {
        logAction('file_chmod', 'Защищённая папка: ' . $fullRelative, 'ERROR');
        echo json_encode(['error' => 'Доступ запрещён']);
        exit;
    }

    $basePath = fmSafeAbsolute($itemPath);
    if ($basePath === null) {
        echo json_encode(['error' => 'Недопустимый путь']);
        exit;
    }

    $fullPath = $basePath . DIRECTORY_SEPARATOR . $itemName;

    if (!file_exists($fullPath)) {
        echo json_encode(['error' => 'Файл или папка не найдены']);
        exit;
    }

    $octalMode = octdec($modeInput);

    if (@chmod($fullPath, $octalMode)) {
        clearstatcache(true, $fullPath);
        $actualPerms = substr(sprintf('%o', fileperms($fullPath)), -4);

        if ($actualPerms !== $modeInput) {
            logAction('file_chmod', 'Частичные права на "' . $itemName . '"', 'INFO');
            echo json_encode([
                'error' => "Сервер применил только частичные права: {$actualPerms}.",
                'new_perms' => $actualPerms
            ]);
            exit;
        }

        logAction('file_chmod', 'Права ' . $modeInput . ' для "' . $itemName . '"', 'INFO');
        echo json_encode([
            'success' => "Права доступа изменены на {$actualPerms}",
            'new_perms' => $actualPerms
        ]);
    } else {
        logAction('file_chmod', 'Ошибка chmod "' . $itemName . '"', 'ERROR');
        echo json_encode(['error' => 'Не удалось изменить права.']);
    }
    exit;
}

// =========================================================================
// 11. СОЗДАНИЕ ПАПКИ
// =========================================================================
if ($action === 'create_dir') {
    $dirName = $_POST['name'] ?? '';

    $dirName = str_replace(['..', '/', '\\', '?', '*', ':', '"', '<', '>', '|', '+', '=', ',', ';'], '', $dirName);
    $dirName = trim($dirName);

    if (empty($dirName)) {
        echo json_encode(['error' => 'Имя папки пустое или содержит запрещённые символы']);
        exit;
    }

    if (fmIsBlacklisted($requestedDir . '/' . $dirName)) {
        logAction('file_create_dir', 'Попытка создания в защищённой папке: ' . $requestedDir . '/' . $dirName, 'ERROR');
        echo json_encode(['error' => 'Создание в этой папке запрещено']);
        exit;
    }

    $targetPath = $currentFullPath . DIRECTORY_SEPARATOR . $dirName;

    if (file_exists($targetPath)) {
        if (is_dir($targetPath)) {
            echo json_encode(['error' => 'Папка с таким именем уже существует']);
        } else {
            echo json_encode(['error' => 'Невозможно создать папку: есть файл с таким именем!']);
        }
        exit;
    }

    if (@mkdir($targetPath, 0755)) {
        logAction('file_create_dir', 'Создана папка "' . $dirName . '"', 'INFO');
        echo json_encode(['success' => "Папка \"{$dirName}\" успешно создана"]);
    } else {
        logAction('file_create_dir', 'Ошибка создания папки "' . $dirName . '"', 'ERROR');
        echo json_encode(['error' => 'Не удалось создать папку.']);
    }
    exit;
}

// =========================================================================
// 12. СОЗДАНИЕ ФАЙЛА
// =========================================================================
if ($action === 'create_file') {
    $fileName = $_POST['name'] ?? '';

    $fileName = str_replace(['..', '/', '\\', '?', '*', ':', '"', '<', '>', '|', '+', '=', ',', ';'], '', $fileName);
    $fileName = trim($fileName);

    if (empty($fileName)) {
        echo json_encode(['error' => 'Имя файла пустое или содержит запрещённые символы']);
        exit;
    }

    if (fmIsBlacklisted($requestedDir . '/' . $fileName)) {
        logAction('file_create_file', 'Попытка создания в защищённой папке: ' . $requestedDir . '/' . $fileName, 'ERROR');
        echo json_encode(['error' => 'Создание в этой папке запрещено']);
        exit;
    }

    $targetPath = $currentFullPath . DIRECTORY_SEPARATOR . $fileName;

    if (file_exists($targetPath)) {
        if (is_file($targetPath)) {
            echo json_encode(['error' => 'Файл с таким именем уже существует']);
        } else {
            echo json_encode(['error' => 'Невозможно создать файл: есть папка с таким именем!']);
        }
        exit;
    }

    if (@file_put_contents($targetPath, '') !== false) {
        logAction('file_create_file', 'Создан файл "' . $fileName . '"', 'INFO');
        echo json_encode(['success' => "Файл \"{$fileName}\" успешно создан"]);
    } else {
        logAction('file_create_file', 'Ошибка создания файла "' . $fileName . '"', 'ERROR');
        echo json_encode(['error' => 'Не удалось создать файл.']);
    }
    exit;
}

// =========================================================================
// 13. ТРАНСЛИТЕРАЦИЯ
// =========================================================================
if ($action === 'translit') {
    $textInput = $_POST['text'] ?? '';
    $resultText = cms_transliterate($textInput);

    echo json_encode([
        'success' => true,
        'transliterated' => $resultText
    ]);
    exit;
}