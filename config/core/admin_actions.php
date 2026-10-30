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
    
    // --- ИСТОРИЯ ---
    elseif (isset($_POST['restore_page_version'])) { handleRestorePageVersion($_POST['page_id'] ?? '', intval($_POST['version_ts'] ?? 0)); exit; }
    elseif (isset($_POST['delete_history_version'])) { handleDeleteHistoryVersion($_POST['page_id'] ?? '', intval($_POST['version_ts'] ?? 0)); exit; }
    elseif (isset($_POST['clear_page_history'])) { handleClearPageHistory($_POST['page_id'] ?? ''); exit; }
    elseif (isset($_POST['clear_all_history'])) { handleClearAllHistory(); exit; }        
    
    // --- SEO ---
    elseif (isset($_POST['regenerate_seo_files'])) {
        $result = generateSeoFiles();

        if ($result['sitemap']['success'] || $result['robots']['success']) {
            logAction('seo_regenerate', 'Пересобраны SEO-файлы', 'INFO');
            echo json_encode(['success' => true, 'message' => 'Файлы пересобраны']);
        } else {
            $error = $result['sitemap']['error'] ?: ($result['robots']['error'] ?? 'Неизвестная ошибка');
            logAction('seo_regenerate', 'Ошибка: ' . $error, 'ERROR');
            echo json_encode(['success' => false, 'error' => $error]);
        }
        exit;
    }
    
    // --- МОДУЛИ И МИНИФИКАЦИЯ ---
    elseif (isset($_POST['unpack_module']))    { echo json_encode(unpackModule($_POST['module_id'] ?? '')); exit; }
    elseif (isset($_POST['rebuild_min']))      { handleRebuildModuleMin($_POST['module_id'] ?? ''); exit; }
    elseif (isset($_POST['rebuild_min_all']))  { handleRebuildAllMin($_POST['minify_exclude_css'] ?? [], $_POST['minify_exclude_js'] ?? []); exit; }
    elseif (isset($_POST['delete_min_all']))   { handleDeleteAllMin(); exit; }
    elseif (isset($_POST['clear_cache']))      { clearPageCache(); echo json_encode(['success' => true, 'message' => 'Кеш очищен']); exit; }
    
    // --- БЕЗОПАСНОСТЬ ---
    elseif (isset($_POST['action_unban']))        { $res = handleActionUnban(); }
    elseif (isset($_POST['action_manual_ban']))   { $res = handleActionManualBan(); }
    elseif (isset($_POST['save_security']))       { $res = handleSaveSecurity(); }
    elseif (isset($_POST['ajax_upload_carousel_img'])) { $res = handleAjaxImageUpload(); }
    
    // --- БЭКАПЫ ---
    elseif (isset($_POST['save_backup_settings'])) { $res = handleSaveBackupSettings(); }
    elseif (isset($_POST['init_backup_process']))   { 
        echo json_encode(handleInitBackupProcess());
        exit;
    }
    elseif (isset($_POST['process_backup_step']))  { 
        echo json_encode(handleProcessBackupStep()); 
        exit;
    }
    elseif (isset($_POST['delete_backup_action'])) {
        handleDeleteBackup();
        header('Location: index.php?tab=backups');
        exit;
    }
    elseif (isset($_POST['restore_backup_action'])) {
        handleRestoreBackup();
        header('Location: index.php?tab=backups');
        exit;
    }
    
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
    
    // --- ЛОГИ ---
    elseif (isset($_POST['save_log_settings'])) { $res = handleSaveLogSettings(); }
    
    // --- ФЛЭШ-СООБЩЕНИЯ ---
    if (!empty($res['success'])) {
        setFlash($res['success'], 'success');
    } elseif (!empty($res['error'])) {
        setFlash($res['error'], 'error');
    }
}