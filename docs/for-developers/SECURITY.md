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

**Функция** `detectZipSlip(ZipArchive $zip): ?string`:

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

**Используется** **в** `unpackModule` **и** `handleRestoreBackup` **ДО** `extractTo`:

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

### Защита в filemanager

**Функции** **из** `filemanager_api.php`:

**`fmSafePath(string $path): ?string`** — **whitelist** **символов**:

```php
function fmSafePath(string $path): ?string {
    $path = str_replace('\\', '/', $path);
    $path = trim($path, '/');
    if ($path === '') return '';
    if (!preg_match('~^[a-zA-Z0-9\-_./]+$~', $path)) return null;
    if (strpos($path, '..') !== false || strpos($path, '//') !== false) return null;
    return $path;
}
```

**`fmSafeAbsolute(string $relativePath): ?string`** — `realpath` + **проверка** **префикса**:

```php
function fmSafeAbsolute(string $relativePath): ?string {
    $realRoot = realpath(FM_SITE_ROOT);
    if ($realRoot === false) return null;
    
    $full = FM_SITE_ROOT;
    if ($relativePath !== '') {
        $full .= DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }
    
    $check = file_exists($full) ? realpath($full) : realpath(dirname($full));
    if ($check === false) return null;
    if (strpos($check, $realRoot) !== 0) return null;
    
    return $full;
}
```

**`fmIsBlacklisted(string $relativePath): bool`** — **запрещённые** **пути**:

```php
$blacklist = ['config/data', 'config/backups', 'config/core', 'data/logs'];
```

**`fmIsSensitiveFile(string $fileName): bool`** — **чувствительные** **файлы**:

```php
$sensitive = ['.pepper', 'credentials.json', 'login_attempts.json', 'backup_config.json'];
```

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

**Всегда** **валидировать** **ID** **модуля**:

```php
if (!preg_match('~^[a-z0-9\-_]+$~i', $moduleType)) {
    echo json_encode(['success' => false, 'error' => 'Невалидный ID модуля']);
    exit;
}
```

**Применяется** **в**:
- `handleGetModuleSettings()`.
- `unpackModule()`.
- `removeModuleFiles()`.
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

**`checkAdminSessionTimeout()`** — **если** **неактивность** > **timeout** — **уничтожение** **сессии**:

```php
function checkAdminSessionTimeout(array $bfData): void {
    if (!isset($_SESSION['last_activity'])) return;
    
    $timeoutMinutes = intval($bfData['settings']['session_timeout'] ?? 20);
    $inactiveTime = time() - $_SESSION['last_activity'];
    
    if ($inactiveTime > ($timeoutMinutes * 60)) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, ...);
        }
        session_destroy();
        header("Location: index.php?msg=timeout");
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}
```

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
- `config/index.php` — `cron_backup.php`.

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

## 📋 Чек-лист при добавлении кода

- [ ] **POST** — проверка `csrf_token`.
- [ ] **GET-параметры** — валидация (`intval`, `preg_match`).
- [ ] **Вывод** — через `e()` / `escapeJsString()`.
- [ ] **HTML-поля** — `validateHtml` + `sanitizeHtml`.
- [ ] **URL** — `safeUrl()`.
- [ ] **Пути** — `fmSafePath` + `fmSafeAbsolute`.
- [ ] **ID** — валидация **regex**.
- [ ] **ZipArchive** — `detectZipSlip` **перед** `extractTo`.
- [ ] **File write** — `safeFileWrite` (**flock**).
- [ ] **Логирование** — `logAction`.
- [ ] **Ошибки** — не **раскрывать** **пути**/**данные**.

---

## 📞 Поддержка

- **Архитектура:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **API:** [API.md](API.md)
- **FAQ** **пользователей:** [../for-users/FAQ.md](../for-users/FAQ.md)