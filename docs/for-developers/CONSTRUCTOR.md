# Конструктор страниц — внутреннее устройство

## 🏗 Модель данных

### `data/pages/{id}.json`

```json
{
    "id": "home",
    "title": "Главная",
    "slug": "",
    "template": "full-width",
    "status": "published",
    "meta": {
        "description": "...",
        "keywords": "..."
    },
    "show_header": true,
    "show_footer": true,
    "rows": [
        {
            "id": "row_1",
            "zone": "main",
            "settings": { "class": "bg-slate-50" },
            "columns": [
                {
                    "id": "col_1",
                    "width_class": "w-6/12",
                    "settings": { "class": "p-4" },
                    "modules": [
                        {
                            "id": "mod_1",
                            "type": "hero",
                            "data": { "title": "Hello" },
                            "settings": { "class": "my-4" },
                            "global": false
                        }
                    ]
                }
            ]
        }
    ]
}
```

### Три уровня

| Уровень | Ключ | Что |
|---------|------|-----|
| **Ряд** | `rows[]` | Горизонтальная полоса |
| **Колонка** | `rows[].columns[]` | Блок внутри ряда |
| **Модуль** | `rows[].columns[].modules[]` | Контент |

---

## 🎨 Frontend (`constructor.js`)

### Класс `PageConstructor`

**Инициализация:**

```js
this.app = document.getElementById('constructor-app');
this.pageId = this.app.dataset.pageId;
this.token = this.app.dataset.token;
this.modules = JSON.parse(this.app.dataset.modules || '{}');
this.data = JSON.parse(document.getElementById('constructor-data')?.value || '[]');
```

**`this.data`** — **массив** **рядов** **из** `constructor-data` (**JSON**).

### `_jsId` — временный ID

**Для** **каждого** **элемента** **генерируется** **уникальный** `_jsId`:

```js
function generateJsId(prefix) {
    return prefix + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
}
```

**`_jsId`** **не** **сохраняется** **в** `pages/{id}.json` — **только** **для** **DOM**.

**Валидация** **при** `init()`:
```js
var idPattern = /^(row|col|mod)_\d+_[a-z0-9]+$/;
if (!row._jsId || !idPattern.test(row._jsId)) {
    row._jsId = generateJsId('row');
}
```

### Рендеринг

**`render()`** **проходит** **по** **зонам** (`constructor-zone`) **и** **рендерит** **ряды**:

```js
const zones = document.querySelectorAll('.constructor-zone');
zones.forEach(zoneEl => {
    const zoneName = zoneEl.dataset.zone;
    const container = zoneEl.querySelector('.constructor-rows');
    const rows = this.data.filter(row => row.zone === zoneName);
    container.innerHTML = rows.map(row => this.renderRow(row)).join('');
});
```

**`renderRow()`** → **`renderColumn()`** → **`renderModule()`** — **шаблонные** **строки** **с** `escapeHtml()`.

### Обновление данных

**`updateFormData()`** — **синхронизирует** `this.data` **с** **скрытыми** **полями**:

```js
const rowsInput = document.getElementById('rows-json-input');
if (rowsInput) rowsInput.value = JSON.stringify(this.data);

const dataEl = document.getElementById('constructor-data');
if (dataEl) dataEl.value = JSON.stringify(this.data);
```

**`rows-json-input`** — **скрытое** **поле** **формы** **страницы** (**идёт** **на** **сервер**).
**`constructor-data`** — **для** **JS** (**при** **переключении** **шаблона**).

### Действия

| Метод | Что делает |
|-------|-----------|
| `addRow(zone)` | Добавить ряд |
| `deleteRow(jsRowId)` | Удалить ряд |
| `moveRow(jsRowId, dir)` | Переместить |
| `addColumn(jsRowId)` | Добавить колонку |
| `deleteColumn(jsColId)` | Удалить колонку |
| `changeColumnWidth(jsColId, dir)` | Изменить ширину |
| `addModuleToColumn(jsColId, type)` | Добавить модуль |
| `deleteModule(jsModId)` | Удалить модуль |
| `openModuleSettings(jsModId)` | Открыть настройки |
| `saveModuleSettings()` | Сохранить настройки |

### Модалки

- **`#module-select-modal`** — выбор модуля.
- **`#module-settings-modal`** — настройки модуля.

**Открытие** **настроек** — **AJAX** **на** `handleGetModuleSettings()`:

```js
const formData = new FormData();
formData.append('csrf_token', token);
formData.append('module_type', moduleType);
formData.append('data', JSON.stringify(payload));

fetch('index.php?tab=pages&action=edit&id=' + this.pageId + '&tab_edit=constructor&ajax=module_settings', {
    method: 'POST',
    body: formData
})
```

---

## 🖥 Backend

### `handleGetModuleSettings()`

**`config/core/admin_controller.php`:**

1. **CSRF-проверка** (**для** POST).
2. **Валидация** `$moduleType` (**regex**).
3. **Проверка** **существования** `config/modules/{id}/module.php`.
4. **Подключение** **CSS**/**JS** **модуля**.
5. **`include`** **файла** **module.php** **с** **буферизацией**.
6. **Возврат** **HTML** **в** **JSON**.

```php
if (!preg_match('~^[a-z0-9\-_]+$~i', $moduleType)) {
    echo json_encode(['success' => false, 'error' => 'Невалидный ID модуля']);
    exit;
}

$moduleDir = APP_ROOT . '/config/modules/' . $moduleType . '/';
$moduleFile = $moduleDir . 'module.php';

if (!file_exists($moduleFile)) {
    echo json_encode(['success' => false, 'error' => 'Модуль не найден']);
    exit;
}

ob_start();

if (file_exists($moduleDir . 'style.css')) {
    echo '<link rel="stylesheet" href="/config/modules/' . $moduleType . '/style.css">';
}

include $moduleFile;

if (file_exists($moduleDir . 'script.js')) {
    echo '<script src="/config/modules/' . $moduleType . '/script.js"></script>';
}

$html = ob_get_clean();
echo json_encode(['success' => true, 'html' => $html]);
```

### `handleSavePage()`

**Сохранение** **страницы:**

1. **Валидация** `title`, `slug`, `template`, `status`.
2. **Проверка** **уникальности** `slug`.
3. **Сохранение** **в** `data/pages/{id}.json`.
4. **Очистка** **кеша** **страницы**.
5. **Логирование**.

**`rows`** **приходят** **в** `$_POST['rows_json']` (**JSON-строка**):

```php
$rowsJson = $_POST['rows_json'] ?? '';
$rows = [];
if (!empty($rowsJson)) {
    $rows = json_decode($rowsJson, true);
    if (!is_array($rows)) $rows = [];
}
```

---

## 🎯 Схема потока

### Добавление модуля

```
Клик "Модуль" в колонке
        ↓
openModuleModalForColumn(colId)
        ↓
Модалка выбора модуля
        ↓
Клик по модулю → selectModule(type)
        ↓
addModuleToColumn(colId, type)
        ↓
checkModule (AJAX: check_module)
        ↓
Если не распакован → unpack_module (AJAX POST)
        ↓
doAddModule() → this.data.push({...})
        ↓
render() → перерисовка DOM
        ↓
updateFormData() → скрытое поле rows_json
        ↓
[Пользователь жмёт "Сохранить"]
        ↓
POST form → handleSavePage() → data/pages/{id}.json
```

### Открытие настроек модуля

```
Клик ✏️ в карточке модуля
        ↓
openModuleSettings(jsModId)
        ↓
POST AJAX: module_settings
        ↓
handleGetModuleSettings()
        ↓
include config/modules/{type}/module.php
        ↓
HTML → модалка
        ↓
[Пользователь заполняет форму]
        ↓
saveModuleSettings()
        ↓
this.data[mod].data = {...}
        ↓
updateFormData()
```

### Смена шаблона

```
Изменение select #constructor-template-select
        ↓
changeTemplate()
        ↓
GET AJAX: ?tab=pages&action=edit&tab_edit=constructor&ajax=1&template=NEW
        ↓
index.php → include constructor.php
        ↓
Новый HTML конструктора
        ↓
Сохранение старого this.data
        ↓
initConstructor() с новым DOM
        ↓
render() с сохранёнными данными
```

---

## ⚠️ Особенности

### Desktop + Mobile

**В** **админке** — **две** **версии** **вывода** (`.desktop-only`, `.mobile-only`).
**JS** **работает** **с** **обеими** (**одинаковые** **data-атрибуты**).

**Дублирование** **HTML** — **цена** **за** **адаптивность** **без** **JS** (**если** **JS** **отключён**).

### Кеш

**После** **сохранения** **страницы** — **`clearPageCache()`** **очищает**:
- `cache/pages/{slug}.html`.
- `cache/assets/{pageId}.css`, `.js`.

### Валидация ID

**`_jsId`** — **генерируется** **JS**, **валидируется** **regex** **при** **init**.

**`id`** (**постоянный**) — **от** **пользователя**, **экранируется** `e()`.

---

## 📞 Поддержка

- **Архитектура:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **API:** [API.md](API.md)
- **Модули:** [MODULES.md](MODULES.md)