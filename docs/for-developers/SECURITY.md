# Безопасность в коде AlPin CMS

## 🛡 Меры безопасности

### Список

| Угроза | Защита |
|--------|--------|
| **CSRF** | `csrf_token` на всех POST |
| **XSS** | `e()`, `escapeJsString()`, `sanitizeHtml()` |
| **ZIP Slip** | `detectZipSlip()` |
| **Path Traversal** | `fmSafePath()`, `fmSafeAbsolute()`, `safeUrl()` |
| **LFI/RCE** | Валидация ID модулей (regex) |
| **Brute-force** | Ограничение попыток + бан по IP |
| **Session fixation** | `session_regenerate_id(true)`, `use_strict_mode` |
| **Session hijacking** | `httponly`, `SameSite=Lax` |
| **SQL Injection** | Нет БД — неактуально |
| **Host Header Injection** | Валидация `HTTP_HOST` |
| **Открытые эндпоинты** | Проверка `admin_auth` |

---

## 🔒 CSRF

### Как работает

**Все** **POST-запросы** **в** **админке** **проверяют** `csrf_token`:

```php
if (empty($_POST['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Ошибка безопасности: невалидный CSRF-токен');
}
```

**`hash_equals`** — **сравнение** **за** **постоянное** **время**.

**Токен** **генерируется** **в** `config.php`:
```php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

**Токен** **передаётся**:
- **В** **формах** — `<input type="hidden" name="csrf_token">`.
- **В** **AJAX** — **в** `FormData` **или** **body**.

### Что делать при добавлении POST

**Всегда** **проверяй** `csrf_token` **в** **начале** **обработчика**:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        header('HTTP/1.1 403 Forbidden');
        exit('CSRF-токен невалиден');
    }
}
```

**Если** **POST** **AJAX** — **тоже**:

```php
$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    echo json_encode(['success' => false, 'error' => 'CSRF-токен невалиден']);
    exit;
}
```

---

## 🛡 XSS

### Три функции

| Функция | Для чего | Что заменяет |
|---------|----------|--------------|
| `e($str)` | HTML-вывод | `<`, `>`, `"`, `'`, `&` |
| `escapeJsString($str)` | JS-строки в HTML-атрибутах | `\`, `'`, `"`, `\n`, `<`, `>`, `&` |
| `sanitizeHtml($html)` | HTML с разрешёнными тегами | whitelist тегов + атрибутов |

**SVG** — это XML с исполняемыми возможностями. Перед сохранением загруженного `.svg` вызывается `validateSvgContent($content)`: `<script>`, `on*`, `foreignObject`, встроенные HTML-элементы, `ENTITY`, `xml-stylesheet`, `javascript:`/`vbscript:`, CSS `expression()` и внешние `href` отклоняются. Дополнительно файл должен быть корректным XML (`SimpleXML` + `LIBXML_NONET`).

### Правила

**1. В** **HTML-контенте** — **всегда** `e()`:

```php
echo e($page['title']);
echo e($settings['email']);
```

**2. В** **атрибутах** — **тоже** `e()`:

```php
<input value="<?php echo e($page['title']); ?>">
<a href="<?php echo e($link); ?>">
```

**3. В** `onclick` **с** **переменной** — `escapeJsString()`:

```php
<button onclick="deleteItem('<?php echo escapeJsString($id); ?>')">
```

**4. Для** **HTML-полей** (**name**, **slogan**, **copyright**) — **санитайзер** **при** **сохранении**:

```php
$error = validateHtml($name);
if ($error !== null) {
    return ['success' => '', 'error' => $error];
}
$name = sanitizeHtml($name);
```

**5. Для** **URL** — `safeUrl()`:

```php
<a href="<?php echo e(safeUrl($item['url'])); ?>">
```

### Что **не** **делать**

❌ **Не** **использовать** `echo $var` **без** `e()`.
❌ **Не** **доверять** `$_GET`, `$_POST`, `$_COOKIE`.
❌ **Не** **вставлять** **JSON** **в** **HTML** **без** `htmlspecialchars`.
❌ **Не** **использовать** `innerHTML` **с** **данными** **от** **пользователя** (**только** `textContent`).

---

## 🚫 ZIP Slip

### Проблема

**Если** **ZIP** **содержит** **файл** **с** `../evil.php`, **при** `extractTo` **можно** **записать** **файл** **за** **пределы** **папки**.

### Защита

**Функция** `detectZipSlip(ZipArchive $zip): ?string` (определена в `config/core/security.php`):

```php
function detectZipSlip(ZipArchive $zip): ?string {
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entryName = $zip->getNameIndex($i);
        if ($entryName === false) continue;
        
        $normalized = str_replace('\\', '/', $entryName);
        
        if (strpos($normalized, '..') !== false ||
            strpos($normalized, "\0") !== false ||
            strpos($normalized, '/') === 0 ||
            preg_match('~^[a-zA-Z]:~', $normalized) === 1) {
            return $entryName;
        }
    }
    return null;
}
```

**Используется** **в** `unpackModuleArchive()`/`validateModuleArchive()` (**установка** **модулей**) **и** при восстановлении из бэкапа (плагин `backups`) **ДО** `extractTo`:

```php
$zipSlipEntry = detectZipSlip($zip);
if ($zipSlipEntry !== null) {
    $zip->close();
    logAction('module_unpack', 'ZIP Slip: ' . $zipSlipEntry, 'ERROR');
    return ['success' => false, 'message' => 'Архив содержит небезопасные имена файлов'];
}

$zip->extractTo($tempDir);
```

---

## 📂 Path Traversal

### Проблема

**Если** `$path = "../../config/data/credentials.json"`, **можно** **прочитать** **чужой** **файл**.

### Защита в проводнике

Проводник вынесен в плагин `filemanager` (`config/plugins/filemanager/`).
Внутри плагина путь проверяется по цепочке: `fmSafePath()` (**whitelist**
символов, запрет `..` и `//`), `fmSafeAbsolute()` (`realpath` + проверка
префикса `APP_ROOT`), `fmIsBlacklisted()` (**запрещённые** **пути**:
`config/data`, `config/backups`, `config/core`, `config/logs`, `data/logs`
и кастомный `storage_path` логов).

**`sensitive_files`** — **фильтр** для **чувствительных** **файлов**.
Плагины добавляют свои имена (например, `backups` → `backup_config.json`).
Базовый список (`.pepper`, `credentials.json`, `login_attempts.json`)
задаёт плагин `filemanager`.

### Защита в `loadPage`

```php
if ($slug !== '') {
    if (!preg_match('~^[a-z0-9\-_/]+$~i', $slug) ||
        strpos($slug, '..') !== false ||
        strpos($slug, '//') !== false) {
        return null;
    }
}
```

### Защита в `loadPageById`

```php
if (!preg_match('~^[a-z0-9\-_]+$~i', $id)) {
    return null;
}
```

### Защита в URL-меню

**`safeUrl(string $url): string`** — **whitelist** **схем**:

```php
function safeUrl(string $url): string {
    $url = trim($url);
    if ($url === '') return '#';
    if ($url[0] === '/' || $url[0] === '#') return $url;
    if (preg_match('~^([a-z][a-z0-9+\-.]*):~i', $url, $m)) {
        $scheme = strtolower($m[1]);
        $allowed = ['http', 'https', 'mailto', 'tel'];
        if (!in_array($scheme, $allowed, true)) return '#';
    }
    return $url;
}
```

---

## 🚫 LFI / RCE

### Проблема

**Если** `$moduleType` **приходит** **от** **пользователя** **без** **валидации**, **можно** **подключить** **произвольный** **файл**:

```php
include APP_ROOT . '/config/modules/' . $moduleType . '/module.php';
```

### Защита

**Всегда** **валидировать** **ID** **модуля** **через** `isValidId()` (`config/core/security.php`):

```php
if (!isValidId($moduleId)) {
    echo json_encode(['success' => false, 'error' => 'Невалидный ID модуля']);
    exit;
}
```

**Применяется** **в**:
- `handleGetModuleSettings()`.
- `unpackModule()`.
- `unpackModuleArchive()`.
- `removeModuleFiles()`, `purgeModule()`.
- `validateModuleArchive()`, `installModuleFromZip()`.
- `downloadModuleZip()`.
- `handleModuleAction()`.
- `check_module` (**в** `config/index.php`).

### `manifest.id` при **установке**

```php
$moduleId = $manifest['id'];
if (!preg_match('~^[a-z0-9\-_]+$~i', $moduleId)) {
    logAction('module_install', 'Невалидный ID: ' . $moduleId, 'ERROR');
    echo json_encode(['success' => false, 'error' => 'Невалидный ID модуля']);
    exit;
}
```

---

## 🔐 Brute-force

### Как работает

**`config/data/login_attempts.json`** **хранит**:
- **Счётчик** **попыток** **по** **IP**.
- **Время** **последней** **попытки**.
- **Количество** **банов**.
- **Флаг** `permanent`.

**`checkIpBlockStatus()`** — **проверка** **при** **логине**:

```php
function checkIpBlockStatus(string $userIp, array $bfData): bool {
    if (!isset($bfData['attempts'][$userIp])) return true;
    
    $ipData = $bfData['attempts'][$userIp];
    
    // Постоянный бан
    if (!empty($ipData['permanent'])) {
        admin_error(103);
        return false;
    }
    
    // Временный бан
    $maxAttempts = intval($bfData['settings']['max_attempts'] ?? 5);
    $lockoutTime = intval($bfData['settings']['lockout_time'] ?? 15);
    $timePassed = time() - $ipData['last_time'];
    
    if ($ipData['count'] >= $maxAttempts && $timePassed < ($lockoutTime * 60)) {
        admin_error(104, ['minutes' => ceil(($lockoutTime * 60 - $timePassed) / 60)]);
        return false;
    }
    
    return true;
}
```

**`handleFailedLogin()`** — **инкремент** **при** **неудаче**:

```php
$bfData['attempts'][$userIp]['count']++;
$bfData['attempts'][$userIp]['last_time'] = time();

if ($count == $maxAttempts) {
    $bfData['attempts'][$userIp]['bans_count']++;
    $bfData['attempts'][$userIp]['bans_history'][] = time();
    
    // Проверка на вечный бан
    $freshBansCount = 0;
    foreach ($bans_history as $banTime) {
        if (time() - $banTime <= $period) $freshBansCount++;
    }
    if ($freshBansCount >= $permCount) {
        $bfData['attempts'][$userIp]['permanent'] = true;
    }
}
```

**`handleSuccessfulLogin()`** — **очистка** **attempts** **для** **IP**.

---

## 🔐 Сессии

### Настройки в `config.php`

```php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

session_start();
```

| Параметр | Зачем |
|----------|-------|
| `cookie_httponly` | JS не видит куку |
| `use_only_cookies` | Session ID только в куке (**не** в URL) |
| `use_strict_mode` | Отвергает **неизвестные** Session ID (**защита** **от** **fixation**) |
| `cookie_samesite` | Защита **от** **CSRF** |
| `cookie_secure` | Кука **только** **по** **HTTPS** |

### `session_regenerate_id`

**При** **успешном** **логине**:

```php
session_regenerate_id(true);
$_SESSION['admin_auth'] = true;
$_SESSION['last_activity'] = time();
```

### Idle timeout

**`checkAdminSessionTimeout()`** — если неактивность > `session_timeout` — уничтожение сессии и редирект на `index.php?msg=timeout`:

```php
$timeoutSeconds = getAdminSessionTimeoutSeconds($bfData);
$inactiveTime   = time() - (int)$_SESSION['last_activity'];

if ($inactiveTime > $timeoutSeconds) {
    // ...
    header('Location: index.php?msg=timeout');
    exit;
}

$_SESSION['last_activity'] = time();
```

**Клиентский таймер.** `renderSessionNotice()` выводит скрытую sticky-полосу
(`data-lifetime`, `data-warn`), а `initSessionTimer()` в `config/js/main.js`:

- показывает предупреждение за `min(120, max(10, lifetime / 2))` секунд
  (для таймаута 1 минута — за 30 секунд);
- на нуле делает `GET index.php?ajax=session_ping`: если сессия жива
  (работала другая вкладка) — синхронизирует таймер, иначе уходит на
  `index.php?msg=timeout`;
- продлевает сессию от любой активности (`pointerdown`, `keydown`, `wheel`,
  `touchstart`, `mousemove`, `scroll`) с троттлингом
  `max(15, min(60, lifetime / 4))` секунд;
- при возврате в вкладку (`visibilitychange`, `pageshow` из bfcache) делает
  ping и берёт с сервера актуальные `lifetime`/`remaining` — таймаут мог
  измениться в соседней вкладке;
- после успешного AJAX к `index.php` сбрасывает локальный отсчёт.

**Смена `session_timeout`.** Формы вкладки «Безопасность» (`save_security`,
`action_unban`, `action_manual_ban`) работают по схеме Post/Redirect/Get:
`handle_admin_post_actions()` ставит флеш и делает редирект на
`index.php?tab=security`. Поэтому `$sessionLifetime` на перерисованной
странице уже новый, а F5 не отправляет POST повторно — иначе флеш
«Настройки сохранены» появлялся бы после каждого обновления.

**`index.php?ajax=session_ping`** обрабатывается в `config/index.php` до
загрузки плагинов и виртуальных кронов. Возвращает JSON
`{ok, remaining, lifetime, warn}`; без авторизации — `401`. Запрос сам
обновляет `last_activity`, поэтому служит и продлением, и проверкой.

---

## 🌐 Host Header Injection

### Проблема

**Если** `$_SERVER['HTTP_HOST']` **идёт** **в** **URL** **или** **письмо** — **можно** **подменить**:

```php
$cronUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/...';
$headers = "From: security@" . $_SERVER['HTTP_HOST'];
```

### Защита

**Валидация** **host**:

```php
$safeHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!preg_match('~^[a-z0-9\-\.]+(:\d+)?$~i', $safeHost)) {
    $safeHost = 'localhost';
}
```

**Применяется** **в**:
- `config.php` — `OG_URL`.
- `handleSaveSecurity` — `From:`.
- `config/index.php` — построение URL крон-задач.

---

## 🔐 Пароли

### Хеширование

**`config/data/credentials.json`**:

```json
{
    "login": "admin",
    "password_hash": "$2y$10$..."
}
```

**`password_hash`** — **двойное** **хеширование**:

```php
$secureMix = hash_hmac('sha256', $password, $login . $pepper);
$finalHash = password_hash($secureMix, PASSWORD_DEFAULT);
```

**`$pepper`** — **случайная** **строка** **в** `config/data/.pepper` (**32** **байта** **hex**).

**Проверка**:

```php
$pepper = trim(file_get_contents(CMS_PEPPER_FILE));
$creds = json_decode(file_get_contents(CMS_CREDS_FILE), true);

$loginMatch = hash_equals($creds['login'], $login);
$secureMix = hash_hmac('sha256', $password, $creds['login'] . $pepper);
$passwordMatch = password_verify($secureMix, $creds['password_hash']);

if ($loginMatch && $passwordMatch) {
    return true;
}
```

**Зачем** **pepper:** **если** `credentials.json` **утечёт** — **без** `.pepper` **хеш** **бесполезен**.

**Зачем** `hash_equals` **и** **сначала** `hash_hmac` **до** **проверки** **логина:** **защита** **от** **timing** **attack** **и** **user** **enumeration**.

---

## 📁 `.htaccess` защита

### Созданы

- `config/.htaccess` — `RewriteEngine Off`, `Options -Indexes`.
- `config/data/.htaccess` — `Require all denied`.
- `config/backups/.htaccess` — `Require all denied`.
- `data/.htaccess` — `Require all denied`.
- `data/history/.htaccess` — `Require all denied`.
- `data/trash/.htaccess` — `Require all denied`.
- `data/preview/.htaccess` — `Require all denied`.
- `cache/.htaccess` — `Require all denied` + whitelist `.css`/`.js`.
- `modules/.htaccess` — `<FilesMatch "\.php$">` denied.
- `templates/.htaccess` — то же.
- `images/.htaccess` — то же.
- `config/modules/.htaccess` — `<FilesMatch "\.php$">` denied.
- `config/modules/archives/.htaccess` — `Require all denied`.

### Формат (Apache 2.2 + 2.4)

```apache
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order Deny,Allow
    Deny from all
</IfModule>
```

### Автосоздание

**В** `admin_auth.php` — **проверка** **и** **создание** `.htaccess` **при** **каждом** **запуске**:

```php
$secureDir = APP_ROOT . '/config/data';
if (!is_dir($secureDir)) {
    @mkdir($secureDir, 0755, true);
}
$htaccessFile = $secureDir . '/.htaccess';
$htaccessData = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n...";

if (!file_exists($htaccessFile) || 
    strpos(file_get_contents($htaccessFile), 'Require all denied') === false) {
    file_put_contents($htaccessFile, $htaccessData);
}
```

---

## 🗝 Токен крона

### Где хранится

**`data/settings.json`** — поле **`cron_token`**.

**Не в конфиге конкретной задачи** (как было до v1.3.0). Причина: токен **общий** для всех крон-задач.

### Как генерируется

```php
function getCronToken(): string {
    $settings = getSettingsData();
    if (!empty($settings['cron_token'])) {
        return (string)$settings['cron_token'];
    }
    return regenerateCronToken();  // bin2hex(random_bytes(16))
}
```

**32 hex-символа** (`bin2hex(random_bytes(16))`). Хранится в открытом виде — токен **не секрет уровня пароля**, он лишь отделяет «свой» запрос от чужого.

### Где используется

**Эндпоинты:**

| URL | Обработчик |
|-----|-----------|
| `/config/cron/run.php?task={id}&token=...` | проверка `enabled`, захват запуска (`claim_cron_task_run()`), затем `run_cron_task()` (задачи из реестра, включая `publish`) |

**Все проверяют переданный токен через `getCronToken()`:**

```php
if ($clientToken === '' || $clientToken !== getCronToken()) {
    header('HTTP/1.1 403 Forbidden');
    die('Error: Access denied');
}
```

### Зачем токен вообще

**Защита от случайного/чужого вызова крона.** Эндпоинт `config/cron/run.php` доступен из браузера. Без токена любой мог бы дёргать любую зарегистрированную задачу или публикацию.

**Что токен **не** защищает:**
- Не защищает данные задач (в них нет токена).
- Не защищает от DoS — злоумышленник может долбить endpoint, но получить 403.

### Перегенерация

**Где:** Настройки сайта → секция «Интеграция с планировщиком».

**Что делает:** сгенерировать новый токен и сохранить его **только при нажатии «Сохранить настройки»** (не мгновенно).

**UI:**
1. Клик «Перегенерировать» → подтверждение с предупреждением.
2. JS генерирует 32 hex-символа в браузере (`crypto.getRandomValues`), без запроса к серверу.
3. Новый токен подставляется в поле и в URL кронов.
4. Токен **записывается в `settings.json`** только при сохранении формы.

**Последствия:** все настроенные внешние крон-задачи сломаются — надо обновить URL в планировщике хостинга. Предупреждение в UI явно об этом говорит.

### Сохранение в handleSaveSettings

**Важно:** при полном сохранении (скрытое поле `settings_full=1`, форма «Настроек сайта») `handleSaveSettings()` **полностью пересобирает** `$newSettings`. Без явного сохранения `cron_token` **теряется** при таком сохранении.

**Решение** (в блоке «СОХРАНЯЕМ ПОЛЯ, КОТОРЫЕ НЕ В ФОРМЕ»):

```php
'cron_token' => (function() {
    $posted = trim($_POST['cron_token'] ?? '');
    if (preg_match('/^[a-f0-9]{32}$/i', $posted)) {
        return $posted;  // валидный новый токен из формы
    }
    return getCronToken();  // текущий
})(),
```

**Валидация `preg_match`** — защита от записи произвольной строки в `cron_token` через POST.

---

## 👁 Preview-токены

Реализация — плагин `preview`. Ядро только проверяет `isPreviewEnabled()` и читает данные. При выключенном плагине `?preview=` игнорируется, AJAX-эндпоинты не отвечают.

### Временные preview

**Файлы:** `data/preview/{preview_id}.json` (32 hex). Рядом `.htaccess`.

**Защита:**

- **Папка** `.htaccess`: `Require all denied` — прямой доступ запрещён.
- **Валидация `preview_id`:** regex `^[a-f0-9]{32}$`.
- **Создание:** AJAX-запрос плагина — POST + CSRF + авторизация админа.
- **Генерация токена:** GET, только случайное значение, состояние не меняет.
- **Не индексируется:** `X-Robots-Tag: noindex, nofollow, noarchive`.
- **Не кешируется:** отключено в `index.php`.

**Очистка (3 триггера):**
1. Логин → всё (хук `admin_login_success`).
2. Заход на `edit.php?id=X` → previews сессии для `X`.
3. Сохранение `X` → previews сессии для `X` (+ `''` для create).

**Безопасность:** preview-токен — **не секрет уровня пароля**, он лишь отделяет «свой» запрос от чужого. Даже если утёк — открывается только preview-версия страницы, не админка.

### Постоянные preview-токены

**Хранятся:** `data/pages/{id}.json`, поле `preview_token`.

**Особенности:**

- **Генерируются по кнопке** (AJAX), записываются в JSON **только при сохранении** страницы.
- **Открывают страницу в любом статусе** — включая `draft` и `archived`.
- **Удаляются** кнопкой «Удалить» + сохранение страницы — сразу теряют силу.
- **Клонирование** токен не копирует; при создании страницы токен не пишется.
- **При выключенном плагине** токен при сохранении теряется (как `history_key` без `history`).

**Риски:**
- **Утечка ссылки** = доступ к неопубликованному контенту. Отзыв — кнопка «Удалить» + сохранение.
- **Индексация:** защита через `X-Robots-Tag` и отсутствие ссылок в публичном HTML.

**Рекомендации:**
- Не публиковать preview-ссылки в открытых каналах.
- Удалять ссылку после согласования.

---

## 🔍 SEO-файлы и 301-редиректы

### sitemap.xml и robots.txt

**Расположение:** корень сайта.

**Создаются/удаляются:** плагином **SEO-инструменты** (`seo-tools`) по его настройкам.

**Не содержат секретов** — публичные файлы.

**sitemap:**
- Включает **только** `published` без истёкшего `unpublish_at`.
- **Не включает** preview-URL, черновики, архивы.

**robots:**
- `Disallow: /config/`, `/data/`, `/cache/`, `/*?preview=`.
- `Allow: /`.
- `Sitemap: {base}/sitemap.xml`.

**Крон-эндпоинт** `cron/run.php?task=seo` — защищён токеном (`getCronToken()`).

### 301-редиректы

**Логика:** при смене `slug` старый сохраняется в `slug_history`. При заходе на старый URL — редирект на актуальный.

**Защита:**
- **`slug_history`** хранится в JSON страницы — не в отдельном файле. Прямой доступ к JSON уже запрещён `.htaccess`.
- **Лимит 50 записей** — защита от роста.
- **Редирект срабатывает только для `published`** — черновики и архивы не «подсвечивают» существование.
- **Валидация `slug`** (regex) — как и раньше.

**Что не индексируется как дубликат:**
- Старый URL отдаёт **301** → crawler переходит на актуальный. **Не индексирует старый.**
- В `sitemap.xml` только актуальные URL.

---

## 🕘 История изменений

Функциональность вынесена в плагин **История изменений** (`history`). Ниже — требования к данным и защите, которые плагин обязан соблюдать.

### Что хранится

Снапшоты страниц — `data/history/{history_key}/{timestamp}.json`. Каждый файл содержит **полный JSON страницы** + служебный блок `_history`.

**Что попадает в снапшот:** заголовок, содержимое, SEO-настройки, шаблон, хедер/футер, `slug_history`, `preview_token`, `id`, `slug`, `history_key`.

### Защита

- **Папка** `data/history/` + вложенные — `.htaccess` `Require all denied` (автосоздание при первом снапшоте).
- **Валидация `history_key`** — regex `^[a-f0-9]{16}$`. Иначе — пустая строка / отказ.
- **Валидация `timestamp`** — `(int)`, `> 0`.
- **Валидация `page_id`** — regex `^[a-z0-9\-_]+$`.
- **AJAX/POST** — CSRF-токен.
- **Авторизация** — только админ (как и все страницы редактора).
- **Не индексируется** — история недоступна из браузера, только через `?tab=pages&action=edit`.

### Удаление истории

- **Ручное** — кнопки «Удалить» / «Очистить всю» в модалке (с CSRF).
- **Автоматическое** — ротация по лимиту (`history_limit`).
- **Каскадное** — при окончательном удалении страницы из корзины (хук `trash_item_purged`).

### Откат — что защищает

- **Обратимость:** перед откатом сохраняется снапшот текущего состояния. Откат отката возможен.
- **Только контент:** `status` / `publish_at` / `unpublish_at` / `slug` / `slug_history` / `preview_token` **не восстанавливаются** — не «откатится» случайно опубликованное.
- **Логирование:** каждое действие (`page_restore`, `page_history_delete`, `page_history_clear`, `history_clear`) — в `logAction`.

---

### Защита от race condition в кроне

**Генерация SEO-файлов** (плагин `seo-tools`) вызывается из крона, при сохранении страницы, вручную.

**Потенциальная проблема:** параллельный вызов из двух потоков → один файл перезаписывается другим.

**Защита:** `safeFileWrite()` — атомарная запись с `flock`. Если два запроса одновременно пишут — второй ждёт.

**Возможный сбой:** если один процесс пишет sitemap, второй читает — увидит частично записанный файл. **Маловероятно** (sitemap пишется < 100 мс). **Не критично.**

---

## 🚧 Технические работы

Режим техработ — плагин `maintenance` (`config/plugins/maintenance/`).
Ядро оставляет хук `index_start` и публичный шаблон `templates/maintenance.php`;
проверка режима, bypass и ответ 503 — в плагине.

### Bypass-токен

- **Отдельный** от `cron_token` — не пересекается с кронами.
- **32 hex**, хранится в `data/settings.json` → `maintenance_bypass_token`.
- **Сравнение** через `hash_equals` — защита от timing attack.
- **Генерируется автоматически** при включении режима; перегенерация — только по кнопке, старые ссылки **ломаются**.

### Bypass-cookie

- Имя: `maintenance_bypass`, значение `1`.
- **Срок:** 24 часа (`time() + 86400`).
- **Флаги:** `httponly`, `SameSite=Lax`, `secure` если HTTPS.
- **Path:** `/` (весь сайт).
- **Устанавливается** только при валидном `?bypass={token}`.
- **Не удаляется** при выключении техработ (клиентская cookie, сервер не управляет).

### Сообщение и заголовки

- HTML сообщения сохраняется через `sanitizeHtml()`; вывод в шаблоне — как есть.
- 503 отдаётся с `Retry-After: 3600` и `X-Robots-Tag: noindex, nofollow, noarchive`.
- Автоснятие по `maintenance_until` — при первом запросе, без крона.

### Что **не** защищено

- **`templates/maintenance.php`** — **публичный** шаблон, редактируется через проводник.
- **Bypass-cookie** — **клиентская**. Если пользователь **очистил** cookie — bypass **слетает**.
- **DoH / прокси** — bypass-токен в URL **может** попасть в **логи** прокси / браузера. **Рекомендация:** не публикуй ссылку в открытых каналах.
- **`maintenance_message`** — **не** должен содержать **секретов** (это публичная страница).

---

## 📋 Чек-лист при добавлении кода

- [ ] **POST** — проверка `csrf_token`.
- [ ] **GET-параметры** — валидация (`intval`, `preg_match`).
- [ ] **Вывод** — через `e()` / `escapeJsString()`.
- [ ] **HTML-поля** — `validateHtml` + `sanitizeHtml`.
- [ ] **Загрузка SVG** — `validateSvgContent` перед сохранением.
- [ ] **URL** — `safeUrl()`.
- [ ] **Пути** — `fmSafePath` + `fmSafeAbsolute`.
- [ ] **ID** — валидация **regex**.
- [ ] **ZipArchive** — `detectZipSlip` **перед** `extractTo`.
- [ ] **File write** — `safeFileWrite` (**flock**).
- [ ] **Логирование** — `logAction` (пишет в `config/logs/`).
- [ ] **Токен крона** — если добавляешь новый крон-эндпоинт, используй `getCronToken()` для проверки; храни только в `settings.json`, не в конфигах подсистем.
- [ ] **Ошибки** — не **раскрывать** **пути**/**данные**.

---

## 📞 Поддержка

- **Архитектура:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **API:** [API.md](API.md)
- **FAQ** **пользователей:** [../for-users/FAQ.md](../for-users/FAQ.md)