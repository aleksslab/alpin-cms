<?php
/**
 * Документация в админке: поиск README плагинов и файлов из docs/.
 *
 * Модуль админки только рисует, вся работа с путями и чтением — здесь.
 * Файлы читаются по белому списку каталогов, размер ограничен.
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

require_once __DIR__ . '/markdown.php';

if (!defined('DOCS_MAX_BYTES')) {
    /** Максимальный размер одного документа, байт. */
    define('DOCS_MAX_BYTES', 300 * 1024);
}

/**
 * Каталоги с документацией.
 *
 * @return array Источник => абсолютный путь
 */
function docs_roots(): array {
    return [
        'plugin' => PLUGINS_DIR,
        'user'   => APP_ROOT . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'for-users',
        'dev'    => APP_ROOT . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'for-developers',
    ];
}

/**
 * Ищет README.md в каталоге без учёта регистра.
 *
 * @param string $dir Каталог
 * @return string|null Абсолютный путь или null
 */
function docs_readme_in(string $dir): ?string {
    $realDir = realpath($dir);

    if ($realDir === false || !is_dir($realDir)) {
        return null;
    }

    $entries = @scandir($realDir);

    if (!is_array($entries)) {
        return null;
    }

    foreach ($entries as $entry) {
        if (strcasecmp((string)$entry, 'README.md') !== 0) {
            continue;
        }

        $path = $realDir . DIRECTORY_SEPARATOR . $entry;

        return is_file($path) ? $path : null;
    }

    return null;
}

/**
 * README установленного плагина.
 *
 * @param string $id ID плагина
 * @return string|null Абсолютный путь или null
 */
function docs_plugin_readme_path(string $id): ?string {
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $id)) {
        return null;
    }

    $root = realpath(PLUGINS_DIR);

    if ($root === false) {
        return null;
    }

    $dir = realpath($root . DIRECTORY_SEPARATOR . $id);

    if ($dir === false || strpos($dir, $root) !== 0) {
        return null;
    }

    return docs_readme_in($dir);
}

/**
 * Собирает список документов для навигации.
 *
 * @return array ['plugins' => array, 'user' => array, 'dev' => array]
 */
function docs_catalog(): array {
    $result = ['plugins' => [], 'user' => [], 'dev' => []];
    $registry = function_exists('getPluginsData') ? getPluginsData() : [];
    $root = realpath(PLUGINS_DIR);
    $entries = $root !== false ? @scandir($root) : [];

    foreach ((array)$entries as $entry) {
        if ($entry === '.' || $entry === '..' || $entry === false) {
            continue;
        }

        $dir = realpath($root . DIRECTORY_SEPARATOR . $entry);

        if ($dir === false || !is_dir($dir) || strpos($dir, $root) !== 0
            || !preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', (string)$entry)) {
            continue;
        }

        $manifest = [];
        $manifestPath = $dir . DIRECTORY_SEPARATOR . 'plugin.json';

        if (is_file($manifestPath)) {
            $decoded = json_decode((string)@file_get_contents($manifestPath), true);

            if (is_array($decoded)) {
                $manifest = $decoded;
            }
        }

        // Каталог без манифеста и без записи в реестре — не плагин.
        if (!$manifest && empty($registry[$entry])) {
            continue;
        }

        $saved = is_array($registry[$entry] ?? null) ? $registry[$entry] : [];

        $result['plugins'][] = [
            'key'     => 'plugin:' . $entry,
            'id'      => (string)$entry,
            'title'   => trim((string)($manifest['name'] ?? $saved['name'] ?? $entry)),
            'version' => (string)($manifest['version'] ?? $saved['version'] ?? ''),
            'active'  => !empty($saved['active']),
            'path'    => docs_readme_in($dir),
        ];
    }

    usort($result['plugins'], function ($a, $b) {
        return strnatcasecmp($a['title'], $b['title']);
    });

    $roots = docs_roots();

    foreach (['user', 'dev'] as $source) {
        $dir = $roots[$source];
        $files = is_dir($dir) ? @scandir($dir) : [];

        foreach ((array)$files as $file) {
            $file = (string)$file;

            if (!preg_match('/^[A-Za-z0-9_-]+\.md$/', $file)) {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $file;
            $text = (string)@file_get_contents($path, false, null, 0, 2048);

            $result[$source][] = [
                'key'   => $source . ':' . substr($file, 0, -3),
                'id'    => substr($file, 0, -3),
                'title' => docs_first_heading($text, substr($file, 0, -3)),
                'path'  => is_file($path) ? $path : null,
            ];
        }

        usort($result[$source], function ($a, $b) {
            return strnatcasecmp($a['title'], $b['title']);
        });
    }

    return $result;
}

/**
 * Заголовок первого уровня из текста.
 *
 * @param string $text     Markdown
 * @param string $fallback Запасной вариант
 * @return string
 */
function docs_first_heading(string $text, string $fallback): string {
    if (preg_match('/^#\s+(.+?)\s*#*\s*$/m', $text, $m)) {
        return trim(strip_tags(markdown_inline($m[1])));
    }

    return $fallback;
}

/**
 * Находит документ по ключу вида «источник:id».
 *
 * @param string $key Ключ из навигации
 * @return array|null ['title', 'source', 'meta', 'markdown', 'truncated']
 */
function docs_resolve(string $key): ?array {
    $key = trim($key);
    $pos = strpos($key, ':');

    if ($pos === false) {
        return null;
    }

    $source = substr($key, 0, $pos);
    $id = substr($key, $pos + 1);

    if ($source === 'plugin') {
        $path = docs_plugin_readme_path($id);

        if ($path === null) {
            return null;
        }

        $manifest = [];
        $manifestPath = dirname($path) . DIRECTORY_SEPARATOR . 'plugin.json';

        if (is_file($manifestPath)) {
            $decoded = json_decode((string)@file_get_contents($manifestPath), true);

            if (is_array($decoded)) {
                $manifest = $decoded;
            }
        }

        $registry = function_exists('getPluginsData') ? getPluginsData() : [];
        $saved = is_array($registry[$id] ?? null) ? $registry[$id] : [];

        $meta = [];

        if (!empty($manifest['version'])) {
            $meta[] = 'v' . $manifest['version'];
        }

        $author = trim((string)($manifest['author'] ?? $saved['author'] ?? ''));

        if ($author !== '') {
            $meta[] = $author;
        }

        $meta[] = !empty($saved['active']) ? 'включён' : 'выключен';

        return docs_build(
            $key,
            'plugin',
            $path,
            trim((string)($manifest['name'] ?? $saved['name'] ?? $id)),
            implode(' · ', $meta)
        );
    }

    if ($source === 'user' || $source === 'dev') {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
            return null;
        }

        $roots = docs_roots();
        $root = realpath($roots[$source]);

        if ($root === false) {
            return null;
        }

        $path = realpath($root . DIRECTORY_SEPARATOR . $id . '.md');

        if ($path === false || strpos($path, $root) !== 0 || !is_file($path)) {
            return null;
        }

        return docs_build(
            $key,
            $source,
            $path,
            $id,
            $source === 'user' ? 'Руководство пользователя' : 'Документация для разработчиков'
        );
    }

    return null;
}

/**
 * Читает документ и собирает данные для вывода.
 *
 * @param string $key      Ключ
 * @param string $source   Источник
 * @param string $path     Абсолютный путь
 * @param string $fallback Заголовок, если в файле нет H1
 * @param string $meta     Строка метаданных
 * @return array
 */
function docs_build(string $key, string $source, string $path, string $fallback, string $meta): array {
    $truncated = false;
    $markdown = docs_read_file($path, $truncated);

    return [
        'key'       => $key,
        'source'    => $source,
        'title'     => docs_first_heading($markdown, $fallback),
        'meta'      => $meta,
        'markdown'  => $markdown,
        'truncated' => $truncated,
    ];
}

/**
 * Читает файл с ограничением размера.
 *
 * @param string $path      Абсолютный путь
 * @param bool   $truncated true, если файл обрезан
 * @return string
 */
function docs_read_file(string $path, bool &$truncated): string {
    $truncated = false;
    $size = @filesize($path);

    if ($size !== false && $size > DOCS_MAX_BYTES) {
        $truncated = true;
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        $data = (string)fread($handle, DOCS_MAX_BYTES);
        fclose($handle);

        return $data;
    }

    $data = @file_get_contents($path);

    return $data === false ? '' : $data;
}

/**
 * Переписывает относительные ссылки на .md в ссылки раздела «Документация».
 *
 * В документации приняты ссылки вида «ARCHITECTURE.md» и
 * «../for-users/FAQ.md»; как файлы они из админки недоступны, поэтому
 * известные имена превращаем в index.php?tab=docs&doc=...
 *
 * @param string     $html    HTML после markdown_render()
 * @param string     $source  Источник текущего документа
 * @param array|null $catalog Готовый каталог документов
 * @return string
 */
function docs_rewrite_links(string $html, string $source, ?array $catalog = null): string {
    $catalog = $catalog ?? docs_catalog();
    $map = [];

    foreach (['user', 'dev'] as $group) {
        foreach ((array)($catalog[$group] ?? []) as $item) {
            $name = strtolower($item['id']);

            if (!isset($map[$name])) {
                $map[$name] = $item['key'];
            }
        }
    }

    return (string)preg_replace_callback(
        '#<a class="docs-link" href="([^"]+)"#',
        function ($m) use ($map, $source) {
            $href = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');

            if (!preg_match('~^(?:\.\.?/)?(?:[A-Za-z0-9_-]+/)*([A-Za-z0-9_-]+)\.md(#.*)?$~i', $href, $parts)) {
                return $m[0];
            }

            $name = strtolower($parts[1]);

            // README плагина ссылается на свои подстраницы — их в разделе нет.
            if (!isset($map[$name]) || $source === 'plugin') {
                return $m[0];
            }

            $url = 'index.php?tab=docs&doc=' . urlencode($map[$name]) . ($parts[2] ?? '');

            return '<a class="docs-link" href="' . markdown_escape($url) . '"';
        },
        $html
    );
}
