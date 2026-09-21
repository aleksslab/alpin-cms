# API функций AlPin CMS

## 📖 Оглавление

- [Утилиты (`functions.php`)](#утилиты)
- [Чтение данных](#чтение-данных)
- [Загрузка контента](#загрузка-контента)
- [Рендер](#рендер)
- [Запись данных (`admin_controller.php`)](#запись-данных)
- [Безопасность](#безопасность)
- [Файловый менеджер](#файловый-менеджер)
- [Логирование](#логирование)

---

## 🛠 Утилиты

### `e(?string $str): string`

**HTML-escape** для безопасного вывода.

```php
echo e($page['title']);  // &lt;script&gt; → &amp;lt;script&amp;gt;
```

**Использует** `htmlspecialchars` с `ENT_QUOTES | UTF-8`.

---

### `escapeJsString(?string $str): string`

**JS-escape** для безопасной вставки в строку внутри HTML-атрибута.

```php
<button onclick="deleteModule('<?php echo escapeJsString($id); ?>')">
```

**Заменяет:** `\`, `'`, `"`, `\n`, `\r`, `<`, `>`, `&`.

---

### `safeUrl(string $url): string`

**Whitelist схем URL.** Возвращает `#` при опасной схеме.

```php
$url = safeUrl('javascript:alert(1)');  // '#'
$url = safeUrl('/about');               // '/about'
$url = safeUrl('https://google.com');   // 'https://google.com'
```

**Разрешает:** `/path`, `#anchor`, `http(s)://`, `mailto:`, `tel:`.  
**Запрещает:** `javascript:`, `data:`, `vbscript:`.

---

### `validateHtml(string $html): ?string`

**Проверка HTML** на опасные конструкции.  
Возвращает **текст ошибки** или `null`.

```php
$error = validateHtml('<script>alert(1)</script>');
// 'Обнаружено недопустимое содержимое: тег <script>'
```

**Проверяет** **после** `html_entity_decode` (**защита** **от** **обфускации**):
- `<script>`, `<iframe>`, `<object>`, `<embed>`, `<form>`, `<meta>`, `<link>`
- `on*="..."` (**события**)
- `javascript:`, `vbscript:`, `data:text/html`
- `expression(...)`
- `\x..`, `\u....`

---

### `sanitizeHtml(string $html): string`

**Очистка HTML** — **whitelist** **тегов**.

```php
$clean = sanitizeHtml('<p>OK</p><script>bad</script>');
// '<p>OK</p>'
```

**Разрешает:** `b, i, u, strong, em, span, ul, ol, li, p, br, a, h1-h4`.  
**Убирает** **опасные** **атрибуты** (`on*`, `style`, `srcdoc`, `formaction`).

---

### `formatPhone(string $phone): string`

**Форматирует** **номер** **в** **читаемый** **вид**.

```php
echo formatPhone('79001234567');  // +7 (900) 123-45-67
echo formatPhone('+7 900 123 45 67');  // +7 (900) 123-45-67
```

---

### `safeFileWrite(string $path, string $content, bool $append = false): bool`

**Атомарная** **запись** **с** **блокировкой** (`flock`).

```php
safeFileWrite($path, $json, false);  // перезапись
safeFileWrite($logFile, $line, true);  // append
```

**Защита** **от** **race** **condition**.

---

## 📖 Чтение данных

### `getData(string $fileName, bool $refresh = false): array`

**Читает** JSON **из** `data/{fileName}.json` **с** **кешем** **в** **памяти**.

```php
$settings = getData('settings');
$pages = getData('pages');
```

**Кеш** — **`$GLOBALS['DATA_CACHE']`** (**сброс** **при** `$refresh = true`).

---

### `getSettingsData(bool $refresh = false): array`

**Обёртка** `getData('settings')`.

---

### `getModulesData(): array`

**Читает** `config/data/modules.json` (**список** **модулей**).

---

### `getTemplateData(): array`

**Читает** `config/data/templates.json`.

---

### `loadModule(string $id): ?array`

**Загружает** **описание** **модуля** **из** `config/data/modules.json`.

---

## 📄 Загрузка контента

### `loadPage(string $slug): ?array`

**Загружает** **страницу** **по** **slug**.

```php
$page = loadPage('about');
```

**Валидация** **slug** (**regex**, **без** `..`).  
**Проверка** `status === 'published'`.

---

### `loadPageById(string $id): ?array`

**Загружает** **страницу** **по** **ID** (**без** **проверки** **статуса**).

---

### `loadTemplate(string $id): ?array`

**Загружает** **шаблон** **из** `templates.json`.

---

### `getTemplateZones(string $templateId): array`

**Зоны** **шаблона** (**main**, **sidebar-left**, **sidebar-right**, **hero**).

---

### `getPagesList(): array`

**Все** **страницы** (**для** **меню**, **селектов**).

---

### `getPagesListPaginated(int $page, int $perPage): array`

**С** **пагинацией**.

```php
$result = getPagesListPaginated(1, 25);
// ['pages' => [...], 'total' => 100, 'page' => 1, 'totalPages' => 4]
```

---

### `getMenusList(): array`

**Все** **меню** **из** `data/menus/*.json`.

---

### `loadMenu(string $id): ?array`

**Загружает** **меню** **по** **ID**.

---

### `getMenuItems(string $menuId): array`

**Пункты** **меню**.

---

### `getMainMenuId(): ?string`

**ID** **главного** **меню**.

---

### `getHomePageId(): string`

**ID** **главной** **страницы**.

---

### `getUsedModules(array $page): array`

**Уникальные** **ID** **модулей** **на** **странице**.

---

### `isModuleInstalled(string $id): bool`

**Установлен** **ли** **модуль** (**распакован**).

---

### `findModuleUsage(string $id): array`

**Где** **используется** **модуль** (**список** **страниц**).

---

## 🎨 Рендер

### `render_module(string $moduleId, array $moduleData = [], array $settings = []): string`

**Рендерит** **модуль** **на** **фронте**.

```php
echo render_module('hero', ['title' => 'Hello']);
```

**`include`** **файла** `modules/{moduleId}.php` **с** **буферизацией**.

---

### `renderMainMenu(array $items): string`

**HTML** **главного** **меню** (**desktop**).

**Использует** `safeUrl()` **и** `e()`.

---

### `renderMobileMenu(array $items): string`

**HTML** **мобильного** **меню** (**бургер**).

---

### `getModuleAssets(array $moduleIds, ?array $settings, string $pageId): array`

**URLs** **CSS**/**JS** **модулей** (**или** **объединённых**).

```php
$assets = getModuleAssets(['hero', 'faq'], $settings, 'home');
// ['css' => [...], 'js' => [...]]
```

---

### `getCombinedAssets(array $moduleIds, array $settings, string $pageId): array`

**Объединённые** **CSS**/**JS** **в** `/cache/assets/{pageId}.{css,js}`.

---

## 💾 Запись данных

### `saveData(string $fileName, array $data): bool`

**Сохраняет** JSON **в** `data/{fileName}.json`.

---

### `savePageData(string $id, array $data): bool`

**Сохраняет** **страницу**.

---

### `saveMenu(string $id, array $data): bool`

**Сохраняет** **меню**.

---

### `saveModulesData(array $data): bool`

**Сохраняет** `config/data/modules.json`.

---

### `handleSaveSettings(): array`

**Сохраняет** **настройки** **сайта**.

```php
$res = handleSaveSettings();
// ['success' => '...', 'error' => '']
```

**Валидация** `name`/`slogan`/`footer_copyright` (**санитайзер**).  
**Валидация** `email`.  
**Обновление** **кеша** **при** **изменении** **ассетов**.

---

### `handleSavePage(): array`

**Сохраняет** **страницу** (**создание**/**редактирование**).

**Валидация** `title`, `slug`, `template`, `status`.  
**Сохранение** **rows**/**columns**/**modules**.

---

### `handleDeletePage(): void`

**Удаляет** **страницу**. **Редирект** **на** **список**.

---

### `handleClonePage(): void`

**Клонирует** **страницу** **в** `{id}-copy`.

---

### `handleSetHomePage(): void`

**Назначает** **главную** **страницу**.

---

### `handleSaveMenu(): array`

**Сохраняет** **меню**.

---

### `handleDeleteMenu(): void`

**Удаляет** **меню**.

---

### `handleSetMainMenu(): void`

**Назначает** **главное** **меню**.

---

### `handleModuleAction(): array`

**Управление** **модулями**:
- `module_action=upload` — загрузка ZIP.
- `module_action=delete` — удаление.

**Валидация** `manifest.id`.  
**`detectZipSlip`** **перед** **распаковкой**.

---

### `unpackModule(string $moduleId): array`

**Распаковывает** **модуль** **из** **ZIP**.

**Валидация** `$moduleId`.  
**`detectZipSlip`**.

---

### `removeModuleFiles(string $moduleId): array`

**Удаляет** **файлы** **модуля**.

---

### `handleSaveSecurity(): array`

**Сохраняет** **настройки** **безопасности**.

**Валидация** `From:` (**Host** **Header** **Injection**).

---

### `handleSaveBackupSettings(): array`

**Сохраняет** **настройки** **бэкапов**.

---

### `handleInitBackupProcess(): array`

**Инициализация** **бэкапа** (**сбор** **файлов**, **разбивка** **на** **пачки**).

---

### `handleProcessBackupStep(): array`

**Пошаговая** **архивация** (**50** **файлов** **на** **шаг**).

---

### `handleRestoreBackup(): array`

**Восстановление** **из** **бэкапа**.

**`detectZipSlip`** **перед** **распаковкой**.

---

### `handleDownloadBackup(): void`

**Отдаёт** **ZIP** **через** `readfile`.

---

### `handleCronBackupAction(string $token): void`

**Крон** **бэкапа** (**токен** **из** `$_GET['token']`).

---

### `clearPageCache(): void`

**Очистка** **кеша** **страниц** + **объединённых** **ассетов**.

---

## 🔐 Безопасность

### `verifyAdminCredentials(string $login, string $password): bool`

**Проверка** **логина**/**пароля** (**с** **`hash_equals`** **и** **`password_verify`**).

```php
if (verifyAdminCredentials($login, $password)) { ... }
```

---

### `checkIpBlockStatus(string $userIp, array $bfData): bool`

**Проверка** **бана** **IP**.

---

### `handleSuccessfulLogin(string $userIp, array $bfData): void`

**Успешный** **вход** (**regenerate** **session**, **очистка** **attempts**).

---

### `handleFailedLogin(string $userIp, array $bfData): void`

**Неудачная** **попытка** (**инкремент** **счётчика**, **бан**).

---

### `handleLiveHashGeneration(string $newPassword): string`

**Генерация** **хеша** **пароля** (**только** **до** **первой** **настройки**).

---

### `checkAdminSessionTimeout(array $bfData): void`

**Idle timeout** — **уничтожение** **сессии** **при** **неактивности**.

---

## 📁 Файловый менеджер

### `fmSafePath(string $path): ?string`

**Валидация** **относительного** **пути** (**whitelist** **символов**).

---

### `fmSafeAbsolute(string $relativePath): ?string`

**`realpath`** + **проверка** **префикса** `FM_SITE_ROOT`.

---

### `fmIsBlacklisted(string $relativePath): bool`

**Запрещённые** **пути:** `config/data`, `config/backups`, `config/core`, `data/logs`.

---

### `fmIsAllowedExtension(string $fileName): bool`

**Whitelist** **расширений**.

---

### `fmIsSensitiveFile(string $fileName): bool`

**Чувствительные** **файлы:** `.pepper`, `credentials.json`, `login_attempts.json`, `backup_config.json`.

---

## 📝 Логирование

### `logAction(string $action, string $message, string $level = 'INFO', ?string $user = null): void`

**Запись** **в** `data/logs/admin.log`.

```php
logAction('page_save', 'Страница сохранена', 'INFO');
logAction('auth_failed', 'Неудачная попытка', 'WARNING');
```

**Уровни:** `INFO`, `WARNING`, `ERROR`, `CRITICAL`.

---

### `rotateLog(string $logDir): void`

**Ротация** **лога** **при** **превышении** **размера**.

---

### `parseLogLine(string $line): ?array`

**Парсинг** **строки** **лога**.

---

### `getLogActions(): array`

**Список** **всех** **возможных** **действий** **для** **логирования**.

---

## 🌐 Меню

### `isBurgerEnabled(): bool`

**Включён** **ли** **бургер**.

---

### `getBurgerBreakpoint(): string`

**Брейкпоинт** **бургера** (`sm`, `md`, `lg`, `xl`).

---

### `getPlainText(): string`

**Название** **сайта** **без** **HTML-тегов**.

---

## 📞 Поддержка

- **Архитектура:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **Модули:** [MODULES.md](MODULES.md)
- **Безопасность:** [SECURITY.md](SECURITY.md)