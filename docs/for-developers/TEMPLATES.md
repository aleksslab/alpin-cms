# Шаблоны хедеров, футеров и страниц

## 📐 Структура шаблонов

```
templates/
├── base.php                # Универсальный рендер
├── full-width.php          # Обёртка над base.php
├── sidebar-left.php
├── sidebar-right.php
├── boxed.php
│
├── headers/
│   ├── default/
│   │   ├── header.php
│   │   ├── preview.png
│   │   └── manifest.json
│   ├── centered/
│   ├── minimal/
│   └── with-cta/
│
└── footers/
    ├── default/
    ├── minimal/
    ├── dark/
    └── light/
```

---

## 🗂 Шаблоны страниц

### Как работает

1. **`index.php`** **загружает** **страницу** **и** **её** `template` (ID).
2. **`loadTemplate($id)`** **читает** **описание** **из** `config/data/templates.json`.
3. **`index.php`** **включает** **файл** `templates/{id}.php`.
4. **Файл** **шаблона** — **обёртка** **над** `base.php`, **задаёт** **$template**.

### `templates/full-width.php`

```php
<?php
/**
 * Шаблон "Полная ширина"
 */
$template = loadTemplate('full-width');
include __DIR__ . '/base.php';
```

### `config/data/templates.json`

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
        }
    ]
}
```

**Зоны:**
- **`main`** — **основная** **зона**.
- **`sidebar-left`** / **`sidebar-right`** — **боковые**.
- **`hero`** — **для** **обложки**.
- **`header`** / **`footer`** — **кастомные** **зоны** (**если** **отключены** **глобальные**).

**`class`** — **Tailwind-классы** **контейнера** **зоны**.

### Как добавить свой шаблон

1. **Создай** `templates/my-template.php`:

```php
<?php
/**
 * Мой шаблон
 */
$template = loadTemplate('my-template');
include __DIR__ . '/base.php';
```

2. **Добавь** **в** `config/data/templates.json`:

```json
{
    "id": "my-template",
    "name": "Мой шаблон",
    "zones": {
        "main": { "class": "container mx-auto px-6" }
    }
}
```

3. **Очисти** **кеш**.

**Готово** — **шаблон** **появится** **в** **селекте** **конструктора**.

---

## 🎨 Хедеры

### Структура

```
templates/headers/my-header/
├── header.php
├── preview.png       # 600×80 (опционально)
└── manifest.json
```

### `manifest.json`

```json
{
    "name": "Мой хедер",
    "description": "Описание хедера",
    "author": "Ваше имя",
    "version": "1.0.0"
}
```

### `header.php`

**Доступные** **переменные:**
- `$settings` — **настройки** **сайта**.
- `$site_name`, `$site_logo`, `$phone`, `$email` — **уже** **извлечены**.
- `$headerFixed` — **фиксированный** **ли** **хедер**.
- `$siteNamePlain` — **название** **без** **HTML**.

**Функции:**
- `getMainMenuId()` — **ID** **главного** **меню**.
- `getMenuItems($menuId)` — **пункты** **меню**.
- `renderMainMenu($items)` — **HTML** **desktop-меню**.
- `renderMobileMenu($items)` — **HTML** **мобильного**.
- `isBurgerEnabled()` — **включён** **ли** **бургер**.
- `getBurgerBreakpoint()` — **брейкпоинт** (`sm`/`md`/`lg`/`xl`).

### Пример (минимальный)

```php
<?php
$mainMenuId = getMainMenuId();
$menuItems = $mainMenuId ? getMenuItems($mainMenuId) : [];

$mainMenuHTML = renderMainMenu($menuItems);
$burgerMenuHTML = renderMobileMenu($menuItems);

$breakpoint = getBurgerBreakpoint();
?>
<header class="<?php echo $headerFixed ? 'sticky top-0 z-50 ' : ''; ?>bg-white border-b border-slate-100 h-20">
    <div class="container mx-auto px-6 h-20 flex items-center justify-between">
        <!-- Логотип -->
        <a href="/" class="flex items-center gap-3">
            <?php if (!empty($site_logo)): ?>
                <img src="<?php echo e($site_logo); ?>" alt="<?php echo e($siteNamePlain); ?>" class="h-10 w-auto">
            <?php else: ?>
                <span class="text-2xl font-black text-slate-800"><?php echo $site_name; ?></span>
            <?php endif; ?>
        </a>

        <!-- Меню -->
        <nav class="hidden <?php echo $breakpoint; ?>:flex items-center gap-8">
            <?php echo $mainMenuHTML; ?>
        </nav>

        <!-- Бургер -->
        <?php if (isBurgerEnabled()): ?>
            <button onclick="toggleMobileMenu()" class="<?php echo $breakpoint; ?>:hidden">
                <span id="burger-icon" class="icon-menu text-xl"></span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Мобильное меню -->
    <?php if (isBurgerEnabled()): ?>
        <div id="mobile-menu" class="hidden fixed inset-0 top-20 ...">
            <?php echo $burgerMenuHTML; ?>
        </div>
    <?php endif; ?>
</header>
```

### Установка хедера

1. **Скопируй** **папку** **в** `templates/headers/my-header/`.
2. **Проверь** `header.php`, `manifest.json`, `preview.png`.
3. **В** **админке** → **Настройки** **сайта** → **Настройки** **хедера** → **выбери** **вариант**.
4. **Сохрани**.

**Или** — **через** **установку** **модуля** (**если** **упакован** **в** **ZIP**).

---

## 🎨 Футеры

### Структура

```
templates/footers/my-footer/
├── footer.php
├── preview.png
└── manifest.json
```

### `manifest.json`

```json
{
    "name": "Мой футер",
    "description": "Описание футера",
    "author": "Ваше имя",
    "version": "1.0.0"
}
```

### `footer.php`

**Доступные** **переменные:**
- `$settings`, `$site_name`, `$site_logo`, `$phone`, `$email`, `$address`.
- `$copyrightText` — **текст** **копирайта**.
- `$site_slogan`.

**Функции:**
- `getMenuItems($menuId)` — **пункты** **меню** (**для** `footer_about`, `footer_help`, `brands`).
- `render_module('social-icons', ...)` — **иконки** **соцсетей**.

### Пример

```php
<footer class="bg-[#1A1E23] py-16 text-slate-300">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <!-- Логотип и слоган -->
            <div>
                <span class="text-2xl font-black text-white"><?php echo $site_name; ?></span>
                <p class="mt-4 text-sm"><?php echo $site_slogan; ?></p>
            </div>

            <!-- Меню -->
            <?php 
            $menuOrder = ['footer_about', 'footer_help', 'brands'];
            foreach ($menuOrder as $menuId):
                $items = getMenuItems($menuId);
                if (empty($items)) continue;
                $menu = loadMenu($menuId);
            ?>
                <div>
                    <h4 class="text-lg font-bold text-white mb-6"><?php echo e($menu['name'] ?? $menuId); ?></h4>
                    <ul class="space-y-3 text-sm">
                        <?php foreach ($items as $item): ?>
                            <li>
                                <a href="<?php echo e(safeUrl($item['url'] ?? '#')); ?>" class="hover:text-white">
                                    <?php echo e($item['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

            <!-- Контакты -->
            <div>
                <h4 class="text-lg font-bold text-white mb-6">Контакты</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="tel:<?php echo e($phone); ?>"><?php echo formatPhone($phone); ?></a></li>
                    <li><a href="mailto:<?php echo e($email); ?>"><?php echo e($email); ?></a></li>
                    <li><?php echo e($address); ?></li>
                </ul>
            </div>
        </div>

        <!-- Нижняя панель -->
        <div class="border-t border-slate-700 pt-8 flex justify-between">
            <p class="text-xs">&copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>. <?php echo $copyrightText; ?></p>
            <?php echo render_module('social-icons', [], ['class' => 'text-slate-300']); ?>
        </div>
    </div>
</footer>
```

---

## 📐 `base.php` — универсальный рендер

**`base.php`** — **файл**, **который** **рендерит** **страницу** **по** **шаблону**.

### Что делает

1. **Читает** `$template['zones']` — **зоны** **шаблона**.
2. **Извлекает** **чекаут** `show_header` / `show_footer`.
3. **Включает** **хедер** (`templates/headers/{variant}/header.php`).
4. **Группирует** **ряды** **по** **зонам**.
5. **Рендерит** **ряды** → **колонки** → **модули**.
6. **Включает** **футер** (`templates/footers/{variant}/footer.php`).

### Ключевые переменные

- `$page` — **данные** **страницы**.
- `$settings` — **настройки** **сайта**.
- `$template` — **текущий** **шаблон**.
- `$zoneName`, `$zoneConfig` — **итерация** **по** **зонам**.
- `$row`, `$col`, `$module` — **итерация** **по** **структуре**.

**Полный** **код** — `templates/base.php`.

---

## 🎯 Соглашения

### Стили

- **Tailwind** **на** **фронте** (**CDN**).
- **Переменные** **CSS** (`var(--primary-color)`).
- **Не** **свои** **CSS-файлы** **без** **необходимости**.

### Безопасность

- **Всегда** `e()` **при** **выводе** **данных**.
- **URL** — **через** `safeUrl()`.
- **HTML** **в** **названии** **сайта** — **уже** **санитизирован**.

### Адаптивность

- **Breakpoints** **Tailwind:** `sm:`, `md:`, `lg:`, `xl:`.
- **Мобильные** — **обязательно** **проверь**.

---

## 📞 Поддержка

- **Архитектура:** [ARCHITECTURE.md](ARCHITECTURE.md)
- **Модули:** [MODULES.md](MODULES.md)
- **GitHub Issues:** [github.com/AlekSSLab/alpin-cms/issues](https://github.com/AlekSSLab/alpin-cms/issues)