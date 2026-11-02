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

if (!run_cron_task((string)$task)) {
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
    }
    die('Error: Task failed');
}

if (!headers_sent()) {
    header('Content-Type: text/plain; charset=utf-8');
}

echo 'Status: OK';
