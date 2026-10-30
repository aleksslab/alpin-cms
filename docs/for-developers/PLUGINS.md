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
└── site/               # Публичные ассеты (опционально)
    ├── style.css       # Скопируется в css/{id}.css
    └── script.js       # Скопируется в js/{id}.js
```

**Как это работает:**
- Ядро (логика) лежит в `plugin.php` и файлах вроде `admin-page.php`.
- Если плагин добавляет стили или скрипты для **сайта** (фронтенда), они кладутся в папку `site/`.
- При установке плагина (из ZIP или при синхронизации) ядро **копирует** `site/style.css` в `css/{id}.css`, а `site/script.js` в `js/{id}.js`. В самом плагине вы подключаете их по этим путям (например, `css/dark-theme.css`).
- При удалении плагина эти скопированные файлы автоматически удаляются.

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

### Кастомные маршруты

```php
add_route('/my-page', function() {
    echo 'Hello from plugin!';
    exit;
});
```

---

## 🛠 Сохранение настроек

Если плагин добавляет свои поля в настройки (например, в `admin-page.php`), он должен использовать функцию `save_plugin_settings()`. Она защищает от затирания данных при сохранении настроек сайта.

```php
add_filter('save_settings_data', function($newSettings) {
    save_plugin_settings(
        $newSettings,
        'my_trigger_field', // Если этого поля нет в POST — форма плагина не отправлялась
        [
            'my_setting_1' => 'default_value',
            'my_setting_2' => true,
        ],
        [
            'my_setting_1',
            'my_setting_2',
        ]
    );
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
2. Форма с настройками (отправляется на `index.php?tab=config_vars` с `save_settings=1`).
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