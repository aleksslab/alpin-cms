<?php
/**
 * Реестр действий логирования
 *
 * Ядро хранит только зарегистрированные действия и их группу для UI.
 * Плагины регистрируют свои действия через register_log_action().
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

/**
 * Регистрирует действие в реестре логирования.
 *
 * @param string $action Ключ действия (латиница, цифры, дефис, подчёркивание)
 * @param string $label  Человекочитаемое название
 * @param string $group  Группа для UI
 * @param string $icon   Класс иконки без префикса icon-
 * @return void
 */
function register_log_action(string $action, string $label, string $group = 'Прочее', string $icon = 'activity'): void {
    if (!_is_valid_hook_name($action)) {
        return;
    }

    if (!isset($GLOBALS['cms_log_actions'])) {
        $GLOBALS['cms_log_actions'] = [];
    }

    $GLOBALS['cms_log_actions'][$action] = [
        'label' => (string)$label,
        'group' => $group !== '' ? $group : 'Прочее',
        'icon'  => $icon !== '' ? $icon : 'activity',
    ];
}

/**
 * Возвращает плоский список действий: [action => label].
 *
 * @return array
 */
function get_log_actions(): array {
    $result = [];

    foreach ($GLOBALS['cms_log_actions'] ?? [] as $action => $meta) {
        $result[$action] = $meta['label'];
    }

    return $result;
}

/**
 * Возвращает действия, сгруппированные для UI:
 * [group => ['icon' => ..., 'actions' => [action => label]]].
 * Порядок групп — порядок регистрации.
 *
 * @return array
 */
function get_log_action_groups(): array {
    $groups = [];

    foreach ($GLOBALS['cms_log_actions'] ?? [] as $action => $meta) {
        $group = $meta['group'];

        if (!isset($groups[$group])) {
            $groups[$group] = ['icon' => $meta['icon'], 'actions' => []];
        }

        $groups[$group]['actions'][$action] = $meta['label'];
    }

    return $groups;
}

/**
 * Регистрирует действия ядра. Вызывается один раз при загрузке.
 *
 * @return void
 */
function _register_core_log_actions(): void {
    $coreActions = [
        'Страницы' => ['icon' => 'file-text', 'actions' => [
            'page_save'         => 'Сохранение страницы',
            'page_trash'        => 'Перемещение страницы в корзину',
            'page_delete'       => 'Окончательное удаление страницы',
            'page_clone'        => 'Клонирование страницы',
            'page_set_home'     => 'Назначение главной страницы',
        ]],
        'Меню' => ['icon' => 'menu', 'actions' => [
            'menu_save'         => 'Сохранение меню',
            'menu_trash'        => 'Перемещение меню в корзину',
            'menu_delete'       => 'Окончательное удаление меню',
            'menu_set_main'     => 'Назначение главного меню',
        ]],
        'Настройки' => ['icon' => 'settings', 'actions' => [
            'settings_save'     => 'Сохранение настроек сайта',
            'security_save'     => 'Сохранение настроек безопасности',
        ]],
        'Файлы' => ['icon' => 'folder', 'actions' => [
            'file_upload'       => 'Загрузка файлов',
            'file_delete'       => 'Удаление файлов',
            'file_edit'         => 'Редактирование файлов',
            'file_rename'       => 'Переименование файлов',
            'file_chmod'        => 'Изменение прав доступа',
            'file_create_dir'   => 'Создание папки',
            'file_create_file'  => 'Создание файла',
            'file_list'         => 'Просмотр списка файлов',
            'file_access'       => 'Доступ к файлу',
        ]],
        'Модули' => ['icon' => 'box', 'actions' => [
            'module_install'    => 'Установка модуля',
            'module_delete'     => 'Удаление модуля',
            'module_update'     => 'Обновление модуля',
            'module_unpack'     => 'Распаковка модуля',
            'module_settings'   => 'Просмотр настроек модуля',
        ]],
        'Плагины' => ['icon' => 'puzzle', 'actions' => [
            'plugin_install'    => 'Установка плагина',
            'plugin_update'     => 'Обновление плагина',
            'plugin_delete'     => 'Удаление плагина',
            'plugin_toggle'     => 'Включение/выключение плагина',
        ]],
        'Фоновые задачи' => ['icon' => 'clock', 'actions' => [
            'cron_run'          => 'Выполнение фоновой задачи',
        ]],
        'Авторизация' => ['icon' => 'shield', 'actions' => [
            'auth_login'        => 'Вход в админку',
            'auth_logout'       => 'Выход из админки',
            'auth_failed'       => 'Неудачная попытка входа',
        ]],
    ];

    foreach ($coreActions as $group => $data) {
        foreach ($data['actions'] as $action => $label) {
            register_log_action($action, $label, $group, $data['icon']);
        }
    }
}

_register_core_log_actions();
