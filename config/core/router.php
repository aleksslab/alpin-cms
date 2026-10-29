<?php
/**
 * Роутер (определение страницы)
 *
 * Отвечает за разбор URL, поиск страницы, preview-режим и 301-редиректы.
 * Возвращает массив с данными для рендеринга.
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

/**
 * Определяет, какую страницу запросил пользователь.
 *
 * @return array [
 *   'page' => array|null,     // Данные страницы
 *   'template' => array|null, // Шаблон
 *   'isPreview' => bool,      // Режим предпросмотра
 *   'isNewPage' => bool,      // Найдена ли страница
 *   'slug' => string,         // Текущий slug
 *   'usedModules' => array    // Используемые модули
 * ]
 */
function resolve_route(): array {
    // Получаем путь из URL (например, /about, /contacts)
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

    // Убираем GET-параметры (всё после ?)
    if (strpos($requestUri, '?') !== false) {
        $requestUri = substr($requestUri, 0, strpos($requestUri, '?'));
    }

    // Убираем ведущий и trailing слеши
    $slug = trim($requestUri, '/');

    $isNewPage = false;
    $isPreview = false;
    $usedModules = [];
    $template = null;
    $page = null;

    // --- PREVIEW MODE ---
    $previewToken = trim($_GET['preview'] ?? '');

    if ($previewToken !== '' && preg_match('/^[a-f0-9]{32}$/i', $previewToken)) {
        // 1. Временный preview (из конструктора)
        $previewData = loadPreviewById($previewToken);

        // 2. Fallback: постоянный preview-токен страницы
        if (!$previewData) {
            $previewData = loadPageByPreviewToken($previewToken);
        }

        if ($previewData) {
            // Проверка slug: если он не совпадает с актуальным — 301 на правильный
            $actualSlug = $previewData['slug'] ?? '';
            if ($slug !== $actualSlug) {
                $redirectUrl = ($actualSlug !== '' ? '/' . $actualSlug : '/')
                             . '?preview=' . urlencode($previewToken);
                header('Location: ' . $redirectUrl, true, 301);
                exit;
            }

            $page = $previewData;
            $isPreview = true;
            $isNewPage = true;
            $template = loadTemplate($page['template'] ?? 'full-width');
            $usedModules = getUsedModules($page);

            header('X-Robots-Tag: noindex, nofollow, noarchive');
        }
    }

    // --- ОБЫЧНЫЙ РОУТИНГ ---
    if (!$isPreview) {
        $page = loadPage($slug);

        if ($page && $page['status'] === 'published') {
            // Проверка: страница найдена по старому slug?
            $actualSlug = $page['slug'] ?? $page['id'] ?? '';

            // Если запрошенный slug непустой и не совпадает с актуальным —
            // значит, нашли через slug_history. Редирект на актуальный.
            if ($slug !== '' && $slug !== $actualSlug) {
                header('Location: /' . $actualSlug, true, 301);
                exit;
            }

            $isNewPage = true;
            $template = loadTemplate($page['template'] ?? 'full-width');
            $usedModules = getUsedModules($page);
        }
    }

    // --- ЕСЛИ СТРАНИЦА НЕ НАЙДЕНА — РЕДИРЕКТ НА ГЛАВНУЮ ---
    if (!$isNewPage) {
        if ($slug !== '') {
            header('Location: /', true, 301);
            exit;
        }
        
        // Заглушка для главной
        $page = [
            'id' => 'home',
            'title' => 'Главная',
            'template' => 'full-width',
            'status' => 'published',
            'zones' => ['main' => ['rows' => []]]
        ];
        $isNewPage = true;
        $template = loadTemplate('full-width');
    }

    return [
        'page'        => $page,
        'template'    => $template,
        'isPreview'   => $isPreview,
        'isNewPage'   => $isNewPage,
        'slug'        => $slug,
        'usedModules' => $usedModules,
    ];
}