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
}
```

---

## 📄 `data/pages/{id}.json`

```json
{
    "id": "about",
    "title": "О компании",
    "slug": "about",
    "template": "full-width",
    "status": "published",
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
| `status` | `published` / `draft` | ✅ |
| `created` | `Y-m-d H:i:s` | Опционально |
| `updated` | `Y-m-d H:i:s` | Опционально |
| `meta` | object | Опционально |
| `show_header` | bool | Опционально |
| `show_footer` | bool | Опционально |
| `rows` | array | Опционально |

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
    "cron_token": "abc123...",
    "last_backup_time": 1705321200
}
```

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