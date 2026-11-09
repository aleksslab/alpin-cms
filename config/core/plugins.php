<?php
/**
 * Загрузчик плагинов
 *
 * Читает config/data/plugins.json, сканирует папку config/plugins/
 * и подключает файлы plugin.php для активных плагинов.
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

/**
 * Проверяет, активен ли конкретный плагин.
 *
 * @param string $id ID плагина (имя папки)
 * @return bool true, если плагин включен, иначе false
 */
function isPluginActive(string $id): bool {
    $data = getPluginsData();
    return !empty($data[$id]['active']);
}

/**
 * Путь к файлу реестра плагинов.
 *
 * @return string
 */
function getPluginsJsonPath(): string {
    return CONFIG_DIR . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'plugins.json';
}

/**
 * Возвращает список всех установленных плагинов из plugins.json.
 *
 * @return array
 */
function getPluginsData(): array {
    $path = getPluginsJsonPath();
    if (!file_exists($path)) {
        return [];
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

/**
 * Сохраняет список плагинов в plugins.json.
 *
 * @param array $data
 * @return bool
 */
function savePluginsData(array $data): bool {
    return safeFileWrite(getPluginsJsonPath(), json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * Сканирует папку PLUGINS_DIR и находит все плагины,
 * у которых есть файл plugin.json.
 * 
 * @return array Массив [id => данные_из_plugin_json]
 */
function scanPluginsDir(): array {
    if (!is_dir(PLUGINS_DIR)) {
        return [];
    }
    
    $found = [];
    $items = scandir(PLUGINS_DIR);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        // Валидация ID плагина (защита от опасных имен папок)
        if (!isValidId($item)) continue;
        
        $pluginDir = PLUGINS_DIR . DIRECTORY_SEPARATOR . $item;
        if (!is_dir($pluginDir)) continue;
        
        $manifestFile = $pluginDir . DIRECTORY_SEPARATOR . 'plugin.json';
        if (!file_exists($manifestFile)) continue;
        
        $manifest = json_decode(file_get_contents($manifestFile), true);
        if (!is_array($manifest)) continue;
        
        // Принудительно ставим ID из имени папки
        $manifest['id'] = $item;
        
        $found[$item] = $manifest;
    }
    
    return $found;
}

/**
 * Синхронизирует папку плагинов с plugins.json.
 * Добавляет новые плагины (выключенными) и удаляет несуществующие.
 *
 * @return array Актуальный список плагинов
 */
function syncPluginsData(): array {
    $currentData = getPluginsData();
    $scanned = scanPluginsDir();
    
    $newData = [];
    $changed = false;
    
    foreach ($scanned as $id => $manifest) {
        if (isset($currentData[$id])) {
            // Берем все данные из манифеста, но сохраняем статус active
            $newManifest = $manifest;
            $newManifest['active'] = !empty($currentData[$id]['active']);
            
            // Если данные отличаются — помечаем как измененные
            if ($newManifest != $currentData[$id]) {
                $changed = true;
            }
            $newData[$id] = $newManifest;
        } else {
            $manifest['active'] = false;
            $newData[$id] = $manifest;
            $changed = true;
        }
    }
    
    if (count($newData) !== count($currentData)) {
        $changed = true;
    }
    
    if ($changed) {
        savePluginsData($newData);
    }
    
    return $newData;
}

/**
 * Подключает активные плагины.
 * Вызывается один раз при старте приложения.
 *
 * @return void
 */
function loadActivePlugins(): void {
    $plugins = getPluginsData();
    $realDir  = realpath(PLUGINS_DIR);
    
    if (!$realDir) return; // Папки нет — плагинов нет
    
    foreach ($plugins as $id => $data) {
        if (empty($data['active'])) continue;
        
        // Валидация ID
        if (!isValidId($id)) continue;
        
        // Если в манифесте указана вкладка — добавляем её автоматически
        if (!empty($data['admin_tab']) && is_array($data['admin_tab'])) {
            $tab = $data['admin_tab'];
            if (!empty($tab['title'])) {
                add_admin_tab(
                    $id, // Берем ID из папки/манифеста
                    (string)$tab['title'],
                    (string)($tab['icon'] ?? 'icon-puzzle'),
                    (string)($tab['group'] ?? 'system')
                );
            }
        }

        $pluginFile = $realDir . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'plugin.php';
        
        // Безопасность: realpath и проверка, что файл внутри папки плагинов
        $realFile = realpath($pluginFile);
        
        if ($realFile && strpos($realFile, $realDir) === 0 && is_file($realFile)) {
            require_once $realFile;
        }
    }
}

/**
 * Безопасно обновляет настройки плагина в общем массиве settings.
 * 
 * Если форма плагина не отправлялась (поле $triggerField отсутствует в POST),
 * возвращает старые значения из settings.json. Это защищает от затирания 
 * настроек плагина при сохранении настроек самого сайта.
 *
 * @param array  $newSettings   Массив новых настроек (передается по ссылке)
 * @param string $triggerField  Имя поля, наличие которого в POST означает, что форма плагина отправлена
 * @param array  $defaults      Значения по умолчанию. Ключ — путь настройки в settings;
 *                              поддерживается вложенность через точку ('theme.dark.bg_main')
 * @param array  $fields        Поля для сохранения. Формат элемента:
 *                              - 'field'                 → $_POST['field'] пишется в настройку 'field';
 *                              - 'post_field' => 'a.b.c' → $_POST['post_field'] пишется в настройку a[b][c].
 * @return bool true, если форма плагина была отправлена
 */
function save_plugin_settings(array &$newSettings, string $triggerField, array $defaults = [], array $fields = []): bool {
    $currentSettings = getSettingsData();
    $submitted = isset($_POST[$triggerField]);

    foreach ($fields as $key => $value) {
        // Числовой ключ — имя поля в POST совпадает с путём настройки
        $postName = is_int($key) ? $value : $key;
        $path     = $value;
        // Дефолт можно задать и плоско ('log_settings.log_enabled'), и вложенно
        $default  = array_key_exists($path, $defaults)
            ? $defaults[$path]
            : _settings_path_get($defaults, $path);

        // Форма плагина не отправлялась — сохраняем текущее значение настройки
        if (!$submitted) {
            _settings_path_set($newSettings, $path, _settings_path_get($currentSettings, $path, $default));
            continue;
        }

        $val = $_POST[$postName] ?? $default;

        // Чекбокс: hidden '0' + checkbox '1'. Приведение по типу значения по умолчанию
        if (is_bool($default)) {
            $val = ($val == '1');
        } elseif (is_int($default)) {
            $val = intval($val);
        }

        _settings_path_set($newSettings, $path, $val);
    }

    return $submitted;
}

/**
 * Читает значение настройки по пути с вложенностью через точку.
 *
 * @param array  $data     Массив настроек
 * @param string $path     Путь, например 'theme.dark.bg_main'
 * @param mixed  $fallback Значение, если путь не найден
 * @return mixed
 */
function _settings_path_get(array $data, string $path, $fallback = null) {
    $cursor = $data;
    foreach (explode('.', $path) as $segment) {
        if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
            return $fallback;
        }
        $cursor = $cursor[$segment];
    }
    return $cursor;
}

/**
 * Пишет значение настройки по пути с вложенностью через точку,
 * создавая недостающие уровни.
 *
 * @param array  $data  Массив настроек (передается по ссылке)
 * @param string $path  Путь, например 'theme.dark.bg_main'
 * @param mixed  $value Значение
 * @return void
 */
function _settings_path_set(array &$data, string $path, $value): void {
    $cursor = &$data;
    foreach (explode('.', $path) as $segment) {
        if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
            $cursor[$segment] = [];
        }
        $cursor = &$cursor[$segment];
    }
    $cursor = $value;
}

/**
 * Скачивает ZIP-архив плагина по URL во временный файл.
 *
 * @param string $url
 * @param string $id ID плагина (для имени файла)
 * @return string|null Путь к временному файлу или null
 */
function downloadPluginZip(string $url, string $id): ?string {
    $env = [
        'curl' => function_exists('curl_init'),
        'fopen' => ini_get('allow_url_fopen') == '1'
    ];
    
    if (!$env['curl'] && !$env['fopen']) {
        return null;
    }
    
    $tempFile = DATA_DIR . 'plugin_' . $id . '.zip';
    $success = false;
    
    if ($env['curl']) {
        $fp = fopen($tempFile, 'w');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AlPin-CMS-Updater/1.0');
        $success = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    } elseif ($env['fopen']) {
        $success = @copy($url, $tempFile);
    }
    
    if (!$success || !file_exists($tempFile) || filesize($tempFile) === 0) {
        @unlink($tempFile);
        return null;
    }
    
    return $tempFile;
}

/**
 * Устанавливает плагин из ZIP-архива.
 *
 * @param string $zipPath Путь к ZIP-архиву
 * @param string $id ID плагина
 * @return array ['success' => bool, 'message' => string]
 */
function installPluginFromZip(string $zipPath, string $id): array {
    // Валидация ID
    if (!isValidId($id)) {
        return ['success' => false, 'message' => 'Невалидный ID плагина.'];
    }
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['success' => false, 'message' => 'Не удалось открыть ZIP-архив.'];
    }
    
    // Защита от ZIP Slip (функция из config/core/security.php)
    $zipSlip = detectZipSlip($zip);
    if ($zipSlip !== null) {
        $zip->close();
        return ['success' => false, 'message' => 'ZIP Slip: архив содержит опасные пути (' . e($zipSlip) . ').'];
    }
    
    $targetDir = PLUGINS_DIR . DIRECTORY_SEPARATOR . $id;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $zip->extractTo($targetDir);
    
    // Копируем публичные ассеты (если есть папка site/)
    $siteDir = $targetDir . DIRECTORY_SEPARATOR . 'site';
    if (is_dir($siteDir)) {
        // CSS
        if (file_exists($siteDir . DIRECTORY_SEPARATOR . 'style.css')) {
            copy($siteDir . DIRECTORY_SEPARATOR . 'style.css', APP_ROOT . '/css/' . $id . '.css');
        }
        // JS
        if (file_exists($siteDir . DIRECTORY_SEPARATOR . 'script.js')) {
            copy($siteDir . DIRECTORY_SEPARATOR . 'script.js', APP_ROOT . '/js/' . $id . '.js');
        }
    }
    
    // Копируем ассеты админки (если есть папка admin/)
    $adminDir = $targetDir . DIRECTORY_SEPARATOR . 'admin';
    if (is_dir($adminDir)) {
        // CSS
        if (file_exists($adminDir . DIRECTORY_SEPARATOR . 'style.css')) {
            copy($adminDir . DIRECTORY_SEPARATOR . 'style.css', CONFIG_DIR . '/css/' . $id . '.css');
        }
        // JS
        if (file_exists($adminDir . DIRECTORY_SEPARATOR . 'script.js')) {
            copy($adminDir . DIRECTORY_SEPARATOR . 'script.js', CONFIG_DIR . '/js/' . $id . '.js');
        }
    }
    
    $zip->close();
    
    // Синхронизируем plugins.json
    if (!syncPluginsData()) {
        return ['success' => false, 'message' => 'Файлы распакованы, но не удалось обновить список плагинов (проверьте права на config/data/).'];
    }
    
    return ['success' => true, 'message' => 'Плагин "' . e($id) . '" успешно установлен!'];
}

/**
 * Скачивает каталог расширений с GitHub.
 *
 * @param string $url
 * @return array|null
 */
function fetch_catalog(string $url): ?array {
    $env = [
        'curl' => function_exists('curl_init'),
        'fopen' => ini_get('allow_url_fopen') == '1'
    ];
    
    if (!$env['curl'] && !$env['fopen']) return null;
    
    $json = false;
    if ($env['curl']) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AlPin-CMS-Core/1.0');
        $json = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) $json = false;
    } elseif ($env['fopen']) {
        $context = stream_context_create([
            'http' => ['timeout' => 10, 'user_agent' => 'AlPin-CMS-Core/1.0']
        ]);
        $json = @file_get_contents($url, false, $context);
    }
    
    if ($json === false) return null;
    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

/**
 * Скачивает каталог, сравнивает с установленными и обновляет кеш.
 *
 * @param string $catalogUrl URL каталога
 * @return array|false Данные каталога или false при ошибке
 */
function refresh_updates_cache(string $catalogUrl) {
    $catalog = fetch_catalog($catalogUrl);
    if (!$catalog) return false;
    
    $installedPlugins = getPluginsData();
    $installedModules = getModulesData();
    
    $new = [];
    $updates = [];
    
    if (!empty($catalog['extensions'])) {
        foreach ($catalog['extensions'] as $extId => $extInfo) {
            $type = $extInfo['type'] ?? 'plugin';
            $latestVersion = $extInfo['version'] ?? '0.0.0';
            $url = $extInfo['download_url'] ?? '#';
            $name = $extInfo['name'] ?? $extId;
            
            $installedVersion = null;
            if ($type === 'module') {
                $isInstalled = function_exists('isModuleInstalled') ? isModuleInstalled($extId) : false;
                $hasArchive = function_exists('getModuleArchivePath') ? file_exists(getModuleArchivePath($extId)) : false;

                if ($isInstalled || $hasArchive) {
                    $installedVersion = $installedModules[$extId]['version'] ?? '0.0.0';
                }
                if (isset($installedModules[$extId]['name'])) {
                    $name = $installedModules[$extId]['name'];
                }
            } elseif ($type === 'plugin' && isset($installedPlugins[$extId])) {
                $installedVersion = $installedPlugins[$extId]['version'] ?? '0.0.0';
                $name = $installedPlugins[$extId]['name'] ?? $name;
            }
            
            if ($installedVersion === null) {
                $new[$extId] = [
                    'name' => $name,
                    'description' => $extInfo['description'] ?? '',
                    'version' => $latestVersion,
                    'type' => $type,
                    'url' => $url
                ];
            } elseif (version_compare($latestVersion, $installedVersion, '>')) {
                $updates[$extId] = [
                    'name' => $name,
                    'current' => $installedVersion,
                    'latest' => $latestVersion,
                    'type' => $type,
                    'url' => $url
                ];
            }
        }
    }
    
    // Ядро
    $currentCoreVersion = defined('CMS_VERSION') ? CMS_VERSION : '1.0.0';
    $coreData = ['update_available' => false];
    if (!empty($catalog['core'])) {
        $latestCore = $catalog['core']['version'] ?? '';
        $coreData = [
            'update_available' => version_compare($latestCore, $currentCoreVersion, '>'),
            'current' => $currentCoreVersion,
            'latest' => $latestCore,
            'url' => $catalog['core']['download_url'] ?? '#'
        ];
    }
    
    // Формируем результат
    $result = [
        'last_check' => time(),
        'data' => [
            'core' => $coreData,
            'new' => $new,
            'updates' => $updates
        ]
    ];
    
    // Сохраняем в кеш
    $cacheFile = DATA_DIR . 'updates_cache.json';
    safeFileWrite($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    return $catalog;
}