<?php
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

$settings = getLogSettings();
$token = $_SESSION['csrf_token'] ?? '';
$allActions = getLogActions();
$filterLevel = $_GET['level'] ?? '';
$filterAction = $_GET['action'] ?? '';
$search = $_GET['search'] ?? '';
?>

<!-- ЗАГОЛОВОК -->
<div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Логи системы</h1>
        <p class="text-slate-500 text-sm">Просмотр и настройка журнала действий администратора</p>
    </div>
</div>

<!-- ФОРМА НАСТРОЕК -->
<div class="bg-white rounded-2xl border border-slate-200 p-6 mb-8">
    <h3 class="text-lg font-bold text-slate-800 border-b border-slate-200/60 pb-3 mb-4">Настройки логирования</h3>
    
    <form method="POST" action="index.php?tab=logs" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
        <input type="hidden" name="save_log_settings" value="1">
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="log_enabled" value="0">
                    <input type="checkbox" name="log_enabled" value="1" <?php echo $settings['log_enabled'] ? 'checked' : ''; ?>>
                    <span class="text-sm font-medium text-slate-700">Включить логирование</span>
                </label>
            </div>
        </div>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Путь хранения логов</label>
                <input type="text" name="storage_path" value="<?php echo e($settings['storage_path']); ?>" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                <p class="text-[10px] text-slate-400 mt-1">По умолчанию: data/logs/</p>
            </div>
        </div>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Максимальный размер файла (МБ)</label>
                <input type="number" name="max_size" value="<?php echo e(($settings['max_size'] ?? 5 * 1024 * 1024) / 1024 / 1024); ?>" min="1" max="100" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                <p class="text-[10px] text-slate-400 mt-1">При превышении размера создаётся новый файл</p>
            </div>
            
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Количество хранимых архивов</label>
                <input type="number" name="keep_count" value="<?php echo e($settings['keep_count'] ?? 10); ?>" min="1" max="50" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                <p class="text-[10px] text-slate-400 mt-1">Старые архивы удаляются</p>
            </div>
        </div>
        
        <!-- Чекбоксы действий -->
        <div class="border-t border-slate-100 pt-4">
            <h4 class="text-sm font-bold text-slate-700 mb-3">Какие действия логировать:</h4>
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 max-h-[300px] overflow-y-scroll">
                <?php 
                $categories = [
                    'file-text' => 'Страницы',
                    'menu' => 'Меню',
                    'settings' => 'Настройки',
                    'database-backup' => 'Бэкапы',
                    'folder' => 'Файлы',
                    'box' => 'Модули',
                    'shield' => 'Авторизация',
                ];

                $actionsMap = [
                    'Страницы' => ['page_save', 'page_delete', 'page_clone', 'page_set_home'],
                    'Меню' => ['menu_save', 'menu_delete', 'menu_set_main'],
                    'Настройки' => ['settings_save', 'security_save'],
                    'Бэкапы' => ['backup_create', 'backup_restore', 'backup_delete'],
                    'Файлы' => ['file_upload', 'file_delete', 'file_edit', 'file_rename', 'file_chmod', 'file_create_dir', 'file_create_file'],
                    'Модули' => ['module_install', 'module_delete', 'module_update'],
                    'Авторизация' => ['auth_login', 'auth_logout', 'auth_failed'],
                ];

                foreach ($categories as $icon => $category): 
                    $actions = $actionsMap[$category] ?? [];
                ?>
                    <div class="mb-3 last:mb-0">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                            <span class="icon-<?php echo $icon; ?> text-sm"></span>
                            <?php echo $category; ?>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2! lg:grid-cols-3! gap-1">
                            <?php foreach ($actions as $action): ?>
                                <?php $label = $allActions[$action] ?? $action; ?>
                                <label class="flex items-center gap-2 text-sm py-0.5 px-2 rounded hover:bg-slate-100 transition-colors cursor-pointer">
                                    <input type="checkbox" name="log_actions[<?php echo $action; ?>]" value="1" <?php echo ($settings['log_actions'][$action] ?? true) ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)] flex-shrink-0">
                                    <span class="text-slate-700 truncate"><?php echo $label; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition-all text-sm">
                Сохранить настройки
            </button>
        </div>
    </form>
</div>

<!-- ФИЛЬТРЫ И ПРОСМОТР ЛОГОВ -->
<div class="bg-white rounded-2xl border border-slate-200 p-6">
    <div class="flex flex-col lg:flex-row items-center justify-between border-b border-slate-200/60 pb-3 mb-4">
        <h3 class="text-lg font-bold text-slate-800">Журнал событий</h3>
        <div class="flex items-center gap-2">
            <span class="text-sm text-slate-500">Показывать</span>
            <select id="per-page-select" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" onchange="loadLogs(1)">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="text-sm text-slate-500">записей</span>
        </div>
    </div>
    
    <!-- ФИЛЬТРЫ -->
    <div id="logs-filters" class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 mb-4">

        <!-- Уровень -->
        <div class="flex-1 min-w-[120px]">
            <select name="filter_level" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                <option value="">Все уровни</option>
                <option value="INFO" <?php echo $filterLevel === 'INFO' ? 'selected' : ''; ?>>INFO</option>
                <option value="WARNING" <?php echo $filterLevel === 'WARNING' ? 'selected' : ''; ?>>WARNING</option>
                <option value="ERROR" <?php echo $filterLevel === 'ERROR' ? 'selected' : ''; ?>>ERROR</option>
                <option value="CRITICAL" <?php echo $filterLevel === 'CRITICAL' ? 'selected' : ''; ?>>CRITICAL</option>
            </select>
        </div>

        <!-- Действие -->
        <div class="flex-1 min-w-[140px]">
            <select name="filter_action" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
                <option value="">Все действия</option>
                <?php foreach ($allActions as $action => $label): ?>
                    <option value="<?php echo $action; ?>" <?php echo $filterAction === $action ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Поиск -->
        <div class="flex-1 min-w-[140px]">
            <input type="text" name="search" placeholder="Поиск..." value="<?php echo e($search); ?>" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>

        <!-- Кнопки -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <button type="button" onclick="loadLogs(1)" class="px-5 py-2.5 bg-[var(--primary-color)] text-white font-bold rounded-xl hover:bg-[var(--primary-dark)] transition text-sm shadow-lg shadow-[var(--primary-color)]/20">
                Применить
            </button>
            <button type="button" onclick="clearFilters()" class="px-5 py-2.5 border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition text-sm">
                Сбросить
            </button>
        </div>

    </div>
    
    <!-- КОНТЕЙНЕР ДЛЯ ТАБЛИЦЫ ЛОГОВ -->
    <div id="logs-container">
        <div class="text-center py-8 text-slate-400">
            <span class="icon-refresh-cw animate-spin inline-block mr-2"></span>
            Загрузка логов...
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadLogs(1);
    
    let searchTimeout;
    
    document.querySelectorAll('#logs-filters select, #logs-filters input').forEach(function(el) {
        el.addEventListener('change', function() {
            loadLogs(1);
        });
    });
    
    document.querySelector('input[name="search"]').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadLogs(1);
        }, 400);
    });
});

function loadLogs(page) {
    page = page || 1;
    
    const level = document.querySelector('select[name="filter_level"]')?.value || '';
    const action = document.querySelector('select[name="filter_action"]')?.value || '';
    const search = document.querySelector('input[name="search"]')?.value || '';
    const perPage = document.querySelector('#per-page-select')?.value || 10;
    
    const container = document.getElementById('logs-container');
    if (!container) return;
    
    // === ПЛАВНОЕ ОБНОВЛЕНИЕ: затемняем контейнер ===
    container.style.transition = 'opacity 0.2s ease';
    container.style.opacity = '0.4';
    
    const params = new URLSearchParams({
        ajax: 'get_logs',
        tab: 'logs',
        level: level,
        action: action,
        search: search,
        page: page,
        per_page: perPage
    });
    
    fetch('index.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            // Сохраняем текущий perPage для отображения в селекте
            data.perPage = parseInt(document.querySelector('#per-page-select')?.value || 50);

            if (data.logs && data.logs.length > 0) {
                container.innerHTML = renderLogsTable(data);
                container.style.opacity = '1';
            } else {
                container.innerHTML = `
                    <div class="text-center py-8 text-slate-400">
                        <span class="icon-file-text text-3xl block mb-2 text-slate-300"></span>
                        Логов нет
                        ${(level || action || search) ? '<br><span class="text-xs">Попробуйте изменить фильтры</span>' : ''}
                    </div>
                `;
                container.style.opacity = '1';
            }
        })
        .catch(function(error) {
            console.error('Fetch error:', error);
            container.innerHTML = `
                <div class="text-center py-8 text-rose-500">
                    <span class="icon-alert-triangle text-2xl block mb-2"></span>
                    Ошибка загрузки логов
                </div>
            `;
            container.style.opacity = '1';
        });
}

function renderLogsTable(data) {
    const levelColors = {
        'INFO': 'text-emerald-700 bg-emerald-50',
        'WARNING': 'text-amber-700 bg-amber-50',
        'ERROR': 'text-rose-700 bg-rose-50',
        'CRITICAL': 'text-rose-900 bg-rose-100'
    };
    
    let html = `
        <div class="text-xs text-slate-400 mb-3">Найдено записей: ${data.total}</div>
        <div class="bg-slate-50 rounded-xl border border-slate-200 overflow-hidden animate-fade-in">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200">
                            <th class="py-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Время</th>
                            <th class="py-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Уровень</th>
                            <th class="py-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Пользователь</th>
                            <th class="py-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">IP</th>
                            <th class="py-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Действие</th>
                            <th class="py-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Сообщение</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    data.logs.forEach(function(log) {
        const colorClass = levelColors[log.level] || 'text-slate-700 bg-slate-100';
        html += `
            <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                <td class="py-2 px-3 font-mono text-xs text-slate-500 whitespace-nowrap">${escapeHtml(log.timestamp)}</td>
                <td class="py-2 px-3">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold ${colorClass}">
                        ${escapeHtml(log.level)}
                    </span>
                </td>
                <td class="py-2 px-3 font-medium text-slate-700">${escapeHtml(log.user)}</td>
                <td class="py-2 px-3 font-mono text-xs text-slate-500">${escapeHtml(log.ip)}</td>
                <td class="py-2 px-3 text-slate-600">${escapeHtml(log.action)}</td>
                <td class="py-2 px-3 text-slate-600">${escapeHtml(log.message)}</td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
    `;
    
    // Кнопки пагинации (по центру, только если больше 1 страницы)
    if (data.totalPages > 1) {
        html += `<div class="flex justify-center gap-1 py-4 px-3 border-t border-slate-200">`;
        for (let i = 1; i <= data.totalPages; i++) {
            const active = i === data.page ? 'bg-[var(--primary-color)] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200';
            html += `<button onclick="loadLogs(${i})" class="px-3 py-1 rounded-lg text-sm ${active} transition-all">${i}</button>`;
        }
        html += `</div>`;
    }
    
    html += `
        </div>
    `;
    
    return html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function clearFilters() {
    document.querySelector('select[name="filter_level"]').value = '';
    document.querySelector('select[name="filter_action"]').value = '';
    document.querySelector('input[name="search"]').value = '';
    loadLogs(1);
}
</script>