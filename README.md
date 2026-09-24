# AlPin CMS

**Flat-File CMS на PHP 7.4+** — система управления контентом без базы данных.  
Все данные хранятся в JSON-файлах. Модульная архитектура, визуальный конструктор страниц, встроенное кеширование и минификация.

---

## ✨ Возможности

### Контент
- 📄 **Страницы** — неограниченное количество, ЧПУ, статусы (черновик/опубликована)
- 🧩 **24 модуля** — hero, hero-landing, text, text-media, cards, 
  icons-list, stats, timeline, faq, contacts, lead-form, quiz, 
  calculator, pricing, testimonials, portfolio, carousel, 
  slider-advanced, before-after, brands-marquee, countdown, 
  map-advanced, social-icons, site-menu
- 🏗 **Визуальный конструктор** — ряды, колонки (11 вариантов ширины), модули
- 📋 **Меню** — иерархические, dropdown, назначение главного
- 📱 **Адаптивные хедеры/футеры** — по 4 варианта, легко добавлять свои

### Дизайн
- 🎨 **8 настраиваемых цветов** — color-picker + text input
- 🌓 **Тёмная тема** админки
- 🎯 **Кастомные CSS/JS** — для тонкой настройки
- 🖼 **Медиа-менеджер** — загрузка, drag-n-drop, превью

### Оптимизация
- ⚡ **Кеширование страниц** — HTML в `/cache/pages/`, TTL настраивается
- 🗜 **Минификация CSS/JS** — `.min.css`/`.min.js` с исключениями по модулям
- 📦 **Объединение ассетов** — все CSS/JS страницы в один файл
- 🚀 **Умная очистка кеша** — при сохранении страницы, обновлении модуля, изменении настроек

### Безопасность
- 🔒 **CSRF-защита** для всех POST-запросов
- 🛡 **XSS-защита** — `e()`, `escapeJsString()`, `sanitizeHtml()`
- 🚫 **ZIP Slip** — `detectZipSlip()`
- 🚫 **Path Traversal** — `fmSafePath()`, `fmSafeAbsolute()`
- 🚫 **LFI/RCE** — валидация ID модулей
- 🧱 **Anti-Brute Force** — ограничение попыток, временный/вечный бан по IP
- 🔐 **Сессии** — `SameSite=Lax`, `use_strict_mode`, `httponly`
- 📜 **Логирование** — все действия админа с ротацией
- 💾 **Резервное копирование** — с шифрованием AES-256, авто-бэкапы
- 🗝 **Файловый менеджер** — доступ к файлам через админку с whitelist/blacklist

### Дополнительно
- 🌐 **SEO** — meta, OG, Twitter Card
- 🔗 **Соцсети** — 13 сетей, легко расширяется
- 📝 **Логи** — фильтры, поиск, ротация
- 🧪 **Совместимость** — PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4

---

## 🚀 Быстрый старт

### Требования

- **PHP** 7.4+ (рекомендуется 8.1+)
- **Apache** с `mod_rewrite` и `mod_headers`
- **Права на запись** для `data/`, `config/data/`, `cache/`
- 50+ МБ дискового пространства

### Установка

1. **Скачай** и распакуй архив в корень сайта.
2. **Настрой** права:
   ```
   chmod 755 data/ config/data/ cache/
   ```
3. **Открой** `https://твой-сайт/config/index.php`.
4. **Сгенерируй** логин/пароль:
   - Кнопка «Конфигурация ключей».
   - Введи пароль → получи хеш.
   - Запиши в `config/data/credentials.json`.
5. **Войди** в админку.
6. **Настрой** сайт: `Настройки` → `Контакты`, `Внешний вид`, `Хедер`, `Футер`.

**Подробнее:** [INSTALL.md](docs/for-users/INSTALL.md)

---

## 📚 Документация

### Для пользователей

- [Установка](docs/for-users/INSTALL.md)
- [Обзор админки](docs/for-users/ADMIN_GUIDE.md)
- [Работа с конструктором](docs/for-users/CONSTRUCTOR_GUIDE.md)
- [Настройки сайта](docs/for-users/SETTINGS_GUIDE.md)
- [Резервное копирование](docs/for-users/BACKUPS.md)
- [Настройка на проде](docs/for-users/SECURITY_SETUP.md)
- [FAQ / Проблемы](docs/for-users/FAQ.md)

### Для разработчиков

- [Архитектура](docs/for-developers/ARCHITECTURE.md)
- [API функций](docs/for-developers/API.md)
- [Разработка модулей](docs/for-developers/MODULES.md)
- [Шаблоны хедеров/футеров](docs/for-developers/TEMPLATES.md)
- [Конструктор — как работает](docs/for-developers/CONSTRUCTOR.md)
- [Структуры JSON](docs/for-developers/DATA_STRUCTURE.md)
- [Безопасность](docs/for-developers/SECURITY.md)
- [История версий](docs/for-developers/CHANGELOG.md)

---

## 🏗 Структура проекта

```
/
├── index.php                     # Фронтенд — роутинг и рендеринг
├── .htaccess                     # ЧПУ + защита
├── README.md                     # Этот файл
│
├── config/                       # Админка (защищена)
│   ├── index.php                 # Точка входа
│   ├── login.php                 # Форма логина
│   ├── config.php                # Константы, сессии, DEBUG
│   ├── cron_backup.php           # Крон для бэкапов
│   │
│   ├── core/
│   │   ├── functions.php         # Чтение данных + утилиты
│   │   ├── admin_auth.php        # Авторизация, безопасность
│   │   ├── admin_controller.php  # Запись + логика
│   │   ├── filemanager_api.php   # API файлового менеджера
│   │   ├── media_modal.php       # Медиа-модалка
│   │   └── icon_modal.php        # Модалка иконок
│   │
│   ├── modules/                  # Модули админки
│   │   ├── pages/                # Страницы + конструктор
│   │   ├── menu/                 # Меню
│   │   ├── config_vars.php       # Настройки сайта
│   │   ├── modules.php           # Управление модулями
│   │   ├── security.php          # Безопасность
│   │   ├── backups.php           # Бэкапы
│   │   ├── filemanager.php       # Проводник
│   │   └── logs.php              # Логи
│   │
│   ├── css/config.css            # Стили админки
│   ├── js/                       # JS админки
│   └── data/                     # Системные данные
│       ├── credentials.json      # Логин/пароль
│       ├── modules.json          # Список модулей
│       ├── templates.json        # Список шаблонов
│       └── .pepper               # Секрет для пароля
│
├── data/                         # ПОЛЬЗОВАТЕЛЬСКИЕ ДАННЫЕ
│   ├── settings.json             # Глобальные настройки
│   ├── pages/*.json              # Страницы
│   ├── menus/*.json              # Меню
│   └── logs/                     # Логи
│
├── cache/                        # КЕШ
│   ├── pages/*.html              # HTML-кеш
│   └── assets/                   # Объединённые CSS/JS
│
├── modules/*.php                 # Публичные модули сайта
├── templates/                    # Шаблоны сайта
│   ├── base.php
│   ├── full-width.php
│   ├── headers/{default,centered,minimal,with-cta}/
│   └── footers/{default,minimal,dark,light}/
│
├── css/, js/, fonts/, images/    # Статические ассеты
└── docs/                         # Документация
```

---

## 🧩 Встроенные модули

| Модуль | Назначение |
|--------|------------|
| `hero` | Обложка с фоном и кнопкой |
| `hero-landing` | Hero для лендинга: 2 кнопки, визуал, микро-факты |
| `text` | Произвольный HTML/текст |
| `text-media` | Текст + картинка (слева/справа) |
| `cards` | Сетка карточек |
| `icons-list` | Список с иконками |
| `stats` | Счётчики с анимацией |
| `timeline` | Временная шкала |
| `faq` | Вопрос-ответ (аккордеон) |
| `contacts` | Контакты + форма + карта |
| `lead-form` | Форма захвата лидов |
| `quiz` | Квиз с вопросами |
| `calculator` | Калькулятор корма |
| `pricing` | Тарифная сетка |
| `testimonials` | Отзывы |
| `portfolio` | Портфолио с фильтрами |
| `carousel` | Простой слайдер |
| `slider-advanced` | Продвинутый слайдер |
| `before-after` | Сравнение до/после |
| `brands-marquee` | Бегущая лента логотипов |
| `countdown` | Таймер обратного отсчёта |
| `map-advanced` | Яндекс.Карты с метками |
| `social-icons` | Социальные иконки |
| `site-menu` | Меню на фронте (для футера) |

---

## 🔧 Технологии

- **PHP** 7.4+ (без БД)
- **Tailwind CSS** (CDN на фронте, локально в админке)
- **Ace Editor** (редактор кода)
- **Lucide Icons** (иконки)
- **Apache** + `.htaccess` (ЧПУ, безопасность)

---

## 📋 Требования к хостингу

- **PHP** 7.4+ с расширениями: `json`, `mbstring`, `openssl`, `zip`, `fileinfo`
- **Apache** 2.4+ с `mod_rewrite`, `mod_headers`
- **Права**: `755` для папок, `644` для файлов
- **HTTPS** — рекомендуется (обязательно для прода)

---

## 🛡 Безопасность

**Что уже реализовано:**
- CSRF-токены на всех POST-запросах
- XSS-экранирование (`e()`, `escapeJsString()`, `sanitizeHtml()`)
- Защита от ZIP Slip, Path Traversal, LFI/RCE
- Anti-brute-force с временным/вечным баном
- Сессии с `SameSite=Lax`, `use_strict_mode`
- `.htaccess` защита для `config/`, `data/`, `cache/`, `logs/`
- Логирование всех действий
- Шифрование бэкапов AES-256

**Что настроить на проде:**
- HTTPS
- `DEBUG_SITE = false`, `DEBUG_ADMIN = false`
- Уникальный логин/пароль админа
- Регулярные бэкапы

**Подробнее:** [SECURITY_SETUP.md](docs/for-users/SECURITY_SETUP.md) · [SECURITY.md](docs/for-developers/SECURITY.md)

---

## 📄 Лицензия

Этот проект распространяется под лицензией **GNU General Public License v3.0**.

Вы можете:
- ✅ Использовать бесплатно (в т.ч. коммерчески)
- ✅ Модифицировать
- ✅ Распространять

При условии:
- 📖 Открытый исходный код производных работ
- 📖 Сохранение лицензии GPL v3
- 📖 Указание авторства и изменений

**Полный текст:** [LICENSE](LICENSE) · [gnu.org/licenses/gpl-3.0](https://www.gnu.org/licenses/gpl-3.0.html)

---

## 👤 Автор

**AlekSSLab** — [GitHub](https://github.com/AlekSSLab)

---

## 📞 Поддержка

- **Документация:** [docs/](docs/)
- **FAQ:** [FAQ.md](docs/for-users/FAQ.md)
- **Issues:** [GitHub Issues](https://github.com/AlekSSLab/alpin-cms/issues)