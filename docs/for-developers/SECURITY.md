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

**Все POST-запросы в админке проверяют** `csrf_token`:

```php
if (empty($_POST['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Ошибка безопасности: невалидный CSRF-токен');
}
```

**`hash_equals`** — **сравнение за постоянное время**.

**Токен генерируется в** `config.php`:
```php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

**Токен передаётся**:
- **В формах** — `<input type="hidden" name="csrf_token">`.
- **В AJAX** — **в** `FormData` **или body**.

### Что делать при добавлении POST

**Всегда проверяй** `csrf_token` **в начале обработчика**:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        header('HTTP/1.1 403 Forbidden');
        exit('CSRF-токен невалиден');
    }
}
```

**Если POST AJAX** — **тоже**:

```php
$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    echo json_encode(['success' => false, 'error' => 'CSRF-токен невалиден']);
    exit;
}
```

### CSRF и кеш страниц

Кеш на диске общий для всех посетителей, а `csrf_token` — свой в каждой сессии.
Поэтому токен в `cache/pages/*.html` не пишется: вместо него заглушка
`__ALPIN_CSRF_TOKEN__`, при выдаче из кеша она заменяется на токен текущей
сессии. Иначе форма с закешированной страницы отклонялась бы как CSRF-ошибка
у всех, кроме автора кеша.

Новые поля с токеном в кешируемом HTML должны брать `$token` из `index.php`:
подстановка выполняется после чтения файла кеша, до буферизации вывода.

### AJAX-эндпоинты с побочными эффектами

Действия, которые ходят во внешнюю сеть или пишут файлы, принимаются только
POST-ом и проверяют токен в самом обработчике:

- `index.php?tab=plugins&ajax=refresh_catalog` и `index.php?tab=modules&ajax=refresh_catalog`;
- GET → `405` + JSON, отсутствующий или неверный `csrf_token` → `403` + JSON.

Общий барьер POST в `config/index.php` проверяет токен первым, но обработчик
проверяет его ещё раз: барьер можно поменять или обойти при рефакторинге.
Клиент отправляет токен в `FormData` (`csrf_token`).

### Внешние запросы

Скачивание ZIP плагина/модуля и каталога обновлений идёт через cURL с
`CURLOPT_FOLLOWLOCATION`. Начальный URL проверяется по схеме, а редирект
ограничен `CURLOPT_REDIR_PROTOCOLS = CURLPROTO_HTTP | CURLPROTO_HTTPS`:
ответ `30x` на `file://`, `ftp://` и другие схемы не выполняется.

---

## 🛡 XSS

### Три функции

| Функция | Для чего | Что заменяет |
|---------|----------|--------------|
| `e($str)` | HTML-вывод | `<`, `>`, `"`, `'`, `&` |
| `escapeJsString($str)` | JS-строки в HTML-атрибутах (вместе с `e()`) | `\`, `'`, `"`, `\n`, `<`, `>`, `&` |
| `sanitizeHtml($html)` | HTML с разрешёнными тегами | whitelist тегов + атрибутов |

**SVG** — это XML с исполняемыми возможностями. Перед сохранением загруженного `.svg` вызывается `validateSvgContent($content)`: `<script>`, `on*`, `foreignObject`, встроенные HTML-элементы, `ENTITY`, `xml-stylesheet`, `javascript:`/`vbscript:`, CSS `expression()` и внешние `href` отклоняются. Дополнительно файл должен быть корректным XML (`SimpleXML` + `LIBXML_NONET`).

### Правила

**1. В HTML-контенте** — **всегда** `e()`:

```php
echo e($page['title']);
echo e($settings['email']);
```

**2. В атрибутах** — **тоже** `e()`:

```php
<input value="<?php echo e($page['title']); ?>">
<a href="<?php echo e($link); ?>">
```

Значения из настроек, которые попадают в `class` (например брейкпоинт бургера),
дополнительно ограничиваются allowlist: `getBurgerBreakpoint()` возвращает только
`sm`/`md`/`lg`/`xl`, остальное читается как `md`.

**3. В** `onclick` **с переменной** — `e(escapeJsString(...))`:

```php
<button onclick="deleteItem('<?php echo e(escapeJsString($id)); ?>')">
```

`escapeJsString()` экранирует JS-строку, `e()` — сам HTML-атрибут. Без `e()`
кавычка `"` в значении закроет `onclick`, потому что обратный слэш в HTML
не работает. Внутри `<script>` — только `escapeJsString()`, без `e()`.

**4. Для HTML-полей** (**name**, **slogan**, **copyright**) — **санитайзер при сохранении**:

```php
$error = validateHtml($name);
if ($error !== null) {
    return ['success' => '', 'error' => $error];
}
$name = sanitizeHtml($name);
```

**5. Для URL** — `safeUrl()`:

```php
<a href="<?php echo e(safeUrl($item['url'])); ?>">
```

**6. В JS**, **который собирает HTML из данных**, — **экранировать значения**:

```js
function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

balloonContent += '<div class="custom-balloon-title">' + escapeHtml(name) + '</div>';
```

В браузере нет `e()`, серверное экранирование здесь не помогает — строка
собирается на клиенте. Так же проверяются имена классов из данных (например,
иконка: `/^icon-[a-z0-9\-_]+$/i`), иначе в `class` можно дописать атрибут.
Если разметку можно построить через DOM — `textContent` предпочтительнее.

### Сырые `<style>` и `<script>`

`custom_css` и `custom_js` из настроек выводятся в `<style>`/`<script>` как код,
поэтому `e()` к ним не применяется. Опасна последовательность закрывающего
тега: `</style` или `</script` внутри значения закрывает блок, и всё после него
браузер разбирает как HTML. `index.php` заменяет её на `<\/style`/`<\/script`:
в CSS и JS обратный слэш перед `/` допустим, а HTML-парсер тег уже не видит.

### Что **не делать**

❌ **Не использовать** `echo $var` **без** `e()`.
❌ **Не доверять** `$_GET`, `$_POST`, `$_COOKIE`.
❌ **Не вставлять JSON в HTML без** `htmlspecialchars`.
❌ **Не использовать** `innerHTML` **с данными от пользователя** (**только** `textContent`).

---

## 🚫 ZIP Slip

### Проблема

**Если ZIP содержит файл с** `../evil.php`, **при** `extractTo` **можно записать файл за пределы папки**.

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

**Используется в** `unpackModuleArchive()`/`validateModuleArchive()` (**установка модулей**) **и** при восстановлении из бэкапа (плагин `backups`) **ДО** `extractTo`:

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

## 🔐 Контрольная сумма архивов

### Зачем

`detectZipSlip()` проверяет **имена** файлов, но не содержимое: подменённый
или оборванный архив распакуется без ошибок. Поэтому `catalog.json` хранит
`sha256` каждого архива, а ядро сверяет его сразу после скачивания.

### Как устроено

- `catalog-builder.php` пишет `sha256` через `hash_file('sha256', $file)` в
  каждую запись `extensions[id]`;
- `refresh_updates_cache()` переносит `sha256` в срез `new`/`updates` кеша;
- вкладки «Плагины» и «Модули» передают хеш третьим аргументом
  `installPluginFromCatalog(url, id, sha256, btn)` (аналогично для модулей) и
  кладут его в POST полем `sha256`;
- AJAX-обработчики проверяют формат (`^[a-f0-9]{64}$`) до скачивания;
- `downloadPluginZip()` и `downloadModuleZip()` после скачивания вызывают
  `verifyArchiveChecksum()`; при несовпадении архив удаляется, установка
  отменяется.

```php
function verifyArchiveChecksum(string $path, string $expected): bool {
    $expected = strtolower(trim($expected));
    if (preg_match('/^[a-f0-9]{64}$/', $expected) !== 1) {
        return false;
    }

    $actual = @hash_file('sha256', $path);
    if ($actual === false) {
        return false;
    }

    return hash_equals($expected, strtolower($actual));
}
```

### Хеша может не быть

Проверяется **то, что есть**: старые каталоги и установка по `http://` без
хеша не блокируются. В этом случае `sha256` — пустая строка, установка идёт
как раньше, а в сообщении об успехе появляется пометка:

> Архив получен по http без контрольной суммы — целостность не проверена.

Сравнение всегда через `hash_equals()`, поэтому тайминг не утекает. Хеш
неверного формата считается несовпадением, а не поводом пропустить проверку.

---

## 📂 Path Traversal

### Проблема

**Если** `$path = "../../config/data/credentials.json"`, **можно прочитать чужой файл**.

### Защита в проводнике

Проводник вынесен в плагин `filemanager` (`config/plugins/filemanager/`).
Внутри плагина путь проверяется по цепочке: `fmSafePath()` (**whitelist**
символов, запрет `..` и `//`), `fmSafeAbsolute()` (`realpath` + проверка
префикса `APP_ROOT`), `fmIsBlacklisted()` (**запрещённые пути**:
`config/data`, `config/backups`, `config/core`, `config/logs`, `data/logs`
и кастомный `storage_path` логов).

**`sensitive_files`** — **фильтр** для **чувствительных файлов**.
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

**`safeUrl(string $url): string`** — **whitelist схем**:

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

**Если** `$moduleType` **приходит от пользователя без валидации**, **можно подключить произвольный файл**:

```php
include APP_ROOT . '/config/modules/' . $moduleType . '/module.php';
```

### Защита

**Всегда валидировать ID модуля через** `isValidId()` (`config/core/security.php`):

```php
if (!isValidId($moduleId)) {
    echo json_encode(['success' => false, 'error' => 'Невалидный ID модуля']);
    exit;
}
```

**Применяется в**:
- `handleGetModuleSettings()`.
- `unpackModule()`.
- `unpackModuleArchive()`.
- `removeModuleFiles()`, `purgeModule()`.
- `validateModuleArchive()`, `installModuleFromZip()`.
- `downloadModuleZip()`.
- `handleModuleAction()`.
- `check_module` (**в** `config/index.php`).
- `handleRebuildModuleMin()`, `createMinFilesForModule()` — ID попадает в путь
  `css/{id}.css` / `js/{id}.js`; невалидный ID возвращает пустой результат.
- `templates/base.php` — тип модуля из `rows_json` перед `include`.

### `manifest.id` при **установке**

```php
$moduleId = $manifest['id'];
if (!preg_match('~^[a-z0-9\-_]+$~i', $moduleId)) {
    logAction('module_install', 'Невалидный ID: ' . $moduleId, 'ERROR');
    echo json_encode(['success' => false, 'error' => 'Невалидный ID модуля']);
    exit;
}
```

### Шаблон и настройки дизайна

`templates/base.php` строит путь `include` из данных страницы и настроек:

- тип модуля из `rows_json` — только `isValidId()`; невалидный тип не
  подключается, в HTML уходит комментарий через `e()`;
- `header_variant` / `footer_variant` — `isValidId()` + `is_dir()` внутри
  `templates/headers` / `templates/footers`. Проверка одинаковая при сохранении
  (`handleSaveSettings()`) и при выводе, поэтому испорченный `settings.json`
  не уводит путь за пределы каталога вариантов. Если варианта нет — `default`,
  затем `includes/header.php` / `includes/footer.php`.

---

## 🔐 Brute-force

### Как работает

**`config/data/login_attempts.json` хранит**:
- **Счётчик попыток по IP**.
- **Время последней попытки**.
- **Количество банов**.
- **Флаг** `permanent`.

**`checkIpBlockStatus()`** — **проверка при логине**:

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

**`handleFailedLogin()`** — **инкремент при неудаче**:

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

**`handleSuccessfulLogin()`** — **очистка attempts для IP**.

**Атомарность.** Чтение и запись `login_attempts.json` по отдельности не
атомарны: два параллельных POST читали одинаковый `count` и оба писали
`count + 1`, из-за чего лимит обходился пачкой запросов. Все изменения идут
через `updateLoginAttempts()`: файл `login_attempts.json.lock` берётся в
`flock(LOCK_EX)`, внутри блокировки журнал перечитывается, меняется и
записывается через `safeFileWrite()`. Ручной бан, разблокировка и сохранение
настроек безопасности используют тот же путь. Если файл блокировки открыть не
удалось, запись выполняется без неё — точность счётчика на редкой гонке важнее,
чем недоступность входа.

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
| `use_strict_mode` | Отвергает **неизвестные** Session ID (**защита от fixation**) |
| `cookie_samesite` | Защита **от CSRF** |
| `cookie_secure` | Кука **только по HTTPS** |

### `session_regenerate_id`

**При успешном логине**:

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

**Если** `$_SERVER['HTTP_HOST']` **идёт в URL или письмо** — **можно подменить**:

```php
$cronUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/...';
$headers = "From: security@" . $_SERVER['HTTP_HOST'];
```

### Защита

**Валидация host**:

```php
$safeHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!preg_match('~^[a-z0-9\-\.]+(:\d+)?$~i', $safeHost)) {
    $safeHost = 'localhost';
}
```

**Применяется в**:
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

**`password_hash`** — **двойное хеширование**:

```php
$secureMix = hash_hmac('sha256', $password, $login . $pepper);
$finalHash = password_hash($secureMix, PASSWORD_DEFAULT);
```

**`$pepper`** — **случайная строка в** `config/data/.pepper` (**32 байта hex**).

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

**Зачем pepper: если** `credentials.json` **утечёт** — **без** `.pepper` **хеш бесполезен**.

**Зачем** `hash_equals` **и сначала** `hash_hmac` **до проверки логина: защита от timing attack и user enumeration**.

### Подтверждение опасных действий

**Сохранение настроек безопасности и управление блокировками**
**требуют подтверждения текущим паролем. Поле «Текущий пароль»**
**в форме не показывается:** `main.js` **перехватывает отправку,**
**открывает модалку и проверяет пароль AJAX-запросом**
`security_confirm` **до отправки основной формы.**

- `handleSecurityConfirmation()` (`admin_controller.php`) **вызывает**
  `verifySecurityPassword()`; **при успехе в сессию кладётся**
  **одноразовое подтверждение** `security_confirmation` **вида**
  `{action, at}`.
- `requireSecurityConfirmation($action)` **в начале** `handleSaveSecurity()`,
  `handleActionUnban()` **и** `handleActionManualBan()` **принимает только**
  **подтверждение своего типа:** `save`, `unban` **или** `ban`.
- `SECURITY_CONFIRM_TTL = 300` **секунд. После успешного действия**
  `consumeSecurityConfirmation()` **снимает подтверждение; ошибка**
  **валидации (например, кривой IP) его не сжигает.**
- **Без JavaScript поле «Текущий пароль» остаётся видимым**
  **в форме сохранения и работает как аварийный путь:**
  **сервер принимает либо подтверждение, либо пароль из**
  `$_POST['sec_current_password']`. **Для** `unban` **и** `ban` **аварийного**
  **поля нет.**

**Проверка пароля** — **тем же способом, что при входе,**
**и с ТЕКУЩИМ логином:**

```php
$currentMix = hash_hmac('sha256', $password, $currentLogin . $pepper);
if (!password_verify($currentMix, $currentCreds['password_hash'])) {
    $attempt = registerSecurityFormFailure($userIp, $bfData);
    if ($attempt >= SECURITY_FORM_MAX_ATTEMPTS) {
        // сессия уничтожается, клиент уходит на index.php?msg=security_failed
    }
    return ['ok' => false, 'error' => 'Неверный текущий пароль.', 'logout' => false];
}
```

**Почему так: пароль привязан к логину через** `hash_hmac`, **поэтому**
**сверять с новым логином нельзя** — **хеш не совпадёт**. **Без этой**
**проверки украденная сессия равна полному захвату аккаунта:**
**атакующему достаточно вписать свой пароль в форму безопасности.**

**Лимит проверки свой и жёсткий:** `SECURITY_FORM_MAX_ATTEMPTS = 3`,
`SECURITY_FORM_LOCKOUT_MINUTES = 15` **в** `admin_auth.php`. **Он не берётся**
**из** `login_attempts.json` → `settings`: **украденная сессия может поднять**
`max_attempts` **и перебирать текущий пароль без ограничений.**

**Счётчик** — **отдельный ключ** `security_attempts[$ip]` **вида**
`{count, last_time}`. **С** `attempts` **входа он не связан: ошибки**
**подтверждения не приближают бан IP и не мешают войти**
**в панель, постоянного бана у подтверждения нет.**

- **неверный текущий пароль** — `count + 1`, `WARNING` (`security_save`)
  **в журнал, действие не выполняется;**
- **третья ошибка** — **сессия уничтожается на сервере, клиент**
  **переходит на** `index.php?msg=security_failed`;
- **пока** `count >= 3` **и не прошло 15 минут, модалка отвечает**
  **паузой;**
- **после паузы серия начинается с нуля;**
- **верный текущий пароль снимает** `security_attempts[$ip]`;
- **успешный вход в панель тоже снимает серию: владелец,**
  **разлогиненный после трёх опечаток, не ждёт паузу;**
- **пустой текущий пароль** — **ошибка без записи и без инкремента**
  **(это не попытка подбора).**

**Текущий пароль нужен при любом сохранении формы, даже если**
**меняются только лимиты или таймаут сессии: иначе украденная**
**сессия поднимает** `max_attempts` **и снимает блокировку входа.**
**После успешной смены логина или пароля сессия завершается** —
**нужен повторный вход; при сохранении только лимитов сессия**
**остаётся активной.**

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

**В** `admin_auth.php` — **проверка и создание** `.htaccess` **при каждом запуске**:

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
- **Валидация `page_id`** — regex `~^[a-z0-9\-_]+$~i`.
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

**Защита:** `safeFileWrite()` пишет новый файл во временный и переименовывает его
поверх целевого. `rename()` атомарен: читатель видит либо старую, либо новую
версию целиком. Если `rename()` не проходит (на Windows целевой файл в этот
момент открыт читателем), запись повторяется напрямую с `LOCK_EX`: данные не
теряются, атомарность не гарантируется только в этом аварийном случае.
Дописывание (`$append = true`) идёт через
`file_put_contents(..., FILE_APPEND | LOCK_EX)`.

**Раньше** (до правки) использовался `file_put_contents(LOCK_EX)`: он сначала
усекал файл, поэтому параллельный читатель мог увидеть пустой или недописанный
JSON, а обрыв процесса оставлял файл пустым.

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
- [ ] **Вывод** — через `e()`; в `onclick` — `e(escapeJsString(...))`.
- [ ] **HTML-поля** — `validateHtml` + `sanitizeHtml`.
- [ ] **Загрузка SVG** — `validateSvgContent` перед сохранением.
- [ ] **URL** — `safeUrl()`.
- [ ] **Пути** — нормализация сегментов (`./`, регистр) + проверка вхождения в каталог по `realpath`.
- [ ] **ID** — валидация **regex**.
- [ ] **ZipArchive** — `detectZipSlip` **перед** `extractTo`.
- [ ] **File write** — `safeFileWrite` (temp + rename, append — `flock`).
- [ ] **Логирование** — `logAction` (пишет в `config/logs/`).
- [ ] **Токен крона** — если добавляешь новый крон-эндпоинт, используй `getCronToken()` для проверки; храни только в `settings.json`, не в конфигах подсистем.
- [ ] **Ошибки** — не **раскрывать пути**/**данные**.

---

## 📞 Поддержка

- **Архитектура:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **API:** [API.md](API.md)
- **FAQ пользователей:** [../for-users/FAQ.md](../for-users/FAQ.md)