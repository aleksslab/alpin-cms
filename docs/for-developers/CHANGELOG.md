# Changelog

Все значимые изменения проекта.

## [Release version 1.4.0] — 2026-10-06

### Добавлено

- **Корзина для страниц и меню** — мягкое удаление:
  - Удалённые сущности перемещаются в `data/trash/` вместо физического удаления.
  - Восстановление в один клик (с авторазрешением конфликта ID: `{id}-restored`).
  - Окончательное удаление (по одному, несколько, или вся корзина сразу).
- **UI корзины** — кнопка «Корзина (N)» в шапке вкладок «Страницы» и «Меню» + модалка со списком.
- **Метаданные в корзине** — `_trash.type`, `_trash.original_id`, `_trash.deleted_at`, `_trash.deleted_by`, `_trash.size_bytes`.
- **Логин админа в сессии** (`$_SESSION['admin_login']`) — устанавливается при успешном входе, используется для `deleted_by` и `logAction()`.

### Изменено

- **`handleDeletePage`** — вместо `unlink` перемещает в корзину через `trashPage()`.
- **`handleDeleteMenu`** — аналогично через `trashMenu()`.
- **`deleteMenu()`** — удалена как мёртвая функция.
- **Логи действий** — `page_trash` / `menu_trash` вместо `page_delete` / `menu_delete`.

### Технические детали

- **`trashEntity()`** — общая логика перемещения сущности в корзину (используется `trashPage` / `trashMenu`).
- **`restoreFromTrash()`** — восстановление с разрешением конфликта ID.
- **`deleteFromTrash()`** / **`clearTrash()`** — окончательное удаление.
- **`getTrashList(?string $type)`** / **`getTrashCount(?string $type)`** — списки и счётчики (фильтр по типу).
- **`handleTrashAction()`** — обработчик POST-действий корзины (`restore`, `delete_selected`, `clear_all`).
- **`config/core/trash_modal.php`** — общая модалка для страниц и меню.
- **`config/js/trash_modal.js`** — логика корзины (open/close, select, submit).
- **`ensureTrashDir()`** — создание папки `data/trash/` + `.htaccess` защита.

### Удалено

- **`deleteMenu()`** — мёртвый код (не вызывается после перехода на корзину).


## [Release version 1.3.0] — 2026-10-05

### Добавлено

- **Планирование публикации** — 4 статуса страницы:
  - `draft` — черновик, невидим на фронте.
  - `scheduled` — запланирована, станет видимой в `publish_at`.
  - `published` — опубликована.
  - `archived` — снята с публикации (вручную или по `unpublish_at`).
- **Поля страницы** `publish_at` / `unpublish_at` (Unix timestamp в JSON).
- **Cron-публикация** — `config/cron/publish.php`:
  - `scheduled + publish_at <= now` → `published`.
  - `published + unpublish_at <= now` → `archived`.
- **Виртуальный крон публикации** — асинхронный вызов при заходе админа (не чаще 1 раза в 5 минут).
- **UI в форме редактирования страницы** — 4 radio + два `datetime-local`.
- **Бейджи статусов** в списке страниц (5 вариантов).
- **Секция «Интеграция с планировщиком»** в Настройках сайта (токен, URL кронов, перегенерация).

### Изменено

- **Токен крона** перенесён из `config/data/backup_config.json` в `data/settings.json`.
  Один токен используется и для бэкапов, и для публикации страниц.
- **`cron_backup.php`** → **`config/cron/backup.php`**. URL изменился.
- **`handleSavePage`** — валидация дат и 4 статусов.
- **`loadPage`** — проверка `unpublish_at` (подстраховка от задержки cron).
- **`handleSaveSettings`** — сохраняет `cron_token` и `last_cron_publish` (не теряет при сохранении).

### Технические детали

- **`getCronToken()`** / **`regenerateCronToken()`** — управление токеном.
- **`handleCronPublishAction()`** — логика крона публикации.
- **`clearPageCacheById()`** — сброс кеша одной страницы (без полного сброса).
- **`getPageStatusBadge()`** — единая логика бейджей статусов.
- **`getLastCronPublishTime()`** / **`setLastCronPublishTime()`** — трекинг запусков виртуального крона.
- **AJAX-эндпоинт** `generate_cron_token` — генерация токена без сохранения (запись только через форму).

### Исправлено

- **Потеря `cron_token`** при сохранении настроек сайта (полная пересборка `settings.json`).
- **Потеря `publish_at` / `unpublish_at`** при смене статуса на `draft` / `archived` (обнуление).
- **Fatal error** в `cron_backup.php` — `admin_controller.php` теперь самодостаточен (`require functions.php`).


## [Release version 1.2.0] — 2026-09-24

### Добавлено

- **Модуль `hero-landing`** — Hero для лендингов:
  - Две кнопки (`button_text`, `button_text_2`).
  - Микро-факты (`features[]`).
  - Визуал справа/слева (`image`, `image_position`).
  - Два layout'а: `centered` и `split`.
  - Три стиля картинки: `none`, `shadow`, `browser`.
- **`onAfterChange` callback** в `initListModule`.
- **`getNextIndex(container, prefix)`** — возвращает `max + 1`.

### Изменено

- **`modules/hero.php`** — добавлен флаг `hero-compact` 
  (компактная высота вместо 100vh).
- **`config/modules/pages/row.php`** — превью модуля выводится 
  через `strip_tags` + `e()` (защита от невалидного HTML).
- **Хедеры и футеры** — контакты в шапке/подвале выводятся 
  только если заполнены.
- **Все list-модули** переведены на `initListModule`.
- **`modules/map-advanced`** — активная метка через флаг `active`.
- **`modules/portfolio`** — множественные категории + фильтр 
  пустых категорий.

### Исправлено

- **Коллизии индексов** в list-модулях (потеря данных).
- **Смена шаблона** в конструкторе ломала форму (невалидный HTML).
- **Переключение хедера/футера** — та же причина.
- **Отступы и адаптив** в hero-секциях на узких экранах.

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

## [Release version 1.0.0]

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