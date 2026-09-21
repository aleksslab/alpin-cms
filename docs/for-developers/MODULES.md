# Разработка модулей

## 📦 Структура модуля

```
my-module.zip
├── manifest.json           # Метаданные
├── preview.png             # Превью (опционально, 200×200)
│
├── admin/
│   ├── module.php          # Форма настроек в админке
│   ├── style.css           # Стили формы
│   └── script.js           # JS формы
│
└── site/
    ├── module.php          # Рендер на фронте
    ├── style.css           # Стили
    └── script.js           # JS
```

---

## 📄 `manifest.json`

**Обязательные** **поля:**

```json
{
    "id": "my-module",
    "name": "Мой модуль",
    "icon": "icon-box",
    "description": "Краткое описание модуля",
    "version": "1.0.0",
    "author": "Ваше имя",
    "has_settings": true,
    "auto_unpack": false
}
```

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | string | **Уникальный** **ID** (**a-z**, **0-9**, `-`, `_`) |
| `name` | string | **Название** **в** **админке** |
| `icon` | string | **Lucide-класс** (`icon-box`, `icon-star`, ...) |
| `description` | string | **Описание** |
| `version` | string | **Версия** |
| `author` | string | **Автор** |
| `has_settings` | bool | **Есть** **ли** **форма** **настроек** |
| `auto_unpack` | bool | **Распаковать** **сразу** **при** **загрузке** |

**Валидация** **при** **загрузке:**
- `id` — **только** `a-z0-9-_`.
- **Размер** **ZIP** ≤ 10 МБ.
- **Обязательно** `manifest.json` **в** **корне** **ZIP**.

---

## 🖥 `admin/module.php` — форма настроек

**Форма** **генерируется** **автоматически** **из** **этого** **файла**.

**Доступные** **переменные:**
- `$moduleData` — **текущие** **данные** **модуля** (`data` **из** `pages/{id}.json`).
- `$moduleSettings` — **настройки** (`settings` **из** `pages/{id}.json`).

**Правила:**
- **Имена** **полей** — **как** **ключи** **в** `data`.
- **Для** **настроек** — **префикс** `settings_`.
- **Экранирование** — **через** `e()`.

**Пример** **простой** **формы:**

```php
<?php
/** @var array $moduleData */
$title = $moduleData['title'] ?? '';
$text = $moduleData['text'] ?? '';
$settingsClass = $moduleData['settings']['class'] ?? '';
?>

<div class="space-y-4">
    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Заголовок</label>
        <input type="text" 
               name="title" 
               value="<?php echo e($title); ?>" 
               class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Текст</label>
        <textarea name="text" 
                  rows="4"
                  class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($text); ?></textarea>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Класс модуля</label>
        <input type="text" 
               name="settings_class" 
               value="<?php echo e($settingsClass); ?>" 
               placeholder="my-4, container"
               class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
    </div>
</div>
```

---

## 🎨 `site/module.php` — рендер на фронте

**Доступные** **переменные:**
- `$moduleData` — **данные** **модуля** (**из** `data`).

**Правила:**
- **Экранирование** **через** `e()`.
- **Проверка** **обязательных** **полей** → `return`.
- **Никаких** `echo` **до** **проверки**.

**Пример:**

```php
<?php
/** @var array $moduleData */
$title = $moduleData['title'] ?? '';
$text = $moduleData['text'] ?? '';
$settingsClass = $moduleData['settings']['class'] ?? '';

if (empty($title) && empty($text)) {
    return;
}
?>

<div class="my-module <?php echo e($settingsClass); ?>">
    <?php if (!empty($title)): ?>
        <h2 class="text-3xl font-bold text-slate-800 mb-4"><?php echo e($title); ?></h2>
    <?php endif; ?>

    <?php if (!empty($text)): ?>
        <div class="text-slate-600 leading-relaxed">
            <?php echo nl2br(e($text)); ?>
        </div>
    <?php endif; ?>
</div>
```

---

## 🎨 `site/style.css` — стили

**Опционально.** **Подключается** **автоматически**, **если** **есть** **файл**.

```css
.my-module {
    padding: 2rem;
    background: var(--bg-card);
    border-radius: 1rem;
}

.my-module h2 {
    color: var(--text-main);
}
```

**Переменные** **доступны:**
- `--primary-color`, `--primary-dark`
- `--bg-main`, `--bg-card`, `--bg-section`
- `--text-main`, `--text-muted`
- `--border-color`

---

## ⚙️ `site/script.js` — JS

**Опционально.** **IIFE** + **readyState** **проверка**.

```javascript
(function() {
    'use strict';

    function initMyModule() {
        var modules = document.querySelectorAll('.my-module');
        if (!modules.length) return;

        modules.forEach(function(module) {
            // ...
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMyModule);
    } else {
        initMyModule();
    }
})();
```

**Правила** (**стандарт** **проекта**):
- **IIFE**.
- **Проверка** `readyState`.
- **Делегирование** **событий** (**слушатели** **на** **родителях**).

---

## 📋 Полный пример: модуль «Цитата»

### `manifest.json`

```json
{
    "id": "quote",
    "name": "Цитата",
    "icon": "icon-quote",
    "description": "Красивая цитата с автором",
    "version": "1.0.0",
    "author": "AlekSSLab",
    "has_settings": true,
    "auto_unpack": true
}
```

### `admin/module.php`

```php
<?php
$text = $moduleData['text'] ?? '';
$author = $moduleData['author'] ?? '';
$position = $moduleData['position'] ?? '';
$settingsClass = $moduleData['settings']['class'] ?? '';
?>

<div class="space-y-4">
    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Текст цитаты</label>
        <textarea name="text" rows="4" required
                  class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($text); ?></textarea>
    </div>

    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Автор</label>
            <input type="text" name="author" value="<?php echo e($author); ?>"
                   class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Должность</label>
            <input type="text" name="position" value="<?php echo e($position); ?>"
                   class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Класс модуля</label>
        <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>"
               placeholder="my-8, container"
               class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
    </div>
</div>
```

### `site/module.php`

```php
<?php
$text = $moduleData['text'] ?? '';
$author = $moduleData['author'] ?? '';
$position = $moduleData['position'] ?? '';
$settingsClass = $moduleData['settings']['class'] ?? '';

if (empty($text)) {
    return;
}
?>

<div class="quote-module <?php echo e($settingsClass); ?>">
    <blockquote class="relative pl-8 border-l-4 border-[var(--primary-color)] py-2">
        <p class="text-lg italic text-slate-700 leading-relaxed mb-4">
            «<?php echo e($text); ?>»
        </p>

        <?php if (!empty($author)): ?>
            <footer class="text-sm">
                <strong class="text-slate-800"><?php echo e($author); ?></strong>
                <?php if (!empty($position)): ?>
                    <span class="text-slate-500">, <?php echo e($position); ?></span>
                <?php endif; ?>
            </footer>
        <?php endif; ?>
    </blockquote>
</div>
```

### `site/style.css`

```css
.quote-module {
    margin: 2rem 0;
}

.quote-module blockquote {
    background: var(--bg-section);
    padding: 1.5rem 2rem;
    border-radius: 1rem;
}
```

---

## 🚀 Установка модуля

### Через админку

1. **Управление** **модулями** → **Установить** **модуль**.
2. **Перетащи** ZIP.
3. Дождись распаковки.
4. **Добавь** **модуль** **на** **страницу** **через** **конструктор**.

### Вручную (для разработки)

1. **Создай** **структуру** **папок:**
   ```
   config/modules/my-module/
   ├── module.php
   ├── style.css
   └── script.js
   ```

2. **Создай** **файлы:**
   - `modules/my-module.php` (публичный).
   - `css/my-module.css` (опционально).
   - `js/my-module.js` (опционально).

3. **Добавь** **в** `config/data/modules.json`:
   ```json
   "my-module": {
       "name": "Мой модуль",
       "icon": "icon-box",
       "description": "Описание",
       "has_settings": true,
       "auto_unpack": true,
       "version": "1.0.0",
       "author": "Вы"
   }
   ```

4. **Очисти** **кеш** **в** **админке**.

---

## 🎯 Соглашения

### Именование

- **ID** — `kebab-case` (`my-module`, `lead-form`).
- **Классы** **CSS** — `my-module`, `my-module-title`, `my-module-content`.
- **Префикс** — **чтобы** **избежать** **конфликтов**.

### Безопасность

- **Всегда** **используй** `e()` **при** **выводе**.
- **Проверяй** **обязательные** **поля**.
- **Не** **используй** `eval()`, `exec()`.
- **Валидируй** **URL** **через** `safeUrl()`.
- **Для** **HTML** — **санитайзер** (`sanitizeHtml`).

### Производительность

- **Минимум** **запросов** **к** **ФС**.
- **Не** **дублируй** **CSS/JS**.
- **Используй** **Tailwind-классы** (**не** **свои** **стили**).
- **Ленивая** **загрузка** **тяжёлых** **модулей**.

### UX

- **Адаптивность** — **проверь** **на** **мобильных**.
- **Кеширование** — **учти** **кеш** **ассетов**.
- **Тёмная** **тема** **админки** — **поддерживай** **свои** **стили**.

---

## 🐛 Отладка

### Модуль не отображается

1. **Проверь** `modules/my-module.php` — **существует**?
2. **Проверь** **статус** **в** **админке** — «**распакован**»?
3. **Проверь** **данные** **модуля** — **не** **пустые**?
4. **Очисти** **кеш**.

### Форма настроек не открывается

1. **Проверь** `config/modules/my-module/module.php`.
2. **Открой** **DevTools** → **Console** — **ошибки**?
3. **Проверь** `manifest.json` — `has_settings: true`?

### Ошибка при сохранении

1. **Проверь** **имена** **полей** — **должны** **совпадать** **с** **ключами** **в** `data`.
2. **Не** **используй** **зарезервированные** **имена** (`csrf_token`, `save_module_settings`).
3. **Проверь** `data/pages/{id}.json` — **сохранились** **ли** **данные**?

---

## 📞 Поддержка

- **Архитектура:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **API:** [API.md](API.md)
- **Безопасность:** [SECURITY.md](SECURITY.md)
- **GitHub Issues:** [github.com/AlekSSLab/alpin-cms/issues](https://github.com/AlekSSLab/alpin-cms/issues)