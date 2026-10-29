# Отладка модулей

Частые проблемы при разработке и их решения. Симптом → причина → что делать.

---

## 🔴 Модуль не отображается на фронте

**Симптом:** добавил модуль в конструктор, сохранил, но на странице его нет.

1. **Проверь, что файл `modules/{id}.php` существует.** Если модуль **не распакован**, файла нет.
   → **Управление модулями** → проверь статус → **распаковать** вручную.
2. **Проверь, что модуль не возвращается досрочно.** Открой `modules/{id}.php` — есть ли `return` в начале?
3. **Проверь данные модуля в JSON.** Открой `data/pages/{page}.json`, найди модуль:
   - Если `data` пустая `{}` — форма не сохранилась.
   - Если `data.items = null` — фильтр `array_filter` отсёк всё, `return`.
4. **Проверь кеш.** Если включено кеширование — очисти (**Настройки → Кеширование страниц → Очистить весь кеш**).
5. **Проверь консоль браузера** — если в JS ошибка, модуль может рендериться пустым.

---

## 🔴 Форма настроек не открывается

**Симптом:** клик по «шестерёнке» на модуле — модалка пустая / не грузится.

1. **`has_settings: true`** в `manifest.json`?
   - Если `false` — кнопки настроек **не будет**.
2. **Файл `config/modules/{id}/module.php` существует?**
   - Если `admin/module.php` не было в ZIP — не будет формы.
3. **Открой DevTools → Network.**
   - POST к `ajax=module_settings` вернул **500**? → синтаксическая ошибка в `module.php`. **Смотри `error_log`.**
   - POST вернул **403**? → CSRF-токен невалидный. Обнови страницу.
   - POST вернул **200**, но `success: false`? → модуль не найден, `module_type` невалиден.
4. **Открой DevTools → Console.**
   - `TypeError: null is not an object` — DOM-элемент не найден. Проверь **id** в `module.php`.
   - `SyntaxError` — JS-синтаксис. Проверь `admin/script.js`.

---

## 🔴 `Undefined array key` в `module.php`

**Симптом:** при открытии модуля — warning / пустая форма.

**Причина:** обращение к `$moduleData['key']` **без `??`**.

**Плохо:**
```php
$title = $moduleData['title']; // Warning при отсутствии ключа
```

**Правильно:**
```php
$title = $moduleData['title'] ?? '';
```

**Особенно критично:**
- **Вложенные массивы.** `$moduleData['settings']['class']` — **два ключа**, **два `??`**.
  ```php
  $settingsClass = $moduleData['settings']['class'] ?? ''; // Warning, если 'settings' нет
  $settings = $moduleData['settings'] ?? [];
  $settingsClass = $settings['class'] ?? ''; // ok
  ```
- **Списки.** `foreach ($items as $item)` — если `$item` не массив, ошибка.
  ```php
  foreach ($items as $item) {
      if (!is_array($item)) continue;
      // ...
  }
  ```

---

## 🔴 После сохранения данные карточек потерялись

**Симптом:** удалил одну карточку, добавил другую, сохранил — старая исчезла.

**Причина:** **коллизия индексов** в `name`. Два `<input>` с одинаковым `name` — второй затирает первый.

**Что проверить:**
1. Открой модалку, добавь 3 карточки, удали среднюю, добавь ещё одну, сохрани.
2. **В консоли** перед сохранением:
   ```js
   var c = document.getElementById('id-проверяемого-контейнера');
   var names = [...c.querySelectorAll('[name^="items["]')].map(i => i.name.match(/items\[\d+\]/)[0]);
   console.log([...new Set(names)]);
   ```
   Должны быть **уникальные** индексы. Если есть дубли — **баг в JS модуля**.
3. **Правильный фикс:** **не считай индекс вручную**. Используй `initListModule` — он берёт `max + 1`.

---

## 🔴 Данные уходят, но на фронте `null`

**Симптом:** JSON содержит `items: [null, {...}, {...}]`.

**Причина:** после удаления среднего элемента в админке индексы **не пересчитались**, `.filter()` в `saveModuleSettings` схлопнул, но **`null` попал в массив**.

**Фикс:**
1. **В админке** — используй `initListModule`. Он пересчитывает подписи и **не допускает** коллизий.
2. **На фронте** — всегда:
   ```php
   $items = array_values(array_filter($items, function($i) {
       return is_array($i);
   }));
   ```

---

## 🔴 Иконки не показываются / показываются квадратики

**Симптом:** вместо иконки — ромб, квадрат, пустота.

1. **Класс иконки должен начинаться с `icon-`.** Правильно: `icon-star`. Неправильно: `star`, `icon_star`.
2. **Иконочный шрифт подключён?** Проверь `<link>` в layout. Обычно `fonts/lucide.woff2`.
3. **Класс не конфликтует с префиксом** `icon-`? **Не называй свои классы** `icon-item-card` — сработает правило `[class^="icon-"] { font-family: 'lucide' }` и текст **превратится в иконки**.
   - Используй `iconitem-card` (без дефиса), `js-icon-card` или `icons-list-card`.

---

## 🔴 Модуль дважды на странице — работает только один

**Симптом:** добавил модуль дважды, но второй не работает.

**Причина:** JS использует `document.querySelector` — берёт **первый** инстанс.

**Плохо:**
```javascript
var root = document.querySelector('.my-module');
root.addEventListener('click', ...);
```

**Правильно:**
```javascript
document.querySelectorAll('.my-module').forEach(function(root) {
    root.addEventListener('click', ...);
});
```

---

## 🔴 `getNextIndex` возвращает 0, когда есть элементы

**Симптом:** новая карточка получает индекс `0`, хотя уже есть `items[1]` и `items[2]`.

**Причина:** `getNextIndex` смотрит на `name` **внутри контейнера**. Если `container` передан неправильно — увидит **пусто** и вернёт 0.

**Что проверить:**
- **id контейнера** в `initListModule.container` совпадает с **id в `module.php`**?
- **Элементы внутри контейнера** имеют `name="items[N][...]"`? Проверь через `container.querySelectorAll('[name]')`.

---

## 🔴 `onAfterAdd` не вызывается

**Симптом:** после добавления карточки превью не обновляется, чипы не рендерятся.

**Причина:** `onAfterAdd` определён **вне** `initListModule` или **не передан**.

**Проверь:**
- В `script.js` модуля — `onAfterAdd: function(card) { ... }` внутри **объекта** `initListModule`.
- Внутри `onAfterAdd` — `card` — **новый DOM-элемент**.

---

## 🔴 Категории / чипы не синхронизируются с карточкой

**Симптом:** выбрал чипы, сохранил — в JSON пусто.

**Причина:** чипы — **UI**, а **данные** идут через `hidden items[N][categories]`. Если hidden **не синхронизирован** — данные **теряются**.

**Правильно:**
```html
<div class="js-categories flex flex-wrap gap-1.5">
    <label><input type="checkbox" class="hidden peer" value="Дизайн">...</label>
</div>
<input type="hidden" name="items[N][categories]" value="" class="js-categories-hidden">
```

```javascript
container.addEventListener('change', function(e) {
    if (!e.target.matches('.js-category-cb')) return;
    // пересобрать hidden
    var card = e.target.closest('.card-item-card');
    var hidden = card.querySelector('.js-categories-hidden');
    var selected = [];
    card.querySelectorAll('.js-category-cb:checked').forEach(function(cb) {
        selected.push(cb.value);
    });
    hidden.value = selected.join('\n');
});
```

**Проверь:**
- У `hidden` есть **`name="items[N][categories]"`**.
- `hidden.value` обновляется **при каждом** `change`.
- **До сохранения** открой DevTools → Elements → найди hidden → проверь `value`.

---

## 🔴 `Undefined array key "options"` в модуле quiz

**Симптом:** на фронте warning `Undefined array key "options"`.

**Причина:** у вопроса тип `text` — поля `options` **нет** в JSON (поле было пустое и не сохранилось).

**Фикс:**
```php
foreach ($questions as $key => $q) {
    if (!isset($q['options']) || !is_array($q['options'])) {
        $questions[$key]['options'] = [];
    }
}

// Потом — безопасное обращение
$options = $q['options'] ?? [];
```

**Общий принцип:** **любой** ключ, который может **не сохраниться**, читай через `?? []` / `?? ''`.

---

## 🔴 CSS модуля переопределяет чужие стили

**Симптом:** после добавления модуля **другой** блок на странице сломался.

**Причина:** **глобальный** селектор без префикса.

**Плохо:**
```css
img { border-radius: 1rem; }  /* сломает ВСЕ img */
.card { padding: 1rem; }      /* сломает .card других модулей */
```

**Правильно:**
```css
.my-module img { border-radius: 1rem; }
.my-module .my-module-card { padding: 1rem; }
```

**Всегда** — **префикс** id модуля на **корневой** элемент, дальше — **вложенные** селекторы.

---

## 🔧 Полезные консольные команды

**Проверить уникальность индексов перед сохранением:**
```js
var c = document.getElementById('cards-rows-container');
var names = [...c.querySelectorAll('[name^="items["]')].map(i => i.name.match(/items\[\d+\]/)[0]);
console.log('Все индексы:', names);
console.log('Уникальные:', [...new Set(names)]);
```

**Найти следующий индекс:**
```js
window.constructor.getNextIndex(document.getElementById('cards-rows-container'), 'items');
```

**Посмотреть данные модуля:**
```js
// Открой JSON страницы в админке через файловый менеджер
// Или в конструкторе:
window.constructor.data.find(r => r.zone === 'main').columns[0].modules;
```

**Проверить, что `initListModule` вызвался:**
```js
typeof window.initListModule; // 'function'
```

---

## 📌 Чек-лист перед сдачей модуля

- [ ] **`manifest.json`** — валидный, все обязательные поля.
- [ ] **`id`** — `kebab-case`, не совпадает с существующими.
- [ ] **Форма** — все поля имеют `??`, экранирование через `e()`.
- [ ] **Список** — использует `<template>`, `__INDEX__`, `initListModule`.
- [ ] **JS модуля** — IIFE + readyState.
- [ ] **`site/module.php`** — `return` при пустых данных, `array_values(array_filter(...))`.
- [ ] **`site/script.js`** — цикл по `.my-module` (множественные инстансы).
- [ ] **CSS** — с префиксом модуля, не глобальные селекторы.
- [ ] **Проверено на мобильном**.
- [ ] **Проверено с двумя инстансами** на одной странице.
- [ ] **Протестирован сценарий** «add → remove → add → save → reopen».
- [ ] **Кеш** — очищен перед финальной проверкой.
