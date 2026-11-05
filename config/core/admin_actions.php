<?php
/**
 * Обработчик POST-запросов админки
 *
 * Вызывается из config/index.php после проверки CSRF.
 * Распределяет запросы по соответствующим функциям.
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

/**
 * Обрабатывает POST-запросы в админке.
 *
 * @return void
 */
function handle_admin_post_actions(): void {
    $res = ['success' => '', 'error' => ''];
    $securityRedirect = false;

    // --- НАСТРОЙКИ И СТРАНИЦЫ ---
    if (isset($_POST['save_settings']))       { $res = handleSaveSettings(); }
    elseif (isset($_POST['save_menu']))       { $res = handleSaveMenu(); }
    elseif (isset($_POST['save_page']))       { $res = handleSavePage(); }
    
    // --- УДАЛЕНИЕ И КЛОНИРОВАНИЕ ---
    elseif (isset($_POST['page_delete']))   { handleDeletePage(); exit; }
    elseif (isset($_POST['page_clone']))    { handleClonePage(); exit; }
    elseif (isset($_POST['page_set_home'])) { handleSetHomePage(); exit; }
    
    elseif (isset($_POST['menu_delete']))    { handleDeleteMenu(); exit; }
    elseif (isset($_POST['menu_set_main']))  { handleSetMainMenu(); exit; }
    
    // --- КОРЗИНА ---
    elseif (isset($_POST['trash_action'])) { handleTrashAction(); exit; }
    
    // --- МОДУЛИ И МИНИФИКАЦИЯ ---
    elseif (isset($_POST['unpack_module']))    { echo json_encode(unpackModule($_POST['module_id'] ?? '')); exit; }
    elseif (isset($_POST['rebuild_min']))      { handleRebuildModuleMin($_POST['module_id'] ?? ''); exit; }
    elseif (isset($_POST['rebuild_min_all']))  { handleRebuildAllMin($_POST['minify_exclude_css'] ?? [], $_POST['minify_exclude_js'] ?? []); exit; }
    elseif (isset($_POST['delete_min_all']))   { handleDeleteAllMin(); exit; }
    elseif (isset($_POST['clear_cache']))      { clearPageCache(); echo json_encode(['success' => true, 'message' => 'Кеш очищен']); exit; }
    
    // --- БЕЗОПАСНОСТЬ ---
    elseif (isset($_POST['action_unban']))        { $res = handleActionUnban();      $securityRedirect = true; }
    elseif (isset($_POST['action_manual_ban']))   { $res = handleActionManualBan(); $securityRedirect = true; }
    elseif (isset($_POST['save_security']))       { $res = handleSaveSecurity();    $securityRedirect = true; }
    elseif (isset($_POST['ajax_upload_carousel_img'])) { $res = handleAjaxImageUpload(); }
    
    // --- БЭКАПЫ ---
    // Обрабатываются плагином backups на admin_init.

    // --- МОДУЛИ (ДЕЙСТВИЯ) ---
    elseif (isset($_POST['module_action'])) {
        $res = handleModuleAction();
        header('Location: index.php?tab=modules');
        exit;
    }
    
    // --- ПЛАГИНЫ (ДЕЙСТВИЯ) ---
    elseif (isset($_POST['plugin_action'])) {
        $res = handlePluginAction();
        
        // Если это AJAX-запрос (из модалки) — возвращаем JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            if (!empty($res['success'])) {
                echo json_encode(['success' => true, 'message' => $res['success']]);
            } elseif (!empty($res['error'])) {
                echo json_encode(['success' => false, 'error' => $res['error']]);
            }
            exit;
        }
        
        // Обычный POST — делаем редирект с флешем
        if (!empty($res['success'])) {
            setFlash($res['success']);
        } elseif (!empty($res['error'])) {
            setFlash($res['error'], 'error');
        }
        header('Location: index.php?tab=plugins');
        exit;
    }
    
    // --- PRG ДЛЯ ВКЛАДКИ «БЕЗОПАСНОСТЬ» ---
    // Без редиректа F5 повторно отправляет POST: действие выполняется заново,
    // а флеш появляется после каждого обновления страницы.
    if ($securityRedirect) {
        if (!empty($res['success'])) {
            setFlash($res['success']);
        } elseif (!empty($res['error'])) {
            setFlash($res['error'], 'error');
        }
        header('Location: index.php?tab=security');
        exit;
    }

    // --- ФЛЭШ-СООБЩЕНИЯ ---
    if (!empty($res['success'])) {
        setFlash($res['success'], 'success');
    } elseif (!empty($res['error'])) {
        setFlash($res['error'], 'error');
    }
}