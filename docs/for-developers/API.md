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
**Проверка** `unpublish_at`: если задан и уже наступил — возвращает `null` (подстраховка от задержки cron).

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

**Валидация** `title`, `slug`, `template`, `status` (4 значения: `draft` / `scheduled` / `published` / `archived`).  
**Валидация дат:**
- `scheduled` требует `publish_at` в будущем.
- `unpublish_at` (если задан) — в будущем и позже `publish_at`.
- Даты в прошлом → ошибка.

**Очистка дат по статусу:**
- `draft` / `archived` → обе даты обнуляются.
- `published` → `publish_at` обнуляется, `unpublish_at` сохраняется.

**Сохранение** `rows`/`columns`/`modules`.  
**Сброс кеша** страницы через `clearPageCacheById($id)`.

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
**Сравнение с** `getCronToken()` — общий токен для бэкапов и публикации, хранится в `data/settings.json`.

---

### `clearPageCache(): void`

**Очистка** **кеша** **страниц** + **объединённых** **ассетов**.

---

### `clearPageCacheById(string $id): void`

**Очистка кеша одной страницы** (HTML + объединённые ассеты).

Отличие от `clearPageCache()`: чистит **только** страницу с указанным ID, а не весь кеш. Используется при сохранении одной страницы или при её публикации/архивации — чтобы не сбрасывать кеш всего сайта.

```php
clearPageCacheById('about');
// Удаляет cache/pages/about.html
// Удаляет cache/assets/about.css, cache/assets/about.js
```

**Особенность:** для главной страницы (getHomePageId()) ключ кеша — home, а не реальный ID. Учитывается автоматически.

**Используется в:**

handleSavePage() — при сохранении страницы.

handleCronPublishAction() — при автопубликации / архивации.

---

## 🗑 Корзина

### `trashPage(string $id): array`

**Перемещает страницу в корзину.** Логин для `_trash.deleted_by` берётся из `$_SESSION['admin_login']`.

```php
$result = trashPage('about');
// ['success' => true, 'error' => '']
```

**Валидация:** тип `page`, ID по regex `[a-z0-9\-_]+`.

---

### `trashMenu(string $id): array`

**Перемещает меню в корзину.** Аналогично `trashPage`, но для `data/menus/`.

```php
$result = trashMenu('footer_help');
```

---

### `trashEntity(string $type, string $id, string $sourceDir): array`

**Общая функция перемещения сущности в корзину.** Используется `trashPage()` и `trashMenu()`. Напрямую обычно не вызывается.

**Что делает:**
1. Валидирует тип и ID.
2. Читает исходный JSON.
3. Добавляет `_trash` с метаданными.
4. Записывает в `data/trash/{type}_{id}_{timestamp}.json`.
5. Удаляет оригинал.
6. **Откат:** если не удалось удалить оригинал — удаляет файл из корзины.

---

### `getTrashList(?string $type = null): array`

**Возвращает список файлов в корзине с метаданными.** Отсортировано по `deleted_at` (свежие сверху).

```php
$list = getTrashList('page');
// [
//   ['file' => 'page_home_1735689600.json', 'type' => 'page', 'original_id' => 'home', 'title' => 'Главная', 'deleted_at' => 1735689600, 'deleted_by' => 'admin', 'size_bytes' => 4096],
//   ...
// ]
```

**Фильтр `$type`:** `'page'` / `'menu'` / `null` (все).

---

### `getTrashCount(?string $type = null): int`

**Возвращает количество файлов в корзине.**

```php
$total = getTrashCount();          // все
$pages = getTrashCount('page');    // только страницы
$menus = getTrashCount('menu');    // только меню
```

Для фильтра по типу использует `glob("{$type}_*.json")` — быстро.

---

### `restoreFromTrash(string $trashFileName): array`

**Восстанавливает сущность из корзины.**

```php
$result = restoreFromTrash('page_about_1735689600.json');
// ['success' => true, 'message' => 'Страница восстановлена как "about-restored" (оригинальный ID был занят).', 'error' => '', 'new_id' => 'about-restored']
```

**Разрешение конфликта ID:**
1. Если `original_id` свободен — восстанавливает под ним.
2. Иначе — `{original_id}-restored`, `{original_id}-restored-2`, ... до 100 попыток.

**Для страниц** slug **всегда пересчитывается** в `{new_id}` — чтобы URL не конфликтовал.

---

### `deleteFromTrash(array $fileNames): array`

**Окончательно удаляет указанные файлы из корзины.**

```php
$result = deleteFromTrash(['page_home_1735689600.json', 'menu_x_1735689601.json']);
// ['success' => true, 'deleted' => 2, 'error' => '']
```

**Валидация имён файлов:** regex `^[a-z]+_[a-z0-9\-_]+_\d+\.json$`.

---

### `clearTrash(): array`

**Полностью очищает корзину.**

```php
$result = clearTrash();
// ['success' => true, 'deleted' => 5, 'error' => '']
```

---

### `handleTrashAction(): void`

**Обработчик POST-действий корзины.** Вызывается из `config/index.php` при `$_POST['trash_action']`.

**Действия:**
- `restore` — восстановить выбранные.
- `delete_selected` — удалить выбранные.
- `clear_all` — очистить всё.

**Дополнительные поля формы:** `trash_context` (`page` / `menu`), `trash_return_url`, `trash_files[]`.

**Редирект** — всегда, в конце. `flash` — success / warning / error.

---

### `ensureTrashDir(): bool`

**Гарантирует существование `data/trash/` + `.htaccess` защиту.** Вызывается из `trashEntity()`.

```php
if (!ensureTrashDir()) {
    return ['success' => false, 'error' => 'Папка корзины недоступна для записи.'];
}
```

---

## ⏰ Cron-публикация

### `handleCronPublishAction(string $token): void`

**Логика крона публикации страниц.** Вызывается из `config/cron/publish.php`.

**Что делает:**

1. Проверяет `token` через `getCronToken()`.
2. Проходит по всем файлам `data/pages/*.json`.
3. Применяет переходы:
   - `scheduled` + `publish_at <= now` → `published`, `publish_at = null`.
   - Если при этом `unpublish_at` уже в прошлом — сразу `archived` (без промежуточного `published`).
   - `published` + `unpublish_at <= now` → `archived`, `unpublish_at = null`.
4. Сбрасывает кеш затронутых страниц через `clearPageCacheById()`.
5. Логирует переходы (`page_publish`, `page_archive`).
6. Отдаёт plain-text: `Status: OK, published: N, archived: M`.

**Не использует** `handleSavePage()` — для крона простая прямая запись через `savePageData()` (без POST-валидации).

---

### `getLastCronPublishTime(): int`

**Время последнего запуска виртуального крона публикации** (Unix timestamp).  
`0` — если ни разу не запускался.

```php
$last = getLastCronPublishTime();
setLastCronPublishTime(int $timestamp): void
```
**Сохраняет время последнего запуска** виртуального крона публикации в data/settings.json.

---

### `setLastCronPublishTime(int $timestamp): void`
**Сохраняет время последнего запуска** виртуального крона публикации в data/settings.json.

```php
setLastCronPublishTime(time());
```
**Используется в** config/index.php перед асинхронным вызовом крона — ограничивает частоту (не чаще 1 раза в 5 минут).

--- 

### `getCronToken(): string`
**Возвращает общий токен** для cron/backup.php и cron/publish.php.
Если в settings.json токена нет — генерирует bin2hex(random_bytes(16)) и сохраняет.

```php
$token = getCronToken();
```
---

### `regenerateCronToken(): string`
**Принудительно генерирует новый токен** и сохраняет в settings.json.
**Внимание:** сломает все настроенные крон-задачи (бэкап + публикация).

```php
$newToken = regenerateCronToken();
```
**Вызывается** из формы «Интеграция с планировщиком» в Настройках сайта (через AJAX-эндпоинт `generate_cron_token` + сохранение формы).

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

## 🏷 Статусы страниц

### `getPageStatusBadge(array $page): array`

**Возвращает** метку, CSS-классы и иконку для бейджа статуса страницы.

```php
$badge = getPageStatusBadge($page);
// ['label' => 'Опубликована', 'class' => 'text-emerald-800 bg-emerald-50', 'icon' => 'icon-circle-check']
```

**Логика:**

| Условие | Label | Класс |
|---------|-------|-------|
| `draft` | Черновик | amber |
| `scheduled` | Запланирована | blue |
| `published` + `unpublish_at` в будущем | Снятие запланировано | orange |
| `published` (без `unpublish_at` или оно в прошлом) | Опубликована | emerald |
| `archived` | Снята с публикации | slate |

**Используется** в `pages.php` — и в десктопной таблице, и в мобильных карточках.

---

## 📞 Поддержка

- **Архитектура:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **Модули:** [MODULES.md](MODULES.md)
- **Безопасность:** [SECURITY.md](SECURITY.md)