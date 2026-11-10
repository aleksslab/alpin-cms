# API функций AlPin CMS

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

### `validateSvgContent(string $content): ?string`

**Проверка содержимого SVG** перед сохранением. Возвращает **текст ошибки** или `null`.

```php
$error = validateSvgContent($svg);
if ($error !== null) {
    // файл отклонён
}
```

**Проверяет** **после** `html_entity_decode`:
- `<script>`, `on*="..."`, `<foreignObject>`
- встроенные HTML-элементы: `iframe`, `object`, `embed`, `frame`, `audio`, `video`, `canvas`
- `<!ENTITY>`, `<?xml-stylesheet?>`
- `javascript:`, `vbscript:`, CSS `expression(...)`
- внешние `href` / `xlink:href`; `data:image/...` разрешён

Дополнительно файл должен быть **корректным XML** (SimpleXML + `LIBXML_NONET`). SVG — XML с исполняемыми возможностями, поэтому одного MIME `image/svg+xml` недостаточно.

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

### `verifyCsrfToken(?string $token): bool`

**Сравнение** **CSRF-токена** **из** **запроса** **с** `$_SESSION['csrf_token']`
**через** `hash_equals()`. **Пустая** **сессия** **или** **пустой** **токен** →
`false`.

```php
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    die('CSRF');
}
```

---

### `isValidId(string $id): bool`

**Проверка** **ID** (**модуль**, **плагин**, **страница**, **файл**) — **только**
`[a-z0-9\-_]+`: **нижний** **регистр**, **без** **точек** **и** **слэшей**.
**Защищает** **от** **path traversal**.

```php
isValidId('hero');        // true
isValidId('../etc');      // false
isValidId('Hero');        // false
```

---

### `detectZipSlip(ZipArchive $zip): ?string`

**Проверка** **имён** **файлов** **в** **ZIP** **до** `extractTo()`. **Возвращает**
**имя** **опасной** **записи** **или** `null`.

**Блокирует:** `..`, **абсолютные** **пути** (`/`, `C:\`), **NUL-байты**.

```php
$entry = detectZipSlip($zip);
if ($entry !== null) {
    $zip->close();
    // установка отменяется
}
```

`verifyCsrfToken()`, `isValidId()` и `detectZipSlip()` **живут** **в**
`config/core/security.php`. **Файл** **подключается** **из** `config.php` **и**
`functions.php`; **сессию** **и** **заголовки** **он** **не** **трогает**, **только**
**чистые** **помощники**.

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

### `getThemePalette(array $settings, string $mode = 'light'): array`

**Возвращает палитру темы** (светлую или тёмную) из настроек.

```php
$themeLight = getThemePalette($settingsData, 'light');
// ['bg_main' => '#FFFFFF', 'bg_card' => '#FFFFFF', ...]

$themeDark = getThemePalette($settingsData, 'dark');
// ['bg_main' => '#0F172A', 'bg_card' => '#1E293B', ...]
```

**Поддерживает две структуры `settings.json`:**
1. Вложенная: `theme.light.*` / `theme.dark.*`.
2. Плоская: `theme.bg_main`, `theme.bg_card`, ... — fallback для светлой темы. Для тёмной — дефолты.

**Ключи палитры:** `bg_main`, `bg_card`, `bg_section`, `text_main`, `text_muted`, `border_color`.  
**`primary_color` / `primary_dark`** — сюда **не входят** (общие для обеих тем, берутся отдельно из `$settings['theme']`).

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

## 🪝 Хуки и Плагины

### Список доступных хуков

| Хук | Тип | Где срабатывает | Аргументы |
|-----|-----|-----------------|-----------|
| `index_start` | action | В начале `index.php` | — |
| `admin_init` | action | В админке, после авторизации | — |
| `admin_header_notifications` | action | В хедере админки | — |
| `admin_settings_sections` | action | В Настройках сайта | — |
| `render_head_start` | action | В `<head>` (до SEO) | — |
| `render_head_end` | action | В `<head>` (перед `</head>`) | — |
| `render_body_end` | action | Перед `</body>` | — |
| `after_file_upload` | action | После загрузки файла (проводник / медиа) | `$filePath` |
| `admin_login_success` | action | После успешного входа в админку | `$userIp` |
| `plugin_before_toggle` | action | Перед вкл/выкл плагина | `$id`, `$active` |
| `plugin_after_toggle` | action | После вкл/выкл плагина | `$id`, `$active` |
| `plugin_before_delete` | action | До удаления плагина | `$id` |
| `plugin_after_delete` | action | После удаления плагина | `$id` |
| `plugin_before_update` | action | Перед обновлением плагина | `$id`, `$newVersion` |
| `plugin_after_update` | action | После обновления плагина | `$id`, `$newVersion` |
| `plugin_after_install` | action | После установки плагина | `$id` |
| `page_title` | filter | Заголовок страницы | `$title`, `$page` |
| `preview_enabled` | filter | Доступен ли предпросмотр. По умолчанию `false`: `?preview=` игнорируется, блок в редакторе и кнопка конструктора скрыты | `$enabled` |
| `edit_page_preview_block` | action | Блок preview в форме редактирования страницы | `$page`, `$pageId` |
| `edit_page_scripts` | action | Скрипты редактора страницы (create и edit) | `$pageId`, `$isEdit` |
| `site_preview_banner` | action | Баннер предпросмотра на фронте | `$isPreview` |
| `save_settings_data` | filter | Массив настроек перед сохранением (при полном сохранении — собранный из POST, при сохранении из плагина — текущий `settings.json`) | `$newSettings` |
| `page_scheduling_enabled` | filter | Доступно ли планирование публикации. По умолчанию `false`: статус «По расписанию» и поля дат в редакторе не показываются | `$enabled` |
| `trash_enabled` | filter | Доступна ли корзина. По умолчанию `false`: удаление окончательное, кнопки и модалки нет | `$enabled` |
| `trash_entity_result` | filter | Результат перемещения страницы/меню в корзину | `$result`, `$type`, `$id` |
| `trash_button` | action | Кнопка «Корзина» в шапке списка | `$type`, `$returnUrl` |
| `trash_modal` | action | Модалка корзины | `$type`, `$returnUrl` |

#### `isPageSchedulingEnabled(): bool`

**Возвращает** `true`, если активный плагин включил фильтр `page_scheduling_enabled`. Ядро по умолчанию считает возможность выключенной: без обработчика фильтра страницу со статусом `scheduled` публиковать некому. Функция — в `config/core/admin_controller.php`.

```php
if (isPageSchedulingEnabled()) {
    // статус «По расписанию» и даты доступны
}
```

Влияет на:

- `config/modules/pages/edit.php` — радиокнопка «По расписанию» и поля `publish_at` / `unpublish_at`;
- `handleSavePage()` — отклоняет `status = scheduled`, если возможность выключена;
- `config/js/pages.js` — нет полей дат, подсветка статусов продолжает работать.

`loadPage()` проверяет `unpublish_at` независимо от фильтра: это страховка фронта, а не часть планировщика.

---

## 🎨 Рендер

### `renderBreadcrumbs(array $page): string`

**Рендерит хлебные крошки** для текущей страницы.

```php
echo renderBreadcrumbs($page);
```

**Логика:**
1. Проверяет `breadcrumbs_enabled` в настройках.
2. Если текущая страница — главная, проверяет `breadcrumbs_show_on_home` (если выключено — возвращает пустую строку).
3. Ищет страницу в `main_menu` (рекурсивно по `children`).
4. Если находит — строит цепочку от корня. Если нет — `[Корень] → Заголовок страницы`.
5. Учитывает настройки: показывать ли корень (`breadcrumbs_show_home_root`), текст корня (`breadcrumbs_home_label`), разделитель (`breadcrumbs_separator`).
6. Возвращает HTML с микроразметкой Schema.org (`BreadcrumbList`).

**Важно:** если цепочка пустая (например, на главной при выключенном корне) — возвращает пустую строку.

---

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

**Сохраняет** `config/data/modules.json` **через** `safeFileWrite()`.

**Формат** — **объект**, **ключ** — **ID** **модуля**:

```json
{
    "hero": {
        "name": "Герой (Hero)",
        "icon": "icon-layout-dashboard",
        "description": "Полноэкранный блок",
        "has_settings": true,
        "auto_unpack": false,
        "version": "1.0.0",
        "author": "AlekSSLab"
    }
}
```

**Порядок** **ключей** — **порядок** **вывода** **в** **админке**.
`syncModulesData()` **сохраняет** **его** **и** **добавляет** **новые** **модули**
**в** **конец**.

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

**Режим сохранения** определяется скрытым полем `settings_full`:

- `settings_full=1` — форма «Настроек сайта» (`config/modules/config_vars.php`): `$newSettings` **пересобирается с нуля** из POST. Ключи, которых нет в форме (настройки выключенных плагинов, пользовательские поля), при этом **сохраняются** из текущего `settings.json`.
- поля нет — форма плагина (например, вкладка «Логи»): берётся текущий `settings.json`, поверх которого фильтр `save_settings_data` записывает только присланные плагином поля. Настройки сайта не затираются.

Форма плагина `settings_full` **не добавляет**. Сохранение своих полей плагин оформляет через `save_plugin_settings()` (см. `PLUGINS.md`).

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

**Управление** **модулями** (POST `module_action`):
- `upload` — загрузка ZIP из формы.
- `delete` — удалить **только код** (архив и запись в реестре остаются).
- `purge` — удалить **полностью**: код, архив, превью и запись в
  `config/data/modules.json`.

**Распаковка** — отдельный POST `unpack_module=1` (`admin_actions.php` →
`unpackModule()`).

**Валидация** `manifest.id` и `isValidId()`.  
**`detectZipSlip`** **перед** **распаковкой**.  
**Проверка** `findModuleUsage()`: используемый на страницах модуль удалить
нельзя.  
**Flash** — через `setFlash()`/`renderFlash()`.

---

### `unpackModule(string $moduleId): array`

**Распаковывает** **модуль** **из** `config/modules/archives/{id}.zip`
**в** `config/modules/{id}/`, `modules/{id}.php`, `css/{id}.css`,
`js/{id}.js`.

**Валидация** `$moduleId` (**`isValidId`**).  
**`detectZipSlip`**.

`unpackModule()` **доступна** **на** **фронте**: **определена** **в**
`config/core/modules.php`, **подключается** **через** `functions.php`.

---

### `unpackModuleArchive(string $zipPath, string $moduleId): array`

**Внутренняя** **распаковка** **произвольного** **ZIP** **во** **временную**
**папку** `config/modules/_temp/{id}/`, **затем** **копирование** `admin/` **и**
`site/` **по** **местам**.

**Перед** **копированием** **старый** **код** **переносится** **в**
`config/modules/_backup/` (`_moduleStashCode()`). **Если** **копирование**
**сорвалось**, **новые** **файлы** **удаляются**, **старый** **код**
**возвращается** (`_moduleRestoreCode()`). **При** **успехе** **резервная**
**копия** **удаляется**.

**`.min`-файлы** **и** **кеш** **обновляются** **только** **в** **админке**
(**через** `function_exists()`). **Остатки** **прерванных** **распаковок**
**в** `config/modules/_backup/` **и** `_temp/` **старше** **часа** **удаляются**
`_moduleCleanupRuntime()`.

---

### `removeModuleFiles(string $moduleId): array`

**Удаляет** **распакованный** **код** **модуля**: `config/modules/{id}/`,
`modules/{id}.php`, `css/{id}.css`, `css/{id}.min.css`, `js/{id}.js`,
`js/{id}.min.js`.

**Возвращает:** `['success' => bool, 'message' => string, 'deleted' => int]`.  
**Архив** **и** **запись** **в** `modules.json` **остаются** — **для** **этого**
**есть** `purgeModule()`.

---

### `purgeModule(string $moduleId): array`

**Полное** **удаление**: `removeModuleFiles()` + архив
`config/modules/archives/{id}.zip` + превью
`config/modules/previews/{id}.png` + запись в `modules.json`.

---

### `reinstallModule(string $moduleId): array`

**Удаляет** **код** **и** **распаковывает** **архив** **заново**.

---

### `validateModuleArchive(string $zipPath, string $moduleId): array`

**Проверка** **ZIP** **перед** **установкой**:

- `manifest.json` **в** **корне**;
- `id` **в** **манифесте** **совпадает** **с** `$moduleId`;
- **нет** `plugin.json` (**это** **не** **плагин**);
- **есть** `site/module.php`;
- **версия** **не** **ниже** **установленной** (**запрет** **даунгрейда**);
- **нет** **ZIP Slip**.

**Возвращает:** `['success' => bool, 'message' => string, 'manifest' => array]`.

---

### `installModuleFromZip(string $zipPath, string $id, bool $replace = false, bool $unpack = true): array`

**Установка** **или** **обновление** **модуля** **из** **ZIP**:

1. `validateModuleArchive()`;
2. **если** **модуль** **уже** **установлен** **и** `$replace = false` — **отказ**;
3. **копия** **архива** **в** `config/modules/archives/{id}.zip`;
4. **превью** **из** `preview.png` **в** `config/modules/previews/{id}.png`;
5. **если** `$unpack = true` — `unpackModuleArchive()` **с** **резервной**
   **копией** **старого** **кода**;
6. **если** `$unpack = false` **и** **модуль** **был** **установлен** —
   `removeModuleFiles()`: **старый** **код** **не** **должен** **отдаваться**
   **вместо** **нового**, **он** **распакуется** **при** **добавлении** **на**
   **страницу**;
7. **запись** **в** `modules.json`.

**Порядок** **вызова**:

- **новый** **модуль** **из** **каталога** — `$unpack = false` (**код** **не**
  **нужен**, **пока** **модуль** **не** **добавлен**);
- **обновление** **из** **каталога** — `$unpack = isModuleInstalled($id) &&
  !empty(findModuleUsage($id))`;
- **ручная** **загрузка** **ZIP** — **решает** `admin_controller.php`:
  `auto_unpack` **или** **использование** **на** **страницах**.

**Используется** **вкладкой** «Модули», **AJAX**
`module_install_from_url` **и** **плагином** `updates`.

---

### `downloadModuleZip(string $url, string $id): ?string`

**Скачивает** **архив** **модуля** **во** **временный** **файл**
`data/module_{id}.zip`.

**Ограничения:** **только** `http(s)://`, **не** **более** **10 МБ**, **таймаут**
**30 с**. **Возвращает** `null` **при** **ошибке** — **файл** **удаляется**.

---

### `syncModulesData(): array`

**Синхронизирует** `config/data/modules.json` **с** **архивами** **в**
`config/modules/archives/`.

- **существующие** **записи** **обновляются** **на** **месте**, **порядок**
  **сохраняется**;
- **новые** **архивы** **добавляются** **в** **конец**;
- **записи** **без** **архива** **удаляются**.

**Возвращает** **актуальный** **список** **модулей**.

---

### `handleSaveSecurity(): array`

**Сохраняет** **настройки** **безопасности**.

**Валидация** `From:` (**Host** **Header** **Injection**).

Вызывается диспетчером `handle_admin_post_actions()` по схеме
Post/Redirect/Get: флеш ставится там же, затем редирект на
`index.php?tab=security`. Так же обрабатываются `action_unban` и
`action_manual_ban`. При смене логина или пароля — выход,
редирект на `index.php?msg=updated`.

---

### Резервное копирование

Функциональность резервного копирования вынесена в плагин `backups` и в ядре
не документируется. Плагин использует только публичный API ядра:
`safeFileWrite()`, `detectZipSlip()`, `logAction()`, `setFlash()` и реестр
фоновых задач (`register_cron_task()`).

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

Плагин `publish` (cron-задача) — при автопубликации / архивации.

---

## 🗑 Корзина

Корзина реализована плагином `trash` (`config/plugins/trash/`). Ядро знает
только возможность и контракт; функции перемещения, восстановления и очистки
в ядро не входят. Обе функции ниже — в `config/core/admin_controller.php`:
корзина относится к админке и на фронте не нужна.

### `isTrashEnabled(): bool`

**Проверяет, доступна ли корзина.** Возвращает результат фильтра
`trash_enabled` (по умолчанию `false`).

```php
if (isTrashEnabled()) {
    // плагин активен: удаление идёт в корзину
}
```

Плагин включает возможность в своём `plugin.php`:

```php
add_filter('trash_enabled', function () {
    return true;
});
```

---

### `getDeleteConfirmMessage(string $type, string $title): string`

**Возвращает текст подтверждения удаления** для `confirm()`.

- корзина доступна — «Переместить страницу/меню «…» в корзину?»;
- корзина отключена — предупреждение об окончательном удалении.

```php
$message = getDeleteConfirmMessage('page', $page['title']);
```

---

### Хуки корзины

| Хук | Тип | Что делает |
|---|---|---|
| `trash_enabled` | filter | Включает корзину. По умолчанию `false` |
| `trash_entity_result` | filter | Ядро передаёт `null, $type, $id`; плагин возвращает результат перемещения в корзину или `null` |
| `trash_button` | action | Вывод кнопки «Корзина» в шапке списка. Аргументы: `$type`, `$returnUrl` |
| `trash_modal` | action | Вывод модалки корзины. Аргументы: `$type`, `$returnUrl` |

**Поведение ядра:**

- `handleDeletePage()` / `handleDeleteMenu()` вызывают
  `apply_filters('trash_entity_result', null, $type, $id)`;
- плагин вернул массив — ядро пишет `page_trash` / `menu_trash`, сбрасывает
  кеш страницы и показывает «перемещено в корзину»;
- вернулся `null` — ядро удаляет файл окончательно, пишет `page_delete` /
  `menu_delete` и вызывает `trash_item_purged` с блоком `_trash`, чтобы
  плагины (например, `history`) почистили связанные данные.

---

### Контракт событий

Плагин `trash` вызывает события, на которые подписываются другие плагины:

- `page_before_delete($id)`, `page_deleted($id)`;
- `page_before_restore($originalId)`, `page_restored($newId)`;
- `trash_item_purged($trashData)` — данные файла корзины с блоком `_trash`.

`trash_item_purged` также вызывается ядром при окончательном удалении, когда
корзина отключена.

---

## 👁 Предпросмотр

Предпросмотр реализован плагином `preview` (`config/plugins/preview/`). Ядро
оставляет режим рендера и публичные функции чтения; создание, токены, UI и
очистка — в плагине.

### `isPreviewEnabled(): bool`

**Проверяет, доступен ли предпросмотр.** Возвращает результат фильтра
`preview_enabled` (по умолчанию `false`). Функция — в `config/core/functions.php`.

```php
if (isPreviewEnabled()) {
    // ?preview= и блок в редакторе доступны
}
```

Плагин включает возможность в `plugin.php`:

```php
add_filter('preview_enabled', function () {
    return true;
});
```

Пока фильтра нет:

- `?preview={token}` игнорируется, старые ссылки не открывают страницы;
- блок «Ссылка для согласования» и кнопка «Предпросмотр» не выводятся;
- AJAX-эндпоинты preview не отвечают.

### `loadPreviewById(string $previewId): ?array`

Читает временный preview-файл `data/preview/{id}.json`, **убирает** служебный
блок `_preview`. **Живёт в `functions.php`** — нужна фронту.

### `loadPageByPreviewToken(string $token): ?array`

Ищет страницу по `preview_token` в `data/pages/*.json`. Возвращает страницу в
**любом** статусе. **Живёт в `functions.php`.**

### Создание временного preview

Создание и перезапись временных preview, а также генерация постоянных токенов —
внутри плагина (обработчики на `admin_init`). Ядро своих preview-эндпоинтов не
регистрирует. Внешний контракт для других плагинов — только чтение через
`loadPreviewById()` и `loadPageByPreviewToken()`.

### Запись preview_token

`preview_token` записывается в `data/pages/{id}.json` **только при сохранении
страницы**: плагин ловит `page_data_before_save` и читает hidden-поле
`preview_token`. Пустое значение удаляет токен. При создании страницы токен не
пишется. `handleClonePage()` не копирует `preview_token` — защита от дублей.

### Хуки предпросмотра

| Хук | Где | Что делает |
|---|---|---|
| `preview_enabled` (фильтр) | `functions.php` | Включает предпросмотр |
| `admin_login_success` | `admin_auth.php` | Событие входа (плагин чистит временные preview) |
| `edit_page_preview_block` | `pages/edit.php` | Блок в форме редактирования страницы |
| `edit_page_scripts` | `pages/edit.php` | Скрипты редактора (create и edit) |
| `site_preview_banner` | корневой `index.php` | Баннер предпросмотра |

### Данные

- `data/preview/{preview_id}.json` — временные preview (32 hex), `.htaccess` рядом;
- `data/pages/{id}.json` → `preview_token` — постоянная ссылка для согласования.

Файлы данных остаются на месте при выключении и удалении плагина.

---

## 🚧 Технические работы

Режим техработ — плагин `maintenance` (`config/plugins/maintenance/`).
В ядре остаётся только шаблон `templates/maintenance.php`: плагин подключает
его при 503, тему можно переопределить.

- Настройки (`maintenance_enabled`, `maintenance_message`, `maintenance_until`,
  `maintenance_bypass_token`, `maintenance_show_timer`) — в `settings.json`.
- Bypass-токен генерируется в админке на клиенте (`crypto.getRandomValues`)
  и сохраняется вместе с формой. Отдельного AJAX-эндпоинта в ядре нет.
- Автоснятие по `maintenance_until` выполняет плагин на `index_start`.
- Заголовки 503, `Retry-After`, `X-Robots-Tag` и bypass-cookie — в плагине.

---

## 🖼 Медиа

### Хелперы для фронта (`functions.php`)

#### `mediaLazyAttrs(): string`

Возвращает строку с атрибутами lazy-загрузки для `<img>`.

```php
// media_lazy_enabled = true:
// → 'loading="lazy" decoding="async" '
// media_lazy_enabled = false:
// → ''
```

**Использование:**
```php
<img src="..." <?php echo mediaLazyAttrs(); ?>alt="...">
```

**Не применять** к hero, carousel, slider-advanced и логотипу в хедере (LCP).

---

#### `mediaImageSrc(string $url): string`

Подменяет `src` на `.webp`, если такой файл **существует** рядом с оригиналом.

```php
mediaImageSrc('images/photo.jpg');   // 'images/photo.webp' (если есть)
mediaImageSrc('images/photo.jpg');   // 'images/photo.jpg' (если .webp нет)
mediaImageSrc('images/photo.webp');  // 'images/photo.webp' (не трогает)
mediaImageSrc('images/logo.svg');    // 'images/logo.svg' (не трогает)
mediaImageSrc('https://cdn.com/a.jpg'); // без изменений
mediaImageSrc('data:image/png;base64,...'); // без изменений
```

**Пропускает:** `data:`, `http(s)://`, `//`, `.webp`, `.svg`, `.avif`, `.ico`.

**Примечание:** Функция только **читает** наличие `.webp`. Сами `.webp` создаются плагином «Медиа (WebP)» (см. `PLUGINS.md`).

**Результат кешируется** в памяти на время запроса (`static $cache`).

**Использование:**
```php
<img src="<?php echo e(mediaImageSrc($url)); ?>" <?php echo mediaLazyAttrs(); ?>alt="...">
```

---

### Медиа (WebP)

Конвертация, статистика и массовая переработка изображений — в плагине
`media-optimization` (`config/plugins/media-optimization/`). Внутренние
функции плагина (`getImageFiles()`, `isWebpSupported()`, `convertToWebp()`,
`getWebpStats()`, `deleteAllWebp()`, `countImagesWithoutWebp()`,
`convertExistingImagesStep()` и обработчики) не входят в публичный API ядра.
Ядро оставляет только `mediaImageSrc()` и `mediaLazyAttrs()` — см. выше.

---

## 🕘 История изменений

Функциональность вынесена в плагин **История изменений** (`history`).
Ядро оставляет только хуки, через которые плагин получает события
(см. `PLUGINS.md`); формат файлов `data/history/` — в `DATA_STRUCTURE.md`.

---

## 🔍 SEO

### `findPageBySlugHistory(string $slug): ?array`

Ищет страницу по slug в `slug_history` других страниц.

**Живёт в `functions.php`.**

```php
$page = findPageBySlugHistory('about');  // находит страницу с актуальным slug 'o-kompanii', если в её истории есть 'about'
```

**Возвращает страницу в любом статусе** — проверка статуса на уровне вызывающего (`loadPage`).

---

### `applyPageDefaults(array $page): array`

Заполняет пропущенные поля страницы значениями по умолчанию. **Живёт в `functions.php`.**

---

### `getBaseUrl(): string`

Возвращает `{protocol}://{host}` без слэша на конце. **Живёт в `functions.php`.**

```php
$baseUrl = getBaseUrl();  // 'https://alpincms.ru'
```

---

## ⏰ Реестр фоновых задач

Ядро хранит список зарегистрированных задач и состояние их запуска
(`last_run`, заявка виртуального крона, пауза после ошибки). Оно не знает,
какой плагин какую задачу добавил и что эта задача делает.

Виртуальный и внешний планировщик делят состояние `config/data/cron_state.json`,
поэтому задача выполняется ровно один раз за `interval`, даже если включены оба.

### `register_cron_task(string $id, callable $callback, array $options = []): void`

**Регистрирует** задачу в общем реестре. `$options`:

- `interval` — минимальный интервал между запусками в секундах (`0` — только ручной запуск);
- `virtual` — участвовать ли в виртуальном кроне (запуск при входе админа);
- `enabled` — активна ли задача (по умолчанию `true`). Только включённые задачи показываются в разделе «Интеграция с планировщиком» Настроек сайта (секция плагина `scheduler`);
- `log` — писать `cron_run` при успешном запуске (по умолчанию `true`). Для частых задач, которые сами логируют результат, ставьте `false` — ошибки всё равно попадут в журнал;
- `label` — человекочитаемое название.

```php
register_cron_task('my-task', 'my_task_callback', [
    'interval' => 3600,
    'virtual'  => true,
    'enabled'  => true,
    'label'    => 'Моя задача',
]);
```

### `get_cron_tasks(): array`

**Возвращает** все зарегистрированные задачи (ключ — `id`).

### `run_cron_task(string $id): bool`

**Выполняет** задачу по `id`. Возвращает `false`, если задача не найдена или
вернула `false`/бросила исключение. Задача не должна сама вызывать
`set_cron_last_run()` и менять состояние крона.

### `get_cron_state(): array`

**Читает** состояние кронов из `config/data/cron_state.json`.

### `set_cron_last_run(string $id, int $timestamp): void`

**Сохраняет** время последнего запуска задачи, не затирая остальные поля
записи. Для ручных операций, которые должны сдвинуть расписание (например,
ручной бэкап); из задачи крона не вызывается.

### `get_cron_last_run(string $id): int`

**Возвращает** время последнего запуска задачи (`0` — не запускалась).

### Захват запуска

Эти функции используют `config/cron/run.php` и виртуальный крон; плагинам
вызывать их не нужно.

- `mark_cron_pending(string $id): string` — атомарно ставит заявку виртуального
  крона и возвращает одноразовый токен (`''` — запуск не нужен: интервал не
  подошёл, жива чужая заявка или действует пауза).
- `claim_cron_task_run(string $id, string $token = ''): bool` — атомарно
  захватывает запуск: по токену — пока заявка жива (`CRON_PENDING_TTL`, 900 с),
  без токена — когда подошёл интервал и нет живой заявки.
- `confirm_cron_task_run(string $id): void` — подтверждает успех и убирает
  служебные поля захвата.
- `release_cron_task_run(string $id): void` — при ошибке откатывает `last_run`
  и ставит паузу `CRON_RETRY_COOLDOWN` (300 с).

### Крон `config/cron/run.php`

URL: `/config/cron/run.php?task={id}&token=xxx[&run=yyy]`.

Единая точка входа для задач плагинов. Ядро проверяет `token` через
`getCronToken()`, валидирует `id` по regex, проверяет `enabled`, захватывает
запуск и вызывает `run_cron_task()`.

Ответы plain-text:

- `Status: OK` — задача выполнена (`cron_run` пишется, если у задачи не отключён `log`);
- `Status: Skipped` (`200`) — задача выключена, интервал не подошёл или запуск
  уже захвачен; в лог не пишется;
- `403` — неверный токен, `400` — невалидный `id`, `404` — неизвестная задача,
  `500` — задача вернула `false` (пишется `cron_run` с уровнем `ERROR`).

Виртуальный крон (`run_virtual_crons()` в `config/core/virtual_crons.php`)
проходит по включённым задачам с флагом `virtual`, ставит заявку и вызывает
`run.php` по HTTP с одноразовым `run`-токеном.

---

## ⏰ Cron-публикация

Логика публикации/архивации вынесена в плагин `publish` (задача `publish`).
Ядро вызывает её через общий эндпоинт `config/cron/run.php?task=publish&token=...`.
Расписание задаёт опция `interval` (60 секунд), флаг `virtual` включает запуск
при заходе админа в админку. Переходы статусов, сброс кеша и логирование
(`page_publish` / `page_archive`) — внутри плагина.

Поля страниц `publish_at` / `unpublish_at` и ручное управление статусами
остаются в ядре (редактор страницы, `handleSavePage()`).

---

### `getCronToken(): string`
**Возвращает общий токен** для всех крон-эндпоинтов в `config/cron/` (в т.ч. `run.php`).
Если в settings.json токена нет — генерирует bin2hex(random_bytes(16)) и сохраняет.

```php
$token = getCronToken();
```
---

### `regenerateCronToken(): string`
**Принудительно генерирует новый токен** и сохраняет в settings.json.
**Внимание:** сломает все настроенные внешние крон-задачи.

```php
$newToken = regenerateCronToken();
```
**Вызывается** из раздела «Интеграция с планировщиком» в Настройках сайта (секция плагина `scheduler`). Новый токен генерируется в браузере (`crypto.getRandomValues`) и попадает в форму через hidden-поле; в `settings.json` он записывается только при сохранении.

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

**Idle timeout** — уничтожение сессии при неактивности. При каждом вызове
обновляет `$_SESSION['last_activity']`.

### `getAdminSessionTimeoutSeconds(array $bfData): int`

Время жизни сессии в секундах (`session_timeout * 60`, минимум 60).

### `getAdminSessionRemaining(array $bfData): int`

Остаток текущей сессии в секундах (`0` — истекла).

### `getAdminSessionWarnSeconds(array $bfData): int`

Порог предупреждения: `min(120, max(10, lifetime / 2))`.

### `renderSessionNotice(int $lifetime): string`

HTML скрытой sticky-полосы предупреждения. Данные для JS —
`data-lifetime`, `data-warn`, `data-ping-url`, `data-login-url`.

### `index.php?ajax=session_ping`

GET-эндпоинт таймера. Продлевает сессию и отдаёт JSON
`{ok, remaining, lifetime, warn}`; без авторизации — `401`.

---

## 📁 Файловый менеджер

Проводник вынесен в плагин `filemanager` (`config/plugins/filemanager/`).
Его функции (`fmSafePath()`, `fmSafeAbsolute()`, `fmIsBlacklisted()`,
`fmIsAllowedExtension()`, `fmIsSensitiveFile()`) не входят в публичный API
ядра.

Плагины могут запретить доступ к своим служебным файлам через фильтр
`sensitive_files` (см. `PLUGINS.md`).

---

## 📝 Логирование

### `logAction(string $action, string $message, string $level = 'INFO', ?string $user = null): void`

**Запись** **в** `config/logs/admin.log` (**по умолчанию**; путь настраивается в плагине `logs`).

```php
logAction('page_save', 'Страница сохранена', 'INFO');
logAction('auth_failed', 'Неудачная попытка', 'WARNING');
```

**Уровни:** `INFO`, `WARNING`, `ERROR`, `CRITICAL`.

**Проверки:** `log_enabled = false` → не пишет; действие отключено в `log_actions` → не пишет (список действий — из реестра, см. `register_log_action()`); небезопасный `storage_path` → fallback на `CMS_LOGS_DIR` (`config/logs/`).

**Константа:** `CMS_LOGS_DIR` определена в `config.php`.

---

### `rotateLog(string $logDir): void`

**Ротация** **лога** **при** **превышении** **размера**.

---

### `getLogSettings(): array`

Настройки логирования из `settings.json` (`log_settings`). Список `log_actions`
собирается из реестра: **новые действия включены по умолчанию**, сохранённый
выбор пользователя приоритетнее.

---

### `register_log_action(string $action, string $label, string $group = 'Прочее', string $icon = 'activity'): void`

**Регистрирует** действие для системы логирования. Реестр — `config/core/log.php`.
Ядро регистрирует свои действия, плагины — свои (на верхнем уровне `plugin.php`).

```php
register_log_action('backup_create', 'Создание бэкапа', 'Бэкапы', 'database-backup');
```

---

### `get_log_actions(): array`

**Возвращает** плоский список зарегистрированных действий: `[action => label]`.

---

### `get_log_action_groups(): array`

**Возвращает** действия, сгруппированные для UI:
`[group => ['icon' => ..., 'actions' => [action => label]]]`.
Порядок групп — порядок регистрации.

---

### `ensureLogsDir(): void`

**Создаёт** папку логов и `.htaccess` (если папка внутри сайта) по пути из
настроек или `CMS_LOGS_DIR`. Вызывается на `admin_init`. Защита папки не зависит
от плагина `logs`.

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