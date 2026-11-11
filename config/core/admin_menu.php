<?php
/**
 * Формирование меню админки (вкладок)
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

/**
 * Возвращает массив вкладок для сайдбара.
 *
 * @return array [
 *   'modules' => [...],
 *   'system'  => [...],
 *   'all'     => [...]
 * ]
 */
function get_admin_tabs(): array {
    $modulesTabs = [];
    $systemTabs  = [];

    // Группа 1: Модули сайта
    $modulesTabs[] = ['id' => 'config_vars', 'title' => 'Настройки сайта', 'icon' => 'icon-settings', 'visible' => true];
    $modulesTabs[] = ['id' => 'pages', 'title' => 'Страницы', 'icon' => 'icon-file-text', 'visible' => true];
    $modulesTabs[] = ['id' => 'modules', 'title' => 'Управление модулями', 'icon' => 'icon-box', 'visible' => true];
    $modulesTabs[] = ['id' => 'menu', 'title' => 'Меню сайта', 'icon' => 'icon-menu', 'visible' => true];
    
    // Группа 2: Управление системой
    $systemTabs[] = ['id' => 'plugins', 'title' => 'Плагины', 'icon' => 'icon-puzzle'];
    $systemTabs[] = ['id' => 'security', 'title' => 'Безопасность', 'icon' => 'icon-shield'];
    $systemTabs[] = ['id' => 'docs', 'title' => 'Документация', 'icon' => 'icon-book-open'];

    // Добавляем вкладки от плагинов
    if (!empty($GLOBALS['cms_admin_tabs'])) {
        foreach ($GLOBALS['cms_admin_tabs'] as $pluginTab) {
            if (($pluginTab['group'] ?? 'system') === 'modules') {
                $modulesTabs[] = $pluginTab;
            } else {
                $systemTabs[] = $pluginTab;
            }
        }
    }

    $allTabs = array_merge($modulesTabs, $systemTabs);

    return [
        'modules' => $modulesTabs,
        'system'  => $systemTabs,
        'all'     => $allTabs
    ];
}