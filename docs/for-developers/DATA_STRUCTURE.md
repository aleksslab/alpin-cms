# Структуры JSON

## 📂 Обзор

**Все данные** — **в** **JSON-файлах**.

| Файл | Что хранит |
|------|-----------|
| `data/settings.json` | Настройки сайта |
| `data/pages/{id}.json` | Страницы |
| `data/menus/{id}.json` | Меню |
| `data/logs/admin.log` | Логи (**не** JSON) |
| `config/data/credentials.json` | Логин/хеш |
| `config/data/modules.json` | Список модулей |
| `config/data/templates.json` | Список шаблонов |
| `config/data/backup_config.json` | Настройки бэкапов |
| `config/data/login_attempts.json` | Логин-попытки, баны IP |

---

## 📄 `data/settings.json`

```json
{
    "name": "AlPin<span class=\"text-[var(--primary-color)]\">CMS</span>",
    "slogan": "Профессиональная CMS",
    "phone": "+79001234567",
    "email": "info@example.com",
    "address": "г. Москва, ул. Ленина, д. 1",
    "logo": "images/logo.png",
    "favicon": "images/favicon.png",

    "theme": {
        "primary_color": "#10B981",
        "primary_dark": "#059669",
        "bg_main": "#FFFFFF",
        "bg_card": "#FFFFFF",
        "bg_section": "#F8FAFC",
        "text_main": "#334155",
        "text_muted": "#64748B",
        "border_color": "#F1F5F9"
    },

    "header_variant": "default",
    "header_fixed": true,
    "burger_enabled": true,
    "burger_breakpoint": "md",

    "footer_variant": "default",
    "footer_copyright": "Все права защищены.",

    "meta_title": "",
    "meta_description": "",
    "meta_keywords": "",

    "og_title": "",
    "og_description": "",
    "og_image": "",
    "og_url": "",

    "twitter_title": "",
    "twitter_description": "",
    "twitter_image": "",

    "socials": [
        { "id": "vk", "url": "https://vk.com/..." },
        { "id": "telegram", "url": "https://t.me/..." }
    ],

    "custom_css": "",
    "custom_js": "",

    "assets_minify": false,
    "assets_combine": false,
    "minify_exceptions": {},

    "cache_enabled": false,
    "cache_ttl": 86400,

    "home_page_id": "home",
    "main_menu": "header_main"
    "cron_token": "a1b2c3d4e5f6...",
    "last_cron_publish": 1735689600
}
```

### Поля

**Контактная информация:**

| Поле | Тип | Описание |
|------|-----|----------|
| `name` | string | Название сайта. HTML разрешён (валидация + `sanitizeHtml`). |
| `slogan` | string | Слоган. HTML разрешён. |
| `phone` | string | Телефон. Только `0-9+`. Формат при выводе — `formatPhone()`. |
| `email` | string | Email. Валидация `FILTER_VALIDATE_EMAIL`. |
| `address` | string | Адрес. HTML вырезается при выводе. |

**Визуальные элементы:**

| Поле | Тип | Описание |
|------|-----|----------|
| `logo` | string | Путь к логотипу, относительно корня: `images/logo.png`. |
| `favicon` | string | Путь к favicon: `images/favicon.png`. |

**Цветовая схема (`theme`):**

| Поле | Тип | Значения |
|------|-----|----------|
| `theme.primary_color` | string | HEX, например `#10B981` |
| `theme.primary_dark` | string | HEX, hover-цвет кнопок |
| `theme.bg_main` | string | HEX, фон страницы |
| `theme.bg_card` | string | HEX, фон карточек |
| `theme.bg_section` | string | HEX, фон секций |
| `theme.text_main` | string | HEX, основной текст |
| `theme.text_muted` | string | HEX, второстепенный текст |
| `theme.border_color` | string | HEX, границы |

**Хедер:**

| Поле | Тип | Значения |
|------|-----|----------|
| `header_variant` | string | `default` / `centered` / `minimal` / `with-cta` |
| `header_fixed` | bool | Фиксация хедера при скролле |
| `burger_enabled` | bool | Мобильное бургер-меню |
| `burger_breakpoint` | string | `sm` / `md` / `lg` / `xl` |

**Футер:**

| Поле | Тип | Значения |
|------|-----|----------|
| `footer_variant` | string | `default` / `minimal` / `dark` / `light` |
| `footer_copyright` | string | HTML разрешён. Если пусто — выводится `© {YEAR} {name}. Все права защищены.` |

**SEO:**

| Поле | Тип | Описание |
|------|-----|----------|
| `meta_title` | string | Глобальный meta title. Если пусто — берётся из страницы. |
| `meta_description` | string | Глобальный meta description. |
| `meta_keywords` | string | Ключевые слова через запятую. |

**Open Graph:**

| Поле | Тип | Описание |
|------|-----|----------|
| `og_title` | string | Если пусто — `meta_title` |
| `og_description` | string | Если пусто — `meta_description` |
| `og_image` | string | Путь: `images/og-image.jpg`. Рекомендуется 1200×630. |
| `og_url` | string | Канонический URL. Если пусто — генерируется из `HTTP_HOST`. |

**Twitter Card:**

| Поле | Тип | Описание |
|------|-----|----------|
| `twitter_title` | string | Если пусто — `og_title` |
| `twitter_description` | string | Если пусто — `og_description` |
| `twitter_image` | string | Если пусто — `og_image` |

**Социальные сети (`socials`):**

Массив объектов `{ "id": "vk", "url": "https://..." }`. ID — из `getSocialNetworks()` (13 сетей). Пустые URL не сохраняются.

**Произвольный код:**

| Поле | Тип | Описание |
|------|-----|----------|
| `custom_css` | string | Вставляется в `<style>` в `<head>` сайта. **Санитайзер не применяется.** |
| `custom_js` | string | Вставляется в `<script>` перед `</body>`. **Санитайзер не применяется.** |

**Ассеты:**

| Поле | Тип | Описание |
|------|-----|----------|
| `assets_minify` | bool | Минификация CSS/JS модулей |
| `assets_combine` | bool | Объединение всех CSS/JS страницы в один файл |
| `minify_exceptions` | object | Карта `{ "module_id": { "css": bool, "js": bool } }`. Исключения из минификации по модулям. |

**Кеширование:**

| Поле | Тип | Описание |
|------|-----|----------|
| `cache_enabled` | bool | Кеширование HTML-страниц |
| `cache_ttl` | int | TTL кеша в секундах. `0` — бессрочно до изменения страницы. |

**Системные привязки:**

| Поле | Тип | Описание |
|------|-----|----------|
| `home_page_id` | string | ID главной страницы. По умолчанию `home`. |
| `main_menu` | string | ID главного меню. Если не задано — хедер без меню. |

### Служебные поля

Эти поля **не редактируются вручную** через админку — управляются кодом:

| Поле | Тип | Описание |
|------|-----|----------|
| `cron_token` | string | Общий токен для `config/cron/backup.php` и `config/cron/publish.php`. Генерируется автоматически (`bin2hex(random_bytes(16))`) при первом обращении к `getCronToken()`, если отсутствует. Перегенерация — в секции «Интеграция с планировщиком» в Настройках сайта. **Один токен для обеих крон-задач.** |
| `last_cron_publish` | int | Unix timestamp последнего запуска виртуального крона публикации. Ограничивает частоту — не чаще **1 раза в 5 минут** при заходе админа в админку (`getLastCronPublishTime()` / `setLastCronPublishTime()`). |

**Важно:** оба поля **сохраняются** при сохранении настроек сайта через `handleSaveSettings()`. Не теряются при перезаписи `settings.json`.

---

## 📄 `data/pages/{id}.json`

```json
{
    "id": "about",
    "title": "О компании",
    "slug": "about",
    "template": "full-width",
    "status": "published",
    "publish_at": null,
    "unpublish_at": null,
    "created": "2025-01-15 12:00:00",
    "updated": "2025-01-15 14:30:00",
    "meta": {
        "description": "Описание страницы",
        "keywords": "ключевые, слова"
    },
    "show_header": true,
    "show_footer": true,
    "rows": [
        {
            "id": "row_1",
            "zone": "main",
            "settings": { "class": "container mx-auto" },
            "columns": [
                {
                    "id": "col_1",
                    "width_class": "w-full",
                    "settings": { "class": "" },
                    "modules": [
                        {
                            "id": "mod_1",
                            "type": "hero",
                            "data": {
                                "title": "Заголовок",
                                "subtitle": "Подзаголовок",
                                "button_text": "Узнать больше",
                                "button_link": "/contacts"
                            },
                            "settings": { "class": "bg-slate-50" },
                            "global": false
                        }
                    ]
                }
            ]
        }
    ]
}
```

**Поля:**

| Поле | Тип | Обязательное |
|------|-----|--------------|
| `id` | string | ✅ |
| `title` | string | ✅ |
| `slug` | string | ✅ |
| `template` | string | ✅ |
| `status` | `draft` / `scheduled` / `published` / `archived` | ✅ |
| `publish_at` | int (Unix timestamp) \| null | Для `scheduled` — обязательно |
| `unpublish_at` | int (Unix timestamp) \| null | Опционально для `scheduled` / `published` |
| `created` | `Y-m-d H:i:s` | Опционально |
| `updated` | `Y-m-d H:i:s` | Опционально |
| `meta` | object | Опционально |
| `show_header` | bool | Опционально |
| `show_footer` | bool | Опционально |
| `rows` | array | Опционально |

### Статусы страниц

| Статус | Видна на фронте | `publish_at` | `unpublish_at` |
|--------|-----------------|--------------|----------------|
| `draft` | Нет | Всегда `null` | Всегда `null` |
| `scheduled` | Станет видна в `publish_at` | Обязателен, в будущем | Опционально, > `publish_at` |
| `published` | Да (до `unpublish_at`, если задан) | Всегда `null` | Опционально, в будущем |
| `archived` | Нет | Всегда `null` | Всегда `null` |

**Переходы статусов:**

- **Вручную** — через форму редактирования страницы (4 radio).
- **Автоматически** — через `cron/publish.php`:
  - `scheduled` → `published`, когда `publish_at <= now`.
  - `published` → `archived`, когда `unpublish_at <= now`.

**Очистка дат при сохранении:**

- `draft` / `archived` → `publish_at` и `unpublish_at` обнуляются.
- `published` → `publish_at` обнуляется, `unpublish_at` сохраняется.
- `scheduled` → обе даты актуальны.

**Подстраховка на фронте:**

`loadPage()` в `functions.php` отдаёт страницу только если:
- `status === 'published'`, и
- `unpublish_at` не задан или ещё не наступил.

Это защита на случай, если cron не сработал (отключён, упал) — страница всё равно не будет видна после истечения `unpublish_at`.

---

## 📄 `data/menus/{id}.json`

```json
{
    "id": "header_main",
    "name": "Главное меню",
    "items": [
        {
            "id": "item_1",
            "type": "page",
            "label": "Главная",
            "page_id": "/",
            "order": 0
        },
        {
            "id": "item_2",
            "type": "dropdown",
            "label": "Услуги",
            "order": 1,
            "children": [
                {
                    "type": "page",
                    "label": "Разработка",
                    "page_id": "/services/dev"
                }
            ]
        },
        {
            "id": "item_3",
            "type": "custom",
            "label": "Внешняя ссылка",
            "url": "https://example.com",
            "order": 2
        },
        {
            "id": "item_4",
            "type": "divider",
            "order": 3
        }
    ]
}
```

**Типы** **пунктов:**

| Тип | Поля |
|-----|------|
| `page` | `label`, `page_id` |
| `section` | `label`, `url` (**якорь** `#section`) |
| `custom` | `label`, `url` |
| `dropdown` | `label`, `children[]` |
| `divider` | — |

---

## 📄 `config/data/credentials.json`

```json
{
    "login": "admin",
    "password_hash": "$2y$10$abcdefghijklmnopqrstuv"
}
```

**`password_hash`** — **`password_hash(hash_hmac('sha256', $password, $login . $pepper))`**.

---

## 📄 `config/data/modules.json`

```json
{
    "hero": {
        "name": "Hero",
        "icon": "icon-image",
        "description": "Обложка с фоном",
        "has_settings": true,
        "auto_unpack": true,
        "version": "1.0.0",
        "author": "AlekSSLab"
    },
    "faq": {
        "name": "FAQ",
        "icon": "icon-help-circle",
        "description": "Вопрос-ответ",
        "has_settings": true,
        "auto_unpack": true,
        "version": "1.0.0",
        "author": "AlekSSLab"
    }
}
```

---

## 📄 `config/data/templates.json`

```json
{
    "templates": [
        {
            "id": "full-width",
            "name": "Полная ширина",
            "zones": {
                "main": { "class": "container mx-auto px-6" }
            }
        },
        {
            "id": "sidebar-left",
            "name": "С сайдбаром слева",
            "zones": {
                "sidebar-left": { "class": "w-full lg:w-1/4" },
                "main": { "class": "w-full lg:w-3/4" }
            }
        },
        {
            "id": "boxed",
            "name": "Боксовый",
            "zones": {
                "main": { "class": "container max-w-6xl mx-auto" }
            }
        }
    ]
}
```

---

## 📄 `config/data/backup_config.json`

```json
{
    "backup_type": "full",
    "targets": [],
    "storage_path": "config/backups/",
    "encrypt_archive": false,
    "archive_password": "",
    "cron_enabled": false,
    "cron_virtual": false,
    "cron_period": 24,
    "last_backup_time": 1705321200
}
```

**Что было убрано из этого файла в v1.3.0:** поле `cron_token` **удалено** из `config/data/backup_config.json` и перенесено в `settings.json`, потому что теперь это **общий** токен для бэкапов и публикации страниц.

---

## 📄 `config/data/login_attempts.json`

```json
{
    "settings": {
        "max_attempts": 5,
        "lockout_time": 15,
        "permanent_trigger_count": 3,
        "permanent_trigger_period": 24,
        "session_timeout": 20
    },
    "attempts": {
        "192.168.1.100": {
            "count": 3,
            "last_time": 1705321200,
            "bans_count": 1,
            "bans_history": [1705320000],
            "permanent": false
        }
    }
}
```

---

## 🔄 Соглашения

### Кодировка

**Все** **файлы** — **UTF-8**, **без** **BOM**.

### Форматирование

**Запись** — **с** `JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT`.

```php
$jsonString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
```

**Чтение** — `json_decode($json, true)` (**ассоциативный** **массив**).

### Безопасность

- **Никаких** **executable** **данных** (**eval**, **PHP-код**) — **только** **данные**.
- **Пользовательский** **HTML** — **санитизируется** **при** **сохранении** (`sanitizeHtml`).
- **Строки** — **экранируются** `e()` **при** **выводе**.

### Кеш в памяти

**`getData($fileName)`** **кеширует** **в** `$GLOBALS['DATA_CACHE']` **на** **время** **запроса**.

**`clearDataCache($fileName)`** — **сброс** **кеша** **при** **записи**.

---

## 🎯 Примеры операций

### Чтение

```php
$settings = getSettingsData();
$page = loadPage('about');
$menuItems = getMenuItems('header_main');
```

### Запись

```php
saveData('settings', $newSettings);
savePageData('about', $pageData);
saveMenu('header_main', $menuData);
```

### Обновление части

```php
$settings = getSettingsData();
$settings['cache_enabled'] = true;
saveData('settings', $settings);
```

---

## 📞 Поддержка

- **Архитектура:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **API:** [API.md](API.md)
- **Модули:** [MODULES.md](MODULES.md)