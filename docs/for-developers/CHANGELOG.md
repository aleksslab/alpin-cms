# Changelog

Все значимые изменения проекта.

## [Release version 1.1.0]

### Безопасность — Critical

- **CSRF на GET-эндпоинты** — все деструктивные действия переведены на POST с CSRF-токеном
- **RCE через модули** — валидация `manifest.id` (regex)
- **ZIP Slip** — функция `detectZipSlip()` в `unpackModule` и `handleRestoreBackup`
- **Path Traversal в `filemanager_api.php`** — функции `fmSafePath`, `fmSafeAbsolute`, `fmIsBlacklisted`, `fmIsAllowedExtension`, `fmIsSensitiveFile`
- **Утечка `.pepper` через `get_code`** — `get_code` переведён на POST + CSRF, whitelist расширений + blacklist путей/файлов
- **LFI/XSS через `module_type`** — валидация regex в `handleGetModuleSettings`, `escapeHtml` в `constructor.js`
- **Path Traversal через `$slug`** — defense in depth в `loadPage` + `loadPageById`
- **JSON-инъекция в `value='...'`** — `htmlspecialchars(..., ENT_QUOTES)` в `constructor.php`, `pages/edit.php`, `menu/edit.php`, `config_vars.php`

### Безопасность — High

- **Stored XSS через `settings.json`** — `e()` в шаблонах + `validateHtml`/`sanitizeHtml` в `handleSaveSettings`
- **XSS через `menu.json → url`** — `safeUrl()` в `renderMainMenu`/`renderMobileMenu`/футерах
- **Host Header Injection** — валидация `HTTP_HOST` в `config.php`, `handleSaveSecurity`, `config/index.php`
- **Открытый `handleLiveHashGeneration`** — работает только до первой настройки
- **XSS в `filemanager.js`** — `escapeHtml` в `renderFmTiles`/`renderFmBreadcrumbs`
- **XSS в `backup_manager.js`** — `textContent` в `showToast`
- **XSS в `menu.js`** — `escapeHtml` в `renderMenu`
- **XSS в `constructor.js`** — `escapeHtml` + `escapeJsString` в `renderRow`/`renderColumn`/`renderModule`
- **XSS в `media_modal.js`** — `data-url` + делегирование + `escapeHtml`
- **CSS-инъекции в модулях** — валидация `background_image`, `background_color`, `height`, `speed`, `logo_height`, `icon_color`, `cardImage`, `$phone`/`$email`/`$address` через `e()`
- **XSS в `modules.php`** — `escapeJsString($id)` в `onclick`, `e($mod['icon'])`, валидация `$moduleId` в POST-обработчике
- **Race condition в `saveData`/`logAction`** — `safeFileWrite()` с `LOCK_EX`
- **Сессии без `SameSite`/`use_strict_mode`** — добавлено в `config.php`
- **XSS через `cookies.php`/`privacy.php`** — `textContent` для `error.message` в `script.js`

### Безопасность — Medium

- **`display_errors`** — раздельные `DEBUG_SITE` / `DEBUG_ADMIN`
- **Security-заголовки** — `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Header unset X-Powered-By`
- **Timing attack** — `hash_equals` для логина + постоянное время выполнения
- **Open redirect в `constructor.js`** — валидация `pageId`
- **`$hasPreview` undefined** — определение в `modules.php`
- **`.htaccess` для `config/data/`** — автосоздание/обновление
- **`encodeURIComponent`** — в `constructor.js` fetch
- **Селект `per_page`** — сохранение в `$_SESSION`

### Безопасность — Low

- **`ServerSignature Off`** — в `.htaccess`
- **`X-Cache: HIT`** — только в DEBUG
- **`robots.txt`** — создан

### Изменено

- **Конструктор** — `escapeHtml`/`escapeJsString` для всех динамических данных
- **Медиа-модалка** — `data-url` вместо `onclick`
- **Список страниц** — пагинация (10/25/50/100/200)
- **`per_page`** — сохранение в сессии

### Технические улучшения

- **Совместимость PHP 7.4 → 8.4** — `php -l` чисто
- **`DEBUG_SITE`** / **`DEBUG_ADMIN`** — раздельные флаги
- **Порядок подключения** в `index.php` и `config/index.php` — исправлен

---

## [1.0.0] — 2025-01-15

### Добавлено

#### Ядро
- Роутинг фронтенда через `index.php` + `.htaccess`
- JSON-хранилище без БД
- Модульная архитектура
- Кеширование данных в памяти (`$GLOBALS['DATA_CACHE']`)

#### Админка
- Авторизация с brute-force защитой
- CSRF-токены
- Flash-сообщения (тосты)
- Тёмная тема
- Адаптивный интерфейс

#### Контент
- Страницы с ЧПУ
- Конструктор: ряды, колонки, модули
- Шаблоны: full-width, sidebar-left, sidebar-right, boxed
- Хедеры: default, centered, minimal, with-cta
- Футеры: default, minimal, dark, light
- Меню с dropdown и divider

#### Модули (23)
- hero, text, text-media, cards, icons-list
- stats, timeline, faq, contacts, lead-form
- quiz, calculator, pricing, testimonials, portfolio
- carousel, slider-advanced, before-after
- brands-marquee, countdown, map-advanced
- social-icons, site-menu

#### Настройки
- Контактная информация
- Цветовая схема (8 цветов)
- Хедер/футер (варианты)
- SEO (meta, OG, Twitter)
- Соцсети (13 сетей)
- Кастомный CSS/JS

#### Оптимизация
- Кеширование страниц (`cache/pages/*.html`)
- Минификация CSS/JS
- Объединение ассетов
- Умная очистка кеша

#### Безопасность
- CSRF-токены
- XSS-защита
- Path Traversal защита
- Brute-force защита
- Логирование
- Резервное копирование с AES-256

#### Система
- Файловый менеджер с редактором Ace
- Логи с фильтрами и ротацией
- Бэкапы (полные/выборочные, шифрование)
- Крон для авто-бэкапов

---

## Формат версий

**MAJOR.MINOR.PATCH**

- **MAJOR** — несовместимые изменения API.
- **MINOR** — новая функциональность (**совместимо**).
- **PATCH** — исправления (**совместимо**).

---

## Ссылки

- **Репозиторий:** [github.com/AlekSSLab/alpin-cms](https://github.com/AlekSSLab/alpin-cms)
- **Issues:** [github.com/AlekSSLab/alpin-cms/issues](https://github.com/AlekSSLab/alpin-cms/issues)