<?php
define('APP_ROOT', dirname(dirname(__DIR__)));

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'admin_controller.php';

$token = $_GET['token'] ?? '';

handleCronPublishAction($token);