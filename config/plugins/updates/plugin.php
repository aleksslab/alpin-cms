<?php
/**
 * Плагин: Проверка обновлений
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

// 1. Сохранение настроек плагина
add_filter('save_settings_data', function($newSettings) {
    save_plugin_settings(
        $newSettings,
        'updates_period',
        [
            'updates_period' => 7,
            'updates_check_core' => true,
            'updates_check_modules' => true,
            'updates_check_plugins' => true,
            'updates_catalog_url' => UPDATES_DEFAULT_CATALOG_URL
        ],
        ['updates_period', 'updates_check_core', 'updates_check_modules', 'updates_check_plugins', 'updates_catalog_url']
    );
    return $newSettings;
});

// 2. AJAX-обработчик проверки обновлений
add_action('admin_init', function() {
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_updates' && isset($_GET['tab']) && $_GET['tab'] === 'updates') {
        header('Content-Type: application/json; charset=utf-8');
        
        $settings = getSettingsData();
        $catalogUrl = !empty($settings['updates_catalog_url']) ? $settings['updates_catalog_url'] : UPDATES_DEFAULT_CATALOG_URL;
        
        // Вызываем единую функцию ядра
        require_once APP_ROOT . '/config/core/plugins.php';
        $result = refresh_updates_cache($catalogUrl);
        
        if ($result !== false) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Не удалось скачать каталог.']);
        }
        exit;
    }
});

/**
 * Рендерит результаты проверки обновлений. (Функция остается, но использует новые ключи)
 */
function render_updates_results(array $data): string {
    $html = '';
    $hasUpdates = false;

    // 1. ЯДРО
    if (!empty($data['core']['update_available'])) {
        $hasUpdates = true;
        $html .= '<div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6">';
        $html .= '<div class="flex items-start gap-4">';
        $html .= '<div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">';
        $html .= '<span class="icon-download text-2xl"></span></div>';
        $html .= '<div>';
        $html .= '<h3 class="text-lg font-bold text-amber-900 mb-1">Доступно обновление ядра</h3>';
        $html .= '<p class="text-sm text-amber-800 mb-4">Установлена версия <strong>' . e($data['core']['current']) . '</strong>. Доступна новая версия <strong>' . e($data['core']['latest']) . '</strong>.</p>';
        $html .= '<a href="' . e($data['core']['url']) . '" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white font-bold rounded-lg hover:bg-amber-700 transition-colors">';
        $html .= '<span class="icon-external-link text-sm"></span> Скачать с GitHub</a>';
        $html .= '</div></div></div>';
    }

    // 2. ОБНОВЛЕНИЯ РАСШИРЕНИЙ
    if (!empty($data['updates'])) {
        $hasUpdates = true;
        $html .= '<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm mb-6">';
        $html .= '<div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">';
        $html .= '<h3 class="text-sm font-bold text-slate-800">Обновления расширений</h3>';
        $html .= '<span class="text-xs font-bold px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full">' . count($data['updates']) . '</span>';
        $html .= '</div>';
        
        $html .= '<table class="w-full text-left border-collapse desktop-only">';
        $html .= '<thead><tr class="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">';
        $html .= '<th class="py-3 px-6">Название</th>';
        $html .= '<th class="py-3">Тип</th>';
        $html .= '<th class="py-3">Версия</th>';
        $html .= '<th class="py-3 text-right pr-6">Действие</th>';
        $html .= '</tr></thead><tbody class="text-sm text-slate-700">';
        
        foreach ($data['updates'] as $id => $ext) {
            $typeLabel = ($ext['type'] === 'module') ? 'Модуль' : 'Плагин';
            $icon = ($ext['type'] === 'module') ? 'icon-box' : 'icon-puzzle';
            
            $html .= '<tr class="border-b border-slate-50 hover:bg-slate-50/50">';
            $html .= '<td class="py-3 px-6 font-semibold text-slate-800 flex items-center gap-2">';
            $html .= '<span class="' . $icon . ' text-slate-400"></span> ' . e($ext['name']);
            $html .= '</td>';
            $html .= '<td class="py-3 text-xs text-slate-500">' . $typeLabel . '</td>';
            $html .= '<td class="py-3 text-xs font-mono text-slate-500">';
            $html .= '<span class="text-slate-400 line-through">' . e($ext['current']) . '</span> &rarr; <span class="text-emerald-600 font-bold">' . e($ext['latest']) . '</span>';
            $html .= '</td>';
            $html .= '<td class="py-3 text-right pr-6">';
            $html .= '<button type="button" onclick="installUpdate(\'' . e($ext['url']) . '\', \'' . e($ext['type']) . '\', \'' . e($id) . '\')" class="inline-block px-3 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200 hover:bg-emerald-100 transition-colors">Обновить</button>';
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<div class="mobile-only p-4 space-y-3">';
        foreach ($data['updates'] as $id => $ext) {
            $typeLabel = ($ext['type'] === 'module') ? 'Модуль' : 'Плагин';
            $icon = ($ext['type'] === 'module') ? 'icon-box' : 'icon-puzzle';
            $html .= '<div class="bg-slate-50 rounded-xl p-4 border border-slate-100">';
            $html .= '<div class="flex items-start justify-between mb-2">';
            $html .= '<div class="flex items-center gap-2 font-bold text-slate-800"><span class="' . $icon . ' text-slate-400"></span>' . e($ext['name']) . '</div>';
            $html .= '<span class="text-[10px] font-bold px-2 py-0.5 bg-slate-200 text-slate-600 rounded">' . $typeLabel . '</span>';
            $html .= '</div>';
            $html .= '<div class="text-xs text-slate-500 mb-3 font-mono">Версия: <span class="line-through text-slate-400">' . e($ext['current']) . '</span> &rarr; <span class="text-emerald-600 font-bold">' . e($ext['latest']) . '</span></div>';
            $html .= '<button type="button" onclick="installUpdate(\'' . e($ext['url']) . '\', \'' . e($ext['type']) . '\', \'' . e($id) . '\')" class="block text-center w-full py-2 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200 hover:bg-emerald-100 transition-colors">Обновить</button>';
            $html .= '</div>';
        }
        $html .= '</div></div>';
    }

    // 3. ВСЁ АКТУАЛЬНО
    if (!$hasUpdates) {
        $html .= '<div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-8 text-center">';
        $html .= '<div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mx-auto mb-4"><span class="icon-check-circle text-3xl"></span></div>';
        $html .= '<h3 class="text-lg font-bold text-emerald-900 mb-1">У вас всё актуально!</h3>';
        $html .= '<p class="text-sm text-emerald-800">Обновлений не найдено.</p>';
        $html .= '</div>';
    }

    return $html;
}

// 3. Автоматическая проверка
add_action('admin_init', function() {
    $settings = getSettingsData();
    if (empty($settings['updates_check_core']) && empty($settings['updates_check_modules']) && empty($settings['updates_check_plugins'])) return;

    $period = (int)($settings['updates_period'] ?? 7);
    $cacheFile = DATA_DIR . 'updates_cache.json';
    
    $lastCheck = 0;
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true);
        $lastCheck = $cache['last_check'] ?? 0;
    }

    if (time() - $lastCheck < ($period * 86400)) return;

    // Асинхронный вызов
    $baseUrl = getBaseUrl() . dirname($_SERVER['SCRIPT_NAME']);
    $url = $baseUrl . '/index.php?tab=updates&ajax=check_updates';
    
    $context = stream_context_create(['http' => ['timeout' => 1.0, 'ignore_errors' => true]]);
    @file_get_contents($url, false, $context);
});

// 4. Уведомление в хедере
add_action('admin_header_notifications', function() {
    $cacheFile = DATA_DIR . 'updates_cache.json';
    if (!file_exists($cacheFile)) return;
    
    $cache = json_decode(file_get_contents($cacheFile), true);
    $data = $cache['data'] ?? [];
    
    $hasUpdates = false;
    if (!empty($data['core']['update_available'])) $hasUpdates = true;
    if (!empty($data['updates'])) $hasUpdates = true;
    
    if (!$hasUpdates) return;

    echo '<a href="index.php?tab=updates" class="relative w-10 h-10 flex items-center justify-center text-amber-500 hover:text-amber-600 transition-colors" title="Доступны обновления">';
    echo '<span class="icon-bell text-xl"></span>';
    echo '<span class="absolute top-2 right-2 w-2.5 h-2.5 bg-rose-500 rounded-full border-2 border-white"></span>';
    echo '</a>';
});