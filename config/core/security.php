<?php

/**
 * =========================================================================
 * Безопасность кода: экранирование вывода, запись файлов, ZIP, CSRF, ID.
 * =========================================================================
 *
 * Файл содержит только чистые помощники. Он не стартует сессию и не
 * отправляет заголовки, поэтому его безопасно подключать на фронте,
 * в админке и в CLI-скриптах.
 *
 * Bootstrap (сессия, CSRF-токен, константы) остаётся в config/config.php,
 * аутентификация — в admin_auth.php, журнал — в log.php.
 */

if (!defined('APP_ROOT')) { define('APP_ROOT', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')); }

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
 * Проверяет архив на ZIP Slip — небезопасные имена файлов.
 *
 * Блокирует: .., абсолютные пути (/...), Windows-пути (C:\...), NUL-байты.
 * Возвращает имя первого небезопасного файла или null, если всё чисто.
 *
 * @param ZipArchive $zip Открытый архив
 * @return string|null Имя небезопасного файла или null
 */
function detectZipSlip(ZipArchive $zip): ?string {
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if ($stat === false) continue;

        $entryName = $stat['name'];
        $normalized = str_replace('\\', '/', $entryName);

        if (
            strpos($normalized, '..') !== false ||
            strpos($normalized, "\0") !== false ||
            strpos($normalized, '/') === 0 ||
            preg_match('#^[a-zA-Z]:#', $normalized) === 1
        ) {
            return $entryName;
        }
    }

    return null;
}

/**
 * Проверяет CSRF-токен из запроса.
 *
 * Сравнение через hash_equals, поэтому токен должен быть строкой.
 * Массив вроде csrf_token[] не вызовет TypeError, а вернёт false.
 *
 * @param string|null $token Токен из POST/GET
 * @return bool true, если токен совпадает с токеном сессии
 */
function verifyCsrfToken(?string $token): bool {
    $session = $_SESSION['csrf_token'] ?? '';
    return $session !== '' && is_string($token) && hash_equals($session, $token);
}

/**
 * Проверяет идентификатор (страница, меню, модуль, плагин, запись).
 *
 * Разрешены латиница, цифры, дефис и подчёркивание.
 *
 * @param string $id Идентификатор для проверки
 * @return bool true, если ID валиден
 */
function isValidId(string $id): bool {
    return $id !== '' && preg_match('/^[a-z0-9\-_]+$/i', $id) === 1;
}
