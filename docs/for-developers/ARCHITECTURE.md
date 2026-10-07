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
├── sitemap.xml            # Генерируется автоматически
├── robots.txt             # Генерируется автоматически
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
├── preview.php            # Эндпоинт создания preview (POST)
├── login.php              # Форма логина
├── .htaccess              # RewriteEngine Off + Options -Indexes
│
├── cron/                  # Эндпоинты для планировщика (хостинг)
│   ├── backup.php         # Крон бэкапов
│   └── publish.php        # Крон публикации/архивации страниц
│   └── seo.php            # Крон генерации sitemap/robots
|
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
```

**`config.php`** загружает:
- **DEBUG_SITE**/**DEBUG_ADMIN**.
- **Сессии** (`session_start`).
- **CSRF-токен**.
- **Динамические** константы (`SITE_NAME`, `SITE_PHONE`, ...).
- **`getBaseUrl()`** — хелпер для протокола + хоста.

### 2. Инициализация

```php
$isNewPage = false;
$isPreview = false;
$usedModules = [];
$template = null;
$page = null;
```

Переменные-флаги, которые определяют дальнейшую логику.

### 3. Проверка preview (приоритет)

```php
$previewToken = trim($_GET['preview'] ?? '');

if ($previewToken !== '' && preg_match('/^[a-f0-9]{32}$/i', $previewToken)) {
    // 1. Временный preview (из конструктора)
    $previewData = loadPreviewById($previewToken);

    // 2. Fallback: постоянный preview-токен страницы
    if (!$previewData) {
        $previewData = loadPageByPreviewToken($previewToken);
    }

    if ($previewData) {
        // 301, если slug в URL не совпадает с актуальным
        $actualSlug = $previewData['slug'] ?? '';
        if ($slug !== $actualSlug) {
            $redirectUrl = ($actualSlug !== '' ? '/' . $actualSlug : '/')
                         . '?preview=' . urlencode($previewToken);
            header('Location: ' . $redirectUrl, true, 301);
            exit;
        }

        $page = $previewData;
        $isPreview = true;
        $isNewPage = true;
        $template = loadTemplate($page['template'] ?? 'full-width');
        $usedModules = getUsedModules($page);

        // Preview не индексируется
        header('X-Robots-Tag: noindex, nofollow, noarchive');
    }
}
```

**Что делает:**
- Ищет preview по токену: **сначала временный, потом постоянный**.
- Если нашли и slug в URL неактуален — **301** на правильный URL.
- Устанавливает `$isPreview = true` — это отключает кеш и включает жёлтую плашку.

### 4. Обычный роутинг

```php
if (!$isPreview) {
    $page = loadPage($slug);

    if ($page && $page['status'] === 'published') {
        $actualSlug = $page['slug'] ?? $page['id'] ?? '';

        // 301, если страница найдена по slug_history
        if ($slug !== '' && $slug !== $actualSlug) {
            header('Location: /' . $actualSlug, true, 301);
            exit;
        }

        $isNewPage = true;
        $template = loadTemplate($page['template'] ?? 'full-width');
        $usedModules = getUsedModules($page);
    }
}
```

**Как работает `loadPage`:**
1. **Прямой путь:** `data/pages/{slug}.json` — если файл есть, читаем.
2. **Fallback:** `findPageBySlugHistory($slug)` — ищем по `slug_history` в других страницах.

**Редирект 301 срабатывает, если:**
- Страница найдена **по истории** (а не по прямому файлу), **и**
- Запрошенный slug **непустой**, **и**
- Запрошенный slug **не совпадает** с актуальным.

### 5. Страница не найдена

```php
if (!$isNewPage) {
    if ($slug !== '') {
        header('Location: /', true, 301);
        exit;
    }

    // Заглушка для главной, если home.json отсутствует
    $page = [
        'id'       => 'home',
        'title'    => 'Главная',
        'template' => 'full-width',
        'status'   => 'published',
        'zones'    => ['main' => ['rows' => []]]
    ];
    $isNewPage = true;
    $template = loadTemplate('full-width');
}
```

**Логика:**
- **Несуществующий slug** (`/nonexistent`) → **301** на главную.
- **Главная (`/`), но `home.json` отсутствует** → отдаётся заглушка.

### 6. Настройки для хедера и футера

```php
$settings = getSettingsData();
$site_name = $settings['name'] ?? SITE_NAME;
$siteNamePlain = getPlainText($site_name);
// ... и т. д.
```

### 7. Кеш страниц

```php
$cacheEnabled = $settings['cache_enabled'] ?? false;
$cacheTTL = $settings['cache_ttl'] ?? 86400;
$cacheFile = $cacheDir . ($slug ?: 'home') . '.html';

// В preview-режиме кеш ОТКЛЮЧАЕМ
if ($cacheEnabled && !$isPreview && file_exists($cacheFile)) {
    $cacheAge = time() - filemtime($cacheFile);
    if ($cacheTTL === 0 || $cacheAge < $cacheTTL) {
        if (DEBUG_SITE) header('X-Cache: HIT');
        readfile($cacheFile);
        exit;
    }
}

ob_start();  // начало буферизации
```

**Ключевое:**
- **Preview не читает кеш** (`!$isPreview`).
- **Preview не пишет в кеш** — то же условие в конце.
- В preview `assets_combine` отключается — не создавать временные `cache/assets/{preview_id}.css`.

### 8. Мета-данные и OG

```php
$pageTitle = !empty(SITE_META_TITLE) ? SITE_META_TITLE : ($page['title'] ?? 'Название компании');
$ogUrl = !empty(OG_URL) ? OG_URL : $currentUrl;
```

**Данные страницы** имеют **приоритет** ниже глобальных настроек — если в настройках SEO указано, оно перекрывает.

### 9. Рендер

```php
$templateFile = APP_ROOT . '/templates/' . ($template['id'] ?? 'full-width') . '.php';
if (file_exists($templateFile)) {
    include $templateFile;
}
```

**`base.php`** включает:
- **Хедер** (`templates/headers/{variant}/header.php`).
- **Зоны шаблона** (`$template['zones']`).
- **Ряды/колонки/модули** (`include modules/{type}.php`).
- **Футер** (`templates/footers/{variant}/footer.php`).

### 10. Сохранение в кеш

```php
$html = ob_get_clean();

if ($cacheEnabled && !$isPreview) {
    file_put_contents($cacheFile, $html);
}

echo $html;
```

**Preview не сохраняется** в кеш — каждый раз генерируется заново.

### Схема (кратко)

```
HTTP-запрос
    │
    ├─→ ?preview=xxx?
    │       ├─ Да → loadPreviewById / loadPageByPreviewToken
    │       │       ├─ Найдено → 301 при несовпадении slug → рендер
    │       │       └─ Не найдено → обычный роутинг
    │       │
    │       └─ Нет → обычный роутинг
    │               │
    │               └─ loadPage(slug)
    │                       ├─ Прямой файл → рендер
    │                       ├─ slug_history → 301 → рендер
    │                       └─ Не найдено → 301 на главную
    │
    ├─→ Кеш (только если !$isPreview)
    │       ├─ HIT → readfile + exit
    │       └─ MISS → ob_start()
    │
    ├─→ Мета + OG + Twitter
    │
    ├─→ Рендер (base.php → хедер → модули → футер)
    │
    └─→ Сохранение в кеш (только если !$isPreview) → echo
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

### 5. Виртуальные кроны

При заходе админа в админку асинхронно дёргаются два виртуальных крона:

**Крон бэкапов** (если включён `cron_virtual` в `backup_config.json`):

```php
$cronSettings = getBackupSettingsData();
if (!empty($cronSettings['cron_virtual'])) {
    $lastCronBackup = intval($cronSettings['last_backup_time'] ?? 0);
    $cronPeriodHours = intval($cronSettings['cron_period'] ?? 24);

    if (time() >= ($lastCronBackup + ($cronPeriodHours * 3600))) {
        $cronUrl = $protocol . $safeHost . dirname($_SERVER['SCRIPT_NAME'])
                 . '/cron/backup.php?token=' . urlencode(getCronToken());

        $cronContext = stream_context_create([
            'http' => ['timeout' => 1.0, 'ignore_errors' => true]
        ]);
        @file_get_contents($cronUrl, false, $cronContext);
    }
}
```

**Крон публикации** (без отдельной настройки — работает всегда, но не чаще 1 раза в 5 минут):

```php
$lastPublish = getLastCronPublishTime();
if (time() >= ($lastPublish + 300)) {
    setLastCronPublishTime(time());

    $publishUrl = $protocol . $safeHost . dirname($_SERVER['SCRIPT_NAME'])
                . '/cron/publish.php?token=' . urlencode(getCronToken());

    $publishContext = stream_context_create([
        'http' => ['timeout' => 1.0, 'ignore_errors' => true]
    ]);
    @file_get_contents($publishUrl, false, $publishContext);
}
```

**Особенности:**
- Запросы асинхронные (`@file_get_contents` с таймаутом 1 секунда) — страница админки открывается мгновенно.
- Для бэкапа — интервал из настроек (по умолчанию 24 часа).
- Для публикации — фиксированные 5 минут (`getCronPublishMinInterval()`).
- Время публикации обновляется **до** запроса — защита от долбёжки при F5.

---

### 6. Роутинг по `$_GET['tab']`

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

### 7. Включение модуля

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
data/settings.json  ────┐
data/pages/*.json  ─────┼──► functions.php ──► index.php ──► шаблон
data/menus/*.json  ─────┤    (getData,          (роутинг,    (base.php)
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
| `loadPage()` | Загрузка опубликованной страницы (проверяет `status` + `unpublish_at`) |
| `loadPageById()` | Загрузка страницы по ID (без проверки статуса, для админки) |
| `loadTemplate()` | Загрузка шаблона |
| `getPagesList()`, `getPagesListPaginated()` | Список страниц |
| `getMenuItems()`, `loadMenu()` | Меню |
| `renderMainMenu()`, `renderMobileMenu()` | Рендер меню |
| `render_module()` | Рендер модуля |
| `getModuleAssets()`, `getCombinedAssets()` | Ассеты |
| `safeFileWrite()` | Атомарная запись с flock |
| `loadPreviewById()` | Чтение временного preview |
| `loadPageByPreviewToken()` | Поиск страницы по постоянному токену |
| `findPageBySlugHistory()` | Поиск страницы по slug_history |
| `applyPageDefaults()` | Дефолты для страницы |
| `getBaseUrl()` | Протокол + хост сайта |

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
| `handleCronBackupAction()` | Системный крон бэкапов |
| `handleCronPublishAction()` | Системный крон публикации/архивации страниц |
| `getCronToken()` | Общий токен для кронов (из `settings.json`) |
| `regenerateCronToken()` | Принудительная перегенерация токена |
| `getLastCronPublishTime()` | Время последнего запуска виртуального крона |
| `setLastCronPublishTime()` | Сохранение времени запуска |
| `getPageStatusBadge()` | Метка/класс/иконка бейджа статуса |
| `clearPageCache()` | Очистка всего кеша |
| `clearPageCacheById()` | Очистка кеша одной страницы |
| `logAction()` | Логирование |
| `createPreview()` | Создание/перезапись временного preview |
| `cleanupAllPreviews()` | Очистка всех previews |
| `cleanupSessionPreviews()` | Очистка previews сессии |
| `cleanupSessionPagePreviews()` | Очистка previews сессии для страницы |
| `getPagePreviewToken()` | Чтение постоянного токена |
| `handleRegeneratePagePreviewToken()` | Создание/перегенерация токена |
| `handleDeletePagePreviewToken()` | Удаление токена |
| `getPagePreviewUrl()` | Формирование URL постоянного preview |
| `generateSitemap()` | Генерация sitemap.xml |
| `generateRobots()` | Генерация robots.txt |
| `generateSeoFiles()` | Обе генерации + удаление при выключенных галочках |
| `maybeRegenerateSeoFiles()` | Обёртка с проверкой auto |

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