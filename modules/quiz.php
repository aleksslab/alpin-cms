<?php
/**
 * Модуль: Квиз — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? '';
$description = $moduleData['description'] ?? '';
$questions = $moduleData['questions'] ?? [];
$fields = $moduleData['fields'] ?? [];
$goal = $moduleData['goal'] ?? 'qualification';
$successMessage = $moduleData['success_message'] ?? '';
$showPrivacyPolicy = $moduleData['show_privacy_policy'] ?? true;

if (!is_array($questions) || empty($questions)) {
    return;
}

// Преобразуем options из строки в массив
foreach ($questions as $key => $q) {
    if (isset($q['options']) && is_string($q['options'])) {
        $questions[$key]['options'] = explode("\n", trim($q['options']));
        $questions[$key]['options'] = array_filter(array_map('trim', $questions[$key]['options']));
    }
}

// Собираем обязательные поля для контактов
$requiredFields = [];
foreach ($fields as $field) {
    if (!empty($field['required']) && !empty($field['type'])) {
        $requiredFields[] = $field['type']; // name, phone, email
    }
}

$hasHeader = !empty($subtitle) || !empty($title) || !empty($description);
$uniqueId = 'quiz-' . md5(json_encode($questions) . json_encode($fields));
$totalQuestions = count($questions);
$token = $_SESSION['csrf_token'] ?? '';
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

<div id="<?php echo $uniqueId; ?>" class="quiz-container max-w-2xl mx-auto" 
     data-total="<?php echo $totalQuestions; ?>"
     data-title="<?php echo e($title); ?>"
     data-csrf="<?php echo $token; ?>"
     data-success-message="<?php echo e($successMessage); ?>"
     data-show-privacy="<?php echo $showPrivacyPolicy ? 'true' : 'false'; ?>">
    
    <!-- Прогресс-бар -->
    <div class="mb-8">
        <div class="flex justify-between text-sm text-slate-500 mb-2">
            <span>Вопрос <span class="quiz-current">1</span> из <span class="quiz-total"><?php echo $totalQuestions; ?></span></span>
            <span class="quiz-progress-text">0%</span>
        </div>
        <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
            <div class="quiz-progress-bar h-full bg-[var(--primary-color)] rounded-full transition-all duration-500" style="width: 0%"></div>
        </div>
    </div>

    <!-- Вопросы -->
    <div class="quiz-questions">
        <?php foreach ($questions as $index => $q):
            $qIndex = $index + 1;
            $type = $q['type'] ?? 'single';
            $options = is_array($q['options']) ? $q['options'] : [];
            $required = !empty($q['required']);
        ?>
        <div class="quiz-question <?php echo $qIndex === 1 ? 'active' : ''; ?>" data-index="<?php echo $qIndex; ?>" data-required="<?php echo $required ? 'true' : 'false'; ?>">
            <h3 class="text-xl font-bold text-slate-800 mb-4"><?php echo e($q['text'] ?? ''); ?></h3>
            
            <?php if ($type === 'single'): ?>
                <div class="space-y-3">
                    <?php foreach ($options as $opt): ?>
                        <?php if (!empty(trim($opt))): ?>
                        <label class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-xl hover:border-[var(--primary-color)] transition-all cursor-pointer">
                            <input type="radio" name="quiz_<?php echo $qIndex; ?>" value="<?php echo e(trim($opt)); ?>" class="quiz-radio w-5 h-5 text-[var(--primary-color)] focus:ring-[var(--primary-color)]">
                            <span class="text-slate-700"><?php echo e(trim($opt)); ?></span>
                        </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($type === 'multiple'): ?>
                <div class="space-y-3">
                    <?php foreach ($options as $opt): ?>
                        <?php if (!empty(trim($opt))): ?>
                        <label class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-xl hover:border-[var(--primary-color)] transition-all cursor-pointer">
                            <input type="checkbox" name="quiz_<?php echo $qIndex; ?>[]" value="<?php echo e(trim($opt)); ?>" class="quiz-checkbox w-5 h-5 text-[var(--primary-color)] rounded focus:ring-[var(--primary-color)]">
                            <span class="text-slate-700"><?php echo e(trim($opt)); ?></span>
                        </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($type === 'text'): ?>
                <textarea name="quiz_<?php echo $qIndex; ?>" rows="4" placeholder="Введите ваш ответ..." class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:border-[var(--primary-color)] focus:outline-none transition-all resize-y"></textarea>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Навигация -->
    <div class="flex justify-between mt-8 gap-4">
        <button type="button" class="quiz-prev px-6 py-3 bg-slate-100 text-slate-600 font-bold rounded-full hover:bg-slate-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed">Назад</button>
        <button type="button" class="quiz-next px-6 py-3 bg-[var(--primary-color)] text-white font-bold rounded-full hover:bg-[var(--primary-dark)] transition-all disabled:opacity-50 disabled:cursor-not-allowed">Далее</button>
    </div>

    <!-- Результаты -->
    <div class="quiz-results hidden mt-8">
        <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm text-center">
            <!-- Поля для сбора данных -->
            <?php if (!empty($fields)): ?>
            <form class="quiz-fields text-left max-w-md mx-auto" id="quiz-form">
                <input type="hidden" name="csrf_token" value="<?php echo $token; ?>" />
                <input type="hidden" name="module" value="quiz" />
                <input type="hidden" name="required_fields" value='<?php echo json_encode($requiredFields); ?>' />
                <input type="hidden" name="success_message" value="<?php echo e($successMessage); ?>" />
                
                <!-- Мотивирующий текст -->
                <p class="text-center text-slate-600 font-medium mb-6">Оставьте свои контактные данные, и мы свяжемся с вами</p>
                
                <?php foreach ($fields as $field):
                    $fieldType = $field['type'] ?? 'text';
                    $label = $field['label'] ?? '';
                    $required = !empty($field['required']);
                    $requiredAttr = $required ? 'required' : '';
                ?>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1"><?php echo e($label); ?> <?php echo $required ? '<span class="text-rose-500">*</span>' : ''; ?></label>
                    <?php if ($fieldType === 'name'): ?>
                        <input type="text" name="name" placeholder="Введите имя..." class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:border-[var(--primary-color)] focus:outline-none transition-all" <?php echo $requiredAttr; ?>>
                    <?php elseif ($fieldType === 'phone'): ?>
                        <input type="tel" name="phone" placeholder="+7 (___) ___-__-__" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:border-[var(--primary-color)] focus:outline-none transition-all" <?php echo $requiredAttr; ?>>
                    <?php elseif ($fieldType === 'email'): ?>
                        <input type="email" name="email" placeholder="email@example.com" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:border-[var(--primary-color)] focus:outline-none transition-all" <?php echo $requiredAttr; ?>>
                    <?php else: ?>
                        <input type="text" name="<?php echo e($fieldType); ?>" placeholder="Введите <?php echo e(mb_strtolower($label)); ?>..." class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:border-[var(--primary-color)] focus:outline-none transition-all" <?php echo $requiredAttr; ?>>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Чекбокс согласия с политикой конфиденциальности -->
                <?php if ($showPrivacyPolicy): ?>
                <div class="flex items-start gap-3 mb-6">
                    <input type="checkbox" name="privacy_policy" id="privacy_policy_quiz" required class="w-5 h-5 mt-0.5 text-[var(--primary-color)] rounded border-slate-300 focus:ring-[var(--primary-color)] flex-shrink-0">
                    <label for="privacy_policy_quiz" class="text-sm text-slate-500">
                        Я соглашаюсь с <button type="button" onclick="openPolicy('privacy')" class="text-[var(--primary-color)] hover:underline font-medium">политикой конфиденциальности</button>
                    </label>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="quiz-submit w-full px-6 py-3 bg-[var(--primary-color)] text-white font-bold rounded-full hover:bg-[var(--primary-dark)] transition-all">Отправить</button>
                <div class="quiz-submit-message hidden mt-4 text-center font-bold text-emerald-600"></div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>