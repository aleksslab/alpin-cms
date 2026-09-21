# Документация AlPin CMS

Полный индекс документации. **Выбери** **раздел** **под** **свою** **роль**.

---

## 🎯 Быстрая навигация

| Я — ... | Мне нужно | Раздел |
|---------|-----------|--------|
| **Владелец** **сайта** | Установить, настроить, работать | [Для пользователей](#-для-пользователей) |
| **Разработчик** | Понять архитектуру, писать модули | [Для разработчиков](#-для-разработчиков) |
| **DevOps** | Настроить **безопасность**, **бэкапы** | [SECURITY_SETUP.md](for-users/SECURITY_SETUP.md) |
| **Новичок** | Начать **с** **нуля** | [INSTALL.md](for-users/INSTALL.md) → [ADMIN_GUIDE.md](for-users/ADMIN_GUIDE.md) |

---

## 📚 Для пользователей

**Документация** **для** **тех**, **кто** **работает** **с** **CMS** **через** **админку**.

| # | Файл | О чём |
|---|------|-------|
| 1 | [INSTALL.md](for-users/INSTALL.md) | Установка на хостинг (**требования**, **права**, **первый** **вход**) |
| 2 | [ADMIN_GUIDE.md](for-users/ADMIN_GUIDE.md) | Обзор **админки** (**вкладки**, **действия**) |
| 3 | [CONSTRUCTOR_GUIDE.md](for-users/CONSTRUCTOR_GUIDE.md) | Работа **с** **конструктором** (**ряды**, **колонки**, **модули**) |
| 4 | [SETTINGS_GUIDE.md](for-users/SETTINGS_GUIDE.md) | Настройки **сайта** (**цвета**, **хедер**, **футер**, **SEO**) |
| 5 | [BACKUPS.md](for-users/BACKUPS.md) | Резервное **копирование**, **восстановление** |
| 6 | [SECURITY_SETUP.md](for-users/SECURITY_SETUP.md) | Настройка **безопасности** **на** **проде** (**HTTPS**, **CHMOD**, **DEBUG**) |
| 7 | [FAQ.md](for-users/FAQ.md) | Частые **проблемы** **и** **решения** |

### С чего начать

**Если** **ты** **только** **что** **скачал** **CMS:**

1. **Прочитай** [INSTALL.md](for-users/INSTALL.md) — **установи**.
2. **Прочитай** [ADMIN_GUIDE.md](for-users/ADMIN_GUIDE.md) — **осмотрись**.
3. **Прочитай** [CONSTRUCTOR_GUIDE.md](for-users/CONSTRUCTOR_GUIDE.md) — **создай** **первую** **страницу**.
4. **Настрой** **сайт** — [SETTINGS_GUIDE.md](for-users/SETTINGS_GUIDE.md).
5. **Настрой** **безопасность** — [SECURITY_SETUP.md](for-users/SECURITY_SETUP.md).

---

## 💻 Для разработчиков

**Документация** **для** **тех**, **кто** **пишет** **код** (**модули**, **шаблоны**, **ядро**).

| # | Файл | О чём |
|---|------|-------|
| 1 | [ARCHITECTURE.md](for-developers/ARCHITECTURE.md) | Общая **архитектура**, **поток** **запроса**, **структура** **папок** |
| 2 | [API.md](for-developers/API.md) | Все **функции** **ядра** (**чтение**, **запись**, **безопасность**) |
| 3 | [MODULES.md](for-developers/MODULES.md) | Как **писать** **модуль** (**структура** **ZIP**, `manifest.json`) |
| 4 | [TEMPLATES.md](for-developers/TEMPLATES.md) | Как **добавить** **хедер**/**футер**/**шаблон** |
| 5 | [CONSTRUCTOR.md](for-developers/CONSTRUCTOR.md) | Как **работает** **конструктор** **внутри** |
| 6 | [DATA_STRUCTURE.md](for-developers/DATA_STRUCTURE.md) | Все **JSON-структуры** |
| 7 | [SECURITY.md](for-developers/SECURITY.md) | Меры **безопасности**, **чек-лист** **при** **добавлении** **кода** |
| 8 | [CHANGELOG.md](for-developers/CHANGELOG.md) | История **версий** |

### С чего начать

**Если** **ты** **только** **что** **присоединился** **к** **проекту:**

1. **Прочитай** [ARCHITECTURE.md](for-developers/ARCHITECTURE.md) — **пойми** **каркас**.
2. **Прочитай** [API.md](for-developers/API.md) — **изучи** **функции**.
3. **Прочитай** [DATA_STRUCTURE.md](for-developers/DATA_STRUCTURE.md) — **разберись** **с** **данными**.
4. **Если** **пишешь** **модуль** — [MODULES.md](for-developers/MODULES.md).
5. **Если** **пишешь** **шаблон** — [TEMPLATES.md](for-developers/TEMPLATES.md).
6. **Перед** **коммитом** — **проверь** **чек-лист** **в** [SECURITY.md](for-developers/SECURITY.md).

---

## 🗂 Полная структура

```
docs/
├── README.md                          ← этот файл
│
├── for-users/                          # Пользовательская документация
│   ├── INSTALL.md
│   ├── ADMIN_GUIDE.md
│   ├── CONSTRUCTOR_GUIDE.md
│   ├── SETTINGS_GUIDE.md
│   ├── BACKUPS.md
│   ├── SECURITY_SETUP.md
│   └── FAQ.md
│
└── for-developers/                     # Документация для разработчиков
    ├── ARCHITECTURE.md
    ├── API.md
    ├── MODULES.md
    ├── TEMPLATES.md
    ├── CONSTRUCTOR.md
    ├── DATA_STRUCTURE.md
    ├── SECURITY.md
    └── CHANGELOG.md
```

---

## 🔍 Поиск по темам

### Установка и настройка

- [Требования к хостингу](for-users/INSTALL.md#-требования)
- [Права CHMOD](for-users/INSTALL.md#2-настрой-права-доступа)
- [Первый администратор](for-users/INSTALL.md#5-создай-первого-администратора)
- [HTTPS](for-users/SECURITY_SETUP.md#-1-https)
- [Security-заголовки](for-users/SECURITY_SETUP.md#-7-security-заголовки)

### Работа с контентом

- [Создание страницы](for-users/CONSTRUCTOR_GUIDE.md)
- [Работа с меню](for-users/ADMIN_GUIDE.md#4-меню-сайта-menu)
- [Установка модуля](for-users/ADMIN_GUIDE.md#3-управление-модулями-modules)
- [Настройка цветов](for-users/SETTINGS_GUIDE.md#-цветовая-схема)

### Оптимизация

- [Кеширование](for-users/SETTINGS_GUIDE.md#-кеширование-страниц)
- [Минификация](for-users/SETTINGS_GUIDE.md#-минификация)
- [Объединение ассетов](for-users/SETTINGS_GUIDE.md#-минификация)

### Безопасность

- [Brute-force](for-users/SECURITY_SETUP.md#-3-anti-brute-force)
- [DEBUG отключить](for-users/SECURITY_SETUP.md#-4-отключи-debug)
- [Ограничение по IP](for-users/SECURITY_SETUP.md#-8-ограничь-доступ-к-админке)
- [Чек-лист перед запуском](for-users/SECURITY_SETUP.md#-чек-лист-перед-запуском)

### Разработка

- [Поток запроса](for-developers/ARCHITECTURE.md#-поток-запроса-фронтенд)
- [Создание модуля](for-developers/MODULES.md)
- [Добавление шаблона](for-developers/TEMPLATES.md)
- [Структуры JSON](for-developers/DATA_STRUCTURE.md)

### Частые проблемы

- [500 ошибка](for-users/INSTALL.md#500-internal-server-error)
- [404 на всех страницах](for-users/INSTALL.md#404-на-всех-страницах-кроме-главной)
- [Логин не работает](for-users/INSTALL.md#логин-не-работает)
- [Кеш не сбрасывается](for-users/INSTALL.md#кеш-не-сбрасывается)
- [Модуль не отображается](for-users/FAQ.md#модуль-не-отображается-на-странице)

---

## 📞 Помощь

- **Не нашёл** **ответ** — [FAQ.md](for-users/FAQ.md).
- **Баг** — [GitHub Issues](https://github.com/AlekSSLab/alpin-cms/issues).
- **Идея** — **создай** **issue** **с** **меткой** `enhancement`.

---

## 📝 Соглашения документации

- **Язык:** русский.
- **Формат:** Markdown.
- **Структура:** один **файл** = **одна** **тема**.
- **Ссылки:** относительные (**между** **документами**).
- **Примеры:** **реальные** **сниппеты** **кода**.
- **Эмодзи:** **для** **наглядности** (**не** **для** **украшения**).

---

## 🤝 Вклад в документацию

**Нашёл** **ошибку** **или** **опечатку?**

1. **Форкни** **репозиторий**.
2. **Внеси** **правку**.
3. **Создай** **PR** **с** **описанием**.

**Подробнее:** [CONTRIBUTING.md](../CONTRIBUTING.md).