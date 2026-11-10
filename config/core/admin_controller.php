<?php

// Самодостаточность: подтягиваем functions.php, если ещё не подключён
if (!function_exists('getSettingsData')) {
    require_once __DIR__ . '/functions.php';
}

// --- Контроллер контента и настроек ---
/**
 * 1. ГЛОБАЛЬНЫЕ ФУНКЦИИ
 */
/**
 * Транслитерирует кириллицу в латиницу для slug и имён файлов.
 *
 * @param string $text Исходный текст
 * @return string Латиница; пробелы заменяются на `_`
 */
function cms_transliterate(string $text): string {
    $cyr = [
        'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
        'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
        'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
        'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я',
        ' '
    ];
    $lat = [
        'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p',
        'r','s','t','u','f','kh','ts','ch','sh','shch','','y','','e','yu','ya',
        'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P',
        'R','S','T','U','F','Kh','Ts','Ch','Sh','Shch','','Y','','E','Yu','Ya',
        '_'
    ];
    return str_replace($cyr, $lat, $text);
}

/**
 * Возвращает общий токен для кронов (бэкапы + автопубликация страниц).
 * Если токен отсутствует в settings.json — генерирует и сохраняет.
 *
 * @return string 32-символьный hex-токен
 */
function getCronToken(): string {
    $settings = getSettingsData();
    if (!empty($settings['cron_token'])) {
        return (string)$settings['cron_token'];
    }
    return regenerateCronToken();
}

/**
 * Принудительно перегенерирует токен крона и сохраняет его в settings.json.
 * Внимание: сломает все существующие внешние крон-задачи.
 *
 * @return string Новый токен
 */
function regenerateCronToken(): string {
    $token = bin2hex(random_bytes(16));
    $settings = getSettingsData();
    $settings['cron_token'] = $token;
    saveData('settings', $settings);
    return $token;
}

/**
 * Сохраняет данные в JSON-файл с красивым форматированием
 *
 * @param string $fileName Имя файла без расширения (например, 'settings')
 * @param array $data Данные для сохранения в виде ассоциативного массива
 * @return bool true при успешной записи, false при ошибке
 */
function saveData(string $fileName, array $data): bool {
    $path = DATA_DIR . "{$fileName}.json";
    $jsonString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    $result = safeFileWrite($path, $jsonString);
    
    if ($result) {
        clearDataCache($fileName);
    }
    return $result;
}

/**
 * Сохраняет данные страницы в файл data/pages/{id}.json
 *
 * @param string $id Идентификатор страницы
 * @param array $data Данные страницы для сохранения
 * @return bool true при успешной записи, false при ошибке
 */
function savePageData(string $id, array $data): bool {
    $filePath = DATA_DIR . 'pages/' . $id . '.json';
    $jsonString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return safeFileWrite($filePath, $jsonString);
}

/**
 * Сохраняет меню
 * 
 * @param string $id ID меню
 * @param array $data Данные меню
 * @return bool true при успешной записи, false при ошибке
 */
function saveMenu(string $id, array $data): bool {
    $filePath = DATA_DIR . 'menus/' . $id . '.json';
    $jsonString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return safeFileWrite($filePath, $jsonString);
}

/**
 * Сохраняет ID главного меню в settings.json
 * 
 * @param string|null $menuId ID главного меню
 * @return bool True в случае успешного сохранения, иначе false
 */
function setMainMenuId(?string $menuId): bool {
    $settings = getSettingsData();
    $settings['main_menu'] = $menuId;
    return saveData('settings', $settings);
}

/**
 * =========================================================================
 * Функции для flash-сообщений (тосты)
 * =========================================================================
 */

/**
 * Устанавливает flash-сообщение в сессию
 *
 * @param string $message Текст сообщения
 * @param string $type Тип: success, error, warning, info
 * @param int $duration Секунды до автозакрытия (0 = бесконечно)
 * @return void
 */
function setFlash(string $message, string $type = 'success', int $duration = 5): void {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type,
        'duration' => $duration
    ];
}

/**
 * Получает flash-сообщение из сессии и удаляет его
 *
 * @return array|null Массив с message, type, duration или null
 */
function getFlash(): ?array {
    if (empty($_SESSION['flash_message'])) {
        return null;
    }
    
    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    
    // Если flash-сообщение пришло как строка (старый формат) — конвертируем
    if (is_string($flash)) {
        return [
            'message' => $flash,
            'type' => 'success',
            'duration' => 5
        ];
    }
    
    // Если массив, но без обязательных полей — дополняем
    if (is_array($flash)) {
        return [
            'message' => $flash['message'] ?? '',
            'type' => $flash['type'] ?? 'success',
            'duration' => $flash['duration'] ?? 5
        ];
    }
    
    return null;
}

/**
 * Проверяет, есть ли flash-сообщение в сессии
 *
 * @return bool
 */
function hasFlash(): bool {
    return !empty($_SESSION['flash_message']);
}

/**
 * Рендерит flash-сообщение (тост)
 *
 * @return string HTML-код тоста или пустая строка
 */
function renderFlash(): string {
    $flash = getFlash();
    
    if (!$flash) {
        return '';
    }
    
    $type = $flash['type'] ?? 'success';
    $duration = intval($flash['duration'] ?? 5);
    
    $typeClasses = [
        'success' => 'text-emerald-800 bg-emerald-50 border-emerald-100',
        'error'   => 'text-rose-800 bg-rose-50 border-rose-100',
        'warning' => 'text-amber-800 bg-amber-50 border-amber-100',
        'info'    => 'text-blue-800 bg-blue-50 border-blue-100'
    ];
    
    $iconClasses = [
        'success' => 'icon-circle-check',
        'error'   => 'icon-circle-x',
        'warning' => 'icon-circle-alert',
        'info'    => 'icon-info'
    ];
    
    $typeClass = $typeClasses[$type] ?? $typeClasses['success'];
    $iconClass = $iconClasses[$type] ?? $iconClasses['success'];
    
    $isAutoClose = $duration > 0;
    $toastId = 'toast-' . uniqid();
    
    $html = '<div id="' . $toastId . '" 
         class="fixed top-24 left-4 right-4 md:left-auto md:right-6 z-[9999] max-w-full md:max-w-md bg-white border rounded-xl shadow-xl p-5 animate-slide-in ' . $typeClass . '"
         data-duration="' . $duration . '"
         data-autoclose="' . ($isAutoClose ? 'true' : 'false') . '">';
    
    $html .= '<div class="flex items-center gap-4">';  // items-center вместо items-start
    $html .= '<div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center bg-white/50">';
    $html .= '<span class="' . $iconClass . ' !text-4xl"></span>';  // text-xl → text-2xl
    $html .= '</div>';
    $html .= '<div class="flex-1 min-w-0">';
    $html .= '<p class="text-sm font-medium">' . e($flash['message']) . '</p>';
    $html .= '</div>';
    $html .= '<button type="button" onclick="closeToast(this)" class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-current/50 hover:text-current/80 hover:bg-white/20 transition-colors cursor-pointer">';
    $html .= '<span class="icon-x text-lg"></span>';
    $html .= '</button>';
    $html .= '</div>';
    
    if ($isAutoClose) {
        $html .= '<div class="mt-3 w-full h-1 bg-white/30 rounded-full overflow-hidden">';
        $html .= '<div class="toast-progress h-full rounded-full bg-current/50" style="width: 100%;"></div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    $html .= getToastScript();
    
    return $html;
}

/**
 * Возвращает скрипт и стили для тостов (только один раз).
 *
 * @param bool $force Вывести повторно, даже если уже выводили
 * @return string HTML/JS или пустая строка при повторном вызове
 */
function getToastScript(bool $force = false): string {
    static $loaded = false;
    if ($loaded && !$force) {
        return '';
    }
    $loaded = true;
    
    return '<script>
    (function() {
        "use strict";
        
        function closeToast(btn) {
            var toast = btn ? btn.closest(".fixed.top-24") : null;
            if (!toast) {
                toast = document.querySelector(".fixed.top-24");
            }
            if (toast) {
                toast.style.transition = "all 0.3s ease";
                toast.style.opacity = "0";
                toast.style.transform = "translateX(100px)";
                setTimeout(function() {
                    if (toast && toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }
        }
        window.closeToast = closeToast;
        
        // Автозакрытие
        document.querySelectorAll("[data-autoclose=\"true\"]").forEach(function(toast) {
            var duration = parseInt(toast.getAttribute("data-duration")) || 5;
            var progressBar = toast.querySelector(".toast-progress");
            
            // Запускаем анимацию прогресс-бара
            if (progressBar) {
                progressBar.style.animation = "toastProgress " + duration + "s linear forwards";
            }
            
            // Закрываем через duration секунд
            setTimeout(function() {
                closeToast(null);
            }, duration * 1000);
        });
    })();
    </script>
    <style>
        @keyframes toastProgress {
            from { width: 100%; }
            to { width: 0%; }
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease forwards;
        }
    </style>';
}

/**
 * Рендерит скрытую sticky-полосу предупреждения об истечении сессии.
 * Показывается клиентским таймером (config/js/main.js) за N секунд до конца.
 *
 * @param int $lifetime Время жизни сессии в секундах.
 * @return string HTML-код полосы.
 */
function renderSessionNotice(int $lifetime): string {
    $lifetime = max(60, $lifetime);
    $warn = min(120, max(10, intdiv($lifetime, 2)));

    $html  = '<div id="js-session-notice" class="hidden sticky top-20 z-30 animate-fade-in" role="status" aria-live="polite"';
    $html .= ' data-lifetime="' . $lifetime . '"';
    $html .= ' data-warn="' . $warn . '"';
    $html .= ' data-ping-url="index.php?ajax=session_ping"';
    $html .= ' data-login-url="index.php?msg=timeout">';
    $html .= '<div class="border-b border-amber-200 bg-amber-50 px-4 md:px-6 py-3">';
    $html .= '<div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">';
    $html .= '<span class="icon-clock text-lg text-amber-700 flex-shrink-0"></span>';
    $html .= '<p class="flex-1 text-sm text-amber-900">';
    $html .= 'Сессия истекает через <span class="font-mono font-bold" data-session-countdown aria-live="off">' . gmdate('i:s', $warn) . '</span>. ';
    $html .= '<span class="text-amber-700/80">Несохранённые изменения будут потеряны.</span>';
    $html .= '</p>';
    $html .= '<button type="button" class="js-session-extend flex-shrink-0 bg-amber-700 hover:bg-amber-800 text-white text-sm font-bold px-4 py-2 rounded-xl transition-colors cursor-pointer">Продолжить сессию</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

/**
 * 2. ОБРАБОТКА СОХРАНЕНИЯ НАСТРОЕК САЙТА
 */

/**
 * Значение чекбокса из POST: '1' — включён, отсутствие или '0' — выключен.
 * Единая замена повторяющемуся isset($_POST[x]) && $_POST[x] == '1'.
 *
 * @param string $name Имя поля
 * @return bool
 */
function post_flag(string $name): bool {
    return isset($_POST[$name]) && $_POST[$name] == '1';
}

/**
 * Сохраняет настройки сайта из формы «Настройки».
 *
 * Валидирует HTML-поля, собирает `settings.json`, применяет фильтры
 * плагинов и пишет файл через `safeFileWrite()`.
 *
 * @return array ['success' => string, 'error' => string]
 */
function handleSaveSettings(): array {
    $cleanPhone = preg_replace('/[^0-9+]/', '', $_POST['set_phone'] ?? '');
    // Если остались только символы «+» без цифр — считаем пустым
    if (preg_replace('/[^0-9]/', '', $cleanPhone) === '') {
        $cleanPhone = '';
    }
    
    // === ВАЛИДАЦИЯ HTML-ПОЛЕЙ ===
    $name = trim($_POST['set_name'] ?? '');
    $slogan = trim($_POST['set_slogan'] ?? '');
    $footerCopyright = trim($_POST['footer_copyright'] ?? '');
    
    $htmlFields = [
        'Название компании' => $name,
        'Слоган' => $slogan,
        'Текст копирайта' => $footerCopyright,
    ];
    
    foreach ($htmlFields as $label => $value) {
        $error = validateHtml($value);
        if ($error !== null) {
            logAction('settings_save', 'Недопустимый HTML в поле "' . $label . '": ' . $error, 'ERROR');
            return ['success' => '', 'error' => 'Поле "' . $label . '": ' . $error];
        }
    }
    
    // Санитизация
    $name = sanitizeHtml($name);
    $slogan = sanitizeHtml($slogan);
    $footerCopyright = sanitizeHtml($footerCopyright);
    
    // === ВАЛИДАЦИЯ EMAIL ===
    $email = trim($_POST['set_email'] ?? '');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logAction('settings_save', 'Некорректный email: ' . $email, 'ERROR');
        return ['success' => '', 'error' => 'Некорректный email.'];
    }
    
    // Социальные сети (массив)
    $socials = [];
    if (isset($_POST['socials']) && is_array($_POST['socials'])) {
        foreach ($_POST['socials'] as $id => $url) {
            $url = trim($url);
            if (!empty($url)) {
                $socials[] = ['id' => $id, 'url' => $url];
            }
        }
    }
    
    // Читаем исключения из скрытого поля
    $exceptionsJson = $_POST['minify_exceptions_hidden'] ?? '{}';
    $exceptionsData = json_decode($exceptionsJson, true);
    
    $modules = getModulesData();
    $minifyExceptions = [];
    foreach (array_keys($modules) as $id) {
        $cssKey = 'minify_exclude_css[' . $id . ']';
        $jsKey = 'minify_exclude_js[' . $id . ']';
        
        $minifyExceptions[$id] = [
            'css' => isset($exceptionsData[$cssKey]) && $exceptionsData[$cssKey] == '1',
            'js' => isset($exceptionsData[$jsKey]) && $exceptionsData[$jsKey] == '1'
        ];
    }
    
    // Полное сохранение приходит только из формы сайта (settings_full=1).
    // Форма плагина шлёт лишь свои поля, её POST ядро из нуля не пересобирает.
    $isFullSave = post_flag('settings_full');

    $siteSettings = [
        // БЛОК 1: Контактная информация
        'name'    => $name,
        'slogan'  => $slogan,
        'phone'   => $cleanPhone,
        'email'   => $email,
        'address' => trim($_POST['set_address'] ?? ''),
        
        // БЛОК 2: Визуальные элементы
        'logo'    => trim($_POST['set_logo'] ?? ''),
        'favicon' => trim($_POST['set_favicon'] ?? ''),
        
        // БЛОК 3: Цветовая схема (ТОЛЬКО СВЕТЛАЯ)
        'theme' => [
            'primary_color' => trim($_POST['theme_primary_color'] ?? '#10B981'),
            'primary_dark' => trim($_POST['theme_primary_dark'] ?? '#059669'),
            'light' => [
                'bg_main' => trim($_POST['theme_light_bg_main'] ?? '#FFFFFF'),
                'bg_card' => trim($_POST['theme_light_bg_card'] ?? '#FFFFFF'),
                'bg_section' => trim($_POST['theme_light_bg_section'] ?? '#F8FAFC'),
                'text_main' => trim($_POST['theme_light_text_main'] ?? '#334155'),
                'text_muted' => trim($_POST['theme_light_text_muted'] ?? '#64748B'),
                'border_color' => trim($_POST['theme_light_border_color'] ?? '#F1F5F9'),
            ],
        ],
        
        // БЛОК 4: Настройки хедера (БЕЗ theme_toggle_enabled)
        'header_variant' => $_POST['header_variant'] ?? 'default',
        'header_fixed' => post_flag('header_fixed'),
        'burger_enabled' => post_flag('burger_enabled'),
        'burger_breakpoint' => trim($_POST['burger_breakpoint'] ?? 'md'),
                
        // БЛОК 5: Настройки футера
        'footer_variant' => $_POST['footer_variant'] ?? 'default',
        'footer_copyright' => $footerCopyright,
        
        // БЛОК 6: SEO настройки
        'meta_title'       => trim($_POST['set_meta_title'] ?? ''),
        'meta_description' => trim($_POST['set_meta_description'] ?? ''),
        'meta_keywords'    => trim($_POST['set_meta_keywords'] ?? ''),
        
        // БЛОК 7: Расширенные SEO (Open Graph + Twitter)
        'og_title'            => trim($_POST['set_og_title'] ?? ''),
        'og_description'      => trim($_POST['set_og_description'] ?? ''),
        'og_image'            => trim($_POST['set_og_image'] ?? ''),
        'og_url'              => trim($_POST['set_og_url'] ?? ''),
        'twitter_title'       => trim($_POST['set_twitter_title'] ?? ''),
        'twitter_description' => trim($_POST['set_twitter_description'] ?? ''),
        'twitter_image'       => trim($_POST['set_twitter_image'] ?? ''),
        
        // БЛОК 8: Социальные сети (массив)
        'socials' => $socials,
        
        // БЛОК 9: Произвольный код
        'custom_css' => trim($_POST['set_custom_css'] ?? ''),
        'custom_js'  => trim($_POST['set_custom_js'] ?? ''),
    
        // БЛОК 10: Минификация
        'assets_minify' => post_flag('assets_minify'),
        'assets_combine' => post_flag('assets_combine'),
        'minify_exceptions' => $minifyExceptions,
        
        // БЛОК 11: Кеширование страниц
        'cache_enabled' => post_flag('cache_enabled'),
        'cache_ttl' => intval($_POST['cache_ttl'] ?? 86400),

        // БЛОК 12: Порядок и видимость секций
        'config_sections_order' => json_decode($_POST['config_sections_order_json'] ?? '[]', true) ?: [],
        'config_sections_hidden' => json_decode($_POST['config_sections_hidden_json'] ?? '[]', true) ?: [],
        
        // === СОХРАНЯЕМ ПОЛЯ, КОТОРЫЕ НЕ В ФОРМЕ ===
        'home_page_id' => getHomePageId(),
        'main_menu'    => getMainMenuId(),
        'cron_token'   => (function() {
            $posted = trim($_POST['cron_token'] ?? '');
            if (preg_match('/^[a-f0-9]{32}$/i', $posted)) {
                return $posted;
            }
            return getCronToken();
        })(),
    ];

    // Форма сайта — пересобираем настройки ядра из POST.
    // Форма плагина — берём текущие настройки и меняем только присланные поля.
    $currentSettings = getSettingsData(true);
    $newSettings = $isFullSave ? $siteSettings : $currentSettings;

    // Ключи, которых нет в форме сайта (настройки выключенных плагинов, кастомные
    // поля), сохраняем как есть — иначе полное сохранение их удалит.
    if ($isFullSave) {
        foreach ($currentSettings as $key => $value) {
            if (!array_key_exists($key, $newSettings)) {
                $newSettings[$key] = $value;
            }
        }
    }

    // Даем плагинам возможность добавить/изменить свои настройки
    $newSettings = apply_filters('save_settings_data', $newSettings);
    
    $oldSettings = $currentSettings;

    // Источник сохранения (сайт или плагин) — для флеша и записи в лог, включая ветку ошибки
    $source = $_POST['settings_source'] ?? 'сайта';
    // Валидация: только кириллица, пробелы и дефис (защита от инъекций)
    if (!preg_match('#^[а-яё\s\-]+$#iu', $source)) {
        $source = 'сайта';
    }
    $source = trim($source);

    // Хук «до сохранения настроек» (для будущих плагинов)
    do_action('settings_before_save', $newSettings);

    if (saveData('settings', $newSettings)) {
        logAction('settings_save', 'Настройки сайта обновлены', 'INFO');

        // Плагины: настройки сохранены (SEO, кеш, .min и т.п.)
        do_action('settings_saved', $oldSettings, $newSettings);

        // === АВТОМАТИЧЕСКОЕ СОЗДАНИЕ .min ПРИ ВКЛЮЧЕНИИ ===
        $oldMinify = $oldSettings['assets_minify'] ?? false;
        $newMinify = $newSettings['assets_minify'] ?? false;
        
        if (!$oldMinify && $newMinify) {
            createMinFilesForAllModules($newSettings);
        }
    
        // === ОЧИСТКА КЕША ПРИ ИЗМЕНЕНИИ НАСТРОЕК АССЕТОВ ===
        $oldCombine = $oldSettings['assets_combine'] ?? false;
        $newCombine = $newSettings['assets_combine'] ?? false;
    
        $oldExceptions = $oldSettings['minify_exceptions'] ?? [];
        $newExceptions = $newSettings['minify_exceptions'] ?? [];

        if ($oldMinify != $newMinify || $oldCombine != $newCombine || $oldExceptions != $newExceptions) {
            clearPageCache();
        }
        
        setFlash('Настройки ' . $source . ' успешно сохранены!');
        
        // Проверяем, откуда пришел запрос (для возврата на нужную вкладку)
        $returnUrl = $_POST['return_url'] ?? 'index.php?tab=config_vars';
        // Валидация URL (защита от Open Redirect)
        if (!preg_match('#^index\.php\?tab=[a-z_]+$#', $returnUrl)) {
            $returnUrl = 'index.php?tab=config_vars';
        }
        
        header('Location: ' . $returnUrl);
        exit;
    }
    logAction('settings_save', 'Ошибка сохранения настроек ' . $source, 'ERROR');
    return ['success' => '', 'error' => 'Ошибка записи на диск.'];
}

/**
 * 3. ОБРАБОТКА ИЗМЕНЕНИЙ БЕЗОПАСНОСТИ, НАСТРОЕК БРУТФОРСА И IP-ЛОГОВ
 */
/**
 * Снимает блокировку IP из журнала попыток входа.
 *
 * @return array ['success' => string, 'error' => string]
 */
function handleActionUnban(): array {
    $unbanIp = trim($_POST['unban_ip'] ?? '');
    if ($unbanIp === '') { return ['success' => '', 'error' => '']; }

    $bfData = file_exists(CMS_ATTEMPTS_FILE) ? (json_decode(file_get_contents(CMS_ATTEMPTS_FILE), true) ?? []) : [];
    
    if (isset($bfData['attempts'][$unbanIp])) {
        unset($bfData['attempts'][$unbanIp]);
        if (safeFileWrite(CMS_ATTEMPTS_FILE, json_encode($bfData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
            return ['success' => "IP-адрес {$unbanIp} успешно разблокирован!", 'error' => ''];
        }
    }
    return ['success' => '', 'error' => 'Ошибка при сохранении лога блокировок.'];
}

/**
 * Заносит IP в постоянный чёрный список вручную.
 *
 * @return array ['success' => string, 'error' => string]
 */
function handleActionManualBan(): array {
    $manualIp = trim($_POST['manual_ban_ip'] ?? '');
    if (!filter_var($manualIp, FILTER_VALIDATE_IP)) {
        return ['success' => '', 'error' => 'Некорректный формат IP-адреса!'];
    }

    $bfData = file_exists(CMS_ATTEMPTS_FILE) ? (json_decode(file_get_contents(CMS_ATTEMPTS_FILE), true) ?? []) : [];
    
    if (!isset($bfData['attempts'])) { $bfData['attempts'] = []; }
    if (!isset($bfData['settings'])) { 
        $bfData['settings'] = ['max_attempts' => 5, 'lockout_time' => 15, 'permanent_trigger_count' => 3, 'permanent_trigger_period' => 24]; 
    }
    
    $bfData['attempts'][$manualIp] = [
        'count'        => intval($bfData['settings']['max_attempts']),
        'last_time'    => time(),
        'bans_count'   => intval($bfData['attempts'][$manualIp]['bans_count'] ?? 0) + 1,
        'bans_history' => $bfData['attempts'][$manualIp]['bans_history'] ?? [],
        'permanent'    => true
    ];
    
    if (safeFileWrite(CMS_ATTEMPTS_FILE, json_encode($bfData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
        return ['success' => "IP-адрес {$manualIp} успешно занесен в вечный черный список!", 'error' => ''];
    }
    return ['success' => '', 'error' => 'Ошибка при записи в черный список.'];
}

/**
 * Сохраняет логин, пароль и параметры защиты от брутфорса.
 *
 * При смене логина или пароля завершает сессию и уводит на повторный вход.
 *
 * @return array ['success' => string, 'error' => string]
 */
function handleSaveSecurity(): array {
    $newLogin = trim($_POST['sec_login'] ?? '');
    $newPass  = $_POST['sec_password'] ?? '';
    
    $pepper = file_exists(CMS_PEPPER_FILE) ? trim(file_get_contents(CMS_PEPPER_FILE)) : '';
    $currentCreds = file_exists(CMS_CREDS_FILE) ? (json_decode(file_get_contents(CMS_CREDS_FILE), true) ?? []) : [];
    
    if (empty($pepper) || empty($currentCreds)) { 
        return ['success' => '', 'error' => 'Системная ошибка безопасности. Запись невозможна.']; 
    }
    if ($newLogin === '') { 
        return ['success' => '', 'error' => 'Логин администратора не может быть пустым!']; 
    }
    
    if ($newPass !== '') {
        $secureMix = hash_hmac('sha256', $newPass, $newLogin . $pepper);
        $finalHash = password_hash($secureMix, PASSWORD_DEFAULT);
    } else {
        if ($newLogin !== $currentCreds['login']) {
            return ['success' => '', 'error' => 'При изменении имени пользователя необходимо обязательно указать новый пароль!'];
        }
        $finalHash = $currentCreds['password_hash'];
    }
    
    $newCredsData = ['login' => $newLogin, 'password_hash' => $finalHash];
    if (!safeFileWrite(CMS_CREDS_FILE, json_encode($newCredsData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
        logAction('security_save', 'Ошибка сохранения настроек учетной записи', 'ERROR');
        return ['success' => '', 'error' => 'Ошибка системы при сохранении данных доступа.'];
    }
    logAction('security_save', 'Настройки учетной записи обновлены (логин: ' . $newLogin . ')', 'INFO');
        
    $bfData = file_exists(CMS_ATTEMPTS_FILE) ? (json_decode(file_get_contents(CMS_ATTEMPTS_FILE), true) ?? []) : [];
    $bfData['settings'] = [
        'max_attempts'             => max(1, intval($_POST['bf_max_attempts'] ?? 5)),
        'lockout_time'             => max(1, intval($_POST['bf_lockout_time'] ?? 15)),
        'permanent_trigger_count'  => max(1, intval($_POST['bf_perm_count'] ?? 3)),
        'permanent_trigger_period' => max(1, intval($_POST['bf_perm_period'] ?? 24)),
        'session_timeout'          => max(1, min(1440, intval($_POST['bf_session_timeout'] ?? 20)))
    ];
    if (!isset($bfData['attempts'])) { $bfData['attempts'] = []; }
    safeFileWrite(CMS_ATTEMPTS_FILE, json_encode($bfData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    $to = SITE_EMAIL;
    $subject = "=?utf-8?B?" . base64_encode("Оповещение безопасности | " . strip_tags(SITE_NAME)) . "?=";
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Неизвестен';
    $date = date('d.m.Y H:i:s');
    $message = "Внимание! На сайте " . strip_tags(SITE_NAME) . " были изменены настройки безопасности панели конфигурации.\n\n"
             . "Детали операции:\n"
             . "• Дата и время: {$date} (время сервера)\n"
             . "• IP-адрес: {$ip}\n"
             . "• Изменение логина: " . ($newLogin !== $currentCreds['login'] ? "ДА (Новый логин: {$newLogin})" : "НЕТ") . "\n"
             . "• Изменение пароля: " . ($newPass !== '' ? "ДА" : "НЕТ") . "\n"
             . "• Лимиты брутфорса: ОБНОВЛЕНЫ\n\n";
    
    $safeHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('#^[a-z0-9\-\.]+(:\d+)?$#i', $safeHost)) {
        $safeHost = 'localhost';
    }
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=utf-8\r\nFrom: security@" . $safeHost . "\r\n";
    @mail($to, $subject, $message, $headers);

    if ($newPass !== '' || $newLogin !== $currentCreds['login']) {
        $_SESSION['admin_auth'] = false;
        header('Location: index.php?msg=updated');
        exit;
    }
    logAction('security_save', 'Настройки безопасности успешно обновлены', 'INFO');
    return ['success' => 'Настройки учетной записи и параметры брутфорса успешно сохранены!', 'error' => ''];
}

/**
 * Принимает изображение из медиа-модалки и сохраняет его в `images/` или корень.
 *
 * Проверяет размер, расширение, MIME и содержимое SVG, очищает имя файла.
 * Отвечает JSON `{success, url}` или `{success, error}`.
 *
 * @return void
 */
function handleAjaxImageUpload(): void {
    header('Content-Type: application/json');
    
    // 1. Проверяем CSRF-токен
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        echo json_encode(['success' => false, 'error' => 'Ошибка безопасности: невалидный CSRF-токен.']);
        exit;
    }

    // 2. Проверяем файл
    if (!isset($_FILES['carousel_file']) || $_FILES['carousel_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Файл не загружен сервером или превышен лимит размера.']);
        exit;
    }
    if ($_FILES['carousel_file']['size'] > MAX_UPLOAD_SIZE) {
        echo json_encode(['success' => false, 'error' => 'Размер файла превышает допустимый лимит (' . (MAX_UPLOAD_SIZE / 1024 / 1024) . ' МБ).']);
        exit;
    }
    
    // 3. Определяем папку назначения
    $subFolder = preg_replace('/[^a-z0-9_-]/i', '', $_POST['target_folder'] ?? '');
    $uploadToRoot = isset($_POST['upload_to_root']) && $_POST['upload_to_root'] === '1';
    
    if ($uploadToRoot) {
        $targetDir = APP_ROOT;
    } else {
        $targetDir = APP_ROOT . DIRECTORY_SEPARATOR . 'images';
        if (!empty($subFolder)) {
            $targetDir .= DIRECTORY_SEPARATOR . $subFolder;
        }
    }
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // 4. Обработка файла
    $file = $_FILES['carousel_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'ico'])) {
        echo json_encode(['success' => false, 'error' => 'Запрещенный формат! Разрешены только JPG, PNG, WEBP, SVG, ICO.']);
        exit;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (stripos($mime, 'image/') !== 0) {
        echo json_encode(['success' => false, 'error' => 'Файл не является валидным изображением.']);
        exit;
    }

    // SVG — XML с исполняемыми возможностями: одного MIME недостаточно
    if ($ext === 'svg') {
        $svgContent = file_get_contents($file['tmp_name']);
        $svgError = validateSvgContent($svgContent === false ? '' : $svgContent);
        if ($svgError !== null) {
            echo json_encode(['success' => false, 'error' => $svgError]);
            exit;
        }
    }
    
    // 5. Генерация имени
    $prefix = !empty($subFolder) ? $subFolder : 'img';
    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
    $translitName = cms_transliterate($originalName);
    $cleanName = preg_replace('/[^a-z0-9._-]/i', '', $translitName);
    $cleanName = preg_replace('/_+/', '_', $cleanName);
    $cleanName = trim($cleanName, '_');
    
    if ($cleanName === '' || mb_strlen($cleanName) > 50) { 
        $cleanName = $prefix; 
    }
    
    $potentialName = $cleanName . '.' . $ext;
    $checkPath = $targetDir . DIRECTORY_SEPARATOR . $potentialName;
    
    if (!file_exists($checkPath)) {
        $finalName = $potentialName;
    } else {
        $randomSalt = bin2hex(random_bytes(2)); 
        $potentialName = $cleanName . '_' . $randomSalt . '.' . $ext;
        $checkPath = $targetDir . DIRECTORY_SEPARATOR . $potentialName;
        
        if (file_exists($checkPath)) {
            $randomSalt = bin2hex(random_bytes(2));
            $potentialName = $cleanName . '_' . $randomSalt . '.' . $ext;
        }
        $finalName = $potentialName;
    }
    
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $finalName;
    
    // 6. Формируем URL для ответа
    if ($uploadToRoot) {
        $relativeUrl = $finalName;
    } else {
        $relativeUrl = 'images/' . (!empty($subFolder) ? $subFolder . '/' : '') . $finalName;
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Сообщаем ядру, что файл загружен
        do_action('after_file_upload', $targetPath);
        
        echo json_encode(['success' => true, 'url' => $relativeUrl]);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Не удалось сохранить файл. Проверьте права на запись.']);
    exit;
}


/**
 * Проверяет, доступно ли планирование публикации страниц.
 *
 * Возможность включается плагином через фильтр `page_scheduling_enabled`:
 * именно он регистрирует cron-задачу, которая переводит `scheduled` в
 * `published`. Пока фильтра нет, статус «По расписанию» и поля дат в
 * редакторе не показываются: опубликовать такую страницу всё равно некому.
 *
 * @return bool true, если расписание поддерживается активным плагином.
 */
function isPageSchedulingEnabled(): bool {
    return (bool) apply_filters('page_scheduling_enabled', false);
}

/**
 * Создаёт или обновляет страницу из формы редактора.
 *
 * Санирует ID и slug, собирает данные страницы, вызывает хуки
 * `page_data_before_save` / `page_before_save` / `page_saved`.
 *
 * @return array ['success' => string, 'error' => string]
 */
function handleSavePage(): array {    
    $action = $_POST['page_action'] ?? 'create';
    $pageId = trim($_POST['page_id'] ?? '');
    
    // Жесткая санация ID страницы от Path Traversal
    $pageId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $pageId);
    
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $template = trim($_POST['template'] ?? 'full-width');
    $status = trim($_POST['status'] ?? 'draft');
    $allowedStatuses = ['draft', 'scheduled', 'published', 'archived'];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'draft';
    }
    // Планирование доступно только при активном плагине автопубликации:
    // без него статус `scheduled` никто не переведёт в `published`.
    // Защита от устаревшей открытой вкладки и подделанного POST.
    if ($status === 'scheduled' && !isPageSchedulingEnabled()) {
        return ['success' => '', 'error' => 'Автопубликация отключена. Выберите другой статус страницы.'];
    }
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $metaKeywords = trim($_POST['meta_keywords'] ?? '');
    $showHeader = isset($_POST['show_header']) ? true : false;
    $showFooter = isset($_POST['show_footer']) ? true : false;
    
    // Получаем rows из POST (если есть)
    $rowsJson = $_POST['rows_json'] ?? '';
    $rows = [];
    if (!empty($rowsJson)) {
        $rows = json_decode($rowsJson, true);
        if (!is_array($rows)) {
            $rows = [];
        }
    }
    
    // Валидация
    if (empty($title)) {
        return ['success' => '', 'error' => 'Заголовок страницы обязателен для заполнения.'];
    }
    
    $pagesDir = DATA_DIR . 'pages/';
    if (!is_dir($pagesDir)) {
        mkdir($pagesDir, 0755, true);
    }
    
    // Определяем ID страницы и управляем именами файлов
    if ($action === 'create') {
        if (empty($slug)) {
            return ['success' => '', 'error' => 'ЧПУ (slug) обязательно для заполнения.'];
        }
        if (!preg_match('/^[a-z0-9\-_]*$/', $slug)) {
            return ['success' => '', 'error' => 'ЧПУ может содержать только латиницу, цифры, дефис и подчёркивание.'];
        }
        if (file_exists($pagesDir . $slug . '.json')) {
            return ['success' => '', 'error' => 'Страница с таким ЧПУ уже существует.'];
        }
        $id = $slug;
    } else {
        // Редактирование
        if (empty($pageId) || !file_exists($pagesDir . $pageId . '.json')) {
            return ['success' => '', 'error' => 'Страница не найдена.'];
        }
        $id = $pageId;
        
        $homeId = getHomePageId();
        if ($id === $homeId) {
            $slug = '';
        } else {
            if (empty($slug)) {
                return ['success' => '', 'error' => 'ЧПУ (slug) обязательно для заполнения.'];
            }
            if (!preg_match('/^[a-z0-9\-_]*$/', $slug)) {
                return ['success' => '', 'error' => 'ЧПУ может содержать только латиницу, цифры, дефис и подчёркивание.'];
            }
            
            // Если администратор МЕНЯЕТ ЧПУ (новое имя не совпадает со старым ID файла)
            if ($slug !== $id) {
                $targetFile = $pagesDir . $slug . '.json';
                $oldFile = $pagesDir . $id . '.json';

                // Проверяем, не занято ли новое ЧПУ кем-то другим
                if (file_exists($targetFile)) {
                    return ['success' => '', 'error' => 'Страница с таким ЧПУ уже существует.'];
                }

                // Загружаем старую версию страницы, чтобы взять оттуда актуальный slug
                // (не $id, а именно поле slug — они могут различаться)
                $oldPage = _jsonToArray($oldFile);
                $oldSlug = $oldPage['slug'] ?? $id;

                // Восстанавливаем историю slug'ов из старого JSON
                // (чтобы не потерять её при перезаписи)
                if (!isset($rows) || !is_array($rows)) {
                    $rows = [];
                }
                $slugHistory = $oldPage['slug_history'] ?? [];
                if (!is_array($slugHistory)) {
                    $slugHistory = [];
                }

                // Физически переименовываем файл на диске
                if (file_exists($oldFile)) {
                    if (!rename($oldFile, $targetFile)) {
                        return ['success' => '', 'error' => 'Не удалось переименовать файл страницы. Проверьте права.'];
                    }
                }

                // Обновляем текущий ID на новое ЧПУ
                $id = $slug;

                // === РАБОТА С ИСТОРИЕЙ SLUG'ОВ ===

                // 1. Удаляем новый slug из истории, если он там был раньше (циклическая смена)
                $slugHistory = array_values(array_filter($slugHistory, function($s) use ($slug) {
                    return $s !== $slug;
                }));

                // 2. Добавляем старый slug в историю, если:
                //    - он непустой (не главная страница)
                //    - его там ещё нет
                //    - он отличается от нового
                if ($oldSlug !== '' && $oldSlug !== $slug && !in_array($oldSlug, $slugHistory, true)) {
                    $slugHistory[] = $oldSlug;
                }

                // 3. Ограничиваем историю (защита от роста)
                if (count($slugHistory) > 50) {
                    $slugHistory = array_slice($slugHistory, -50);
                }

                // Сохраняем для последующей записи в $pageData
                $computedSlugHistory = $slugHistory;
            } else {
                // Slug не менялся — сохраняем существующую историю
                $existingPage = _jsonToArray($pagesDir . $id . '.json');
                $computedSlugHistory = $existingPage['slug_history'] ?? [];
                if (!is_array($computedSlugHistory)) {
                    $computedSlugHistory = [];
                }
            }
        }
    }
    
    
    // Даты
    $publishAtRaw   = trim($_POST['publish_at'] ?? '');
    $unpublishAtRaw = trim($_POST['unpublish_at'] ?? '');
    $publishAt   = $publishAtRaw !== '' ? strtotime($publishAtRaw) : null;
    $unpublishAt = $unpublishAtRaw !== '' ? strtotime($unpublishAtRaw) : null;
    $now = time();
    
    // Валидация дат
    if ($publishAtRaw !== '' && $publishAt === false) {
        return ['success' => '', 'error' => 'Некорректный формат даты публикации.'];
    }
    if ($unpublishAtRaw !== '' && $unpublishAt === false) {
        return ['success' => '', 'error' => 'Некорректный формат даты снятия с публикации.'];
    }

    // Логика по статусу
    if ($status === 'scheduled') {
        if ($publishAt === null) {
            return ['success' => '', 'error' => 'Для запланированной публикации укажите дату и время.'];
        }
        if ($publishAt <= $now) {
            return ['success' => '', 'error' => 'Дата публикации уже прошла. Выберите будущую дату или смените статус на «Опубликована».'];
        }
    }

    if ($unpublishAt !== null) {
        if ($unpublishAt <= $now) {
            return ['success' => '', 'error' => 'Дата снятия с публикации уже прошла. Выберите будущую дату.'];
        }
        if ($publishAt !== null && $unpublishAt <= $publishAt) {
            return ['success' => '', 'error' => 'Дата снятия с публикации должна быть позже даты публикации.'];
        }
    }
    
    // Очистка неактуальных дат в зависимости от статуса
    if ($status === 'draft' || $status === 'archived') {
        $publishAt = null;
        $unpublishAt = null;
    }
    if ($status === 'published') {
        $publishAt = null;  // не нужна для уже опубликованной
        // unpublish_at оставляем — может пригодиться (автоснятие)
    }

    // Формируем структуру страницы
    $pageData = [
        'id' => $id,
        'title' => $title,
        'slug' => $slug,
        'template' => $template,
        'status' => $status,
        'publish_at' => $publishAt,
        'unpublish_at' => $unpublishAt,
        'meta' => [
            'description' => $metaDescription,
            'keywords' => $metaKeywords
        ],
        'show_header' => $showHeader,
        'show_footer' => $showFooter,
        'rows' => $rows
    ];

    // Slug history (для 301-редиректов)
    if (!empty($computedSlugHistory)) {
        $pageData['slug_history'] = $computedSlugHistory;
    }

    // Версия страницы на диске до перезаписи (null при создании)
    $oldPage = ($action === 'create') ? null : loadPageById($id);

    // Плагины: правка данных перед записью (history_key, preview_token, служебные поля)
    $pageData = apply_filters('page_data_before_save', $pageData, $id, $oldPage);

    // Хук «до сохранения страницы» (для плагинов: история, превью)
    do_action('page_before_save', $id, $pageData, $oldPage);

    // Сохраняем файл через безопасную атомарную функцию ядра
    if (savePageData($id, $pageData) !== false) {
        // Удаляем кеш страницы (HTML + объединённые ассеты)
        clearPageCacheById($id);

        // Плагины: страница сохранена (SEO, история, превью)
        do_action('page_saved', $id, $pageData, $oldPage);
        
        $msg = 'Страница "' . $title . '" успешно ' . ($action === 'create' ? 'создана' : 'обновлена');
        logAction('page_save', $msg . ' (ID: ' . $id . ')', 'INFO');
        setFlash($msg . '!');
        header('Location: ?tab=pages&action=edit&id=' . $id);
        exit;
    }
    logAction('page_save', 'Ошибка сохранения страницы "' . $title . '" (ID: ' . $id . ')', 'ERROR');
    return ['success' => '', 'error' => 'Ошибка при сохранении файла. Проверьте права на запись.'];
}

/**
 * Возвращает метку, CSS-классы и иконку для бейджа статуса страницы.
 * Используется в списке страниц (pages.php).
 *
 * @param array $page Данные страницы (нужны status, publish_at, unpublish_at)
 * @return array ['label' => string, 'class' => string, 'icon' => string]
 */
function getPageStatusBadge(array $page): array {
    $status = $page['status'] ?? 'draft';
    $now = time();

    switch ($status) {
        case 'published':
            if (!empty($page['unpublish_at']) && (int)$page['unpublish_at'] > $now) {
                return [
                    'label' => 'Снятие запланировано',
                    'class' => 'text-orange-700 bg-orange-50',
                    'icon' => 'icon-clock'
                ];
            }
            return [
                'label' => 'Опубликована',
                'class' => 'text-emerald-800 bg-emerald-50',
                'icon' => 'icon-circle-check'
            ];

        case 'scheduled':
            return [
                'label' => 'Запланирована',
                'class' => 'text-blue-700 bg-blue-50',
                'icon' => 'icon-calendar-clock'
            ];

        case 'archived':
            return [
                'label' => 'Снята с публикации',
                'class' => 'text-slate-600 bg-slate-100',
                'icon' => 'icon-archive'
            ];

        case 'draft':
        default:
            return [
                'label' => 'Черновик',
                'class' => 'text-amber-700 bg-amber-50',
                'icon' => 'icon-file-edit'
            ];
    }
}

/**
 * Назначает страницу главной и сбрасывает кеш старой и новой главной.
 *
 * @return void
 */
function handleSetHomePage(): void {
    $pageId = $_POST['id'] ?? '';
    if (empty($pageId)) {
        setFlash('Ошибка: ID страницы не указан.', 'error');
        header('Location: ?tab=pages');
        exit;
    }
    
    $page = loadPageById($pageId);
    if (!$page) {
        setFlash('Ошибка: страница не найдена.', 'error');
        header('Location: ?tab=pages');
        exit;
    }
    
    if ($page['status'] !== 'published') {
        setFlash('Ошибка: страница должна быть опубликована.', 'error');
        header('Location: ?tab=pages');
        exit;
    }
    
    // Получаем текущую главную страницу
    $oldHomeId = getHomePageId();
    $oldHome = loadPageById($oldHomeId);
    
    // Хук «до смены главной страницы» (для будущих плагинов)
    do_action('home_before_change', $oldHomeId, $pageId);

    // Обновляем globals.json
    $globals = getData('settings');
    $globals['home_page_id'] = $pageId;
    saveData('settings', $globals);
    
    // У старой главной восстанавливаем slug
    if ($oldHome && $oldHome['id'] !== $pageId) {
        $oldHome['slug'] = $oldHome['id'];
        savePageData($oldHome['id'], $oldHome);
    }
    
    // У новой главной slug = ''
    $page['slug'] = '';
    savePageData($pageId, $page);
    
    // Сбрасываем кеш старой и новой главной
    clearPageCacheById($oldHomeId);  // было home.html
    clearPageCacheById($pageId);     // станет home.html

    // Плюс: общий кеш главной (/)
    $homeCacheFile = APP_ROOT . '/cache/pages/home.html';
    if (file_exists($homeCacheFile)) {
        @unlink($homeCacheFile);
    }
    
    // Плагины: главная страница сменилась
    do_action('home_changed', $pageId);
    
    logAction('page_set_home', 'Страница "' . $page['title'] . '" назначена главной (ID: ' . $pageId . ')', 'INFO');
    setFlash('Страница "' . $page['title'] . '" теперь главная!');
    header('Location: ?tab=pages');
    exit;
}

/**
 * Клонирует страницу в черновик с новым ID.
 *
 * @return void
 */
function handleClonePage(): void {
    $pageId = $_POST['id'] ?? '';
    if (empty($pageId)) {
        setFlash('Ошибка: ID страницы не указан.', 'error');
        header('Location: ?tab=pages');
        exit;
    }
    
    $page = loadPageById($pageId);
    if (!$page) {
        setFlash('Ошибка: страница не найдена.', 'error');
        header('Location: ?tab=pages');
        exit;
    }
    
    // Генерируем новый ID и slug
    $baseId = $page['id'];
    $newId = $baseId . '-copy';
    $counter = 1;
    while (file_exists(DATA_DIR . 'pages/' . $newId . '.json')) {
        $newId = $baseId . '-copy-' . ++$counter;
    }
    
    // Копируем данные страницы
    $newPage = $page;
    $newPage['id'] = $newId;
    $newPage['title'] = $page['title'] . ' (копия)';
    $newPage['slug'] = $newId;
    $newPage['status'] = 'draft'; // Копия создаётся как черновик

    // Slug history НЕ копируем — клон это новая страница.
    // При публикации клона старые URL должны вести на оригинал, не на копию.
    unset($newPage['slug_history']);

    // Preview-токен тоже не копируем
    unset($newPage['preview_token']);
    
    // Хук «до клонирования страницы» (для будущих плагинов)
    do_action('page_before_clone', $pageId);

    // Сохраняем новую страницу
    if (savePageData($newId, $newPage)) {
        // Плагины: страница клонирована
        do_action('page_cloned', $pageId, $newId);
        
        logAction('page_clone', 'Страница "' . $page['title'] . '" клонирована как "' . $newPage['title'] . '" (ID: ' . $newId . ')', 'INFO');
        setFlash('Страница "' . $newPage['title'] . '" успешно создана!');
    } else {
        logAction('page_clone', 'Ошибка клонирования страницы "' . $page['title'] . '"', 'ERROR');
        setFlash('Ошибка при клонировании страницы.', 'error');
    }
    
    header('Location: ?tab=pages');
    exit;
}

/**
 * Проверяет, доступна ли корзина для страниц и меню.
 *
 * Возможность включается плагином через фильтр `trash_enabled`: только он
 * умеет перемещать файлы в data/trash/ и восстанавливать их. Пока фильтра
 * нет, удаление выполняется окончательно, а кнопка и модалка корзины не
 * выводятся.
 *
 * @return bool true, если активный плагин включил корзину.
 */
function isTrashEnabled(): bool {
    return (bool) apply_filters('trash_enabled', false);
}

/**
 * Возвращает текст подтверждения удаления страницы или меню.
 *
 * @param string $type  'page' | 'menu'
 * @param string $title Название страницы или меню
 * @return string Текст для confirm()
 */
function getDeleteConfirmMessage(string $type, string $title): string {
    $enabled = isTrashEnabled();

    if ($type === 'menu') {
        return $enabled
            ? 'Переместить меню «' . $title . '» в корзину?'
            : 'Плагин «Корзина» отключён. Меню «' . $title . '» будет удалено окончательно, восстановить его не получится. Удалить?';
    }

    return $enabled
        ? 'Переместить страницу «' . $title . '» в корзину?'
        : 'Плагин «Корзина» отключён. Страница «' . $title . '» будет удалена окончательно, восстановить её не получится. Удалить?';
}

/**
 * Окончательно удаляет страницу или меню, когда корзина отключена.
 *
 * Читает данные до удаления, чтобы плагины (например, history) получили
 * history_key и метаданные через `trash_item_purged`.
 *
 * @param string $type      'page' | 'menu'
 * @param string $id        ID сущности
 * @param string $sourceDir Папка-источник (с trailing separator)
 * @return array ['success' => bool, 'error' => string]
 */
function deleteEntityPermanently(string $type, string $id, string $sourceDir): array {
    if (!in_array($type, ['page', 'menu'], true)) {
        return ['success' => false, 'error' => 'Неизвестный тип сущности.'];
    }
    if (!isValidId($id)) {
        return ['success' => false, 'error' => 'Невалидный ID.'];
    }

    $sourceFile = $sourceDir . $id . '.json';
    if (!file_exists($sourceFile)) {
        return ['success' => false, 'error' => 'Файл не найден.'];
    }

    $data = json_decode((string)file_get_contents($sourceFile), true);
    if (!is_array($data)) {
        return ['success' => false, 'error' => 'Повреждённый JSON.'];
    }

    $sizeBytes = (int)@filesize($sourceFile);

    if (!@unlink($sourceFile)) {
        return ['success' => false, 'error' => 'Не удалось удалить файл.'];
    }

    // Плагины: элемент удалён окончательно (история, превью)
    $data['_trash'] = [
        'type'        => $type,
        'original_id' => $id,
        'deleted_at'  => time(),
        'deleted_by'  => $_SESSION['admin_login'] ?? 'system',
        'size_bytes'  => $sizeBytes,
    ];
    do_action('trash_item_purged', $data);

    return ['success' => true, 'error' => ''];
}

/**
 * Удаляет страницу: в корзину, если плагин активен, иначе окончательно.
 *
 * @return void
 */
function handleDeletePage(): void {
    $pageId = $_POST['id'] ?? '';
    if (empty($pageId)) {
        setFlash('Ошибка: ID страницы не указан.', 'error');
        header('Location: ?tab=pages');
        exit;
    }
    
    // Проверяем, что страница не является главной
    $homeId = getHomePageId();
    if ($pageId === $homeId) {
        setFlash('Ошибка: нельзя удалить главную страницу.', 'error');
        header('Location: ?tab=pages');
        exit;
    }
    
    $page = loadPageById($pageId);
    if (!$page) {
        setFlash('Ошибка: страница не найдена.', 'error');
        header('Location: ?tab=pages');
        exit;
    }
    
    // Хук «до удаления страницы» (для будущих плагинов)
    do_action('page_before_delete', $pageId);

    // Корзина живёт в плагине. null — плагин не активен, удаляем окончательно.
    $result = apply_filters('trash_entity_result', null, 'page', $pageId);
    $permanent = ($result === null);

    if ($permanent) {
        $result = deleteEntityPermanently('page', $pageId, DATA_DIR . 'pages' . DIRECTORY_SEPARATOR);
    } elseif (!is_array($result)) {
        $result = ['success' => false, 'error' => 'Некорректный ответ плагина корзины.'];
    }

    if ($result['success']) {
        clearPageCacheById($pageId);

        // Плагины: страница удалена (в корзину или окончательно)
        do_action('page_deleted', $pageId);

        if ($permanent) {
            logAction('page_delete', 'Страница "' . $page['title'] . '" удалена окончательно (ID: ' . $pageId . ')', 'INFO');
            setFlash('Страница "' . $page['title'] . '" удалена окончательно.');
        } else {
            logAction('page_trash', 'Страница "' . $page['title'] . '" перемещена в корзину (ID: ' . $pageId . ')', 'INFO');
            setFlash('Страница "' . $page['title'] . '" перемещена в корзину.');
        }
    } else {
        $action = $permanent ? 'page_delete' : 'page_trash';
        $verb   = $permanent ? 'удаления' : 'перемещения в корзину';
        logAction($action, 'Ошибка ' . $verb . ' "' . $page['title'] . '" (ID: ' . $pageId . '): ' . $result['error'], 'ERROR');
        setFlash('Ошибка: ' . $result['error'], 'error');
    }

    header('Location: ?tab=pages');
    exit;
}

/**
 * Отдаёт HTML-форму настроек модуля для конструктора.
 *
 * Проверяет CSRF, валидирует ID модуля и подключает
 * `config/modules/{id}/module.php`.
 *
 * @return void
 */
function handleGetModuleSettings(): void {
    // Проверяем CSRF-токен (для POST-запросов)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'error' => 'Критическая ошибка безопасности: CSRF-токен невалиден или устарел. Обновите страницу.']);
            exit;
        }
    }
    
    $moduleType = $_POST['module_type'] ?? $_GET['module'] ?? '';
    if (empty($moduleType)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Не указан модуль']);
        exit;
    }
    
    $moduleData = [];
    $moduleSettings = [];

    // === ВАЛИДАЦИЯ ID МОДУЛЯ ===
    if (!isValidId($moduleType)) {
        logAction('module_settings', 'Невалидный module_type: ' . $moduleType, 'ERROR');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Невалидный ID модуля']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
        $payload = json_decode($_POST['data'], true);
        $moduleData = $payload['data'] ?? [];
        $moduleSettings = $payload['settings'] ?? [];
        if (!empty($moduleSettings)) {
            $moduleData['settings'] = $moduleSettings;
        }
        
    } else {
        $moduleData = json_decode($_GET['data'] ?? '{}', true);
    }
    
    header('Content-Type: application/json');
    
    if (!$moduleType) {
        echo json_encode(['success' => false, 'error' => 'Не указан модуль']);
        exit;
    }
    
    $moduleDir = APP_ROOT . '/config/modules/' . $moduleType . '/';
    $moduleFile = $moduleDir . 'module.php';
    
    if (!file_exists($moduleFile)) {
        echo json_encode(['success' => false, 'error' => 'Модуль не найден: ' . e($moduleType)]);
        exit;
    }
    
    // Передаём данные и настройки в файл
    $moduleData = $moduleData;
    $moduleSettings = $moduleSettings;
    
    // Буферизация HTML
    ob_start();
    
    // Подключаем CSS (если есть)
    if (file_exists($moduleDir . 'style.css')) {
        echo '<link rel="stylesheet" href="/config/modules/' . $moduleType . '/style.css">';
    }
    
    // Основной файл модуля
    include $moduleFile;
    
    // Подключаем JS (если есть)
    if (file_exists($moduleDir . 'script.js')) {
        echo '<script src="/config/modules/' . $moduleType . '/script.js"></script>';
    }
    
    $html = ob_get_clean();
    
    echo json_encode(['success' => true, 'html' => $html]);
    exit;
}

/**
 * Обрабатывает действия с модулями: установка, обновление, удаление, очистка.
 *
 * @return array ['success' => bool] или результат AJAX-ответа
 */
function handleModuleAction(): array {
    $action = $_POST['module_action'] ?? '';
    $moduleId = $_POST['module_id'] ?? '';

    if (($action === 'delete' || $action === 'purge') && $moduleId) {
        if (!isValidId($moduleId)) {
            logAction('module_delete', 'Попытка удаления модуля с невалидным ID: ' . $moduleId, 'ERROR');
            setFlash('Невалидный ID модуля.', 'error');
            return ['success' => false];
        }

        // Проверяем использование
        $usedIn = findModuleUsage($moduleId);
        if (!empty($usedIn)) {
            logAction('module_delete', 'Модуль "' . $moduleId . '" используется. Удалите модуль со страниц, чтобы удалить его.', 'ERROR');
            setFlash('Модуль используется на страницах: ' . implode(', ', $usedIn) . '. Удалите модуль со страниц, чтобы удалить его.', 'error');
            return ['success' => false];
        }

        if ($action === 'purge') {
            $result = purgeModule($moduleId);
            logAction('module_purge', 'Модуль "' . $moduleId . '" удалён полностью. Удалено файлов: ' . $result['deleted'], 'INFO');
            setFlash('Модуль "' . $moduleId . '" удалён полностью. Удалено файлов: ' . $result['deleted']);
            return ['success' => true];
        }

        // Удаляем только распакованные файлы
        $result = removeModuleFiles($moduleId);
        logAction('module_delete', 'Модуль "' . $moduleId . '" удалён. Удалено файлов: ' . $result['deleted'], 'INFO');
        setFlash('Модуль "' . $moduleId . '" удалён. Удалено файлов: ' . $result['deleted']);
        return ['success' => true];
    }
    
    if ($action === 'upload') {
        // Проверяем CSRF
        if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            echo json_encode(['success' => false, 'error' => 'Ошибка безопасности: CSRF-токен невалиден']);
            exit;
        }

        // Проверяем файл
        if (!isset($_FILES['module_archive']) || $_FILES['module_archive']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Ошибка загрузки файла. Код: ' . ($_FILES['module_archive']['error'] ?? 'неизвестно')]);
            exit;
        }

        $file = $_FILES['module_archive'];

        // Лимит размера архива — 10 МБ
        if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'Архив больше 10 МБ']);
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'zip') {
            echo json_encode(['success' => false, 'error' => 'Допустимы только ZIP-архивы']);
            exit;
        }

        // Читаем manifest.json из архива
        $zip = new ZipArchive();
        $openResult = $zip->open($file['tmp_name']);
        if ($openResult !== true) {
            echo json_encode(['success' => false, 'error' => 'Не удалось открыть архив. Код: ' . $openResult]);
            exit;
        }

        $manifestContent = $zip->getFromName('manifest.json');
        $previewData = $zip->getFromName('preview.png');
        $zip->close();

        if (!$manifestContent) {
            echo json_encode(['success' => false, 'error' => 'В архиве отсутствует manifest.json']);
            exit;
        }

        $manifest = json_decode($manifestContent, true);
        if (!is_array($manifest)) {
            echo json_encode(['success' => false, 'error' => 'manifest.json имеет неверный формат']);
            exit;
        }
        $moduleId = (string)($manifest['id'] ?? '');
        
        if (empty($moduleId)) {
            echo json_encode(['success' => false, 'error' => 'В manifest.json отсутствует поле "id"']);
            exit;
        }

        // Валидация ID модуля: только латиница, цифры, дефис, подчёркивание
        if (!isValidId($moduleId)) {
            logAction('module_install', 'Попытка установки модуля с невалидным ID: ' . $moduleId, 'ERROR');
            echo json_encode(['success' => false, 'error' => 'Невалидный ID модуля. Разрешены только латиница, цифры, дефис и подчёркивание.']);
            exit;
        }
        
        $archivePath = APP_ROOT . '/config/modules/archives/' . $moduleId . '.zip';
        $archiveDir = dirname($archivePath);
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }

        // Проверяем, существует ли уже модуль
        $modules = getModulesData();
        $isUpdate = isset($modules[$moduleId]);

        if ($isUpdate) {
            // === ОБНОВЛЕНИЕ МОДУЛЯ ===
            // Старый код не удаляем: распаковка уберёт его в резервную копию
            // и вернёт, если что-то пойдёт не так.
            if (file_exists($archivePath)) {
                $attempt = 0;
                while ($attempt < 5 && !@unlink($archivePath)) {
                    $attempt++;
                    usleep(100000);
                }
            }
            $oldPreview = APP_ROOT . '/config/modules/previews/' . $moduleId . '.png';
            if (file_exists($oldPreview)) {
                unlink($oldPreview);
            }
        }

        // Сохраняем новый архив
        if (!move_uploaded_file($file['tmp_name'], $archivePath)) {
            logAction('module_install', 'Модуль "' . $moduleId . '" не загружен. Проверьте права на запись', 'ERROR');
            echo json_encode(['success' => false, 'error' => 'Не удалось сохранить архив. Проверьте права на запись.']);
            exit;
        }

        // Сохраняем превью
        if ($previewData) {
            $previewDir = APP_ROOT . '/config/modules/previews/';
            if (!is_dir($previewDir)) {
                mkdir($previewDir, 0755, true);
            }
            $previewPath = $previewDir . $moduleId . '.png';
            file_put_contents($previewPath, $previewData);
        }

        // Проверяем auto_unpack
        $autoUnpack = $manifest['auto_unpack'] ?? false;

        // Обновляем modules.json
        $modules[$moduleId] = [
            'name' => $manifest['name'] ?? $moduleId,
            'icon' => $manifest['icon'] ?? 'icon-box',
            'description' => $manifest['description'] ?? '',
            'has_settings' => $manifest['has_settings'] ?? false,
            'auto_unpack' => $autoUnpack,
            'version' => $manifest['version'] ?? '1.0.0',
            'author' => $manifest['author'] ?? ''
        ];
        saveModulesData($modules);

        $usedIn = findModuleUsage($moduleId);
        $shouldUnpack = $autoUnpack || !empty($usedIn);

        if ($shouldUnpack) {
            // Старый код уйдёт в резервную копию и вернётся при ошибке
            $unpackResult = unpackModuleArchive($archivePath, $moduleId);
            if (!$unpackResult['success']) {
                logAction('module_install', 'Модуль "' . $moduleId . '" загружен, но не распакован: ' . $unpackResult['message'], 'ERROR');
                echo json_encode([
                    'success' => false,
                    'error' => 'Модуль загружен, но не распакован: ' . $unpackResult['message']
                ]);
                exit;
            }
            logAction('module_install', 'Модуль "' . $moduleId . '" установлен/обновлён и распакован', 'INFO');
            echo json_encode([
                'success' => true,
                'message' => $isUpdate
                    ? 'Модуль "' . ($manifest['name'] ?? $moduleId) . '" обновлён и распакован!'
                    : 'Модуль "' . ($manifest['name'] ?? $moduleId) . '" успешно установлен и распакован!'
            ]);
        } else {
            // Не используется и auto_unpack выключен: код не нужен,
            // он распакуется при добавлении модуля на страницу.
            if ($isUpdate) {
                removeModuleFiles($moduleId);
            }
            $message = $isUpdate
                ? 'Модуль "' . ($manifest['name'] ?? $moduleId) . '" обновлён! Код распакуется при добавлении на страницу.'
                : 'Модуль "' . ($manifest['name'] ?? $moduleId) . '" добавлен в библиотеку! Код распакуется при добавлении на страницу.';
            logAction('module_install', $message, 'INFO');
            echo json_encode(['success' => true, 'message' => $message]);
        }
        exit;
    }
    
    $_SESSION['module_error'] = 'Неизвестное действие';
    return ['success' => false];
}

/**
 * Сохраняет меню: создаёт новое, редактирует или переименовывает ID.
 *
 * @return array ['success' => string, 'error' => string]
 */
function handleSaveMenu(): array {
    $isEdit = isset($_POST['menu_id_original']) && !empty($_POST['menu_name']);
    $menuName = trim($_POST['menu_name'] ?? '');
    $menuId = trim($_POST['menu_id'] ?? '');

    // Жесткая санация ID меню от Path Traversal (разрешаем только латиницу, цифры, дефис и подчёркивание)
    $menuId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $menuId);
    $menuIdOriginal = isset($_POST['menu_id_original']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['menu_id_original']) : '';

    if (empty($menuName)) {
        return ['success' => '', 'error' => 'Название меню обязательно для заполнения.'];
    }
    if (empty($menuId)) {
        return ['success' => '', 'error' => 'Идентификатор (ID) меню обязателен для заполнения.'];
    }

    $menusDir = DATA_DIR . 'menus/';
    if (!is_dir($menusDir)) {
        mkdir($menusDir, 0755, true);
    }
    // Управление файловой структурой меню
    if (!$isEdit) {
        // Режим создания: проверяем, не занят ли ID
        if (file_exists($menusDir . $menuId . '.json')) {
            return ['success' => '', 'error' => 'Меню с таким идентификатором уже существует.'];
        }
        $id = $menuId;
    } else {
        // Режим редактирования
        $id = $menuIdOriginal;
        if (!file_exists($menusDir . $id . '.json')) {
            return ['success' => '', 'error' => 'Редактируемое меню не найдено.'];
        }

        // Если администратор изменил ID меню
        if ($menuId !== $id) {
            if (file_exists($menusDir . $menuId . '.json')) {
                return ['success' => '', 'error' => 'Новый идентификатор меню уже занят другим меню.'];
            }

            // Переименовываем старый файл на диске
            if (!rename($menusDir . $id . '.json', $menusDir . $menuId . '.json')) {
                return ['success' => '', 'error' => 'Не удалось переименовать файл меню. Проверьте права.'];
            }

            // Если это меню было главным в настройках сайта — обновляем его ID в globals/settings
            if ($id === getMainMenuId()) {
                setMainMenuId($menuId);
            }

            $id = $menuId; // Обновляем рабочий ID на новый
        }
    }
    // Получаем структурированный JSON пунктов меню с фронтенда
    $itemsJson = $_POST['menu_items_json'] ?? '[]';
    $items = json_decode($itemsJson, true);
    if (!is_array($items)) {
        $items = [];
    }

    // Формируем чистый иерархический массив данных для JSON-базы
    $menuData = [
        'id' => $id,
        'name' => $menuName,
        'items' => $items
    ];

    // Сохраняем файл через встроенную атомарную функцию ядра
    if (saveMenu($id, $menuData)) {
        logAction('menu_save', 'Меню "' . $menuName . '" сохранено (ID: ' . $id . ')', 'INFO');
        setFlash('Меню "' . $menuName . '" успешно сохранено!');
        // Перенаправляем на актуальный ID (в случае переименования он будет новым)
        header('Location: ?tab=menu&action=edit&id=' . $id);
        exit;
    }
    logAction('menu_save', 'Ошибка сохранения меню "' . $menuName . '" (ID: ' . $id . ')', 'ERROR');
    return ['success' => '', 'error' => 'Ошибка при записи файла меню на диск.'];
}

/**
 * Назначает меню главным или снимает этот статус.
 *
 * @return void
 */
function handleSetMainMenu(): void {
    $menuId = $_POST['id'] ?? '';
    if (empty($menuId)) {
        setFlash('Ошибка: ID меню не указан.', 'error');
        header('Location: ?tab=menu');
        exit;
    }
    // ID пойдёт в settings.main_menu и в лог — вырезаем всё, кроме разрешённых символов
    $menuId = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$menuId);
    if ($menuId === '') {
        setFlash('Ошибка: ID меню не указан.', 'error');
        header('Location: ?tab=menu');
        exit;
    }
    
    $menu = loadMenu($menuId);
    if (!$menu) {
        setFlash('Ошибка: меню не найдено.', 'error');
        header('Location: ?tab=menu');
        exit;
    }
    
    $settings = getSettingsData();
    $currentMain = $settings['main_menu'] ?? null;
    
    if ($currentMain === $menuId) {
        unset($settings['main_menu']);
        logAction('menu_set_main', 'Статус главного меню снят с "' . $menu['name'] . '" (ID: ' . $menuId . ')', 'INFO');
        setFlash('Статус главного меню снят с "' . $menu['name'] . '".', 'info');
    } else {
        $settings['main_menu'] = $menuId;
        logAction('menu_set_main', 'Меню "' . $menu['name'] . '" назначено главным (ID: ' . $menuId . ')', 'INFO');
        setFlash('Меню "' . $menu['name'] . '" теперь главное!');
    }
    
    saveData('settings', $settings);
    header('Location: ?tab=menu');
    exit;
}

/**
 * Удаляет меню: в корзину, если плагин активен, иначе окончательно.
 *
 * @return void
 */
function handleDeleteMenu(): void {
    $menuId = $_POST['id'] ?? '';
    if (empty($menuId)) {
        setFlash('Ошибка: ID меню не указан.', 'error');
        header('Location: ?tab=menu');
        exit;
    }
    
    // Проверяем, не главное ли это меню
    $settings = getSettingsData();
    $mainMenuId = $settings['main_menu'] ?? null;
    
    if ($menuId === $mainMenuId) {
        setFlash('Ошибка: нельзя удалить главное меню.', 'error');
        header('Location: ?tab=menu');
        exit;
    }
    
    $menu = loadMenu($menuId);
    if (!$menu) {
        setFlash('Ошибка: меню не найдено.', 'error');
        header('Location: ?tab=menu');
        exit;
    }
    
    // Корзина живёт в плагине. null — плагин не активен, удаляем окончательно.
    $result = apply_filters('trash_entity_result', null, 'menu', $menuId);
    $permanent = ($result === null);

    if ($permanent) {
        $result = deleteEntityPermanently('menu', $menuId, DATA_DIR . 'menus' . DIRECTORY_SEPARATOR);
    } elseif (!is_array($result)) {
        $result = ['success' => false, 'error' => 'Некорректный ответ плагина корзины.'];
    }

    if ($result['success']) {
        if ($permanent) {
            logAction('menu_delete', 'Меню "' . $menu['name'] . '" удалено окончательно (ID: ' . $menuId . ')', 'INFO');
            setFlash('Меню "' . $menu['name'] . '" удалено окончательно.');
        } else {
            logAction('menu_trash', 'Меню "' . $menu['name'] . '" перемещено в корзину (ID: ' . $menuId . ')', 'INFO');
            setFlash('Меню "' . $menu['name'] . '" перемещено в корзину.');
        }
    } else {
        $action = $permanent ? 'menu_delete' : 'menu_trash';
        $verb   = $permanent ? 'удаления' : 'перемещения в корзину';
        logAction($action, 'Ошибка ' . $verb . ' "' . $menu['name'] . '" (ID: ' . $menuId . '): ' . $result['error'], 'ERROR');
        setFlash('Ошибка: ' . $result['error'], 'error');
    }

    header('Location: ?tab=menu');
    exit;
}

/**
 * 14. СИСТЕМА ЛОГГИРОВАНИЯ
 */

/**
 * Запись в лог (только для админки)
 *
 * @param string $action   Действие (page_save, file_delete, backup_create и т.д.)
 * @param string $message  Сообщение
 * @param string $level    Уровень: INFO, WARNING, ERROR, CRITICAL
 * @param string|null $user Имя пользователя (если не указан — берётся из сессии)
 * @return void
 */
function logAction(string $action, string $message, string $level = 'INFO', ?string $user = null): void {
    $settings = getLogSettings();

    // Проверка 1: логирование выключено — не пишем
    if (empty($settings['log_enabled'])) {
        return;
    }

    // Проверка 2: действие отключено в настройках — не пишем
    $allowedActions = $settings['log_actions'] ?? [];
    if (empty($allowedActions[$action])) {
        return;
    }

    // Проверка 3: путь к логам (пустая строка → fallback на CMS_LOGS_DIR)
    $logDir = trim($settings['storage_path'] ?? '') ?: CMS_LOGS_DIR;

    // Нормализуем слэши для кросс-платформенного сравнения
    $normalizedLogDir    = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logDir);
    $normalizedConfigDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, CONFIG_DIR);
    $normalizedDataDir   = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, DATA_DIR);

    // Абсолютный путь (Linux/Mac: /... | Windows: C:\... или C:/...)
    $isAbsolute = preg_match('#^(/|[a-zA-Z]:[\\\\/])#', $logDir) === 1;

    // Внутри сайта (config/ или data/) — регистронезависимо (Windows)
    $isInsideSite = stripos($normalizedLogDir, $normalizedConfigDir) === 0
                 || stripos($normalizedLogDir, $normalizedDataDir) === 0;

    // Разрешены: абсолютные пути и пути внутри config/ или data/
    // Всё остальное (относительные пути вне сайта) — fallback на дефолт
    if (!$isAbsolute && !$isInsideSite) {
        $logDir = CMS_LOGS_DIR;
    }

    // Нормализуем trailing separator
    $logDir = rtrim($logDir, '/\\') . DIRECTORY_SEPARATOR;

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . 'admin.log';
    
    $user = $user ?? ($_SESSION['admin_login'] ?? 'system');
    $user = str_replace(["\r", "\n"], ' ', $user);
    $ip = str_replace(["\r", "\n"], ' ', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $timestamp = date('Y-m-d H:i:s');
    $level = strtoupper($level);

    // CR/LF и управляющие символы подделывают строки журнала (log injection)
    $action  = str_replace(["\r", "\n"], ' ', $action);
    $message = str_replace(["\r", "\n"], ' ', $message);
    $level   = str_replace(["\r", "\n"], ' ', $level);
    
    $line = sprintf(
        "[%s] [%s] [%s] [%s] %s: %s\n",
        $timestamp,
        $level,
        $user,
        $ip,
        $action,
        $message
    );
    
    safeFileWrite($logFile, $line, true);
    
    // Ротация по размеру
    $maxSize = $settings['max_size'] ?? 5 * 1024 * 1024; // 5 MB по умолчанию
    if (file_exists($logFile) && filesize($logFile) > $maxSize) {
        rotateLog($logDir);
    }
}

/**
 * Ротирует лог-файл и удаляет старые архивы сверх лимита.
 *
 * @param string $logDir Папка с `admin.log`
 * @return void
 */
function rotateLog(string $logDir): void {
    $logFile = $logDir . 'admin.log';
    if (!file_exists($logFile)) return;
    
    $date = date('Y-m-d_H-i-s');
    $archiveDir = $logDir . 'archive/';
    if (!is_dir($archiveDir)) {
        @mkdir($archiveDir, 0755, true);
    }
    
    $archiveFile = $archiveDir . 'admin-' . $date . '.log';
    @rename($logFile, $archiveFile);
    
    // Оставляем только последние N архивов
    $settings = getLogSettings();
    $keepCount = $settings['keep_count'] ?? 10;
    
    $archives = glob($archiveDir . 'admin-*.log');
    usort($archives, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    while (count($archives) > $keepCount) {
        $oldest = array_shift($archives);
        if (file_exists($oldest)) {
            @unlink($oldest);
        }
    }
}

/**
 * Гарантирует существование папки логов (по пути из настроек или CMS_LOGS_DIR) + .htaccess.
 * Вызывается на admin_init; защита папки не зависит от плагина logs.
 *
 * @return void
 */
function ensureLogsDir(): void {
    $settings = getLogSettings();
    $logDir = trim($settings['storage_path'] ?? '') ?: CMS_LOGS_DIR;

    // Нормализуем trailing separator
    $logDir = rtrim($logDir, '/\\') . DIRECTORY_SEPARATOR;

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    // .htaccess — только если папка внутри сайта (вне сайта не нужно)
    $realRoot = realpath(APP_ROOT);
    $realLogs = realpath($logDir);
    if ($realRoot !== false && $realLogs !== false && strpos($realLogs, $realRoot) === 0) {
        $htaccess = $logDir . '.htaccess';
        if (!file_exists($htaccess)) {
            $data = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
                  . "<IfModule !mod_authz_core.c>\n    Order Deny,Allow\n    Deny from all\n</IfModule>\n";
            @file_put_contents($htaccess, $data);
        }
    }
}

/**
 * Возвращает настройки логирования с дефолтами.
 *
 * Список действий берётся из `get_log_actions()`; сохранённый выбор
 * приоритетнее. Новые действия включены по умолчанию.
 *
 * @return array Настройки `log_enabled`, `log_levels`, `storage_path`, `max_size`, `keep_count`, `log_actions`
 */
function getLogSettings(): array {
    $settings = getData('settings');

    // Список действий берётся из реестра: все по умолчанию включены
    $logActions = [];
    foreach (get_log_actions() as $action => $label) {
        $logActions[$action] = true;
    }

    $defaults = [
        'log_enabled' => true,
        'log_levels' => ['INFO', 'WARNING', 'ERROR', 'CRITICAL'],
        'storage_path' => CMS_LOGS_DIR,
        'max_size' => 5 * 1024 * 1024, // 5 MB
        'keep_count' => 10,
        'log_actions' => $logActions,
    ];

    $saved  = $settings['log_settings'] ?? [];
    $result = array_merge($defaults, $saved);

    // Новые действия включаются по умолчанию; сохранённый выбор приоритетнее
    if (isset($saved['log_actions']) && is_array($saved['log_actions'])) {
        $result['log_actions'] = array_merge($logActions, $saved['log_actions']);
    }

    return $result;
}

add_action('admin_init', 'ensureLogsDir');

/**
 * =========================================================================
 * МИНИФИКАЦИЯ АССЕТОВ
 * =========================================================================
 */

/**
 * Минифицирует CSS: убирает BOM, комментарии и лишние пробелы.
 *
 * @param string $css Исходный CSS
 * @return string Минифицированный CSS
 */
function minifyCss($css) {
    // Удаляем BOM
    $css = preg_replace('/^\xEF\xBB\xBF/', '', $css);
    
    // Удаляем многострочные комментарии /* ... */
    $css = preg_replace('/\/\*.*?\*\//s', '', $css);
    
    // Удаляем однострочные комментарии // (но не трогаем http://)
    $css = preg_replace('/\/\/[^\n]*/', '', $css);
    
    // Удаляем пробелы в начале и конце строк
    $css = preg_replace('/^\s+|\s+$/m', '', $css);
    
    // Удаляем лишние пробелы и переносы
    $css = preg_replace('/\s+/', ' ', $css);
    
    // Удаляем пробелы вокруг { } : ; ,
    $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
    
    // Удаляем пробелы вокруг > и +
    $css = preg_replace('/\s*([>+])\s*/', '$1', $css);
    
    // Удаляем последнюю точку с запятой в блоке
    $css = preg_replace('/;}/', '}', $css);
    
    // Удаляем пробел после ( и перед )
    $css = preg_replace('/\(\s+/', '(', $css);
    $css = preg_replace('/\s+\)/', ')', $css);
    
    return trim($css);
}

/**
 * Минифицирует JS: убирает BOM, комментарии и лишние пробелы.
 *
 * @param string $js Исходный JS
 * @return string Минифицированный JS
 */
function minifyJs($js) {
    // Удаляем BOM
    $js = preg_replace('/^\xEF\xBB\xBF/', '', $js);
    
    // Удаляем многострочные комментарии /* ... */
    $js = preg_replace('/\/\*.*?\*\//s', '', $js);
    
    // Удаляем однострочные комментарии // (но не трогаем http://)
    $js = preg_replace('/\/\/[^\n]*/', '', $js);
    
    // Удаляем пробелы в начале и конце строк
    $js = preg_replace('/^\s+|\s+$/m', '', $js);
    
    // Удаляем лишние пробелы и переносы
    $js = preg_replace('/\s+/', ' ', $js);
    
    // Удаляем пробелы вокруг { } ; , ( )
    $js = preg_replace('/\s*([{}();:,])\s*/', '$1', $js);
    
    // Удаляем пробелы вокруг операторов
    $js = preg_replace('/\s*([!<>=])\s*/', '$1', $js);
    
    return trim($js);
}

/**
 * Создаёт `.min.css` / `.min.js` для одного модуля.
 *
 * @param string $moduleId ID модуля
 * @return array Список созданных файлов относительно корня
 */
function createMinFilesForModule($moduleId) {
    $baseDir = APP_ROOT;
    $created = [];
    
    // CSS
    $cssFile = $baseDir . '/css/' . $moduleId . '.css';
    if (file_exists($cssFile)) {
        $minCssFile = $baseDir . '/css/' . $moduleId . '.min.css';
        $content = file_get_contents($cssFile);
        $minContent = minifyCss($content);
        if (file_put_contents($minCssFile, $minContent) !== false) {
            $created[] = 'css/' . $moduleId . '.min.css';
        }
    }
    
    // JS
    $jsFile = $baseDir . '/js/' . $moduleId . '.js';
    if (file_exists($jsFile)) {
        $minJsFile = $baseDir . '/js/' . $moduleId . '.min.js';
        $content = file_get_contents($jsFile);
        $minContent = minifyJs($content);
        if (file_put_contents($minJsFile, $minContent) !== false) {
            $created[] = 'js/' . $moduleId . '.min.js';
        }
    }
    
    return $created;
}

/**
 * Создаёт .min файлы для всех модулей
 * 
 * @param array $settings Настройки сайта (опционально)
 * @return array Список созданных файлов
 */
function createMinFilesForAllModules($settings = null) {
    if ($settings === null) {
        $settings = getSettingsData();
    }
    
    if (empty($settings['assets_minify'])) {
        return [];
    }
    
    $modules = getModulesData();
    $exceptions = $settings['minify_exceptions'] ?? [];
    $created = [];
    
    foreach (array_keys($modules) as $moduleId) {
        // Проверяем исключения: если модуль в исключениях по CSS и JS — пропускаем
        if (!empty($exceptions[$moduleId]['css']) && !empty($exceptions[$moduleId]['js'])) {
            continue;
        }
        $result = createMinFilesForModule($moduleId);
        $created = array_merge($created, $result);
    }
    
    return $created;
}

/**
 * Получить статистику минификации.
 *
 * @param array|null $exceptions Исключения; null — взять из настроек
 * @return array ['css' => [...], 'js' => [...]]
 */
function getMinifyStats($exceptions = null): array {
    $modules = getModulesData();
    $settings = getSettingsData();
    $minifyEnabled = $settings['assets_minify'] ?? false;
    
    // Если исключения не переданы — берём из настроек
    if ($exceptions === null) {
        $exceptions = $settings['minify_exceptions'] ?? [];
    }
    
    $stats = [
        'css' => ['total' => 0, 'minified' => 0, 'ratio' => 0, 'original_size' => 0, 'minified_size' => 0],
        'js' => ['total' => 0, 'minified' => 0, 'ratio' => 0, 'original_size' => 0, 'minified_size' => 0]
    ];
    
    foreach (array_keys($modules) as $id) {
        // CSS
        $cssFile = APP_ROOT . '/css/' . $id . '.css';
        $minCssFile = APP_ROOT . '/css/' . $id . '.min.css';
        if (file_exists($cssFile)) {
            $stats['css']['total']++;
            $stats['css']['original_size'] += filesize($cssFile);
            
            $isExcluded = !empty($exceptions[$id]['css']);
            if ($minifyEnabled && !$isExcluded && file_exists($minCssFile)) {
                $stats['css']['minified']++;
                $stats['css']['minified_size'] += filesize($minCssFile);
            }
        }
        
        // JS
        $jsFile = APP_ROOT . '/js/' . $id . '.js';
        $minJsFile = APP_ROOT . '/js/' . $id . '.min.js';
        if (file_exists($jsFile)) {
            $stats['js']['total']++;
            $stats['js']['original_size'] += filesize($jsFile);
            
            $isExcluded = !empty($exceptions[$id]['js']);
            if ($minifyEnabled && !$isExcluded && file_exists($minJsFile)) {
                $stats['js']['minified']++;
                $stats['js']['minified_size'] += filesize($minJsFile);
            }
        }
    }
    
    // Вычисляем соотношение
    foreach (['css', 'js'] as $type) {
        if ($stats[$type]['total'] > 0) {
            $stats[$type]['ratio'] = $stats[$type]['minified'] / $stats[$type]['total'];
        } else {
            $stats[$type]['ratio'] = 0;
        }
    }
    
    return $stats;
}

/**
 * Форматирует размер файла в читаемый вид.
 *
 * @param int $bytes Размер в байтах
 * @return string Например, `12.4 KB`
 */
function formatSize($bytes): string {
    if ($bytes === 0) {
        return '0 B';
    }
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 1) . ' ' . $units[$i];
}

/**
 * Отдаёт статистику минификации в JSON (AJAX).
 *
 * @return void
 */
function handleGetMinifyStats(): void {
    $excludeCss = $_GET['minify_exclude_css'] ?? [];
    $excludeJs = $_GET['minify_exclude_js'] ?? [];
    
    $exceptions = null;
    if (!empty($excludeCss) || !empty($excludeJs)) {
        $modules = getModulesData();
        $exceptions = [];
        foreach (array_keys($modules) as $id) {
            $exceptions[$id] = [
                'css' => isset($excludeCss[$id]) && $excludeCss[$id] == '1',
                'js' => isset($excludeJs[$id]) && $excludeJs[$id] == '1'
            ];
        }
    }
    
    $stats = getMinifyStats($exceptions);
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

/**
 * Сохраняет исключения минификации из обычной формы и редиректит.
 *
 * @return void
 */
function handleSaveMinifyExceptions(): void {
    $exceptions = [];
    $excludeCss = $_POST['exclude_css'] ?? [];
    $excludeJs = $_POST['exclude_js'] ?? [];
    
    $modules = getModulesData();
    foreach (array_keys($modules) as $id) {
        $exceptions[$id] = [
            'css' => isset($excludeCss[$id]) && $excludeCss[$id] == '1',
            'js' => isset($excludeJs[$id]) && $excludeJs[$id] == '1'
        ];
    }
    
    $settings = getSettingsData();
    $settings['minify_exceptions'] = $exceptions;
    
    if (saveData('settings', $settings)) {
        setFlash('Настройки исключений минификации сохранены!', 'success');
    } else {
        setFlash('Ошибка сохранения исключений', 'error');
    }
    
    header('Location: index.php?tab=config_vars');
    exit;
}

/**
 * Создаёт `.min`-файлы для всех модулей с учётом переданных исключений.
 *
 * @param array $settings Настройки сайта
 * @param array $exceptions Исключения `[moduleId => ['css' => bool, 'js' => bool]]`
 * @return array Список созданных файлов
 */
function createMinFilesForAllModulesWithExceptions($settings, $exceptions) {
    if (empty($settings['assets_minify'])) {
        return [];
    }
    
    $modules = getModulesData();
    $created = [];
    
    foreach (array_keys($modules) as $moduleId) {
        // Проверяем исключения из переданных данных
        if (!empty($exceptions[$moduleId]['css']) && !empty($exceptions[$moduleId]['js'])) {
            continue;
        }
        $result = createMinFilesForModule($moduleId);
        $created = array_merge($created, $result);
    }
    
    return $created;
}

/**
 * Пересобирает `.min` для всех модулей (AJAX).
 *
 * @param array $excludeCss Модули, исключённые для CSS
 * @param array $excludeJs Модули, исключённые для JS
 * @return void
 */
function handleRebuildAllMin($excludeCss = [], $excludeJs = []): void {
    $settings = getSettingsData();
    
    if (empty($settings['assets_minify'])) {
        echo json_encode(['success' => false, 'error' => 'Минификация выключена в настройках']);
        exit;
    }
    
    // Формируем исключения из переданных данных
    $exceptions = [];
    $modules = getModulesData();
    foreach (array_keys($modules) as $id) {
        $exceptions[$id] = [
            'css' => isset($excludeCss[$id]) && $excludeCss[$id] == '1',
            'js' => isset($excludeJs[$id]) && $excludeJs[$id] == '1'
        ];
    }
    
    // Удаляем все .min файлы
    $minFiles = glob(APP_ROOT . '/css/*.min.css');
    foreach ($minFiles as $file) {
        @unlink($file);
    }
    $minFiles = glob(APP_ROOT . '/js/*.min.js');
    foreach ($minFiles as $file) {
        @unlink($file);
    }
    
    // Создаём заново с учётом переданных исключений
    $result = createMinFilesForAllModulesWithExceptions($settings, $exceptions);
    
    echo json_encode(['success' => true, 'message' => 'Пересобрано .min файлов: ' . count($result)]);
    exit;
}

/**
 * Пересобирает `.min` для одного модуля (AJAX).
 *
 * @param string $moduleId ID модуля
 * @return void
 */
function handleRebuildModuleMin($moduleId): void {
    if (empty($moduleId)) {
        echo json_encode(['success' => false, 'error' => 'Не указан модуль']);
        exit;
    }
    
    $settings = getSettingsData();
    
    if (empty($settings['assets_minify'])) {
        echo json_encode(['success' => false, 'error' => 'Минификация выключена в настройках']);
        exit;
    }
    
    // Читаем исключения из настроек
    $exceptions = $settings['minify_exceptions'] ?? [];
    
    // Проверяем, не в исключениях ли модуль
    $cssExcluded = !empty($exceptions[$moduleId]['css']);
    $jsExcluded = !empty($exceptions[$moduleId]['js']);
    
    // Если оба в исключениях — ничего не делаем
    if ($cssExcluded && $jsExcluded) {
        echo json_encode(['success' => false, 'error' => 'Модуль полностью в исключениях минификации']);
        exit;
    }
    
    $created = [];
    
    // CSS — если не в исключениях
    if (!$cssExcluded) {
        $cssFile = APP_ROOT . '/css/' . $moduleId . '.css';
        $minCssFile = APP_ROOT . '/css/' . $moduleId . '.min.css';
        if (file_exists($cssFile)) {
            if (file_exists($minCssFile)) {
                @unlink($minCssFile);
            }
            $content = file_get_contents($cssFile);
            $minContent = minifyCss($content);
            if (file_put_contents($minCssFile, $minContent) !== false) {
                $created[] = 'css/' . $moduleId . '.min.css';
            }
        }
    }
    
    // JS — если не в исключениях
    if (!$jsExcluded) {
        $jsFile = APP_ROOT . '/js/' . $moduleId . '.js';
        $minJsFile = APP_ROOT . '/js/' . $moduleId . '.min.js';
        if (file_exists($jsFile)) {
            if (file_exists($minJsFile)) {
                @unlink($minJsFile);
            }
            $content = file_get_contents($jsFile);
            $minContent = minifyJs($content);
            if (file_put_contents($minJsFile, $minContent) !== false) {
                $created[] = 'js/' . $moduleId . '.min.js';
            }
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Пересобрано .min файлов: ' . count($created)]);
    exit;
}

/**
 * Удаляет все `.min`-файлы (AJAX).
 *
 * @return void
 */
function handleDeleteAllMin(): void {
    $deleted = 0;
    
    // CSS
    $minFiles = glob(APP_ROOT . '/css/*.min.css');
    foreach ($minFiles as $file) {
        if (unlink($file)) {
            $deleted++;
        }
    }
    
    // JS
    $minFiles = glob(APP_ROOT . '/js/*.min.js');
    foreach ($minFiles as $file) {
        if (unlink($file)) {
            $deleted++;
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Удалено .min файлов: ' . $deleted]);
    exit;
}

/**
 * Сохраняет исключения минификации через AJAX.
 *
 * @return void
 */
function handleSaveMinifyExceptionsAjax(): void {
    // Проверка CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        echo json_encode(['success' => false, 'error' => 'Ошибка безопасности: CSRF-токен невалиден']);
        exit;
    }
    
    $exceptions = [];
    $excludeCss = $_POST['exclude_css'] ?? [];
    $excludeJs = $_POST['exclude_js'] ?? [];
    
    $modules = getModulesData();
    foreach (array_keys($modules) as $id) {
        $exceptions[$id] = [
            'css' => isset($excludeCss[$id]) && $excludeCss[$id] == '1',
            'js' => isset($excludeJs[$id]) && $excludeJs[$id] == '1'
        ];
    }
    
    $settings = getSettingsData();
    $settings['minify_exceptions'] = $exceptions;
    
    if (saveData('settings', $settings)) {
        echo json_encode(['success' => true, 'message' => 'Исключения сохранены']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ошибка сохранения']);
    }
    exit;
}

/**
 * Очищает кеш страниц и объединённых ассетов.
 *
 * @return void
 */
function clearPageCache(): void {
    // Очищаем кеш страниц
    $cacheDir = APP_ROOT . '/cache/pages/';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*.html');
        foreach ($files as $file) {
            @unlink($file);
        }
    }
    
    // Очищаем кеш объединённых ассетов
    $assetsDir = APP_ROOT . '/cache/assets/';
    if (is_dir($assetsDir)) {
        $files = glob($assetsDir . '*.{css,js}', GLOB_BRACE);
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

/**
 * Сбрасывает кеш страницы по её ID: HTML-кеш + объединённые ассеты.
 * Учитывает, что главная страница кешируется как home.html.
 *
 * @param string $id ID страницы
 * @return void
 */
function clearPageCacheById(string $id): void {
    if ($id === '') {
        return;
    }

    $cacheKey = ($id === getHomePageId()) ? 'home' : $id;

    // HTML-кеш страницы
    $cacheFile = APP_ROOT . '/cache/pages/' . $cacheKey . '.html';
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }

    // Объединённые ассеты страницы
    $files = glob(APP_ROOT . '/cache/assets/' . $cacheKey . '.{css,js}', GLOB_BRACE);
    if (is_array($files)) {
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

/**
 * ПЛАГИНЫ
 */
/**
 * Обработчик загрузки и установки плагина из ZIP (POST).
 *
 * @return array ['success' => string, 'error' => string]
 */
function handlePluginUpload(): array {
    if (!isset($_FILES['plugin_zip']) || $_FILES['plugin_zip']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => '', 'error' => 'Ошибка загрузки файла.'];
    }
    
    $tempFile = $_FILES['plugin_zip']['tmp_name'];
    
    // Проверяем, что это ZIP
    $zip = new ZipArchive();
    if ($zip->open($tempFile) !== true) {
        return ['success' => '', 'error' => 'Файл не является ZIP-архивом.'];
    }
    
    // Читаем манифест (строго в корне архива)
    $manifestJson = $zip->getFromName('plugin.json');
    
    if (!$manifestJson) {
        $zip->close();
        return ['success' => '', 'error' => 'В корне архива отсутствует plugin.json. Убедитесь, что вы упаковали файлы плагина, а не папку с ними.'];
    }
    
    $zip->close();
    
    $manifest = json_decode($manifestJson, true);
    $id = $manifest['id'] ?? '';
    
    // Валидация ID
    if (empty($id) || !isValidId($id)) {
        return ['success' => '', 'error' => 'В plugin.json отсутствует или невалидное поле "id".'];
    }
    
     // Читаем версию из нового манифеста
    $newVersion = $manifest['version'] ?? '1.0.0';
    
    // Проверка: не установлен ли уже
    $plugins = getPluginsData();
    if (isset($plugins[$id])) {
        $currentVersion = $plugins[$id]['version'] ?? '0.0.0';
        
        // Если версии совпадают — пропускаем
        if (version_compare($newVersion, $currentVersion, '==')) {
            return ['success' => '', 'error' => 'Плагин с ID "' . e($id) . '" уже установлен (версия ' . e($currentVersion) . '). Обновление не требуется.'];
        }
        
        // Если новая версия ниже текущей — предупреждаем
        if (version_compare($newVersion, $currentVersion, '<')) {
            return ['success' => '', 'error' => 'Установленная версия плагина (' . e($currentVersion) . ') выше загруженной (' . e($newVersion) . ').'];
        }
        
        // Если новая версия выше — это обновление, продолжаем
        
        // Хук ПЕРЕД обновлением
        do_action('plugin_before_update', $id, $newVersion);
    }
    
    // Устанавливаем (с перезаписью)
    require_once __DIR__ . '/plugins.php';
    $result = installPluginFromZip($tempFile, $id);
    if ($result['success']) {
        if (isset($plugins[$id])) {
            do_action('plugin_after_update', $id, $newVersion);
            logAction('plugin_update', 'Плагин "' . $id . '" обновлён до версии ' . $newVersion, 'INFO');
            $msg = 'Плагин "' . e($id) . '" успешно обновлен до версии ' . e($newVersion) . '!';
        } else {
            do_action('plugin_after_install', $id);
            logAction('plugin_install', 'Установлен плагин "' . $id . '" (версия ' . $newVersion . ')', 'INFO');
            $msg = $result['message'];
        }
        return ['success' => $msg, 'error' => ''];
    }
}

/**
 * Обрабатывает все POST-действия с плагинами (загрузка, вкл/выкл, удаление).
 *
 * @return array ['success' => string, 'error' => string]
 */
function handlePluginAction(): array {
    $action = $_POST['plugin_action'] ?? '';
    $pluginId = $_POST['plugin_id'] ?? '';
    
    // --- УСТАНОВКА (UPLOAD) ---
    if ($action === 'upload') {
        return handlePluginUpload(); // Функция, которую мы уже написали
    }
    
    // --- УДАЛЕНИЕ ---
    if ($action === 'delete' && $pluginId) {
        if (!isValidId($pluginId)) {
            return ['success' => '', 'error' => 'Невалидный ID плагина.'];
        }
        
        $plugins = getPluginsData();
        if (!isset($plugins[$pluginId])) {
            return ['success' => '', 'error' => 'Плагин не найден.'];
        }
        
        // Хук ДО удаления (пока код плагина еще доступен)
        do_action('plugin_before_delete', $pluginId);
        
        // Удаляем папку плагина
        $pluginDir = PLUGINS_DIR . DIRECTORY_SEPARATOR . $pluginId;
        if (is_dir($pluginDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($pluginDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($pluginDir);
        }
        
        // Удаляем публичные ассеты (css и js)
        $cssFile = APP_ROOT . '/css/' . $pluginId . '.css';
        if (file_exists($cssFile)) {
            @unlink($cssFile);
        }
        
        $jsFile = APP_ROOT . '/js/' . $pluginId . '.js';
        if (file_exists($jsFile)) {
            @unlink($jsFile);
        }
        
        // Удаляем минифицированные версии, если были
        $cssMinFile = APP_ROOT . '/css/' . $pluginId . '.min.css';
        if (file_exists($cssMinFile)) {
            @unlink($cssMinFile);
        }
        
        $jsMinFile = APP_ROOT . '/js/' . $pluginId . '.min.js';
        if (file_exists($jsMinFile)) {
            @unlink($jsMinFile);
        }
        
        // Удаляем ассеты админки (css и js)
        $adminCssFile = CONFIG_DIR . '/css/' . $pluginId . '.css';
        if (file_exists($adminCssFile)) {
            @unlink($adminCssFile);
        }
        
        $adminCssMinFile = CONFIG_DIR . '/css/' . $pluginId . '.min.css';
        if (file_exists($adminCssMinFile)) {
            @unlink($adminCssMinFile);
        }
        
        $adminJsFile = CONFIG_DIR . '/js/' . $pluginId . '.js';
        if (file_exists($adminJsFile)) {
            @unlink($adminJsFile);
        }
        
        $adminJsMinFile = CONFIG_DIR . '/js/' . $pluginId . '.min.js';
        if (file_exists($adminJsMinFile)) {
            @unlink($adminJsMinFile);
        }
        
        // Удаляем из JSON
        // Имя плагина — ДО unset
        $pluginName = $plugins[$pluginId]['name'] ?? $pluginId;

        unset($plugins[$pluginId]);
        savePluginsData($plugins);
        do_action('plugin_after_delete', $pluginId);
        logAction('plugin_delete', 'Удалён плагин "' . $pluginId . '"' . ($pluginName !== $pluginId ? ' (' . $pluginName . ')' : ''), 'INFO');

        // Чистим кеш
        $cacheFile = DATA_DIR . 'updates_cache.json';
        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true);
            unset($cache['data']['new'][$pluginId]);
            safeFileWrite($cacheFile, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        
        return ['success' => 'Плагин "' . e($pluginName) . '" успешно удален.', 'error' => ''];
    }
    
    // --- ПЕРЕКЛЮЧЕНИЕ СТАТУСА ---
    if ($action === 'toggle' && $pluginId) {
        if (!isValidId($pluginId)) {
            return ['success' => '', 'error' => 'Невалидный ID плагина.'];
        }
        
        $plugins = getPluginsData();
        if (!isset($plugins[$pluginId])) {
            return ['success' => '', 'error' => 'Плагин не найден.'];
        }
        
        $active = ($_POST['plugin_active'] ?? '0') === '1';
        
        // Хук ДО переключения
        do_action('plugin_before_toggle', $pluginId, $active);
        
        $plugins[$pluginId]['active'] = $active;
        savePluginsData($plugins);
        
        // Хук ПОСЛЕ переключения
        do_action('plugin_after_toggle', $pluginId, $active);

        $pluginName = $plugins[$pluginId]['name'] ?? $pluginId;
        logAction('plugin_toggle', 'Плагин "' . $pluginId . '" ' . ($active ? 'включён' : 'выключен') . ($pluginName !== $pluginId ? ' (' . $pluginName . ')' : ''), 'INFO');

        return [
            'success' => 'Плагин "' . e($pluginName) . '" ' . ($active ? 'включен' : 'выключен') . '.',
            'error' => ''
        ];
    }
    
    return ['success' => '', 'error' => 'Неизвестное действие.'];
}