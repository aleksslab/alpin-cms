<?php
/**
 * Виртуальные кроны
 *
 * Запускаются при заходе админа в админку. Не требуют настройки crontab.
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

/**
 * Запускает виртуальные кроны: задачи, зарегистрированные через register_cron_task().
 * Вызывается из config/index.php после авторизации.
 *
 * @return void
 */
function run_virtual_crons(): void {
    run_virtual_cron_tasks();
}

/**
 * Запускает задачи, у которых подошёл интервал.
 *
 * Ядро не знает, что именно делает задача: оно только проверяет расписание
 * и отправляет запрос на общий endpoint /config/cron/run.php.
 *
 * @return void
 */
function run_virtual_cron_tasks(): void {
    $tasks = get_cron_tasks();

    if (empty($tasks)) {
        return;
    }

    foreach ($tasks as $id => $task) {
        if (empty($task['virtual']) || empty($task['enabled']) || (int)$task['interval'] <= 0) {
            continue;
        }

        // Атомарно заявляем запуск: повторные запросы админки не создадут
        // дублей, а last_run обновится только после реального старта задачи.
        $runToken = mark_cron_pending($id);
        if ($runToken === '') {
            continue;
        }

        $cronUrl = getBaseUrl() . dirname($_SERVER['SCRIPT_NAME'])
            . '/cron/run.php?task=' . urlencode($id)
            . '&token=' . urlencode(getCronToken())
            . '&run=' . urlencode($runToken);

        $cronContext = stream_context_create([
            'http' => [
                'timeout' => 1.0,
                'ignore_errors' => true
            ]
        ]);
        @file_get_contents($cronUrl, false, $cronContext);
    }
}
