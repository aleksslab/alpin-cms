<?php
/**
 * Модуль: FAQ (Вопрос-Ответ) — админка
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? 'Часто задаваемые вопросы';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$openFirst = $moduleData['open_first'] ?? true;
$allowMultiple = $moduleData['allow_multiple'] ?? false;
$allowHtml = $moduleData['allow_html'] ?? true;
$settingsClass = $moduleData['settings']['class'] ?? '';

if (empty($items)) {
    $items = [
        ['question' => '', 'answer' => '']
    ];
}
?>

<div class="space-y-4">
    <!-- БЛОК 1: Основной контент -->
    <div class="editor-row">
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Подзаголовок</label>
            <input type="text" name="subtitle" value="<?php echo e($subtitle); ?>" placeholder="Краткое описание блока" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
        <div class="editor-field">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Заголовок</label>
            <input type="text" name="title" value="<?php echo e($title); ?>" placeholder="Часто задаваемые вопросы" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
        </div>
    </div>

    <div>
        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Описание</label>
        <textarea name="description" rows="2" placeholder="Краткое описание блока..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]"><?php echo e($description); ?></textarea>
    </div>

    <!-- БЛОК 2: Настройки отображения -->
    <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
        <h4 class="text-sm font-bold text-slate-700 mb-2">Настройки отображения</h4>
        
        <div class="editor-row">
            <div class="editor-field">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Класс модуля</label>
                <input type="text" name="settings_class" value="<?php echo e($settingsClass); ?>" placeholder="my-4, container..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
            </div>
            <div class="editor-field">
                <!-- Пусто для выравнивания -->
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="open_first" value="0">
                <input type="checkbox" name="open_first" value="1" <?php echo $openFirst ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                <span class="text-sm text-slate-600">Открыть первый</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="allow_multiple" value="0">
                <input type="checkbox" name="allow_multiple" value="1" <?php echo $allowMultiple ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                <span class="text-sm text-slate-600">Можно открыть несколько</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="allow_html" value="0">
                <input type="checkbox" name="allow_html" value="1" <?php echo $allowHtml ? 'checked' : ''; ?> class="w-4 h-4 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)]">
                <span class="text-sm text-slate-600">Разрешить HTML в ответах</span>
            </label>
        </div>
    </div>

    <!-- БЛОК 3: Вопросы -->
    <div id="faq-items-container" class="space-y-3">
        <?php 
        $itemTemplate = '
        <div class="faq-item-card bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="js-faq-num text-xs font-bold text-[var(--primary-color)] uppercase tracking-wider">Вопрос #{INDEX}</span>
                <button type="button" class="js-remove-faq w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 hover:bg-rose-50 transition-all cursor-pointer" title="Удалить вопрос">
                    <span class="icon-x text-sm"></span>
                </button>
            </div>
            
            <div class="editor-row">
                <div class="editor-field">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Вопрос</label>
                    <input type="text" name="items[{INDEX}][question]" value="{QUESTION}" placeholder="Введите вопрос..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">
                </div>
            </div>
            
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ответ</label>
                <textarea name="items[{INDEX}][answer]" rows="3" placeholder="Введите ответ..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-[var(--primary-color)]">{ANSWER}</textarea>
                <p class="text-[10px] text-slate-400 mt-1 js-html-hint">Допустимы HTML-теги: &lt;b&gt;, &lt;i&gt;, &lt;u&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;span&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;p&gt;, &lt;br&gt;, &lt;a&gt;, &lt;img&gt;</p>
            </div>
        </div>';
        ?>

        <?php foreach ($items as $idx => $item): ?>
            <?php 
            $num = $idx + 1;
            echo str_replace(
                ['{INDEX}', '{QUESTION}', '{ANSWER}'],
                [$num, e($item['question'] ?? ''), e($item['answer'] ?? '')],
                $itemTemplate
            );
            ?>
        <?php endforeach; ?>
    </div>

    <template id="tmpl-faq-item">
        <?php echo str_replace(
            ['{INDEX}', '{QUESTION}', '{ANSWER}'],
            ['__INDEX__', '', ''],
            $itemTemplate
        ); ?>
    </template>

    <button type="button" id="add-faq-btn" class="w-full py-2.5 border border-dashed border-slate-300 text-slate-500 font-medium rounded-lg hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition-all cursor-pointer text-sm flex items-center justify-center gap-2">
        <span class="icon-plus text-sm"></span> Добавить вопрос
    </button>
</div>