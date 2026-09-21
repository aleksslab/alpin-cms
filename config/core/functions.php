<?php

/**
 * =========================================================================
 * Глобальное ядро функций данных
 * =========================================================================
 */

if (!defined('APP_ROOT')) { define('APP_ROOT', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')); }

if (!defined('DATA_DIR')) { define('DATA_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR); }

// Глобальный массив кэша в памяти
$GLOBALS['DATA_CACHE'] = [];

/**
 * Очищает кэш для указанного файла данных
 *
 * @param string $fileName Имя файла без расширения (например, 'settings')
 * @return void
 */
function clearDataCache(string $fileName): void {
    if (isset($GLOBALS['DATA_CACHE'][$fileName])) {
        unset($GLOBALS['DATA_CACHE'][$fileName]);
    }
}

/**
 * Форматирует номер телефона в единый читаемый вид
 *
 * @param string $phone Сырой номер телефона (может содержать любые символы)
 * @return string Отформатированный номер в формате +7 (999) 000-00-00
 */
function formatPhone(string $phone): string {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($clean) === 11 && ($clean[0] === '7' || $clean[0] === '8')) {
        $clean = substr($clean, 1);
    }
    if (strlen($clean) === 10) {
        return "+7 (" . substr($clean, 0, 3) . ") " . substr($clean, 3, 3) . "-" . substr($clean, 6, 2) . "-" . substr($clean, 8, 2);
    }
    return '+' . $clean;
}

/**
 * Читает JSON-файл и декодирует его в ассоциативный массив
 *
 * @param string $path Полный путь к JSON-файлу
 * @return array Декодированные данные или пустой массив при ошибке
 */
function _jsonToArray($path): array {
    if (!file_exists($path)) { return []; }
    return json_decode(file_get_contents($path), true) ?? [];
}

/**
 * Экранирует строку для безопасного вывода в HTML
 *
 * @param string|null $str Строка для экранирования
 * @return string Экранированная строка
 */
function e(?string $str): string { 
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); 
}

/**
 * Атомарная запись файла с блокировкой (flock).
 * 
 * @param string $path Путь к файлу
 * @param string $content Содержимое
 * @param bool $append Режим добавления (true) или перезаписи (false)
 * @return bool
 */
function safeFileWrite(string $path, string $content, bool $append = false): bool {
    $flags = $append ? (FILE_APPEND | LOCK_EX) : LOCK_EX;
    return @file_put_contents($path, $content, $flags) !== false;
}

/**
 * Экранирует строку для безопасной вставки в JS-строку внутри HTML-атрибута.
 * 
 * @param string|null $str
 * @return string
 */
function escapeJsString(?string $str): string {
    $str = $str ?? '';
    return str_replace(
        ['\\', "'", '"', "\n", "\r", '<', '>', '&'],
        ['\\\\', "\\'", '\\"', '\\n', '\\r', '\\x3C', '\\x3E', '\\x26'],
        $str
    );
}

/**
 * Проверяет URL на безопасность (whitelist схем).
 * 
 * @param string $url URL для проверки
 * @return string Безопасный URL или '#' при ошибке
 */
function safeUrl(string $url): string {
    $url = trim($url);
    
    if ($url === '') {
        return '#';
    }
    
    // Разрешаем: /path, #anchor, mailto:, tel:, http(s)://
    // Запрещаем: javascript:, vbscript:, data:, всё остальное с :
    
    // Если URL начинается с / или # — ок
    if ($url[0] === '/' || $url[0] === '#') {
        return $url;
    }
    
    // Если содержит протокол
    if (preg_match('#^([a-z][a-z0-9+\-.]*):#i', $url, $m)) {
        $scheme = strtolower($m[1]);
        $allowed = ['http', 'https', 'mailto', 'tel'];
        if (!in_array($scheme, $allowed, true)) {
            return '#';
        }
    }
    
    return $url;
}

/**
 * Проверяет HTML на опасные конструкции.
 * 
 * @param string $html Проверяемый HTML
 * @return string|null Текст ошибки или null, если всё ок
 */
function validateHtml(string $html): ?string {
    if ($html === '') return null;
    
    $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    $dangerous = [
        '/<script/i'               => 'тег <script>',
        '/<iframe/i'               => 'тег <iframe>',
        '/<object/i'               => 'тег <object>',
        '/<embed/i'                => 'тег <embed>',
        '/<form/i'                 => 'тег <form>',
        '/<meta/i'                 => 'тег <meta>',
        '/<link/i'                 => 'тег <link>',
        '/on\w+\s*=/i'             => 'событие on*',
        '/javascript\s*:/i'        => 'javascript:',
        '/vbscript\s*:/i'          => 'vbscript:',
        '/data\s*:\s*text\/html/i' => 'data:text/html',
        '/expression\s*\(/i'       => 'CSS expression',
        '/\\\\x[0-9a-f]{2}/i'      => 'escape \\x',
        '/\\\\u[0-9a-f]{4}/i'      => 'escape \\u',
    ];
    
    foreach ($dangerous as $pattern => $label) {
        if (preg_match($pattern, $decoded)) {
            return 'Обнаружено недопустимое содержимое: ' . $label;
        }
    }
    
    return null;
}

/**
 * Очищает HTML — оставляет только безопасные теги.
 * 
 * @param string $html Исходный HTML
 * @return string Очищенный HTML
 */
function sanitizeHtml(string $html): string {
    $allowed = ['b', 'i', 'u', 'strong', 'em', 'span', 'ul', 'ol', 'li', 'p', 'br', 'a', 'h1', 'h2', 'h3', 'h4'];
    $html = strip_tags($html, '<' . implode('><', $allowed) . '>');
    
    // Убираем опасные атрибуты
    $html = preg_replace('/\s(on\w+|style|srcdoc|formaction)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/javascript\s*:/i', '', $html);
    
    return $html;
}

/**
 * Возвращает "чистый" текст без HTML-тегов (для alt, title и т.д.)
 *
 * @return string
 */
function getPlainText(): string {
    $settings = getSettingsData();
    $name = $settings['name'] ?? 'Сайт';
    // Удаляем все HTML-теги
    return strip_tags($name);
}

/**
 * Читает данные из JSON-файла с кэшированием в памяти на время одного запроса
 *
 * @param string $fileName Имя файла без расширения (например, 'settings')
 * @param bool $refresh Принудительно перечитать файл с диска, игнорируя кэш
 * @return array Данные из файла в виде ассоциативного массива
 */
function getData(string $fileName, bool $refresh = false): array {
    if ($refresh) { 
        clearDataCache($fileName); 
    }

    if (isset($GLOBALS['DATA_CACHE'][$fileName])) { 
        return $GLOBALS['DATA_CACHE'][$fileName]; 
    }
    
    $path = DATA_DIR . "{$fileName}.json";
    
    if (file_exists($path)) {
        clearstatcache(true, $path);
    }
    
    $GLOBALS['DATA_CACHE'][$fileName] = _jsonToArray($path);
    return $GLOBALS['DATA_CACHE'][$fileName];
}

/**
 * Загружает список модулей из config/data/modules.json
 * @return array
 */
function getModulesData(): array {
    $path = APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'modules.json';
    if (!file_exists($path)) {
        return [];
    }
    $data = json_decode(file_get_contents($path), true);
    return $data ?? [];
}

/**
 * Читает данные шаблонов из config/data/templates.json
 *
 * @return array Данные шаблонов
 */
function getTemplateData(): array {
    $path = APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'templates.json';
    if (!file_exists($path)) {
        return ['templates' => []];
    }
    return json_decode(file_get_contents($path), true) ?? ['templates' => []];
}

/**
 * Возвращает настройки сайта из файла data/settings.json
 *
 * @param bool $refresh Принудительно перечитать файл с диска, игнорируя кэш
 * @return array Настройки с полями name, phone, email, address
 */
function getSettingsData(bool $refresh = false): array { return getData('settings', $refresh); }

/**
 * Возвращает список всех страниц (для админки)
 * 
 * @return array Массив страниц с id, title, slug, status
 */
function getPagesList(): array {
    $pagesDir = DATA_DIR . 'pages/';
    if (!is_dir($pagesDir)) {
        return [];
    }
    
    $pages = [];
    $files = glob($pagesDir . '*.json');
    
    foreach ($files as $file) {
        $page = _jsonToArray($file);
        if (!empty($page['id'])) {
            $pages[] = [
                'id' => $page['id'],
                'title' => $page['title'] ?? $page['id'],
                'slug' => $page['slug'] ?? $page['id'],
                'status' => $page['status'] ?? 'draft'
            ];
        }
    }
    
    return $pages;
}

/**
 * Возвращает список страниц с пагинацией.
 *
 * @param int $page Номер страницы (начиная с 1)
 * @param int $perPage Количество на страницу
 * @return array ['pages' => [...], 'total' => int, 'page' => int, 'totalPages' => int]
 */
function getPagesListPaginated(int $page = 1, int $perPage = 25): array {
    $pagesDir = DATA_DIR . 'pages/';
    if (!is_dir($pagesDir)) {
        return ['pages' => [], 'total' => 0, 'page' => 1, 'totalPages' => 0];
    }

    $files = glob($pagesDir . '*.json');
    $total = count($files);

    // Сортировка: сначала по дате изменения (свежие сверху)
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    // Пагинация
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    $slice = array_slice($files, $offset, $perPage);

    $pages = [];
    foreach ($slice as $file) {
        $page_data = _jsonToArray($file);
        if (!empty($page_data['id'])) {
            $pages[] = [
                'id' => $page_data['id'],
                'title' => $page_data['title'] ?? $page_data['id'],
                'slug' => $page_data['slug'] ?? $page_data['id'],
                'template' => $page_data['template'] ?? 'full-width',
                'status' => $page_data['status'] ?? 'draft',
            ];
        }
    }

    return [
        'pages' => $pages,
        'total' => $total,
        'page' => $page,
        'totalPages' => $totalPages,
    ];
}

/**
 * Возвращает ID главной страницы из data/globals.json
 * Если в настройках не указано, возвращает 'home'
 *
 * @return string ID главной страницы
 */
function getHomePageId(): string {
    $globals = getData('settings');
    return $globals['home_page_id'] ?? 'home';
}

/**
 * Загружает страницу из data/pages/{slug}.json по её ЧПУ (slug)
 * 
 * @param string $slug ЧПУ страницы (пустая строка означает главную)
 * @return array|null Массив с данными страницы или null, если страница не найдена
 */
function loadPage(string $slug): ?array {
    if ($slug !== '') {
        if (!preg_match('#^[a-z0-9\-_/]+$#i', $slug) ||
            strpos($slug, '..') !== false ||
            strpos($slug, '//') !== false) {
            return null;
        }
    }
    
    if ($slug === '') {
        $globals = getData('globals');
        $homeId = $globals['home_page_id'] ?? 'home';
        return loadPageById($homeId);
    }
    
    $filePath = DATA_DIR . 'pages/' . $slug . '.json';
    if (!file_exists($filePath)) {
        return null;
    }
    
    $page = _jsonToArray($filePath);
    if (empty($page['status']) || $page['status'] !== 'published') {
        return null;
    }
    
    // Добавляем поля по умолчанию, если их нет
    if (!isset($page['show_header'])) {
        $page['show_header'] = true;
    }
    if (!isset($page['show_footer'])) {
        $page['show_footer'] = true;
    }
    if (!isset($page['custom_header'])) {
        $page['custom_header'] = '';
    }
    if (!isset($page['custom_footer'])) {
        $page['custom_footer'] = '';
    }
    if (!isset($page['rows'])) {
        $page['rows'] = [];
    }
    
    return $page;
}

/**
 * Загружает страницу из data/pages/{id}.json по её ID
 *
 * @param string $id Идентификатор страницы
 * @return array|null Массив с данными страницы или null, если страница не найдена
 */
function loadPageById(string $id): ?array {
    if (!preg_match('#^[a-z0-9\-_]+$#i', $id)) {
        return null;
    }
    
    $filePath = DATA_DIR . 'pages/' . $id . '.json';
    if (!file_exists($filePath)) {
        return null;
    }
    return _jsonToArray($filePath);
}

/**
 * Загружает шаблон из config/data/templates.json по его ID
 *
 * @param string $id Идентификатор шаблона
 * @return array|null Массив с данными шаблона или null, если шаблон не найден
 */
function loadTemplate(string $id): ?array {
    $templatesData = getTemplateData();
    if (empty($templatesData['templates'])) return null;
    
    foreach ($templatesData['templates'] as $template) {
        if ($template['id'] === $id) {
            return $template;
        }
    }
    
    return null;
}

/**
 * Возвращает зоны шаблона по его ID
 *
 * @param string $templateId ID шаблона
 * @return array Массив зон с классами
 */
function getTemplateZones(string $templateId): array {
    $template = loadTemplate($templateId);
    if (!$template || empty($template['zones'])) {
        return ['main' => ['class' => '']];
    }
    return $template['zones'];
}

/**
 * Группирует ряды страницы по зонам
 *
 * @param array $rows Массив рядов
 * @return array Массив рядов, сгруппированных по зонам
 */
function groupRowsByZone(array $rows): array {
    $grouped = [];
    foreach ($rows as $row) {
        $zone = $row['zone'] ?? 'main';
        if (!isset($grouped[$zone])) {
            $grouped[$zone] = [];
        }
        $grouped[$zone][] = $row;
    }
    return $grouped;
}

/**
 * =========================================================================
 * Функции управления модулями
 * =========================================================================
 */

/**
 * Возвращает путь к архиву модуля
 *
 * @param string $id Идентификатор модуля
 * @return string Полный путь к архиву
 */
function getModuleArchivePath(string $id): string {
    return APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'archives' . DIRECTORY_SEPARATOR . $id . '.zip';
}

/**
 * Возвращает путь к папке с превью модулей
 *
 * @param string $id Идентификатор модуля
 * @return string Полный путь к превью
 */
function getModulePreviewPath(string $id): string {
    return APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'previews' . DIRECTORY_SEPARATOR . $id . '.png';
}

/**
 * Проверяет, распакован ли модуль (есть ли файлы в общих папках)
 *
 * @param string $id Идентификатор модуля
 * @return bool true если распакован, false если нет
 */
function isModuleInstalled(string $id): bool {
    // Для админки: ищем папку с модулем
    $adminPath = APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'module.php';
    
    // Для сайта: ищем файл модуля
    $sitePath = APP_ROOT . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $id . '.php';
    
    return file_exists($adminPath) && file_exists($sitePath);
}

/**
 * Проверяет, используется ли модуль на каких-либо страницах
 *
 * @param string $id Идентификатор модуля
 * @return array Массив страниц, где используется модуль
 */
function findModuleUsage(string $id): array {
    $pagesDir = APP_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR;
    if (!is_dir($pagesDir)) {
        return [];
    }
    
    $usedIn = [];
    $files = glob($pagesDir . '*.json');
    
    foreach ($files as $file) {
        $page = json_decode(file_get_contents($file), true);
        if (empty($page['rows'])) {
            continue;
        }
        
        foreach ($page['rows'] as $row) {
            foreach ($row['columns'] as $col) {
                foreach ($col['modules'] as $module) {
                    if (($module['type'] ?? '') === $id) {
                        $usedIn[] = $page['id'] ?? basename($file, '.json');
                        break 3;
                    }
                }
            }
        }
    }
    
    return $usedIn;
}


/**
 * Загружает описание модуля из config/modules/modules.json по его ID
 *
 * @param string $id Идентификатор модуля (hero, advantages, text и т.д.)
 * @return array|null Массив с описанием модуля или null, если модуль не найден
 */
function loadModule(string $id): ?array {
    $modulesData = getData('modules');
    if (empty($modulesData[$id])) return null;
    return $modulesData[$id];
}

/**
 * Рендеринг модуля по ID
 * 
 * @param string $moduleId   ID модуля (например: 'social-icons')
 * @param array  $moduleData Данные для модуля
 * @param array  $settings   Дополнительные настройки (class и т.д.)
 * @return string
 */
function render_module($moduleId, $moduleData = [], $settings = []) {
    // Если модуль не установлен — пытаемся распаковать
    if (!isModuleInstalled($moduleId)) {
        $result = unpackModule($moduleId);
        
        if (!$result['success']) {
            return '<!-- Модуль ' . $moduleId . ': ' . $result['message'] . ' -->';
        }
    }
    
    // Путь к файлу модуля на сайте
    $moduleFile = APP_ROOT . '/modules/' . $moduleId . '.php';
    
    if (!file_exists($moduleFile)) {
        return '<!-- Модуль ' . $moduleId . ' не найден -->';
    }
    
    // Извлекаем class из настроек
    $moduleClass = $settings['class'] ?? '';
    
    // Добавляем class в данные модуля
    if (!empty($moduleClass)) {
        $moduleData['settings']['class'] = $moduleClass;
    }
    
    // Буферизация вывода
    ob_start();
    
    // Подключаем файл модуля
    include $moduleFile;
    
    return ob_get_clean();
}

/**
 * Собирает все уникальные ID модулей, используемых на странице
 *
 * @param array $page Массив страницы из pages.json
 * @return array Массив уникальных ID модулей
 */
function getUsedModules(array $page): array {
    $modules = [];
    $rows = $page['rows'] ?? [];
    
    foreach ($rows as $row) {
        foreach ($row['columns'] as $col) {
            foreach ($col['modules'] as $module) {
                $modules[] = $module['type'];
            }
        }
    }
    
    return array_unique($modules);
}

/**
 * =========================================================================
 * Функции управления соцсетями
 * =========================================================================
 */

/**
 * Возвращает список всех доступных соцсетей
 */
function getSocialNetworks(): array {
    return [
        'vk' => ['label' => 'ВКонтакте'],
        'ok' => ['label' => 'Одноклассники'],
        'telegram' => ['label' => 'Telegram'],
        'tg_channel' => ['label' => 'Telegram-канал'],
        'whatsapp' => ['label' => 'WhatsApp'],
        'instagram' => ['label' => 'Instagram'],
        'facebook' => ['label' => 'Facebook'],
        'twitter' => ['label' => 'Twitter/X'],
        'youtube' => ['label' => 'YouTube'],
        'tiktok' => ['label' => 'TikTok'],
        'github' => ['label' => 'GitHub'],
        'linkedin' => ['label' => 'LinkedIn'],
        'rss' => ['label' => 'RSS'],
    ];
}

/**
 * Возвращает сохранённые соцсети из настроек
 */
function getSavedSocials(bool $refresh = false): array {
    $settings = getSettingsData($refresh);
    $socials = $settings['socials'] ?? [];
    $allNetworks = getSocialNetworks();
    
    foreach ($socials as $key => $social) {
        if (!empty($social['id']) && isset($allNetworks[$social['id']])) {
            $socials[$key]['label'] = $allNetworks[$social['id']]['label'];
        } elseif (empty($social['label'])) {
            $socials[$key]['label'] = $social['id'] ?? '';
        }
    }
    
    return $socials;
}

/**
 * Возвращает ID сохранённых соцсетей
 */
function getSavedSocialIds(bool $refresh = false): array {
    $socials = getSavedSocials($refresh);
    return array_column($socials, 'id');
}

/**
 * Возвращает доступные для добавления соцсети (которые ещё не сохранены)
 */
function getAvailableSocials(bool $refresh = false): array {
    $allNetworks = getSocialNetworks();
    $savedIds = getSavedSocialIds($refresh);
    $available = [];
    
    foreach ($allNetworks as $id => $data) {
        if (!in_array($id, $savedIds)) {
            $available[$id] = $data;
        }
    }
    
    return $available;
}

/**
 * Возвращает ID основных соцсетей
 *
 * @return array
 */
function getPrimarySocialIds(): array {
    return ['vk', 'ok', 'telegram'];
}

/**
 * =========================================================================
 * Функции управления меню
 * =========================================================================
 */

/**
 * Возвращает список всех меню
 * 
 * @return array Массив меню
 */
function getMenusList(): array {
    $menusDir = DATA_DIR . 'menus/';
    if (!is_dir($menusDir)) {
        return [];
    }
    
    $menus = [];
    $files = glob($menusDir . '*.json');
    
    foreach ($files as $file) {
        $menu = json_decode(file_get_contents($file), true);
        if (!empty($menu['id'])) {
            $menus[] = $menu;
        }
    }
    
    // Сортируем: главное сверху, остальные по имени
    usort($menus, function($a, $b) {
        if (($a['is_main'] ?? false) && !($b['is_main'] ?? false)) return -1;
        if (!($a['is_main'] ?? false) && ($b['is_main'] ?? false)) return 1;
        return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    });
    
    return $menus;
}

/**
 * Загружает меню по ID
 * 
 * @param string $id ID меню
 * @return array|null Массив с данными меню или null, если не найдено
 */
function loadMenu(string $id): ?array {
    $filePath = DATA_DIR . 'menus/' . $id . '.json';
    if (!file_exists($filePath)) {
        return null;
    }
    return json_decode(file_get_contents($filePath), true);
}

/**
 * Возвращает ID главного меню
 * 
 * @return string|null ID главного меню или null
 */
function getMainMenuId(): ?string {
    $settings = getSettingsData();
    return $settings['main_menu'] ?? null;
}

/**
 * Возвращает пункты меню по ID меню
 * 
 * @param string $menuId ID меню
 * @return array Массив пунктов меню
 */
function getMenuItems(string $menuId): array {
    $menu = loadMenu($menuId);
    if (!$menu) {
        return [];
    }
    return $menu['items'] ?? [];
}

/**
 * Проверяет, включён ли бургер
 * 
 * @return bool True, если бургер включен в настройках, иначе false
 */
function isBurgerEnabled(): bool {
    return (bool)(getSettingsData()['burger_enabled'] ?? false);
}

/**
 * Возвращает брекпоинт бургера
 * 
 * @return string Строковое значение брейкпоинта (например, 'md', 'lg')
 */
function getBurgerBreakpoint(): string {
    return (string)(getSettingsData()['burger_breakpoint'] ?? 'md');
}

/**
 * Генерирует HTML-разметку главного меню для десктопной версии сайта
 * 
 * @param array $items Массив пунктов меню
 * @return string HTML-код навигации
 */
function renderMainMenu(array $items): string {
    $html = '';
    
    foreach ($items as $item) {
        $type = $item['type'] ?? 'page';
        $label = $item['label'] ?? '';
        $url = $type === 'page' ? ($item['page_id'] ?? '/') : ($item['url'] ?? '#');
        $url = safeUrl($url);

        if ($type === 'divider') {
            continue;
        }

        if ($type === 'dropdown') {
            $children = $item['children'] ?? [];
            if (!empty($children)) {
                $html .= '<div class="relative group h-20 flex items-center">';
                $html .= '<button type="button" class="inline-flex items-center gap-1 text-sm font-semibold tracking-wide text-slate-600 group-hover:text-[var(--primary-color)] transition-colors cursor-pointer">';
                $html .= e($label) . '<span class="icon-chevron-down text-[10px] text-slate-400 group-hover:rotate-180 transition-transform"></span>';
                $html .= '</button>';
                $html .= '<div class="absolute top-20 left-0 hidden group-hover:flex flex-col bg-white border border-slate-100 shadow-xl rounded-xl py-2 min-w-[200px] animate-fade-in">';
                
                foreach ($children as $child) {
                    $childUrl = $child['type'] === 'page' ? ($child['page_id'] ?? '/') : ($child['url'] ?? '#');
                    $childUrl = safeUrl($childUrl);
                    $html .= '<a href="' . e($childUrl) . '" class="px-4 py-2 text-sm text-slate-600 hover:text-[var(--primary-color)] hover:bg-slate-50 transition-colors">' . e($child['label']) . '</a>';
                }
                
                $html .= '</div></div>';
            }
            continue;
        }

        $html .= '<a href="' . e($url) . '" class="text-sm font-semibold tracking-wide text-slate-600 hover:text-[var(--primary-color)] transition-colors cursor-pointer">' . e($label) . '</a>';
    }
    
    return $html;
}

/**
 * Генерирует HTML-разметку мобильного меню с поддержкой закрытых аккордеонов
 * 
 * @param array $items Массив пунктов меню
 * @return string HTML-код мобильной навигации
 */
function renderMobileMenu(array $items): string {
    $html = '';
    
    foreach ($items as $item) {
        $type = $item['type'] ?? 'page';
        $label = $item['label'] ?? '';
        $url = $type === 'page' ? ($item['page_id'] ?? '/') : ($item['url'] ?? '#');
        $url = safeUrl($url);

        if ($type === 'divider') {
            $html .= '<div class="border-t border-slate-100 my-2"></div>';
            continue;
        }

        if ($type === 'dropdown') {
            $children = $item['children'] ?? [];
            if (!empty($children)) {
                $dropdownId = 'mob_drop_' . uniqid();
                
                // Рендерим кнопку дропдауна (внешне как обычный пункт меню, но со стрелочкой)
                $html .= '<div class="flex flex-col">';
                $html .= '<button type="button" onclick="toggleMobileDropdown(\'' . $dropdownId . '\', this)" class="w-full text-left font-semibold text-slate-600 hover:text-[var(--primary-color)] py-2 flex items-center justify-between cursor-pointer transition-colors">';
                $html .= e($label);
                $html .= '<span class="icon-chevron-right text-xs text-slate-400 transition-transform"></span>';
                $html .= '</button>';
                
                // Скрытый контейнер с подпунктами (по умолчанию hidden)
                $html .= '<div id="' . $dropdownId . '" class="hidden flex flex-col pl-4 border-l border-slate-100 mt-1 mb-2 space-y-2">';
                
                foreach ($children as $child) {
                    $childUrl = $child['type'] === 'page' ? ($child['page_id'] ?? '/') : ($child['url'] ?? '#');
                    $childUrl = safeUrl($childUrl);
                    $html .= '<a href="' . e($childUrl) . '" class="text-left text-sm font-medium text-slate-500 hover:text-[var(--primary-color)] py-1.5 transition-colors">' . e($child['label']) . '</a>';
                }
                
                $html .= '</div></div>';
            }
            continue;
        }

        $html .= '<a href="' . e($url) . '" class="text-left font-semibold text-slate-600 hover:text-[var(--primary-color)] py-2 cursor-pointer transition-colors">' . e($label) . '</a>';
    }
    
    return $html;
}

/**
 * Получить список доступных шаблонов хедера
 *
 * @return array Массив с ключами: id, name, description, author, preview
 */
function getHeaderVariants(): array {
    $dir = APP_ROOT . '/templates/headers/';
    $variants = [];
    
    if (!is_dir($dir)) {
        return $variants;
    }
    
    $folders = glob($dir . '*', GLOB_ONLYDIR);
    foreach ($folders as $folder) {
        $id = basename($folder);
        $manifestPath = $folder . '/manifest.json';
        
        $name = $id;
        $description = '';
        $author = '';
        $preview = '';
        
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if ($manifest) {
                $name = $manifest['name'] ?? $id;
                $description = $manifest['description'] ?? '';
                $author = $manifest['author'] ?? '';
            }
        }
        
        $previewPath = '/templates/headers/' . $id . '/preview.png';
        if (file_exists(APP_ROOT . $previewPath)) {
            $preview = $previewPath;
        }
        
        $variants[] = [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'author' => $author,
            'preview' => $preview
        ];
    }
    
    return $variants;
}

/**
 * Получить список доступных шаблонов футера
 *
 * @return array Массив с ключами: id, name, description, author, preview
 */
function getFooterVariants(): array {
    $dir = APP_ROOT . '/templates/footers/';
    $variants = [];
    
    if (!is_dir($dir)) {
        return $variants;
    }
    
    $folders = glob($dir . '*', GLOB_ONLYDIR);
    foreach ($folders as $folder) {
        $id = basename($folder);
        $manifestPath = $folder . '/manifest.json';
        
        $name = $id;
        $description = '';
        $author = '';
        $preview = '';
        
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if ($manifest) {
                $name = $manifest['name'] ?? $id;
                $description = $manifest['description'] ?? '';
                $author = $manifest['author'] ?? '';
            }
        }
        
        $previewPath = '/templates/footers/' . $id . '/preview.png';
        if (file_exists(APP_ROOT . $previewPath)) {
            $preview = $previewPath;
        }
        
        $variants[] = [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'author' => $author,
            'preview' => $preview
        ];
    }
    
    return $variants;
}

/**
 * Возвращает пути к CSS и JS файлам для списка модулей
 * 
 * @param array $moduleIds Список ID модулей
 * @param array $settings Настройки сайта (опционально)
 * @param string $pageId ID страницы (для объединённых файлов)
 * @return array ['css' => [url], 'js' => [url]]
 */
function getModuleAssets($moduleIds, $settings = null, $pageId = 'home') {
    if ($settings === null) {
        $settings = getSettingsData();
    }
    
    $combine = $settings['assets_combine'] ?? false;
    
    // Если объединение включено — собираем в один файл
    if ($combine) {
        return getCombinedAssets($moduleIds, $settings, $pageId);
    }
    
    $minify = $settings['assets_minify'] ?? false;
    $exceptions = $settings['minify_exceptions'] ?? [];
    
    // Иначе — по отдельности
    $result = ['css' => [], 'js' => []];
    
    foreach ($moduleIds as $moduleId) {
        $cssUrl = getSingleAssetUrl($moduleId, 'css', $minify, $exceptions);
        if ($cssUrl) {
            $result['css'][] = $cssUrl;
        }
        
        $jsUrl = getSingleAssetUrl($moduleId, 'js', $minify, $exceptions);
        if ($jsUrl) {
            $result['js'][] = $jsUrl;
        }
    }
    
    return $result;
}

/**
 * Собирает все CSS/JS модулей страницы в один файл
 * Файл сохраняется в /cache/assets/{pageId}.css и /cache/assets/{pageId}.js
 *
 * @param array $moduleIds Список ID модулей на странице
 * @param array $settings Настройки сайта
 * @param string $pageId ID страницы (для имени файла)
 * @return array ['css' => [url], 'js' => [url]]
 */
function getSingleAssetUrl($moduleId, $type, $minify, $exceptions) {
    $ext = $type === 'css' ? 'css' : 'js';
    $basePath = '/' . $type . '/' . $moduleId;
    
    // Если минификация включена и модуль не в исключениях
    if ($minify && empty($exceptions[$moduleId][$type])) {
        $minFile = $basePath . '.min.' . $ext;
        if (file_exists(APP_ROOT . $minFile)) {
            return $minFile . '?v=' . filemtime(APP_ROOT . $minFile);
        }
    }
    
    // Обычный файл
    $file = $basePath . '.' . $ext;
    if (file_exists(APP_ROOT . $file)) {
        return $file . '?v=' . filemtime(APP_ROOT . $file);
    }
    
    return null;
}

/**
 * Собирает все CSS/JS модулей страницы в один файл
 * 
 * @param array $moduleIds Список ID модулей на странице
 * @param array $settings Настройки сайта
 * @return array ['css' => [url], 'js' => [url]]
 */
function getCombinedAssets($moduleIds, $settings, $pageId = 'home') {
    $minify = $settings['assets_minify'] ?? false;
    $exceptions = $settings['minify_exceptions'] ?? [];
    $cacheDir = APP_ROOT . '/cache/assets/';
    
    // Имя файла по ID страницы
    $cssFile = $cacheDir . $pageId . '.css';
    $jsFile = $cacheDir . $pageId . '.js';
    $cssUrl = '/cache/assets/' . $pageId . '.css';
    $jsUrl = '/cache/assets/' . $pageId . '.js';
    
    // Проверяем, есть ли уже собранные файлы
    $cssExists = file_exists($cssFile);
    $jsExists = file_exists($jsFile);
    
    if ($cssExists && $jsExists) {
        return [
            'css' => [$cssUrl . '?v=' . filemtime($cssFile)], 
            'js' => [$jsUrl . '?v=' . filemtime($jsFile)]
        ];
    }
    
    // Собираем CSS
    $cssContent = '';
    foreach ($moduleIds as $moduleId) {
        // Проверяем исключения для CSS
        if (!empty($exceptions[$moduleId]['css'])) {
            continue;
        }
        
        if ($minify && file_exists(APP_ROOT . '/css/' . $moduleId . '.min.css')) {
            $file = '/css/' . $moduleId . '.min.css';
        } else {
            $file = '/css/' . $moduleId . '.css';
        }
        $path = APP_ROOT . $file;
        if (file_exists($path)) {
            $cssContent .= "\n/* === " . $moduleId . " === */\n";
            $cssContent .= file_get_contents($path);
        }
    }
    
    // Собираем JS
    $jsContent = '';
    foreach ($moduleIds as $moduleId) {
        // Проверяем исключения для JS
        if (!empty($exceptions[$moduleId]['js'])) {
            continue;
        }
        
        if ($minify && file_exists(APP_ROOT . '/js/' . $moduleId . '.min.js')) {
            $file = '/js/' . $moduleId . '.min.js';
        } else {
            $file = '/js/' . $moduleId . '.js';
        }
        $path = APP_ROOT . $file;
        if (file_exists($path)) {
            $jsContent .= "\n/* === " . $moduleId . " === */\n";
            $jsContent .= file_get_contents($path);
        }
    }
    
    // Сохраняем
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    if (!empty($cssContent)) {
        file_put_contents($cssFile, $cssContent);
    }
    if (!empty($jsContent)) {
        file_put_contents($jsFile, $jsContent);
    }
    
    return [
        'css' => !empty($cssContent) ? [$cssUrl . '?v=' . time()] : [],
        'js' => !empty($jsContent) ? [$jsUrl . '?v=' . time()] : []
    ];
}