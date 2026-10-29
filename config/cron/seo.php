<?php
/**
 * Крон генерации SEO-файлов (sitemap.xml + robots.txt).
 *
 * URL: /config/cron/seo.php?token=xxx
 * Токен — общий (тот же, что для backup.php и publish.php).
 *
 * Рекомендуемая частота: 1 раз в сутки.
 */

define('APP_ROOT', dirname(dirname(__DIR__)));

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'admin_controller.php';

$token = $_GET['token'] ?? '';

// Проверка токена — тот же, что у бэкапов и публикации
if ($token === '' || $token !== getCronToken()) {
    header('HTTP/1.1 403 Forbidden');
    die('Error: Access denied');
}

// Генерируем файлы
$result = generateSeoFiles();

// Формируем ответ
$sitemapStatus = 'skip';
if (isset($result['sitemap']['success']) && $result['sitemap']['success']) {
    $sitemapStatus = !empty($result['sitemap']['skipped']) ? 'skip' : 'ok';
}

$robotsStatus = 'skip';
if (isset($result['robots']['success']) && $result['robots']['success']) {
    $robotsStatus = !empty($result['robots']['skipped']) ? 'skip' : 'ok';
}

$message = 'Status: OK';
$message .= ', sitemap: ' . $sitemapStatus;
$message .= ', robots: ' . $robotsStatus;

// Если sitemap-часть явно упала с ошибкой
if (isset($result['sitemap']['error']) && $result['sitemap']['error'] !== '') {
    $message .= ', sitemap_error: ' . $result['sitemap']['error'];
}
if (isset($result['robots']['error']) && $result['robots']['error'] !== '') {
    $message .= ', robots_error: ' . $result['robots']['error'];
}

header('Content-Type: text/plain; charset=utf-8');
echo $message;
exit;