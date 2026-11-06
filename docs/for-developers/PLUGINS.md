# Разработка плагинов

Плагин в AlPin CMS — это **папка** в `config/plugins/{id}/` с манифестом `plugin.json` и файлом логики `plugin.php`. Плагины расширяют системные функции ядра (SEO, кеш, обновления, техработы и т.д.).

---

## 📂 Структура плагина

```
config/plugins/my-plugin/
├── plugin.json         # Манифест (обязательно, в корне)
├── plugin.php          # Логика (хуки, фильтры, сохранение)
├── admin-page.php      # Отдельная страница в админке (если указан admin_tab)
├── admin-before.php    # Фрагмент кода ДО (для внедрения в существующую секцию настроек)
├── admin-after.php     # Фрагмент кода ПОСЛЕ (для внедрения в существующую секцию настроек)
├── admin/              # Ассеты админки (опционально)
│   ├── style.css       # Скопируется в config/css/{id}.css
│   └── script.js       # Скопируется в config/js/{id}.js
└── site/               # Публичные ассеты (опционально)
    ├── style.css       # Скопируется в css/{id}.css
    └── script.js       # Скопируется в js/{id}.js
```

**Как это работает:**
- Ядро (логика) лежит в `plugin.php` и файлах вроде `admin-page.php`.
- Если плагин добавляет стили или скрипты для **сайта** (фронтенда), они кладутся в папку `site/`.
- При установке плагина (из ZIP) ядро **копирует** `site/style.css` в `css/{id}.css`, а `site/script.js` в `js/{id}.js`. В самом плагине вы подключаете их по этим путям (например, `css/dark-theme.css`).
- Если плагину нужны стили или скрипты в **админке**, они кладутся в папку `admin/`. При установке `admin/style.css` копируется в `config/css/{id}.css`, а `admin/script.js` — в `config/js/{id}.js`. Подключать их нужно самостоятельно в `admin-page.php` (или другом фрагменте) по путям `css/{id}.css` и `js/{id}.js` — относительно `config/index.php`.
- При удалении плагина скопированные файлы сайта и админки (включая `.min`-варианты) автоматически удаляются.

**Куда устанавливается:** плагины лежат в `config/plugins/` (защищено `.htaccess`). Не путать с **модулями** (`modules/*.php`) — модули выводят контент на сайте, плагины меняют логику ядра.

**Файлы `admin-before.php` и `admin-after.php`** нужны только если плагин использует `register_plugin_settings` для внедрения в **существующую** секцию настроек (например, в «Цветовую схему»). Они содержат HTML-фрагменты, которые вызываются в соответствующих местах (до и после основного контента секции).

---

## 📦 Структура ZIP

Для установки через админку плагин должен быть упакован в ZIP-архив. **`plugin.json` обязан лежать в корне архива.**

```
my-plugin.zip
├── plugin.json         # Манифест (обязательно)
├── plugin.php          # Логика (хуки)
├── admin-page.php      # Отдельная страница в админке (опционально)
├── admin-before.php    # Фрагмент для внедрения в секцию (опционально)
├── admin-after.php     # Фрагмент для внедрения в секцию (опционально)
├── admin/              # Ассеты админки (опционально)
│   ├── style.css       # Скопируется в config/css/{id}.css
│   └── script.js       # Скопируется в config/js/{id}.js
└── site/               # Публичные ассеты (опционально)
    ├── style.css       # Скопируется в css/{id}.css
    └── script.js       # Скопируется в js/{id}.js
```

**Важно:** не упаковывайте файлы в папку (например, `my-plugin/plugin.json`). Манифест должен быть в корне ZIP-архива. Если вы запаковали папку, установка выдаст ошибку «В корне архива отсутствует plugin.json».

---

## 📄 `plugin.json`

Манифест описывает плагин и его интеграцию в админку.

```json
{
    "id": "updates",
    "name": "Обновления",
    "description": "Проверка новых версий ядра, модулей и плагинов",
    "version": "1.0.0",
    "author": "AlekSSLab",
    "admin_tab": {
        "title": "Обновления",
        "icon": "icon-refresh-cw",
        "group": "system"
    }
}
```

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | string | **Обязательно.** Уникальный ID (латиница, цифры, дефис). Совпадает с именем папки плагина. |
| `name` | string | Название плагина |
| `description` | string | Описание |
| `version` | string | Версия (SemVer) |
| `author` | string | Автор |
| `admin_tab` | object | (Опционально) Вкладка в админке |

### `admin_tab`

| Поле | Тип | Описание |
|------|-----|----------|
| `title` | string | Заголовок в меню |
| `icon` | string | Класс иконки Lucide |
| `group` | string | Группа (`modules` или `system`) |

**Важно:** если указан `admin_tab`, ядро автоматически создаст вкладку в сайдбаре. **ID вкладки берется из корневого поля `id`.** Ядро будет искать файл `config/plugins/{id}/admin-page.php` для её отображения.

---

## ⚙️ `plugin.php` — логика плагина

Подключение к хукам ядра. Файл подключается автоматически при старте (если плагин включен).

### Хуки (Actions)

События, которые происходят в ядре. Ты можешь на них подписаться.

```php
add_action('index_start', function() {
    // Выполнится в начале index.php (до роутинга)
});
```

**Основные хуки (фронт и админка):**

| Хук | Где срабатывает | Аргументы |
|-----|-----------------|-----------|
| `index_start` | В начале `index.php` | — |
| `admin_init` | В админке, после авторизации | — |
| `admin_header_notifications` | В хедере админки | — |
| `admin_settings_sections` | В Настройках сайта | — |
| `render_head_start` | В `<head>` (до SEO) | — |
| `render_head_end` | В `<head>` (перед `</head>`) | — |
| `render_body_end` | Перед `</body>` | — |
| `after_file_upload` | После успешной загрузки файла через проводник или медиа-модалку | `$filePath` |
| `admin_login_success` | После успешного входа в админку | `$userIp` |
| `site_preview_banner` | На фронте, перед контентом | `$isPreview` |

### 📄 Хуки изменения контента

Пары «до/после» для страниц и настроек. `*_before_*` срабатывают до записи файла, события без `before` — после успешной записи.

| Хук | Когда срабатывает | Аргументы |
|-----|-------------------|-----------|
| `page_data_before_save` (фильтр) | Перед записью страницы — можно изменить данные | `$pageData`, `$id`, `$oldPage` (null при создании) |
| `page_before_save` | Перед записью страницы | `$id`, `$pageData`, `$oldPage` |
| `page_saved` | После успешного сохранения страницы | `$id`, `$pageData`, `$oldPage` |
| `home_before_change` | Перед сменой главной страницы | `$oldHomeId`, `$newHomeId` |
| `home_changed` | После смены главной | `$newHomeId` |
| `page_before_clone` | Перед клонированием страницы | `$sourceId` |
| `page_cloned` | После клонирования | `$sourceId`, `$newId` |
| `page_before_delete` | Перед удалением страницы (в корзину или окончательно) | `$pageId` |
| `page_deleted` | После удаления страницы (в корзину или окончательно) | `$pageId` |
| `page_before_restore` | Перед восстановлением из корзины или истории | `$pageId` |
| `page_restored` | После восстановления | `$pageId` |
| `trash_item_purged` | После окончательного удаления элемента корзины или прямого удаления без корзины | `$trashData` (данные страницы/меню) |
| `trash_enabled` (фильтр) | Включает корзину. По умолчанию `false` | `$enabled` |
| `trash_entity_result` (фильтр) | Перемещение страницы/меню в корзину | `$result`, `$type`, `$id` |
| `trash_button` | Кнопка «Корзина» в шапке списка | `$type`, `$returnUrl` |
| `trash_modal` | Модалка корзины | `$type`, `$returnUrl` |
| `settings_before_save` | Перед записью настроек | `$newSettings` |
| `settings_saved` | После записи настроек | `$oldSettings`, `$newSettings` |
| `backup_restored` | После успешного восстановления из резервной копии | `$archiveName`, `$itemsToRestore` (корневые элементы архива) |

**Если плагин сам пишет страницу** (крон, миграция, импорт) через `savePageData()`, он обязан повторить контракт ядра, иначе SEO, история и кеш не узнают об изменении:

```php
$page = apply_filters('page_data_before_save', $page, $page['id'], $oldPage);
do_action('page_before_save', $page['id'], $page, $oldPage);

if (savePageData($page['id'], $page)) {
    clearPageCacheById($page['id']);
    do_action('page_saved', $page['id'], $page, $oldPage);
}
```

Правило действует и для изменения только статуса: `page_saved` нужен подписчикам, а снапшот истории не создастся — статус не входит в контентные поля.

**Хуки админки для формы страницы:**

| Хук | Когда срабатывает | Аргументы |
|-----|-------------------|-----------|
| `edit_page_history_block` | В блоке настроек формы редактирования | `$editPage` |
| `edit_page_preview_block` | В блоке настроек формы редактирования (preview-ссылка) | `$editPage`, `$pageId` |
| `edit_page_scripts` | Перед скриптами редактора (create и edit) | `$pageId`, `$isEdit` |
| `edit_page_modals` | После формы, до скриптов (для модалок) | `$pageId` |

**Пример: плагин SEO-инструментов (`seo-tools`)**

```php
add_action('page_saved', function() { seo_maybe_regenerate(); });
add_action('page_deleted', function() { seo_maybe_regenerate(); });
add_action('page_restored', function() { seo_maybe_regenerate(); });
add_action('page_cloned', function() { seo_maybe_regenerate(); });
add_action('home_changed', function() { seo_maybe_regenerate(); });

// Восстановление бэкапа: sitemap зависит только от страниц,
// поэтому реагируем лишь на замену каталога data.
add_action('backup_restored', function($archiveName, $items) {
    if (!is_array($items) || !in_array('data', $items, true)) {
        return;
    }

    clearDataCache('settings'); // settings.json заменён на диске
    seo_maybe_regenerate();
}, 10, 2);
```

**Пример: плагин истории (`history`) — свой ключ и снапшот**

```php
add_filter('page_data_before_save', function($pageData, $id, $oldPage) {
    $pageData['history_key'] = bin2hex(random_bytes(8));
    return $pageData;
}, 10, 3);

add_action('page_before_save', function($id, $newPage, $oldPage) {
    history_save_snapshot($oldPage, $newPage, 'admin');
}, 10, 3);
```

### 🧩 Хуки жизненного цикла плагинов (только для админки)

Эти хуки позволяют плагину реагировать на собственное отключение, удаление или обновление. Они срабатывают **только если плагин активен** (кроме `before_delete`, который срабатывает до удаления файлов).

| Хук | Когда срабатывает | Аргументы |
|-----|-------------------|-----------|
| `plugin_before_toggle` | Перед изменением статуса (вкл/выкл) | `$id`, `$active` (bool) |
| `plugin_after_toggle` | После изменения статуса | `$id`, `$active` (bool) |
| `plugin_before_delete` | **До** удаления папки плагина | `$id` |
| `plugin_after_delete` | После удаления (код плагина уже недоступен) | `$id` |
| `plugin_before_update` | Перед распаковкой новой версии | `$id`, `$newVersion` |
| `plugin_after_update` | После успешного обновления | `$id`, `$newVersion` |
| `plugin_after_install` | После установки нового плагина | `$id` |

**Пример: сброс настроек при отключении плагина**

```php
add_action('plugin_after_toggle', function($id, $active) {
    if ($id === 'my-plugin' && !$active) {
        $settings = getSettingsData();
        $settings['my_plugin_enabled'] = false;
        saveData('settings', $settings);
    }
});
```

### Фильтры (Filters)

Модификация данных.

```php
add_filter('page_title', function($title, $page) {
    return $title . ' | Мой плагин';
}, 10);
```

**Основные фильтры:**

| Фильтр | Что модифицирует | Аргументы |
|--------|------------------|-----------|
| `page_title` | Заголовок страницы | `$title`, `$page` |
| `save_settings_data` | Массив настроек перед сохранением | `$newSettings` |
| `page_scheduling_enabled` | Включает планирование публикации: статус «По расписанию» и поля дат в редакторе. По умолчанию `false` | `$enabled` |
| `trash_enabled` | Включает корзину для страниц и меню. По умолчанию `false` | `$enabled` |
| `preview_enabled` | Включает предпросмотр: `?preview=`, блок в редакторе и кнопку конструктора. По умолчанию `false` | `$enabled` |

### Корзина

Корзина — отдельный плагин `trash`. Ядро само не перемещает файлы: оно
спрашивает фильтр `trash_entity_result` и вызывает `trash_button` /
`trash_modal` для UI.

```php
add_filter('trash_enabled', function () {
    return true;
});

add_filter('trash_entity_result', function ($result, string $type, string $id) {
    if ($result !== null) {
        return $result;
    }

    return $type === 'page' ? trash_page($id) : trash_menu($id);
}, 10, 3);
```

Если фильтр `trash_entity_result` вернул `null`, ядро удаляет файл
окончательно и пишет `page_delete` / `menu_delete`. Плагин корзины должен
подписываться на `trash_enabled`, иначе его кнопка и модалка не появятся.

### Планирование публикации

Если ваш плагин умеет переводить страницы `scheduled → published` (cron, внешний воркер, очередь), включите возможность в `plugin.php`:

```php
add_filter('page_scheduling_enabled', function () {
    return true;
});
```

Ядро по умолчанию считает планирование выключенным, поэтому UI расписания не появляется без обработчика фильтра. Проверка доступна через `isPageSchedulingEnabled()`.

### Предпросмотр

Предпросмотр — отдельный плагин `preview`. Ядро оставляет режим рендера
(`$isPreview`), публичные `loadPreviewById()` / `loadPageByPreviewToken()` и
проверку `isPreviewEnabled()`. Плагин создаёт временные preview-файлы, хранит
постоянные токены и рисует UI.

```php
add_filter('preview_enabled', function () {
    return true;
});
```

Пока фильтра нет, `?preview=` игнорируется, блок в редакторе и кнопка
конструктора скрыты, AJAX-эндпоинты плагина не отвечают. Данные
(`data/preview/`, поле `preview_token`) остаются на месте.

### Кастомные маршруты

```php
add_route('/my-page', function() {
    echo 'Hello from plugin!';
    exit;
});
```

---

## ⏰ Фоновые задачи (крон)

Плагин может зарегистрировать фоновую задачу в общем реестре ядра. Ядро хранит
только ID, интервал и время последнего запуска — оно не знает, что делает задача.

```php
register_cron_task('my-task', 'my_task_callback', [
    'interval' => 6 * 3600,  // минимальный интервал в секундах
    'virtual'  => true,      // запускать при входе админа (виртуальный крон)
    'enabled'  => true,      // показывать URL задачи в Настройках сайта
    'log'      => true,      // писать cron_run при успехе (false — только ошибки)
    'label'    => 'Моя задача',
]);

function my_task_callback(): bool {
    // ... работа ...
    return true; // false — только при реальной ошибке
}
```

Время запуска ядро фиксирует само при захвате задачи. **Не вызывайте
`set_cron_last_run()` из задачи крона** — иначе расписание и состояние захвата
разъедутся. Функция остаётся для ручных операций, которые должны сдвинуть
расписание (например, ручной бэкап в плагине `backups`).

Ручной запуск — общий эндпоинт `config/cron/run.php?task=my-task&token=...`
(токен — `getCronToken()`). Виртуальные задачи ядро вызывает само при заходе
админа в админку. Коллбэк должен вернуть `true` при успехе и `false` при ошибке.
URL задачи с `enabled => true` ядро автоматически показывает в разделе
«Интеграция с планировщиком» Настроек сайта.

**Один запуск за интервал.** Виртуальный и внешний планировщик делят состояние
`config/data/cron_state.json`, поэтому задача выполняется ровно один раз за
`interval`, даже если включены оба:

- виртуальный крон атомарно ставит заявку (`pending` + одноразовый
  `pending_token`) и передаёт её в `run.php?task=...&run=...`;
- эндпоинт захватывает запуск: по токену — пока заявка жива (15 минут), без
  токена — только когда подошёл интервал и нет живой заявки;
- повторный или чужой вызов получает `200 Status: Skipped` — это не ошибка;
- при `false` от задачи `last_run` откатывается и ставится пауза 5 минут.

Логи: ядро пишет действие `cron_run` (успех с длительностью, ошибка). Плагин
может писать свои действия, как `backups` (`backup_create` с именем архива).
Для частой задачи, которая сама логирует результат, отключите техническую
запись опцией `'log' => false` — тогда `cron_run` появится только при ошибке.
Так сделано у `publish`: `page_publish` / `page_archive` пишутся лишь при
реальной публикации или снятии, а не на каждом минутном запуске.

**ВАЖНО:** регистрируйте задачу на верхнем уровне `plugin.php` (не внутри
`admin_init`), иначе она не будет видна эндпоинту `run.php`.

---

## 📝 Действия логирования

Плагин может добавить свои действия в реестр логирования — тогда они появятся
в фильтре и настройках вкладки «Логи системы». Регистрируйте на верхнем уровне
`plugin.php`.

```php
register_log_action('backup_create', 'Создание бэкапа', 'Бэкапы', 'database-backup');
```

Аргументы: `$action` — ключ (совпадает с первым аргументом `logAction()`),
`$label` — название, `$group` — группа в UI, `$icon` — класс иконки без префикса `icon-`.

**ВАЖНО:** без регистрации действие не логируется (`logAction()` сверяется с реестром).

---

## 🔒 Чувствительные файлы (проводник)

Плагин может запретить просмотр и редактирование своих файлов через проводник,
добавив имя файла в фильтр `sensitive_files`.

```php
add_filter('sensitive_files', function (array $files): array {
    $files[] = 'my_plugin_config.json';
    return $files;
});
```

---

## 🛠 Сохранение настроек

Если плагин добавляет свои поля в настройки (например, в `admin-page.php`), он должен использовать функцию `save_plugin_settings()`. Она защищает от затирания данных при сохранении настроек сайта и других плагинов.

**Как это работает.** Функция вызывается из фильтра `save_settings_data` и смотрит на поле-триггер:

- поле-триггер есть в POST — форма плагина отправлена, значения читаются из POST;
- поля нет — форма не отправлялась (сохраняется сайт или другой плагин), и функция подставляет сохранённые значения, чтобы они не исчезли.

Функция возвращает `true`, если форма плагина была отправлена: по этому признаку можно доделать валидацию или вычисляемые поля (так сделано в `dark-theme`, `logs`, `media-optimization`).

Правила для плагина:

- не добавляй `settings_full` в свою форму — иначе ядро пересоберёт настройки сайта из POST и затрёт их;
- поле-триггер должно присутствовать в форме всегда (обычно это `hidden`-поле чекбокса), иначе ветка «форма отправлена» не сработает;
- тип значения берётся из `$defaults`: `true`/`false` — чекбокс, `int` — приведение к числу, остальное — как есть;
- вложенные настройки задаются путём через точку: `'log_settings.storage_path'`.

```php
add_filter('save_settings_data', function($newSettings) {
    $submitted = save_plugin_settings(
        $newSettings,
        'my_trigger_field', // Если этого поля нет в POST — форма плагина не отправлялась
        [
            'my_setting_1'    => 'default_value',
            'my_setting_2'    => true,      // чекбокс
            'my_setting_3'    => 0,         // число
            'my_nested.value' => 'default', // настройка my_nested['value']
        ],
        [
            'my_setting_1',
            'my_setting_2',
            'my_setting_3',
            'my_field_in_post' => 'my_nested.value', // имя поля в POST ≠ путь настройки
        ]
    );

    // Форма не отправлялась — дальше обрабатывать нечего
    if (!$submitted) {
        return $newSettings;
    }

    // Валидация и вычисляемые поля — после вызова хелпера
    $newSettings['my_setting_3'] = max(0, (int)$newSettings['my_setting_3']);

    return $newSettings;
});
```

---

## 🧩 Добавление своей секции в «Настройки сайта»

Если плагину нужна собственная секция в общих настройках (а не отдельная вкладка), он может зарегистрировать её через `register_plugin_section()`. Ядро автоматически добавит её в общий аккордеон, отсортирует и покажет в модалке «Настроить разделы».

### 1. Регистрация в `plugin.php`

```php
register_plugin_section('my-plugin', 'Мой плагин', function() {
    // Вывод HTML секции
    include __DIR__ . '/admin-section.php';
});
```

**Аргументы:**
- `$id` — уникальный ID секции (совпадает с ID плагина).
- `$title` — заголовок в аккордеоне.
- `$callback` — функция, которая выводит HTML.

### 2. Файл `admin-section.php`

Создай в папке плагина файл `admin-section.php` и выведи в нём поля. Форма должна отправляться на `index.php?tab=config_vars` с `save_settings=1`.

```php
<?php
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}
$settings = getSettingsData();
$token = $_SESSION['csrf_token'];
?>

<div class="border border-slate-200 rounded-2xl overflow-hidden">
    <div class="section-header bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition-colors" onclick="window.toggleSection(this)">
        <div class="flex items-center gap-3">
            <span class="section-arrow icon-chevron-right text-xs text-slate-400"></span>
            <span class="text-sm font-bold text-slate-700">Мой плагин</span>
        </div>
    </div>
    <div class="section-content p-6 bg-slate-50 hidden">
        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
        
        <!-- Твои поля -->
        <input type="text" name="my_plugin_setting" value="<?php echo e($settings['my_plugin_setting'] ?? ''); ?>">
    </div>
</div>
```

### 3. Сохранение настроек

Добавь в `plugin.php` фильтр `save_settings_data` (как описано выше в разделе «Сохранение настроек»). Поля из `admin-section.php` будут автоматически переданы в `$_POST`.

**Важно:** Пользователь может **перемещать** секцию (вверх/вниз) и **скрывать** её через кнопку «Настроить разделы» в шапке «Настроек сайта». Порядок и видимость сохраняются в `settings.json` (`config_sections_order`, `config_sections_hidden`).

---

## 🖥 `admin-page.php` — страница в админке

Если у плагина есть `admin_tab` в манифесте, ядро будет искать этот файл.

**Что должно быть внутри:**
1. Проверка авторизации (как в других модулях).
2. Форма с настройками (отправляется на `index.php?tab=config_vars` с `save_settings=1`). Признак `settings_full` **не ставить** — иначе ядро пересоберёт настройки сайта из POST и затрёт их.
3. Скрытые поля: `csrf_token`, `return_url` (чтобы вернуться на эту же страницу после сохранения).

**Пример:**

```php
<?php
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}
$settings = getSettingsData();
$token = $_SESSION['csrf_token'];
?>

<form method="POST" action="index.php?tab=config_vars">
    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
    <input type="hidden" name="save_settings" value="1">
    <input type="hidden" name="return_url" value="index.php?tab=<?php echo e($_GET['tab']); ?>">
    <input type="hidden" name="settings_source" value="плагина">
    
    <!-- Твои поля -->
    <input type="text" name="my_setting_1" value="<?php echo e($settings['my_setting_1'] ?? ''); ?>">
    
    <button type="submit">Сохранить</button>
</form>
```

### Обработка POST/GET вкладки

Форму плагина удобно обрабатывать на хуке `admin_init` — он срабатывает после
проверки авторизации и CSRF. Внутри проверяйте только свои поля.

```php
add_action('admin_init', function() {
    if (isset($_POST['my_save'])) {
        // ... сохранение ...
        header('Location: index.php?tab=my-plugin');
        exit;
    }
});
```

---

## 🎯 Пример: Плагин «Технические работы»

**1. `plugin.json`:**
```json
{
    "id": "maintenance",
    "name": "Технические работы",
    "admin_tab": {
        "title": "Технические работы",
        "icon": "icon-construction",
        "group": "system"
    }
}
```

**2. `plugin.php`:**
```php
<?php
// Перехватываем фронтенд
add_action('index_start', function() {
    $settings = getSettingsData();
    if (!empty($settings['maintenance_enabled'])) {
        header('HTTP/1.1 503 Service Unavailable');
        include APP_ROOT . '/templates/maintenance.php';
        exit;
    }
});
```

**3. `admin-page.php`:** форма с чекбоксом и сохранением (как описано выше).

---

## 📞 См. также

- [API.md](API.md) — все функции хуков
- [MODULES.md](MODULES.md) — разработка модулей контента