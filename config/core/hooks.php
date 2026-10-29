<?php
/**
 * Система хуков (Actions, Filters, Routes)
 *
 * Обеспечивает расширяемость ядра без изменения его исходного кода.
 * Позволяет плагинам внедряться в работу CMS.
 */

// Глобальные хранилища
$GLOBALS['cms_actions'] = [];
$GLOBALS['cms_filters'] = [];
$GLOBALS['cms_routes']  = [];

/**
 * Валидация имени хука (только латиница, цифры, дефис, подчеркивание).
 * Защита от инъекций и случайных ошибок.
 *
 * @param string $hook
 * @return bool
 */
function _is_valid_hook_name(string $hook): bool {
    return preg_match('/^[a-z0-9_\-]+$/i', $hook) === 1;
}

/**
 * Добавляет обработчик события (действия).
 *
 * @param string   $hook     Имя события
 * @param callable $callback Функция-обработчик
 * @param int      $priority Приоритет (меньше = раньше)
 * @return void
 */
function add_action(string $hook, callable $callback, int $priority = 10): void {
    if (!_is_valid_hook_name($hook)) return;
    $GLOBALS['cms_actions'][$hook][$priority][] = $callback;
}

/**
 * Выполняет событие (действие).
 *
 * @param string $hook Имя события
 * @param mixed  ...$args Аргументы
 * @return void
 */
function do_action(string $hook, ...$args): void {
    if (!_is_valid_hook_name($hook) || empty($GLOBALS['cms_actions'][$hook])) {
        return;
    }
    
    ksort($GLOBALS['cms_actions'][$hook]);
    
    foreach ($GLOBALS['cms_actions'][$hook] as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            call_user_func_array($callback, $args);
        }
    }
}

/**
 * Добавляет фильтр (модификатор данных).
 *
 * @param string   $hook     Имя фильтра
 * @param callable $callback Функция-обработчик
 * @param int      $priority Приоритет
 * @return void
 */
function add_filter(string $hook, callable $callback, int $priority = 10): void {
    if (!_is_valid_hook_name($hook)) return;
    $GLOBALS['cms_filters'][$hook][$priority][] = $callback;
}

/**
 * Применяет фильтры к значению.
 *
 * @param string $hook Имя фильтра
 * @param mixed  $value Исходное значение
 * @param mixed  ...$args Дополнительные аргументы
 * @return mixed
 */
function apply_filters(string $hook, $value, ...$args) {
    if (!_is_valid_hook_name($hook) || empty($GLOBALS['cms_filters'][$hook])) {
        return $value;
    }
    
    ksort($GLOBALS['cms_filters'][$hook]);
    
    foreach ($GLOBALS['cms_filters'][$hook] as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            $value = call_user_func_array($callback, array_merge([$value], $args));
        }
    }
    
    return $value;
}

/**
 * Регистрирует кастомный маршрут.
 * 
 * @param string   $pattern  Путь (например, '/updates')
 * @param callable $callback Функция-обработчик
 * @return void
 */
function add_route(string $pattern, callable $callback): void {
    // Валидация маршрута (только безопасные символы URL)
    $clean = trim($pattern, '/');
    if ($clean === '' || !preg_match('/^[a-z0-9_\-\.\/]+$/i', $clean)) {
        return; // Опасный маршрут — игнорируем
    }
    
    $GLOBALS['cms_routes'][] = [
        'pattern'  => $clean,
        'callback' => $callback
    ];
}

/**
 * Ищет кастомный маршрут и выполняет его.
 *
 * @param string $slug Текущий запрошенный путь
 * @return bool true, если выполнен
 */
function dispatch_custom_route(string $slug): bool {
    $slug = trim($slug, '/');
    foreach ($GLOBALS['cms_routes'] as $route) {
        if ($route['pattern'] === $slug) {
            call_user_func($route['callback']);
            return true;
        }
    }
    return false;
}

/**
 * Добавляет вкладку в сайдбар админки.
 * Используется плагинами для добавления своих страниц.
 *
 * @param string $id    ID вкладки (например, 'updates')
 * @param string $title Заголовок в меню
 * @param string $icon  Класс иконки Lucide (например, 'icon-refresh-cw')
 * @param string $group Группа ('modules' или 'system'). По умолчанию 'system'.
 * @return void
 */
function add_admin_tab(string $id, string $title, string $icon = 'icon-puzzle', string $group = 'system'): void {
    // Валидация ID
    if (!preg_match('/^[a-z0-9\-_]+$/i', $id)) {
        return;
    }

    if (!isset($GLOBALS['cms_admin_tabs'])) {
        $GLOBALS['cms_admin_tabs'] = [];
    }

    $GLOBALS['cms_admin_tabs'][] = [
        'id'      => $id,
        'title'   => $title,
        'icon'    => $icon,
        'group'   => $group, // 'modules' или 'system'
        'visible' => true
    ];
}