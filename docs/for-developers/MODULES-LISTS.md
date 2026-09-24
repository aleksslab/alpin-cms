# Модули со списками

**Модуль со списком** — модуль, у которого **N однотипных элементов**
(карточки, вопросы, тарифы, логотипы, метки). Примеры: cards, faq,
pricing, testimonials, timeline, brands-marquee, map-advanced, portfolio.

Характерные черты:
- Массив `items[N]` (или `slides[N]`, `questions[N]`, ...).
- `<template>` для добавления **новых** элементов через JS.
- `initListModule` — **общая фабрика** для add/remove/renumber.
- `js-*`-классы для якорей кнопок и подписей.

Общая структура модуля — в **[MODULES.md](MODULES.md)**.
Простой модуль — в **[MODULES-SIMPLE.md](MODULES-SIMPLE.md)**.

---

## 🎯 Ключевые понятия

### Префикс списка

Префикс = **имя поля** в JSON. У разных модулей — **разное**:

| Модуль | Префикс |
|--------|---------|
| carousel | `slides` |
| slider-advanced | `slides` |
| cards | `items` |
| faq | `items` |
| pricing | `items` |
| testimonials | `items` |
| timeline | `items` |
| brands-marquee | `items` |
| map-advanced | `items` |
| portfolio | `items` |
| stats | `stats` |
| quiz | `questions`, `fields` |
| text-media | `features`, `stats` |

В `FormData` **ключи** будут `slides[0][image]`, `items[1][title]`,
`questions[2][text]` и т.д.

### Имена классов

Зафиксированы в **каждом** модуле:

- **Контейнер списка:** `#{prefix}-container` (например, `#cards-rows-container`).
- **Карточка:** `.{prefix}-item-card` (например, `.card-item-card`).
- **Кнопка «Добавить»:** `#add-{something}-btn` (например, `#add-card-btn`).
- **Кнопка «Удалить» внутри карточки:** `.js-remove-{something}` (например, `.js-remove-card`).
- **Подпись карточки:** `.js-{something}-num` (например, `.js-card-num`) — с префиксом `js-`, чтобы не конфликтовать с иконочным шрифтом.

### `<template>` для добавления

HTML-шаблон **новой** карточки. В `module.php`:

```php
<template id="tmpl-card-item">
    <?php echo str_replace(
        ['{INDEX}', '{TITLE}', ...],
        ['__INDEX__', '', ...],
        $itemTemplate
    ); ?>
</template>
```

**Плейсхолдер `__INDEX__`** — заменяется JS на реальный индекс при добавлении.

### `initListModule` — фабрика

См. полный API ниже. **Не пиши add/remove/renumber руками** — используй фабрику.

---

## 🖥 `admin/module.php` — форма со списком

### Скелет

```php
<?php
/** @var array $moduleData */
$items = $moduleData['items'] ?? [];
$settingsClass = $moduleData['settings']['class'] ?? '';

if (empty($items)) {
    $items = [
        ['title' => '', 'text' => '']
    ];
}
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основные поля модуля -->
    <!-- ... (как в простом модуле) ... -->

    <!-- БЛОК 2: Список карточек -->
    <div id="cards-rows-container" class="space-y-3">
        <?php
        $itemTemplate = '
        <div class="card-item-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="js-card-num text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Карточка #{INDEX}</span>
                <button type="button" class="js-remove-card w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить карточку">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
                <input type="text" name="items[{INDEX}][title]" value="{TITLE}"
                       placeholder="Заголовок карточки"
                       class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Текст</label>
                <textarea name="items[{INDEX}][text]" rows="2"
                          placeholder="Текст..."
                          class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">{TEXT}</textarea>
            </div>
        </div>';
        ?>

        <?php foreach ($items as $idx => $item): ?>
            <?php
            echo str_replace(
                ['{INDEX}', '{TITLE}', '{TEXT}'],
                [$idx + 1, e($item['title'] ?? ''), e($item['text'] ?? '')],
                $itemTemplate
            );
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-card-item">
        <?php echo str_replace(
            ['{INDEX}', '{TITLE}', '{TEXT}'],
            ['__INDEX__', '', ''],
            $itemTemplate
        ); ?>
    </template>

    <button type="button" id="add-card-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить карточку
    </button>
</div>
```

**Что важно:**
- **`#cards-rows-container`** — контейнер списка.
- **`.card-item-card`** — класс карточки.
- **`.js-card-num`** — подпись. **Обязательно** с префиксом `js-`, чтобы не пересечься с `[class^="icon-"]`.
- **`.js-remove-card`** — кнопка удаления.
- **`#add-card-btn`** — кнопка добавления.
- **`<template id="tmpl-card-item">`** — шаблон с `__INDEX__`.
- **`{INDEX}`** в существующих карточках = **`$idx + 1`** (начинаем с 1 для отображения).

### Нюанс — суффикс `_card` / `_item`

Имена **могут быть любыми**, главное — **уникальны** в модуле. Стандарт:
- carousel → `.slide-item-card`, `.js-remove-slide`
- cards → `.card-item-card`, `.js-remove-card`
- faq → `.faq-item-card`, `.js-remove-faq`
- ...

Придерживайся **своего** префикса, но **не используй** `icon-`.

---

## 🎨 `site/module.php` — рендер со списком

### Скелет

```php
<?php
$items = $moduleData['items'] ?? [];
$settingsClass = $moduleData['settings']['class'] ?? '';

if (!is_array($items) || empty($items)) {
    return;
}

// === Защита от битых данных ===
$items = array_values(array_filter($items, function($item) {
    return is_array($item) && (!empty($item['title']) || !empty($item['text']));
}));

if (empty($items)) {
    return;
}
?>

<div class="my-module <?php echo e($settingsClass); ?>">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($items as $item): 
            $itemTitle = $item['title'] ?? '';
            $itemText = $item['text'] ?? '';
        ?>
            <div class="my-module-card bg-white rounded-2xl p-6 shadow-sm">
                <?php if (!empty($itemTitle)): ?>
                    <h3 class="text-lg font-bold text-slate-800 mb-2"><?php echo e($itemTitle); ?></h3>
                <?php endif; ?>

                <?php if (!empty($itemText)): ?>
                    <p class="text-slate-600"><?php echo e($itemText); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
```

**Обязательно:**
- **`array_values(array_filter(...))`** — даже если данные пришли нормально. Защита от дырок (после удаления среднего элемента в админке), `null`, пустых объектов.
- **`if (!is_array($items) || empty($items))` — `return`**.
- **Проверка каждого поля** через `?? ''`.

### Фильтрация «использованных» значений

Если у тебя есть **справочник** (категории, роли) и **карточки** ссылаются на него —
**есть взможность не выводить фильтры для пустых значений**. Пример из portfolio:

```php
// Собираем категории, реально используемые в работах
$usedCategories = [];
foreach ($items as $item) {
    $cats = explode("\n", (string)($item['categories'] ?? ''));
    foreach ($cats as $c) {
        $c = trim($c);
        if ($c !== '') $usedCategories[$c] = true;
    }
}

// Фильтры — только непустые
$filteredCategories = array_values(array_filter($categories, function($cat) use ($usedCategories) {
    if ($cat === 'Все') return false;
    return isset($usedCategories[$cat]);
}));
```

---

## ⚙️ `admin/script.js` — инициализация через `initListModule`

### Полный конфиг

```javascript
(function() {
    'use strict';

    function init() {
        if (typeof window.initListModule !== 'function') return;

        window.initListModule({
            // === Обязательные ===
            container:     'cards-rows-container',   // id контейнера (без '#')
            prefix:        'items',                  // префикс name="items[N][...]"
            template:      'tmpl-card-item',         // id <template> (без '#')
            cardSelector:  '.card-item-card',        // селектор карточки
            addBtn:        'add-card-btn',           // id кнопки добавления
            removeBtn:     '.js-remove-card',        // селектор кнопки удаления

            // === Опциональные ===
            labelSelector: '.js-card-num',           // селектор подписи (для «Карточка #N»)
            labelText:     'Карточка #',             // текст перед номером
            minCount:      1,                        // минимум карточек
            minMessage:    'Должна остаться хотя бы одна карточка',

            // Вызывается после вставки новой карточки
            onAfterAdd: function(card) {
                card.querySelectorAll('.js-image-url-input').forEach(function(input) {
                    if (typeof updatePreviewOnManualInput === 'function') {
                        updatePreviewOnManualInput(input);
                    }
                });
            },

            // Вызывается после ЛЮБОГО изменения: и после add, и после remove.
            // Полезно для обновления связанных элементов (внешние селекты,
            // фильтры, счётчики), которые зависят от состава карточек.
            onAfterChange: function(container) {
                // Например, обновить внешний селект по меткам
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
```

### Что `initListModule` делает сам

| Действие | Как |
|----------|-----|
| Добавить карточку | Клонирует кнопку `addBtn`, вешает обработчик. По клику — `getNextIndex(max+1)`, заменяет `__INDEX__`, вставляет в контейнер, вызывает `onAfterAdd`, пересчитывает подписи, вызывает `onAfterChange` |
| Удалить карточку | Делегирование `click` на `container`. Проверяет `minCount`, вызывает `onAfterChange` |
| Первичная нумерация | Вызывает `updateCardNumbers` при инициализации |
| Уведомления | `onAfterAdd(card)` — только при add. `onAfterChange(container)` — и при add, и при remove |

### Что `initListModule` **не делает**

- **Не перерисовывает** внешние элементы автоматически (селекты в шапке модуля, фильтры, счётчики). Для этого — используй `onAfterChange`.
- **Не синхронизирует** связанные данные (категории, теги, справочники). Тоже через `onAfterChange`.
- **Не вызывает** `onAfterAdd` при **первичной инициализации** — только при **добавлении** новой карточки.

**Для случаев «обновить что-то после add/remove»** — используй `onAfterChange(container)`:
он вызывается **после** того, как карточка добавлена или удалена, **и** после пересчёта подписей.

### Пример `onAfterChange` — обновление внешнего селекта

Скажем, у модуля есть `<select id="active-marker-select">` в шапке, в котором
перечислены **метки**. Если пользователь удалил метку — этот `<select>` надо
перерисовать.

```javascript
window.initListModule({
    container:     'map-items-container',
    prefix:        'items',
    template:      'tmpl-map-item',
    cardSelector:  '.map-item-card',
    addBtn:        'add-map-item-btn',
    removeBtn:     '.js-remove-map-item',
    minCount:      1,
    minMessage:    'Должна остаться хотя бы одна метка',

    onAfterChange: function(container) {
        // Пересобрать <option> в селекте по текущим карточкам
        refreshActiveMarkerOptions();
    }
});
```

**Не путай:** `onAfterAdd` — **только** при добавлении (удобно для инициализации превью, чипов в новой карточке).
`onAfterChange` — **и** add, **и** remove (удобно для обновления связанных списков).

### `getNextIndex` — почему `max + 1`, а не «первый свободный»

`getNextIndex` возвращает **`max + 1`** — **не** «первый свободный».

**Почему:**
- `FormData` итерирует поля **в порядке DOM**.
- `saveModuleSettings` присваивает по индексам **в порядке итерации**.
- Если индексы **возрастают** — массив получается **в правильном порядке**.
- Если индексы **убывают** (например, 1, потом 0) — **порядок переворачивается**.

Поэтому: **`max + 1`** гарантирует **монотонное возрастание** в DOM.

**После сохранения** JSON содержит **плотный массив** `[0..N-1]` (`.filter()` в `saveModuleSettings`). При **переоткрытии модалки** индексы снова начинаются с 0 — «утечки» номеров нет.

### Множественные списки в одном модуле

Модуль может иметь **несколько списков**. Пример — `quiz`:

```javascript
window.initListModule({
    container:    'questions-container',
    prefix:       'questions',
    template:     'tmpl-question',
    cardSelector: '.question-item-card',
    addBtn:       'add-question-btn',
    removeBtn:    '.js-remove-question',
    labelSelector: '.js-question-num',
    labelText:    'Вопрос #',
    minCount:     1,
    minMessage:   'Должен остаться хотя бы один вопрос'
});

window.initListModule({
    container:    'fields-container',
    prefix:       'fields',
    template:     'tmpl-field',
    cardSelector: '.field-item-card',
    addBtn:       'add-field-btn',
    removeBtn:    '.js-remove-field',
    labelSelector: '.js-field-num',
    labelText:    'Поле #',
    minCount:     1,
    minMessage:   'Должно остаться хотя бы одно поле'
});
```

**Важно:** контейнеры, шаблоны, кнопки — **разные**. `prefix` — **разный**. **Иначе коллизии.**

---

## 🎨 `site/script.js` — если модуль требует JS

Для **сложных** модулей (карта, карусель, слайдер) — **свой JS**. Требования:

- **IIFE** + **readyState**.
- **Цикл по инстансам** — модуль может быть **добавлен несколько раз**.
- **`data-*`** атрибуты для передачи параметров из PHP в JS.
- **Не использовать** `getElementById` **без префикса модуля** — id могут пересекаться с другими модулями.

### Пример — carousel

`site/module.php`:

```php
<div class="carousel-module"
     data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>"
     data-interval="<?php echo (int)$interval; ?>">
    ...
</div>
```

`site/script.js`:

```javascript
(function() {
    'use strict';

    function init() {
        document.querySelectorAll('.carousel-module').forEach(function(root) {
            var autoplay = root.dataset.autoplay === 'true';
            var interval = parseInt(root.dataset.interval, 10) || 5000;
            // ... логика карусели
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
```

### Передача данных в JS — `data-*` + JSON

**Никогда** не пихай **JSON в атрибут без экранирования**. Правильно:

```php
$dataJson = json_encode($items, JSON_UNESCAPED_UNICODE);
?>
<div class="my-module" data-items='<?php echo e($dataJson); ?>'>
```

`e()` экранирует **кавычки** и **спецсимволы** для атрибута. Браузер **сам** расшифрует при чтении через `dataset.items`. JS:

```javascript
var items = [];
try {
    items = JSON.parse(root.dataset.items) || [];
} catch (e) {
    items = [];
}
```

---

## 🎯 См. также

- Отладка — **[MODULES-DEBUG.md](MODULES-DEBUG.md)**.
- Безопасность — [SECURITY.md](SECURITY.md).


