<?php
/**
 * Единая точка входа для фоновых задач плагинов.
 *
 * URL: /config/cron/run.php?task={id}&token=xxx
 *
 * Ядро проверяет токен и запускает задачу по её ID.
 * Оно не знает, какой плагин зарегистрировал задачу и что она делает.
 */

define('APP_ROOT', dirname(dirname(__DIR__)));

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'functions.php';
require_once APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'admin_controller.php';

$token = $_GET['token'] ?? '';

if ($token === '' || !hash_equals(getCronToken(), (string)$token)) {
    header('HTTP/1.1 403 Forbidden');
    die('Error: Access denied');
}

$task = $_GET['task'] ?? '';

if (!preg_match('/^[a-z0-9\-_]+$/i', (string)$task)) {
    header('HTTP/1.1 400 Bad Request');
    die('Error: Invalid task');
}

// Долгие задачи не должны упираться в таймауты
@set_time_limit(0);
ignore_user_abort(true);

// Активные плагины регистрируют свои задачи
loadActivePlugins();

$tasks = get_cron_tasks();

if (!isset($tasks[$task])) {
    if (!headers_sent()) {
        header('HTTP/1.1 404 Not Found');
    }
    die('Error: Unknown task');
}

$taskInfo = $tasks[$task];
$taskLabel = (string)($taskInfo['label'] ?? $task);

if (empty($taskInfo['enabled'])) {
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
    }
    die('Status: Skipped');
}

// Запуск разрешает только захват: интервал или одноразовый токен виртуального крона.
// Так виртуальный и внешний планировщики не создают дублей.
$runToken = (string)($_GET['run'] ?? '');

if (!claim_cron_task_run((string)$task, $runToken)) {
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
    }
    die('Status: Skipped');
}

$startedAt = microtime(true);
$success = run_cron_task((string)$task);
$elapsedMs = (int)round((microtime(true) - $startedAt) * 1000);

if (!$success) {
    release_cron_task_run((string)$task);
    logAction('cron_run', 'Задача «' . $taskLabel . '» (' . $task . ') завершилась с ошибкой', 'ERROR');

    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
    }
    die('Error: Task failed');
}

confirm_cron_task_run((string)$task);

// Частые задачи (например, автопубликация) пишут в лог только о реальных
// изменениях, поэтому техническую запись cron_run отключают опцией 'log' => false.
if (!empty($taskInfo['log'])) {
    logAction('cron_run', 'Задача «' . $taskLabel . '» (' . $task . ') выполнена за ' . $elapsedMs . ' мс', 'INFO');
}

if (!headers_sent()) {
    header('Content-Type: text/plain; charset=utf-8');
}

echo 'Status: OK';
