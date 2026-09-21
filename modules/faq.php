<?php
/**
 * Модуль: FAQ (Вопрос-Ответ) — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? 'Часто задаваемые вопросы';
$description = $moduleData['description'] ?? '';
$items = $moduleData['items'] ?? [];
$openFirst = $moduleData['open_first'] ?? true;
$allowMultiple = $moduleData['allow_multiple'] ?? false;
$allowHtml = $moduleData['allow_html'] ?? true;

if (empty($items)) {
    return;
}

$items = array_filter($items, function($item) {
    return !empty($item['question']) || !empty($item['answer']);
});

if (empty($items)) {
    return;
}

$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);
?>

<?php if ($hasHeader): ?>
<div class="text-center max-w-3xl mx-auto mb-12">
    <?php if (!empty($subtitle)): ?>
    <span class="text-[var(--primary-color)] font-bold tracking-wider uppercase text-sm block mb-2"><?php echo e($subtitle); ?></span>
    <?php endif; ?>

    <?php if (!empty($title)): ?>
    <h2 class="text-3xl md:text-4xl font-bold text-slate-800 mb-4"><?php echo e($title); ?></h2>
    <?php endif; ?>

    <?php if (!empty($description)): ?>
    <p class="text-[var(--text-muted)] max-w-2xl mx-auto text-lg"><?php echo e($description); ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="faq-module max-w-3xl mx-auto space-y-3" data-open-first="<?php echo $openFirst ? 'true' : 'false'; ?>" data-allow-multiple="<?php echo $allowMultiple ? 'true' : 'false'; ?>">
    <?php foreach ($items as $index => $item): ?>
        <?php 
        $isOpen = $openFirst && $index === 0;
        $question = $item['question'] ?? '';
        $answer = $item['answer'] ?? '';
        ?>
        <div class="faq-item border border-slate-200 rounded-xl overflow-hidden bg-white <?php echo $isOpen ? 'is-open' : ''; ?>">
            <button type="button" class="faq-question w-full px-6 py-4 text-left flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                <span class="text-base font-semibold text-slate-800"><?php echo e($question); ?></span>
                <span class="faq-icon flex-shrink-0 w-6 h-6 rounded-full border border-slate-300 flex items-center justify-center text-slate-500 transition-transform duration-300 <?php echo $isOpen ? 'rotate-45' : ''; ?>">
                    <span class="icon-plus text-sm"></span>
                </span>
            </button>
            <div class="faq-answer overflow-hidden transition-all duration-300 ease-in-out" style="max-height: 0; opacity: 0;">
                <div class="px-6 pb-4 text-slate-600 leading-relaxed">
                    <?php if ($allowHtml): ?>
                        <?php echo sanitizeHtml($answer); ?>
                    <?php else: ?>
                        <?php echo nl2br(e($answer)); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>