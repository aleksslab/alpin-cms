# Архитектура AlPin CMS

## 📐 Общая схема

```
┌─────────────────────────────────────────────────────────────────┐
│                          HTTP-ЗАПРОС                            │
└────────────────────────────┬────────────────────────────────────┘
                             │
              ┌──────────────┴──────────────┐
              │                             │
       ┌──────▼──────┐              ┌───────▼────────┐
       │  ФРОНТЕНД   │              │    АДМИНКА     │
       │  index.php  │              │ config/index.php│
       └──────┬──────┘              └───────┬────────┘
              │                             │
              │                             │
       ┌──────▼──────┐              ┌───────▼────────┐
       │  Роутинг    │              │  Авторизация   │
       │  $slug      │              │  CSRF          │
       └──────┬──────┘              └───────┬────────┘
              │                             │
       ┌──────▼──────┐              ┌───────▼────────┐
       │  loadPage   │              │  Роутинг       │
       │  шаблон     │              │  по tab        │
       └──────┬──────┘              └───────┬────────┘
              │                             │
       ┌──────▼──────┐              ┌───────▼────────┐
       │  Кеш?       │              │  include       │
       │  HIT / MISS │              │  modules/*.php │
       └──────┬──────┘              └────────────────┘
              │
       ┌──────▼──────┐
       │  Рендер     │
       │  шаблона    │
       │  base.php   │
       └──────┬──────┘
              │
       ┌──────▼──────┐
       │  Хедер      │
       │  Меню       │
       │  Модули     │
       │  Футер      │
       └─────────────┘
```

---

## 📂 Структура папок

### Корень

```
/
├── index.php              # Фронтенд (роутинг + рендер)
├── .htaccess              # ЧПУ + защита
├── README.md
├── docs/                  # Документация
│
├── config/                # АДМИНКА (защищена .htaccess)
├── data/                  # Пользовательские данные (JSON)
├── cache/                 # Кеш (HTML + объединённые ассеты)
├── modules/               # Публичные модули (выполняются на фронте)
├── templates/             # Шаблоны страниц, хедеров, футеров
├── css/, js/, fonts/, images/  # Статические ассеты
└── includes/              # PHP-вставки (политики, формы)
```

### `config/` (админка)

```
config/
├── index.php              # Точка входа, роутинг по tab
├── config.php             # Константы, сессии, DEBUG
├── login.php              # Форма логина
├── cron_backup.php        # Эндпоинт для планировщика
├── .htaccess              # RewriteEngine Off + Options -Indexes
│
├── core/
│   ├── functions.php      # ЧТЕНИЕ данных + утилиты
│   ├── admin_auth.php     # Авторизация, brute-force
│   ├── admin_controller.php  # ЗАПИСЬ + логика
│   ├── filemanager_api.php   # API проводника
│   ├── media_modal.php       # Медиа-модалка
│   └── icon_modal.php        # Модалка иконок
│
├── modules/               # Админские модули (вкладки)
│   ├── config_vars.php
│   ├── pages/
│   ├── menu/
│   ├── modules.php
│   ├── security.php
│   ├── backups.php
│   ├── filemanager.php
│   ├── logs.php
│   └── module_upload.php
│
├── css/config.css         # Стили админки
├── js/                    # JS админки
└── data/                  # Системные данные (защищено)
    ├── credentials.json   # Логин/хеш
    ├── .pepper            # Секрет для пароля
    ├── modules.json       # Список модулей
    ├── templates.json     # Список шаблонов
    └── backup_config.json # Настройки бэкапов
```

---

## 🔄 Поток запроса (фронтенд)

### 1. Запрос попадает в `index.php`

```php
require_once 'config/core/functions.php';
require_once 'config/config.php';

$requestUri = $_SERVER['REQUEST_URI'];
$slug = trim($requestUri, '/');
$page = loadPage($slug);
```

**`config.php`** **загружает**:
- **DEBUG_SITE**/**DEBUG_ADMIN**.
- **Сессии** (**`session_start`**).
- **CSRF-токен**.
- **Динамические** **константы** (`SITE_NAME`, `SITE_PHONE`, ...).

### 2. Валидация `$slug`

```php
if ($slug !== '') {
    if (!preg_match('~^[a-z0-9\-_/]+$~i', $slug) ||
        strpos($slug, '..') !== false ||
        strpos($slug, '//') !== false) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }
}
```

**Защита** **от** **Path Traversal** **через** `$slug`.

### 3. `loadPage($slug)`

```php
function loadPage(string $slug): ?array {
    // Валидация
    if ($slug !== '') {
        if (!preg_match('~^[a-z0-9\-_/]+$~i', $slug) ||
            strpos($slug, '..') !== false) {
            return null;
        }
    }
    
    if ($slug === '') {
        $homeId = getHomePageId();
        return loadPageById($homeId);
    }
    
    $filePath = DATA_DIR . 'pages/' . $slug . '.json';
    if (!file_exists($filePath)) return null;
    
    $page = _jsonToArray($filePath);
    if ($page['status'] !== 'published') return null;
    return $page;
}
```

### 4. Кеш

```php
$cacheFile = $cacheDir . ($slug ?: 'home') . '.html';
if ($cacheEnabled && file_exists($cacheFile)) {
    if ($cacheTTL === 0 || (time() - filemtime($cacheFile)) < $cacheTTL) {
        header('X-Cache: HIT');
        readfile($cacheFile);
        exit;
    }
}
ob_start();  // начало буферизации
```

### 5. Рендер

```php
$template = loadTemplate($page['template'] ?? 'full-width');
$usedModules = getUsedModules($page);
$assets = getModuleAssets($usedModules, $settings, $page['id']);
include APP_ROOT . '/templates/' . $template['id'] . '.php';
```

**`base.php`** **включает**:
- **Хедер** (`templates/headers/{variant}/header.php`).
- **Зоны** **шаблона** (`$template['zones']`).
- **Ряды**/**колонки**/**модули** (`include modules/{type}.php`).
- **Футер** (`templates/footers/{variant}/footer.php`).

### 6. Сохранение в кеш

```php
$html = ob_get_clean();
if ($cacheEnabled) {
    file_put_contents($cacheFile, $html);
}
echo $html;
```

---

## 🔄 Поток запроса (админка)

### 1. `config/index.php`

```php
require_once 'config.php';
require_once 'core/functions.php';
require_once 'core/admin_auth.php';
```

### 2. Авторизация

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    // Проверка brute-force → verifyAdminCredentials → handleSuccessfulLogin
}
```

### 3. CSRF-защита (для POST)

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        header('HTTP/1.1 403 Forbidden');
        exit('CSRF-токен невалиден');
    }
}
```

### 4. Idle Timeout

```php
checkAdminSessionTimeout($bfData);
```

### 5. Роутинг по `$_GET['tab']`

```php
if (isset($_GET['ajax']) && $_GET['ajax'] === 'module_settings') {
    handleGetModuleSettings();
    exit;
}
// ... другие AJAX

if (isset($_POST['save_settings'])) {
    $res = handleSaveSettings();
}
// ...
```

### 6. Включение модуля

```php
$adminModuleFile = __DIR__ . '/modules/' . $currentTab . '.php';
if (file_exists($adminModuleFile)) {
    include $adminModuleFile;
}
```

---

## 📊 Поток данных

### Чтение

```
data/settings.json ────┐
data/pages/*.json ─────┼──► functions.php ──► index.php ──► шаблон
data/menus/*.json ─────┤    (getData,          (роутинг,    (base.php)
config/data/modules.json│    loadPage,          рендер)
                       │    getMenuItems)
```

**Кеш** **в** **памяти** (`$GLOBALS['DATA_CACHE']`) **на** **время** **запроса**.

### Запись

```
POST-форма ──► config/index.php ──► admin_controller.php ──► safeFileWrite ──► data/*.json
                       │
                       └──► logAction ──► data/logs/admin.log
```

---

## 🧩 Ядро

### `functions.php` — чтение + утилиты

| Функция | Что делает |
|---------|-----------|
| `e()` | HTML-escape |
| `escapeJsString()` | JS-escape |
| `safeUrl()` | Whitelist схем URL |
| `validateHtml()` | Проверка HTML на опасные теги |
| `sanitizeHtml()` | Очистка HTML (whitelist) |
| `getData()` | Чтение JSON с кешем |
| `getSettingsData()` | Настройки сайта |
| `loadPage()`, `loadPageById()` | Загрузка страницы |
| `loadTemplate()` | Загрузка шаблона |
| `getPagesList()`, `getPagesListPaginated()` | Список страниц |
| `getMenuItems()`, `loadMenu()` | Меню |
| `renderMainMenu()`, `renderMobileMenu()` | Рендер меню |
| `render_module()` | Рендер модуля |
| `getModuleAssets()`, `getCombinedAssets()` | Ассеты |
| `safeFileWrite()` | Атомарная запись с flock |

### `admin_auth.php` — авторизация

| Функция | Что делает |
|---------|-----------|
| `getLoginAttemptsData()` | Чтение login_attempts.json |
| `checkIpBlockStatus()` | Проверка бана IP |
| `verifyAdminCredentials()` | Проверка логина/пароля |
| `handleSuccessfulLogin()` | Успешный вход |
| `handleFailedLogin()` | Неудачная попытка |
| `handleLiveHashGeneration()` | Генерация хеша |
| `checkAdminSessionTimeout()` | Idle timeout |

### `admin_controller.php` — запись + логика

| Функция | Что делает |
|---------|-----------|
| `saveData()` | Запись JSON |
| `savePageData()` | Запись страницы |
| `saveMenu()`, `saveModulesData()` | Запись меню/модулей |
| `detectZipSlip()` | Защита от ZIP Slip |
| `handleSaveSettings()` | Сохранение настроек |
| `handleSavePage()` | Сохранение страницы |
| `handleDeletePage()` | Удаление страницы |
| `handleClonePage()` | Клонирование |
| `handleSetHomePage()` | Назначение главной |
| `handleSaveMenu()`, `handleDeleteMenu()` | Меню |
| `handleModuleAction()` | Модули (upload/delete) |
| `unpackModule()` | Распаковка модуля |
| `handleSaveSecurity()` | Настройки безопасности |
| `handleSaveBackupSettings()` | Настройки бэкапов |
| `handleInitBackupProcess()` | Инициализация бэкапа |
| `handleProcessBackupStep()` | Пошаговая архивация |
| `handleRestoreBackup()` | Восстановление |
| `handleDownloadBackup()` | Скачивание |
| `handleCronBackupAction()` | Системный крон |
| `clearPageCache()` | Очистка кеша |
| `logAction()` | Логирование |

### `filemanager_api.php` — API проводника

| Функция | Что делает |
|---------|-----------|
| `fmSafePath()` | Whitelist символов в пути |
| `fmSafeAbsolute()` | `realpath` + проверка префикса |
| `fmIsBlacklisted()` | Запрещённые пути |
| `fmIsAllowedExtension()` | Whitelist расширений |
| `fmIsSensitiveFile()` | Чувствительные файлы |
| `action=list` | Список файлов |
| `action=delete` | Удаление |
| `action=rename` | Переименование |
| `action=get_code` | Чтение файла |
| `action=save_code` | Сохранение файла |
| `action=upload` | Загрузка |
| `action=chmod` | Права |
| `action=create_dir`, `action=create_file` | Создание |
| `action=translit` | Транслитерация |

---

## 🗂 Модули

### Типы

1. **Публичные** (`modules/*.php`) — выполняются на фронте.
2. **Админские** (`config/modules/{id}/module.php`) — форма настроек.

### Структура ZIP-модуля

```
module.zip
├── manifest.json       # Метаданные
├── preview.png         # Превью (опционально)
├── admin/
│   ├── module.php      # Форма настроек
│   ├── style.css       # Стили формы
│   └── script.js       # JS формы
└── site/
    ├── module.php      # Рендер на фронте
    ├── style.css       # Стили
    └── script.js       # JS
```

### После распаковки

```
config/modules/{id}/module.php    # Админский
modules/{id}.php                  # Публичный
css/{id}.css, css/{id}.min.css    # Стили
js/{id}.js, js/{id}.min.js        # JS
config/modules/previews/{id}.png  # Превью
```

---

## 📦 Кеш

### Страницы

`cache/pages/{slug}.html`:
- **Рендер** **страницы** (**HTML**).
- **При** **включённом** `cache_enabled`.
- **Очистка** **при** **сохранении**/**удалении**/**клонировании** **страницы**.

### Ассеты (объединённые)

`cache/assets/{pageId}.css` **и** `.js`:
- **Склейка** **всех** **CSS**/**JS** **модулей** **страницы**.
- **При** `assets_combine = true`.
- **Очистка** **при** **обновлении** **модуля**/**настроек**.

---

## 🔐 Безопасность

**См.** [SECURITY.md](SECURITY.md).

---

## 📞 Поддержка

- **Документация:** [docs/](../)
- **API:** [API.md](API.md)
- **Модули:** [MODULES.md](MODULES.md)