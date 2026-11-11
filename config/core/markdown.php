<?php
/**
 * Markdown-рендерер ядра.
 *
 * Без внешних библиотек: поддерживается подмножество, которым пользуются
 * README плагинов и документация в docs/. Сначала текст экранируется, затем
 * применяется разметка, поэтому сырой HTML из файла в вывод не попадает.
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

/**
 * Рендерит Markdown в безопасный HTML.
 *
 * @param string $text Исходный Markdown
 * @return array ['html' => string, 'toc' => array]
 */
function markdown_render(string $text): array {
    $text = str_replace(["\r\n", "\r", "\t"], ["\n", "\n", '    '], $text);
    $lines = explode("\n", $text);
    $count = count($lines);
    $html = [];
    $toc = [];
    $used = [];
    $i = 0;

    while ($i < $count) {
        $line = rtrim($lines[$i]);
        $trimmed = trim($line);

        if ($trimmed === '') {
            $i++;
            continue;
        }

        // Блок кода в тройных обратных кавычках или тильдах.
        if (preg_match('/^(`{3,}|~{3,})\s*([A-Za-z0-9_+#.-]*)\s*$/', $trimmed, $m)) {
            $fence = $m[1];
            $lang = strtolower($m[2]);
            $code = [];
            $i++;

            while ($i < $count && rtrim($lines[$i]) !== $fence) {
                $code[] = $lines[$i];
                $i++;
            }

            $i++;
            $class = $lang !== '' ? ' class="language-' . markdown_escape($lang) . '"' : '';
            $html[] = '<pre class="docs-pre"><code' . $class . '>'
                . markdown_escape(implode("\n", $code)) . '</code></pre>';
            continue;
        }

        // Заголовки.
        if (preg_match('/^(#{1,6})\s+(.+?)\s*#*$/', $trimmed, $m)) {
            $level = strlen($m[1]);
            $id = markdown_slug($m[2], $used);
            $html[] = '<h' . $level . ' id="' . markdown_escape($id) . '" class="docs-h docs-h' . $level . '">'
                . markdown_inline($m[2]) . '</h' . $level . '>';

            if ($level === 2 || $level === 3) {
                $toc[] = [
                    'level' => $level,
                    'id'    => $id,
                    'title' => trim(strip_tags(markdown_inline($m[2]))),
                ];
            }

            $i++;
            continue;
        }

        // Горизонтальная линия.
        if (preg_match('/^([-*_])\s*(\1\s*){2,}$/', $trimmed)) {
            $html[] = '<hr class="docs-hr">';
            $i++;
            continue;
        }

        // Таблица: строка с | и строка-разделитель под ней.
        if (strpos($trimmed, '|') !== false
            && $i + 1 < $count
            && markdown_is_table_separator($lines[$i + 1])) {
            $header = markdown_split_row($trimmed);
            $align = markdown_table_align($lines[$i + 1]);
            $i += 2;
            $rows = [];

            while ($i < $count && trim($lines[$i]) !== '' && strpos($lines[$i], '|') !== false) {
                $rows[] = markdown_split_row($lines[$i]);
                $i++;
            }

            $html[] = markdown_table($header, $align, $rows);
            continue;
        }

        // Цитата.
        if (preg_match('/^>\s?(.*)$/', $trimmed, $m)) {
            $inner = [];

            while ($i < $count && preg_match('/^>\s?(.*)$/', rtrim($lines[$i]), $m2)) {
                $inner[] = $m2[1];
                $i++;
            }

            $nested = markdown_render(implode("\n", $inner));
            $html[] = '<blockquote class="docs-quote">' . $nested['html'] . '</blockquote>';
            continue;
        }

        // Списки: собираем все пункты блока, рисуем рекурсивно по отступам.
        if (markdown_list_marker($line) !== null) {
            $items = [];
            markdown_collect_list($lines, $i, $items);
            $index = 0;

            while ($index < count($items)) {
                $html[] = markdown_list($items, $index, $items[$index]['indent']);
            }

            continue;
        }

        // Абзац: до пустой строки или до начала следующего блока.
        $paragraph = [];

        while ($i < $count) {
            $current = rtrim($lines[$i]);

            if (trim($current) === '') {
                break;
            }

            if ($paragraph && markdown_starts_block($lines, $i)) {
                break;
            }

            $paragraph[] = trim($current);
            $i++;
        }

        $html[] = '<p class="docs-p">' . markdown_inline(implode(' ', $paragraph)) . '</p>';
    }

    return ['html' => implode("\n", $html), 'toc' => $toc];
}

/**
 * Экранирует текст для вывода в HTML.
 *
 * @param string $text Текст
 * @return string
 */
function markdown_escape(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Разметка внутри строки: код, картинки, ссылки, выделения.
 *
 * @param string $text Строка без разметки блоков
 * @return string HTML
 */
function markdown_inline(string $text): string {
    $text = markdown_escape($text);
    $store = [];

    // Инлайн-код защищаем от остальных правил.
    $text = preg_replace_callback('/`([^`\n]+)`/', function ($m) use (&$store) {
        $store[] = '<code class="docs-inline-code">' . $m[1] . '</code>';
        return "\x02" . (count($store) - 1) . "\x03";
    }, $text);

    // Картинки: файлы из плагинов закрыты от веба, поэтому оставляем alt-текст.
    $text = preg_replace('/!\[([^\]]*)\]\([^()]*(?:\([^()]*\)[^()]*)*\)/', '$1', $text);

    // Ссылки: только безопасные схемы, остальное — просто текст.
    $text = preg_replace_callback(
        '/\[([^\]]+)\]\(\s*([^()\s]+(?:\([^()\s]*\)[^()\s]*)*)(?:\s+"[^"]*")?\s*\)/',
        function ($m) {
            $url = markdown_safe_url(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'));

            if ($url === null) {
                return $m[1];
            }

            return '<a class="docs-link" href="' . markdown_escape($url) . '"'
                . ' target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
        },
        $text
    );

    $text = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(?=\S)(.+?)(?<=\S)__/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/(?<![\p{L}\p{N}_])_(?=\S)(.+?)(?<=\S)_(?![\p{L}\p{N}_])/su', '<em>$1</em>', $text);
    $text = preg_replace('/\*(?=\S)(.+?)(?<=\S)\*/s', '<em>$1</em>', $text);
    $text = preg_replace('/~~(?=\S)(.+?)(?<=\S)~~/s', '<del>$1</del>', $text);

    if ($store) {
        $text = preg_replace_callback('/\x02(\d+)\x03/', function ($m) use ($store) {
            $index = (int)$m[1];

            return $store[$index] ?? '';
        }, $text);
    }

    return $text;
}

/**
 * Проверяет, допустим ли адрес ссылки.
 *
 * @param string $url Адрес
 * @return string|null Адрес или null, если схема не разрешена
 */
function markdown_safe_url(string $url): ?string {
    $url = trim(str_replace(["\n", "\r", "\t"], '', $url));

    if ($url === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    if (preg_match('#^mailto:[^@\s]+@[^@\s]+$#i', $url)) {
        return $url;
    }

    if (preg_match('#^(/|\#|\./|\.\./)#', $url)) {
        return $url;
    }

    return null;
}

/**
 * Строит уникальный id для заголовка.
 *
 * @param string $title Заголовок
 * @param array  $used  Уже занятые id
 * @return string
 */
function markdown_slug(string $title, array &$used): string {
    $slug = mb_strtolower(strip_tags(markdown_inline($title)), 'UTF-8');
    $slug = preg_replace('/[^\p{L}\p{N}\s_-]+/u', '', $slug);
    $slug = trim((string)preg_replace('/[\s_]+/u', '-', (string)$slug), '-');

    if ($slug === '') {
        $slug = 'section';
    }

    $base = $slug;
    $n = 2;

    while (isset($used[$slug])) {
        $slug = $base . '-' . $n;
        $n++;
    }

    $used[$slug] = true;

    return $slug;
}

/**
 * Начинается ли на этой строке новый блок.
 *
 * @param array $lines Все строки
 * @param int   $index Индекс строки
 * @return bool
 */
function markdown_starts_block(array $lines, int $index): bool {
    $line = rtrim($lines[$index]);
    $trimmed = trim($line);

    if ($trimmed === '') {
        return true;
    }

    if (preg_match('/^(`{3,}|~{3,})/', $trimmed)) {
        return true;
    }

    if (preg_match('/^#{1,6}\s+/', $trimmed)) {
        return true;
    }

    if (preg_match('/^([-*_])\s*(\1\s*){2,}$/', $trimmed)) {
        return true;
    }

    if (preg_match('/^>\s?/', $trimmed)) {
        return true;
    }

    if (markdown_list_marker($line) !== null) {
        return true;
    }

    return strpos($trimmed, '|') !== false
        && isset($lines[$index + 1])
        && markdown_is_table_separator($lines[$index + 1]);
}

/**
 * Разбирает маркер списка.
 *
 * @param string $line Строка
 * @return array|null ['indent' => int, 'ordered' => bool, 'text' => string]
 */
function markdown_list_marker(string $line): ?array {
    if (!preg_match('/^(\s*)([-*+]|\d{1,9}[.)])\s+(.*)$/', $line, $m)) {
        return null;
    }

    return [
        'indent'  => strlen($m[1]),
        'ordered' => ctype_digit($m[2][0]),
        'text'    => $m[3],
    ];
}

/**
 * Собирает строки одного списка: пункты, вложенность и переносы.
 *
 * @param array $lines Все строки
 * @param int   $index Текущий индекс, сдвигается до конца списка
 * @param array $items Накопленные пункты
 * @return void
 */
function markdown_collect_list(array $lines, int &$index, array &$items): void {
    $count = count($lines);

    while ($index < $count) {
        $line = rtrim($lines[$index]);
        $marker = markdown_list_marker($line);

        if ($marker !== null) {
            $items[] = $marker;
            $index++;
            continue;
        }

        if (trim($line) === '') {
            // Пустая строка допустима между пунктами.
            $next = $index;

            while ($next < $count && trim($lines[$next]) === '') {
                $next++;
            }

            if ($next < $count && markdown_list_marker(rtrim($lines[$next])) !== null) {
                $index = $next;
                continue;
            }

            break;
        }

        // Перенос строки внутри пункта.
        if ($items && strlen($line) - strlen(ltrim($line)) > 0) {
            $last = count($items) - 1;
            $items[$last]['text'] .= ' ' . trim($line);
            $index++;
            continue;
        }

        break;
    }
}

/**
 * Рисует список с вложенностью.
 *
 * @param array $items Все пункты
 * @param int   $index Текущий пункт
 * @param int   $indent Отступ уровня
 * @return string HTML
 */
function markdown_list(array $items, int &$index, int $indent): string {
    $ordered = (bool)$items[$index]['ordered'];
    $tag = $ordered ? 'ol' : 'ul';
    $html = '<' . $tag . ' class="docs-list">';
    $total = count($items);

    while ($index < $total) {
        $item = $items[$index];

        if ($item['indent'] < $indent) {
            break;
        }

        if ($item['indent'] > $indent) {
            $html .= markdown_list($items, $index, $item['indent']);
            continue;
        }

        if ((bool)$item['ordered'] !== $ordered) {
            break;
        }

        $index++;
        $html .= '<li>' . markdown_list_item($item['text']);

        if ($index < $total && $items[$index]['indent'] > $indent) {
            $html .= markdown_list($items, $index, $items[$index]['indent']);
        }

        $html .= '</li>';
    }

    return $html . '</' . $tag . '>';
}

/**
 * Текст пункта списка: чекбокс или обычная строка.
 *
 * @param string $text Текст пункта
 * @return string HTML
 */
function markdown_list_item(string $text): string {
    if (preg_match('/^\[( |x|X)\]\s+(.*)$/', $text, $m)) {
        $checked = strtolower($m[1]) === 'x' ? ' checked' : '';

        return '<span class="docs-task"><input type="checkbox" disabled' . $checked . '> '
            . markdown_inline($m[2]) . '</span>';
    }

    return markdown_inline($text);
}

/**
 * Похожа ли строка на разделитель таблицы.
 *
 * @param string $line Строка
 * @return bool
 */
function markdown_is_table_separator(string $line): bool {
    $line = trim($line);

    if (strpos($line, '-') === false) {
        return false;
    }

    return (bool)preg_match('/^\|?\s*:?-{2,}:?\s*(\|\s*:?-{2,}:?\s*)*\|?$/', $line);
}

/**
 * Делит строку таблицы на ячейки.
 *
 * @param string $line Строка
 * @return array
 */
function markdown_split_row(string $line): array {
    $line = trim($line);
    $cells = preg_split('/(?<!\\\\)\|/', $line);

    if (!is_array($cells)) {
        return [];
    }

    if ($cells && trim($cells[0]) === '') {
        array_shift($cells);
    }

    if ($cells && trim($cells[count($cells) - 1]) === '') {
        array_pop($cells);
    }

    $result = [];

    foreach ($cells as $cell) {
        $result[] = trim(str_replace('\\|', '|', $cell));
    }

    return $result;
}

/**
 * Выравнивание колонок из строки-разделителя.
 *
 * @param string $line Строка-разделитель
 * @return array Список 'left' | 'center' | 'right' | ''
 */
function markdown_table_align(string $line): array {
    $result = [];

    foreach (markdown_split_row($line) as $cell) {
        $cell = trim($cell);
        $left = strpos($cell, ':') === 0;
        $right = substr($cell, -1) === ':';

        if ($left && $right) {
            $result[] = 'center';
        } elseif ($right) {
            $result[] = 'right';
        } elseif ($left) {
            $result[] = 'left';
        } else {
            $result[] = '';
        }
    }

    return $result;
}

/**
 * Рисует таблицу.
 *
 * @param array $header Ячейки шапки
 * @param array $align  Выравнивание колонок
 * @param array $rows   Строки
 * @return string HTML
 */
function markdown_table(array $header, array $align, array $rows): string {
    $html = '<div class="docs-table-wrap"><table class="docs-table">';
    $html .= '<thead><tr>';

    foreach ($header as $column => $cell) {
        $html .= '<th' . markdown_align_attr($align[$column] ?? '') . '>'
            . markdown_inline($cell) . '</th>';
    }

    $html .= '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>';

        foreach ($header as $column => $cell) {
            $html .= '<td' . markdown_align_attr($align[$column] ?? '') . '>'
                . markdown_inline($row[$column] ?? '') . '</td>';
        }

        $html .= '</tr>';
    }

    return $html . '</tbody></table></div>';
}

/**
 * Атрибут выравнивания ячейки.
 *
 * @param string $align left|center|right|''
 * @return string
 */
function markdown_align_attr(string $align): string {
    if ($align === 'center' || $align === 'right') {
        return ' class="docs-cell-' . $align . '"';
    }

    return '';
}
