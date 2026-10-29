<?php
/**
 * Эндпоинт создания preview.
 * POST → создать/перезаписать preview-файл.
 * 
 * Требует авторизации админа. Возвращает JSON.
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'functions.php';
require_once APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'admin_controller.php';

header('Content-Type: application/json; charset=utf-8');

// === ПРОВЕРКА АВТОРИЗАЦИИ ===
if (empty($_SESSION['admin_auth'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Требуется авторизация.']);
    exit;
}

// === ПРОВЕРКА МЕТОДА ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается.']);
    exit;
}

// === CSRF ===
$incomingToken = $_POST['csrf_token'] ?? '';
$sessionToken  = $_SESSION['csrf_token'] ?? '';
if (empty($sessionToken) || !hash_equals($sessionToken, $incomingToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF-токен невалиден.']);
    exit;
}

// === ПАРАМЕТРЫ ===
$pageId        = trim($_POST['page_id'] ?? '');
$template      = trim($_POST['template'] ?? 'full-width');
$showHeader    = !empty($_POST['show_header']) ? true : false;
$showFooter    = !empty($_POST['show_footer']) ? true : false;
$title         = trim($_POST['title'] ?? '');
$slug          = trim($_POST['slug'] ?? '');
$rowsJson      = $_POST['rows_json'] ?? '';
$metaDesc      = trim($_POST['meta_description'] ?? '');
$metaKeywords  = trim($_POST['meta_keywords'] ?? '');
$existingId    = trim($_POST['existing_preview_id'] ?? '');

// Валидация ID страницы (для сохранённых)
if ($pageId !== '' && !preg_match('/^[a-z0-9\-_]+$/i', $pageId)) {
    echo json_encode(['success' => false, 'error' => 'Невалидный ID страницы.']);
    exit;
}

// Валидация slug (для новых)
if ($slug !== '' && !preg_match('/^[a-z0-9\-_]+$/i', $slug)) {
    echo json_encode(['success' => false, 'error' => 'Невалидный slug.']);
    exit;
}

// Валидация template
if (!preg_match('/^[a-z0-9\-_]+$/i', $template)) {
    $template = 'full-width';
}
$templatesData = getTemplateData();
$templates = $templatesData['templates'] ?? [];
$validTemplates = array_column($templates, 'id');
if (!in_array($template, $validTemplates, true)) {
    $template = 'full-width';
}

// Парсим rows
$rows = [];
if (!empty($rowsJson)) {
    $rows = json_decode($rowsJson, true);
    if (!is_array($rows)) {
        $rows = [];
    }
}

// === СОЗДАЁМ PREVIEW ===
$sessionId = session_id();
$createdBy = $_SESSION['admin_login'] ?? 'system';

$pageData = [
    'id'           => $pageId !== '' ? $pageId : 'preview',
    'title'        => $title,
    'slug'         => $slug,
    'template'     => $template,
    'status'       => 'published',  // в preview статус роли не играет
    'publish_at'   => null,
    'unpublish_at' => null,
    'meta'         => [
        'description' => $metaDesc,
        'keywords'    => $metaKeywords,
    ],
    'show_header'  => $showHeader,
    'show_footer'  => $showFooter,
    'rows'         => $rows,
];

$result = createPreview(
    $pageData,
    $sessionId,
    $pageId,
    $existingId !== '' ? $existingId : null,
    $createdBy
);

if (!$result['success']) {
    echo json_encode(['success' => false, 'error' => $result['error']]);
    exit;
}

echo json_encode([
    'success'    => true,
    'preview_id' => $result['preview_id'],
    'url'        => getPreviewUrl($result['preview_id'], $slug),
]);
exit;