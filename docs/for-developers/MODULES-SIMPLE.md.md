# Простые модули

**Простой модуль** — модуль **без списка элементов**. Примеры: hero,
text, countdown, calculator, before-after.

Характерные черты:
- **Фиксированный набор полей** (title, subtitle, image, ...).
- **Нет** `<template>`, `items[N]`, `initListModule`.
- **Форма в админке** — просто поля.
- **Рендер на фронте** — вывод полей.

Общая структура — в **[MODULES.md](MODULES.md)**. Здесь — про содержимое
`admin/module.php` и `site/module.php`.

---

## 🖥 `admin/module.php` — форма настроек

**Доступные переменные:**
- `$moduleData` — массив данных модуля (из `data` в JSON страницы).
- `$moduleData['settings']['class']` — класс модуля (то, что в `settings_class`).

**Правила:**
- Имена полей — **как ключи** в `data`.
- Для настроек — **префикс `settings_`** (без префикса `items` и т.д.).
- **Всегда** экранируй через `e()`.
- **Не используй** имена `csrf_token`, `save_module_settings` — они зарезервированы.
- **Проверяй** наличие ключа через `??`.

### Пример

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

### Чекбоксы

Для **булевых** значений — **классический паттерн**: hidden + checkbox.

```php
<div>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="show_button" value="0">
        <input type="checkbox" name="show_button" value="1"
               <?php echo !empty($moduleData['show_button']) ? 'checked' : ''; ?>
               class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
        <span class="text-sm text-slate-600">Показывать кнопку</span>
    </label>
</div>
```

**Почему hidden + checkbox:** если чекбокс **не отмечен**, браузер **не отправит его** в `FormData`.
Hidden гарантирует, что **значение 0** уйдёт на сервер. Если отмечен — **оба** уйдут, но
**checkbox перезапишет** hidden (в `FormData` последнее побеждает).

### Select

```php
<select name="alignment" class="...">
    <option value="left"   <?php echo ($moduleData['alignment'] ?? 'left') === 'left'   ? 'selected' : ''; ?>>Слева</option>
    <option value="center" <?php echo ($moduleData['alignment'] ?? 'left') === 'center' ? 'selected' : ''; ?>>По центру</option>
    <option value="right"  <?php echo ($moduleData['alignment'] ?? 'left') === 'right'  ? 'selected' : ''; ?>>Справа</option>
</select>
```

### Медиа-поле (изображение)

Используй **общий шаблон** из других модулей (hero, text-media):

```php
<?php
$image = $moduleData['image'] ?? '';
$hasImg = !empty($image);
$previewSrc = $hasImg
    ? ((stripos($image, 'http://') === 0 || stripos($image, 'https://') === 0) ? $image : '../' . $image)
    : '';
?>

<div class="js-media-module-container">
    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Изображение</label>
    <div class="flex gap-2 items-center js-media-input-group">
        <div onclick="zoomCarouselImage(this)" class="carousel-inline-preview"
             style="width:80px;height:60px;flex-shrink:0;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;cursor:pointer;display:flex;align-items:center;justify-content:center;">
            <img src="<?php echo $previewSrc; ?>"
                 class="js-slide-preview-img w-full h-full object-cover <?php echo $hasImg ? '' : 'hidden'; ?>"
                 alt="Превью"
                 onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
            <div class="js-slide-preview-placeholder flex items-center justify-center w-full h-full text-slate-400 <?php echo $hasImg ? 'hidden' : ''; ?>">
                <span class="icon-image text-lg"></span>
            </div>
        </div>
        <input type="text" name="image" value="<?php echo e($image); ?>" placeholder="/images/photo.jpg"
               class="js-image-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"
               oninput="updatePreviewOnManualInput(this)" />
        <button type="button" onclick="openMediaModal(this)"
                class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
            <span class="icon-log-out rotate-90 text-sm"></span> Фото
        </button>
    </div>
</div>

<?php
$mediaTargetFolder = 'my-module';
include APP_ROOT . '/config/core/media_modal.php';
?>
```

### Иконочное поле

Аналогично — используй шаблон из `icons-list`/`text-media`:

```php
<div class="flex gap-2 items-center js-icon-input-group">
    <div class="icon-preview-wrapper">
        <span class="js-icon-preview-icon <?php echo e($icon ?: 'icon-star'); ?>"></span>
    </div>
    <input type="text" name="icon" value="<?php echo e($icon); ?>" placeholder="icon-star"
           class="js-icon-url-input flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]" />
    <button type="button" onclick="openIconModal(this)"
            class="px-3 py-2 bg-slate-200/60 hover:bg-slate-200 border border-slate-200 text-slate-700 font-medium text-sm rounded-lg transition-all cursor-pointer flex items-center gap-1.5 flex-shrink-0">
        <span class="icon-search text-sm"></span> Иконка
    </button>
</div>

<?php include APP_ROOT . '/config/core/icon_modal.php'; ?>
```

---

## 🎨 `site/module.php` — рендер на фронте

**Доступные переменные:**
- `$moduleData` — массив данных модуля.
- `$moduleData['settings']['class']` — класс модуля.

**Правила:**
- **Всегда** экранируй через `e()`.
- **Проверяй обязательные поля** → `return`, если пусто.
- **Никаких echo до проверки** — иначе битый HTML.
- Используй **`settings_class`** для кастомизации пользователем.
- Для **HTML-полей** — `sanitizeHtml()` или `validateHtml()`.

### Пример

```php
<?php
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

### Защита от битых данных

Если модуль **большой** и содержит **HTML**, всегда проверяй:

```php
$content = $moduleData['content'] ?? '';
if (!is_string($content)) {
    $content = '';
}
```

Если модуль содержит **числа**:

```php
$count = (int)($moduleData['count'] ?? 0);
if ($count < 0) $count = 0;
if ($count > 100) $count = 100;
```

Если модуль содержит **URL**:

```php
$link = $moduleData['link'] ?? '';
if ($link !== '' && !preg_match('#^https?://#i', $link)) {
    $link = '#'; // отсекаем javascript:, data: и т.п.
}
```

---

## 🎨 `site/style.css` — стили

Опционально. Подключается автоматически, если файл есть.

- Используй **CSS-переменные проекта**:
  - `--primary-color`, `--primary-dark`
  - `--bg-main`, `--bg-card`, `--bg-section`
  - `--text-main`, `--text-muted`
  - `--border-color`
- **Префикс классов** = id модуля. Например, для `quote` — `.quote-module`, `.quote-author`.
- **Не переопределяй** глобальные стили (body, a, h1, ...).
- **Поддерживай** адаптив — модуль должен работать **на мобильных**.

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

---

## ⚙️ `site/script.js` — JS на фронте

Опционально. Подключается автоматически, если файл есть.

### Обязательный шаблон

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

### Правила
- **IIFE** — обязательно.
- **Проверка `readyState`** — обязательно.
- **Делегирование событий** — слушатели на **родителе**, не на каждом элементе.
- **Множественные инстансы** — модуль может быть **добавлен дважды** на страницу. **Всегда** цикл по `.my-module`, а не `document.querySelector`.
- **Никаких глобальных переменных** — только внутри IIFE.

### Множественные инстансы — пример

```javascript
function initMyModule() {
    document.querySelectorAll('.my-module').forEach(function(root) {
        var button = root.querySelector('.my-button');
        if (!button) return;

        button.addEventListener('click', function() {
            // Этот обработчик привязан к КОНКРЕТНОМУ инстансу
            root.classList.toggle('active');
        });
    });
}
```

**Антипаттерн:**

```javascript
// ПЛОХО: берёт первый инстанс, остальные — игнорирует.
var button = document.querySelector('.my-button');
button.addEventListener('click', ...);
```

---

## 🎯 Итог

- Простой модуль — **без списков**.
- Форма — **простые поля**, `settings_*` для настроек.
- Рендер — **`e()` + проверка + `return`**.
- CSS — **переменные проекта**, префикс = id.
- JS — **IIFE + readyState + цикл по инстансам**.
- Со списками — см. **[MODULES-LISTS.md](MODULES-LISTS.md)**.
- Отладка — **[MODULES-DEBUG.md](MODULES-DEBUG.md)**.

