# Changelog

Все значимые изменения проекта.

## [Release version 1.9.0] — 2026-10-23

### Добавлено

- **Режим технических работ**:
  - Галочка «Включить режим технических работ» в настройках.
  - Все посетители видят заглушку с HTTP **503** (`Retry-After`, `X-Robots-Tag: noindex`).
  - Админ (при залогиненной сессии) — видит сайт как обычно.
  - **Bypass-ссылка** `?bypass={token}` — устанавливает cookie на 24 часа, открывает сайт в обход заглушки. Для проверки сайта перед публикацией.
  - **Автоснятие** — опционально, по дате (`maintenance_until`). На фронте при истечении срока режим выключается автоматически, без крона.
  - **Таймер обратного отсчёта** — если задано автоснятие и включена галочка «Показывать таймер». Формат `Д:Ч:М:С`, JS-обновление.
  - **HTML-сообщение** — через `sanitizeHtml` (можно `p`, `h1-h4`, `a`, `ul`, `img`). Опасные теги (`<script>`, `<iframe>`, `<form>`) — вырезаются.
  - **Страница заглушки** — `templates/maintenance.php`: логотип, название, сообщение, таймер, контакты. Inline-CSS, адаптив, без внешних зависимостей.
- **Секция «Технические работы»** в Настройках сайта (между «Кеширование» и «История изменений»):
  - Галочка вкл/выкл.
  - Textarea для сообщения.
  - `datetime-local` для автоснятия.
  - Галочка «Показывать таймер».
  - Bypass-токен + «Перегенерировать» + готовая ссылка + «Копировать».
- **Новые поля в `data/settings.json`**:
  - `maintenance_enabled` (bool, по умолчанию `false`).
  - `maintenance_message` (string, HTML разрешён через `sanitizeHtml`).
  - `maintenance_show_timer` (bool, по умолчанию `true`).
  - `maintenance_until` (int|null, Unix timestamp автоснятия).
  - `maintenance_bypass_token` (string, 32 hex, генерируется при включении).

### Исправлено

- **WebP-подмена для `background-image`** — в модулях `hero` и `cards` картинка задавалась через CSS-фон, а не `<img>`. Теперь используется `mediaImageSrc()`.
  - `modules/hero.php` — фон.
  - `modules/cards.php` — картинка карточки.

### Технические детали

- **Новые функции в `admin_controller.php`**: `isMaintenanceEnabled()`, `getMaintenanceBypassToken()`, `regenerateMaintenanceToken()`.
- **Новый файл:** `config/modules/config_vars/maintenance.php`.
- **Новый шаблон:** `templates/maintenance.php`.
- **Обновлено:** `config_vars.php` (`'maintenance'` в `$sections`), `config/index.php` (AJAX `generate_maintenance_token`), `config/js/config_vars.js` (`regenerateMaintenanceToken`, `copyMaintenanceBypassUrl`), `index.php` (проверка режима + bypass + автоснятие).
- **Автоснятие на фронте** — без крона. При первом запросе после `maintenance_until`:
  - `maintenance_enabled` → `false`,
  - `maintenance_until` → `null`,
  - запись через `safeFileWrite` (в `index.php` нет `saveData` из `admin_controller.php`),
  - сброс кеша `clearDataCache('settings')`.
- **Bypass-cookie**: `maintenance_bypass=1`, 24 часа, `httponly`, `SameSite=Lax`, `secure` если HTTPS.

## [Release version 1.8.0] — 2026-10-22

### Добавлено

- **Медиа: Lazy loading изображений**:
  - Хелпер `mediaLazyAttrs()` в `functions.php` — возвращает `loading="lazy" decoding="async" ` при `media_lazy_enabled`.
  - Применён в модулях и шаблонах: `cards`, `text-media`, `portfolio`, `testimonials`, `before-after`, `brands-marquee`, футеры.
  - **Не применяется** к hero, carousel, slider-advanced и логотипу в хедере (LCP / выше линии сгиба).
  - По умолчанию **включено** (не создаёт файлов, безопасно).
- **Медиа: WebP-конвертация при загрузке**:
  - Хелперы `isWebpSupported()`, `convertToWebp()`, `maybeConvertToWebp()` в `admin_controller.php`.
  - При загрузке через **медиа-модалку** и **проводник** создаётся `.webp` рядом с `.jpg`/`.png`. Оригинал сохраняется.
  - Работает **рекурсивно**: подпапки `images/products/`, `images/blog/` и т. д. — тоже обрабатываются.
  - Поддержка **GD** (приоритет) и **Imagick** (fallback). Прозрачность PNG сохраняется.
  - Хелпер `mediaImageSrc()` в `functions.php` — подменяет `src` на `.webp`, если файл существует.
  - Кеш `mediaImageSrc` — в памяти на время запроса.
  - `gif`, `svg`, `ico`, `avif` — **не конвертируются** (анимация / вектор / уже оптимизированы).
  - По умолчанию **выключено** (создаёт файлы — согласуем явно).
- **Секция настроек «Медиа»** в Настройках сайта:
  - Lazy loading — вкл/выкл.
  - WebP-конвертация — вкл/выкл + качество (1–100, по умолчанию 82).
  - Индикатор движка (GD / Imagick) или предупреждение «не поддерживается».
  - Статистика `.webp` (файлов, объём).
  - Кнопка **«Удалить все .webp»** — с проверкой «есть оригинал рядом» (нативные `.webp` без пары не трогаются).
  - Кнопка **«Конвертировать N существующих»** — AJAX-шагами с прогресс-баром, для старых картинок без `.webp`.
- **Защита от рассинхрона** «локалка → прод»: если в `settings.json` `media_webp_enabled: true`, а на сервере GD/Imagick нет — конвертация **тихо не срабатывает**, в настройках показывается **двойное предупреждение** (галочка disabled + «была включена, отключится при сохранении»).

### Изменено

- **Медиа-модалка** — исправлено **drag & drop** и **клик по плиткам**: раньше слушатели навешивались через `DOMContentLoaded`, но модалка подключается **после** его срабатывания. Теперь — делегирование на `document`, работает **всегда**.
- **`handleSaveSettings`** — сохраняет `media_lazy_enabled`, `media_webp_enabled`, `media_quality`. Защита от POST-инъекции `media_webp_enabled` при отсутствии поддержки.

### Исправлено

- **Медиа-модалка** — не работали выбор изображения и drag & drop (см. «Изменено»).
- **`formatPhone`** — пустое значение возвращает `''` (было `'+'`), короткий номер (`661199`) — без `+`.

### Технические детали

- **Новые функции в `functions.php`**: `mediaLazyAttrs()`, `mediaImageSrc()`.
- **Новые функции в `admin_controller.php`**: `isWebpSupported()`, `convertToWebp()`, `maybeConvertToWebp()`, `getWebpStats()`, `deleteAllWebp()`, `handleDeleteAllWebp()`, `countImagesWithoutWebp()`, `convertExistingImagesStep()`, `handleConvertExistingImagesStep()`.
- **Новые поля в `data/settings.json`**: `media_lazy_enabled` (bool), `media_webp_enabled` (bool), `media_quality` (int).
- **Новый файл:** `config/modules/config_vars/media.php`.
- **Обновлено:** `config/modules/config_vars.php` (`'media'` в `$sections`), `config/js/config_vars.js` (`deleteAllWebp`, `convertExistingImages`), `config/js/media_modal.js` (делегирование), `config/index.php` (приём POST).

## [Release version 1.7.0] — 2026-10-08

### Добавлено

- **История изменений страниц** — снапшоты при сохранении и откат на любую версию:
  - Хранилище `data/history/{history_key}/{timestamp}.json`.
  - Ключ `history_key` в JSON страницы — генерируется при создании, не меняется при переименовании slug, сбрасывается при клонировании.
  - Снапшот создаётся **перед** перезаписью страницы.
  - **Дедупликация:** снапшот не пишется, если пользователь ничего не менял или такое же состояние уже есть в последнем снапшоте.
  - **Откат восстанавливает только контент:** `title`, `template`, `meta`, `show_header`, `show_footer`, `rows`.
  - **Не восстанавливаются:** `status`, `publish_at`, `unpublish_at`, `slug`, `slug_history`, `preview_token`, `id`, `history_key`.
  - **Обратимость:** перед откатом сохраняется снапшот текущего состояния — можно откатить откат.
  - **Лимит версий** на страницу — настраивается, по умолчанию 20 (диапазон 1–100).
  - **Очистка истории** — кнопки «Удалить версию» и «Очистить всю историю» в модалке.
  - **Чистка при удалении из корзины** — при окончательном удалении страницы её история удаляется.
- **Секция настроек «История изменений»** в Настройках сайта:
  - Галочка «Хранить историю изменений страниц».
  - Лимит версий.
  - Статистика: страниц / версий / объём.
  - Кнопка «Очистить всю историю».
- **Модалка истории** в редакторе страницы (`config/core/history_modal.php` + `config/js/history_modal.js`).
- **Кнопка «Открыть историю»** в `edit.php` (только при включённой галочке в настройках), с бейджем количества версий.
- **AJAX-эндпоинт** `generate_preview_token` — генерация preview-токена без сохранения (запись только при сохранении страницы).

### Изменено

- **Preview-токен страницы** — генерируется AJAX-кнопкой, но **записывается в JSON только при сохранении страницы**. Кнопка «Удалить» очищает поле в форме; реальное удаление — при сохранении.
- **`handleSavePage`** — сохраняет `preview_token` из POST (если валидный 32-hex), иначе при перезаписи файла токен удаляется.
- **`handleSaveSettings`** — сохраняет `history_enabled` и `history_limit`.
- **`formatPhone`** — пустая строка → пустая (без `+`); короткий номер → без `+`.
- **`handleSaveSettings`** — защита от «голых плюсов» в поле телефона.

### Удалено

- **AJAX-обработчики** `regenerate_page_preview_token` и `delete_page_preview_token` — заменены на единый `generate_preview_token` (без сохранения) + запись при сохранении.
- **Функции** `_regeneratePagePreviewTokenLogic`, `handleRegeneratePagePreviewToken`, `handleDeletePagePreviewToken`, `clearPagePreviewToken`.
- **Поле** `page_id_for_preview` в `edit.php` — больше не нужно.

### Технические детали

- **Новые функции в `admin_controller.php`**:
  - `isHistoryEnabled()`, `getHistoryLimit()`, `getHistoryDir()`.
  - `ensureHistoryDir()`, `getOrCreateHistoryKey()`, `savePageSnapshot()`, `rotatePageHistory()`.
  - `getPageHistory()`, `getHistorySnapshot()`, `getHistoryContentFields()`, `extractHistoryContent()`, `normalizeHistoryContent()`, `normalizeHistorySort()`.
  - `deleteHistoryVersion()`, `clearPageHistory()`, `getHistoryStats()`.
  - `restorePageFromHistory()`, `handleRestorePageVersion()`, `handleDeleteHistoryVersion()`, `handleClearPageHistory()`, `handleClearAllHistory()`.
- **Новые файлы:** `config/core/history_modal.php`, `config/js/history_modal.js`, `config/modules/config_vars/history.php`.
- **Сигнатура `savePageSnapshot($oldPage, $newPage, $savedBy)`** — второй аргумент нужен для дедупликации «старое ≡ новое».
- **Поле `history_key`** в `data/pages/{id}.json` (16 hex, генерируется при создании).
- **Поля `history_enabled` / `history_limit`** в `data/settings.json`.
- **Папка `data/history/`** — с автосозданием `.htaccess`.
- **Секция «История изменений»** в `config_vars.php` — между «Кеширование» и «Интеграция с планировщиком».

## [Release version 1.6.0] — 2026-10-07

### Добавлено

- **301-редиректы при смене slug**:
  - Поле `slug_history` в JSON страницы (пишется при переименовании).
  - Функция `findPageBySlugHistory()` — поиск по старым slug'ам.
  - Редирект срабатывает в `index.php` (обычный роутинг + preview).
  - Работает автоматически, без настройки.
- **Автогенерация `sitemap.xml` и `robots.txt`**:
  - Функции `generateSitemap()`, `generateRobots()`, `generateSeoFiles()`.
  - UI в Настройках сайта → SEO → «Технический SEO».
  - 3 галочки: sitemap, robots, автообновление.
  - Кнопка «Пересобрать сейчас».
  - Ссылки на актуальные файлы + дата последней генерации.
  - **Крон-эндпоинт `config/cron/seo.php`** — общий токен, рекомендуемая частота раз в сутки.
- **Хелпер `getBaseUrl()`** в `functions.php` — единый протокол + хост.

### Изменено

- **`loadPage()`** — fallback по `slug_history` + вынесение дефолтов в `applyPageDefaults()`.
- **`handleSavePage()`** — запись старого slug в `slug_history` при переименовании.
- **`handleSaveSettings()`** — сохранение `seo_*` и `last_seo_regenerate`.
- **`restoreFromTrash()`** / **`handleClonePage()`** — клон не копирует `slug_history` и `preview_token`.
- **5 функций** (`handleSavePage`, `handleDeletePage`, `restoreFromTrash`, `handleClonePage`, `handleSetHomePage`) — вызывают `maybeRegenerateSeoFiles()`.

### Исправлено

- **Главная страница** — исправлена ошибка. Читается из `data/settings.json`, а не `data/globals.json`.
- **Смена главной** — сброс кеша обеих страниц (старой и новой).

### Технические детали

- **Новые функции в `admin_controller.php`**:
  - `generateSitemap()`, `generateRobots()`, `generateSeoFiles()`, `maybeRegenerateSeoFiles()`.
- **Новые функции в `functions.php`**:
  - `findPageBySlugHistory()`, `applyPageDefaults()`, `getBaseUrl()`.
- **Новый файл:** `config/cron/seo.php`.
- **UI:** блок «Технический SEO» в `config_vars/seo.php`.
- **JS:** `regenerateSeoFiles()`, `copyToClipboard()` в `config_vars.js`.


## [Release version 1.5.0] — 2026-10-06

### Добавлено

- **Временный предпросмотр из конструктора** — кнопка «Предпросмотр» показывает страницу с текущим (несохранённым) состоянием:
  - Работает для сохранённых страниц и для новых (в процессе создания).
  - Файлы — в `data/preview/{preview_id}.json`.
  - Не затрагивает основную БД, не индексируется, не кешируется.
- **Постоянная preview-ссылка для согласования** — с заказчиком:
  - Хранится в `data/pages/{id}.json` как поле `preview_token`.
  - Создаётся/удаляется по кнопке в `edit.php`.
  - Открывает страницу в любом статусе (черновик, запланирована, снята).
- **Обработка `?preview=xxx` в `index.php`** — приоритет временного preview, fallback на постоянный.
- **Жёлтая плашка превью** — фиксированный баннер сверху.
- **X-Robots-Tag: noindex** — preview не индексируется.
- **Отключение кеша в preview** — не отдаём из кеша, не пишем в кеш.
- **Отключение assets_combine в preview** — чтобы не создавать временные `cache/assets/{preview_id}.css`.
- **Очистка previews** — 3 триггера:
  1. Успешный логин → удалить всё.
  2. Заход на `edit.php?id=X` → удалить previews сессии для `X`.
  3. Сохранение страницы `X` → удалить previews сессии для `X`.

### Технические детали

- **`config/preview.php`** — эндпоинт POST для создания временного preview.
- **Функции в `admin_controller.php`**:
  - `cleanupAllPreviews()`, `cleanupSessionPreviews()`, `cleanupSessionPagePreviews()`.
  - `createPreview()`, `ensurePreviewDir()`.
  - `getPagePreviewToken()`, `handleRegeneratePagePreviewToken()`, `handleDeletePagePreviewToken()`.
  - `getPreviewUrl()`, `getPagePreviewUrl()`.
- **Функции в `functions.php`**:
  - `loadPreviewById()` — чтение временного preview (нужна фронту).
  - `loadPageByPreviewToken()` — поиск страницы по постоянному токену.
- **JS**: `window.previewPage()` в `constructor.js` — async POST + открытие новой вкладки.
- **JS**: `copyPagePreviewUrl()`, `regeneratePagePreviewToken()`, `deletePagePreviewToken()` в `pages.js`.


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