<?php
/**
 * Модуль: Калькулятор корма — сайт
 */

$subtitle = $moduleData['subtitle'] ?? '';
$title = $moduleData['title'] ?? 'Калькулятор корма';
$description = $moduleData['description'] ?? '';
$showDisclaimer = $moduleData['show_disclaimer'] ?? true;
$disclaimer = $moduleData['disclaimer'] ?? '*Приведённые расчёты являются приблизительными. Рекомендуемые нормы кормления см. на упаковке конкретного корма. Перед изменением рациона проконсультируйтесь с ветеринаром.';

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

<div class="bg-white p-10 md:p-14 rounded-[3rem] shadow-[0_10px_40px_rgb(0,0,0,0.03)] border border-slate-100 max-w-5xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">
        <div class="lg:col-span-3 space-y-8">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wide">Кого мы кормим?</label>
                <div class="flex gap-4">
                    <button type="button" id="pet-dog" onclick="setPetType('dog')" class="flex-1 py-4 px-6 rounded-2xl flex flex-col items-center justify-center gap-3 border transition-all border-[var(--primary-color)] bg-emerald-50 text-[var(--primary-dark)] shadow-sm">
                        <span class="icon-dog text-3xl"></span>
                        <span class="font-medium">Собака</span>
                    </button>
                    <button type="button" id="pet-cat" onclick="setPetType('cat')" class="flex-1 py-4 px-6 rounded-2xl flex flex-col items-center justify-center gap-3 border transition-all border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50">
                        <span class="icon-cat text-3xl"></span>
                        <span class="font-medium">Кошка</span>
                    </button>
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-sm font-semibold text-slate-700 uppercase tracking-wide">Вес питомца</label>
                    <span id="weight-val" class="text-lg font-bold text-[var(--primary-color)]">10 кг</span>
                </div>
                <input type="range" id="weight-range" min="1" max="80" value="10" oninput="updateWeight(this.value)" class="w-full h-3 bg-slate-100 rounded-full appearance-none cursor-pointer accent-[var(--primary-color)] shadow-inner" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wide">Возраст</label>
                    <select id="age-select" onchange="checkAndReset()" class="w-full p-4 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent bg-slate-50 text-slate-700 font-medium outline-none transition-shadow">
                        <option value="puppy">До 1 года</option>
                        <option value="adult" selected>1 - 7 лет</option>
                        <option value="senior">Старше 7 лет</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wide">Активность</label>
                    <select id="activity-select" onchange="checkAndReset()" class="w-full p-4 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent bg-slate-50 text-slate-700 font-medium outline-none transition-shadow">
                        <option value="low">Низкая</option>
                        <option value="moderate" selected>Умеренная</option>
                        <option value="high">Высокая</option>
                    </select>
                </div>
            </div>
            <button type="button" onclick="calculateFeed()" class="w-full rounded-xl border border-[var(--primary-color)] text-[var(--primary-color)] font-semibold hover:bg-[var(--primary-color)] hover:text-white transition-colors text-lg mt-4 shadow-lg shadow-[var(--primary-color)]/30 py-4">Рассчитать норму</button>
        </div>
        <div class="lg:col-span-2 bg-gradient-to-br from-emerald-50 to-teal-50/50 p-10 rounded-[2.5rem] flex flex-col items-center justify-center text-center relative overflow-hidden">
            <div class="w-24 h-24 bg-white rounded-[2rem] flex items-center justify-center mb-8 shadow-sm rotate-3">
                <span class="icon-scale text-4xl text-[var(--primary-color)] -rotate-3"></span>
            </div>
            <div id="calc-result-box" class="relative z-10">
                <p class="text-slate-500 font-medium leading-relaxed">Заполните данные и нажмите кнопку расчета, чтобы получить рекомендацию.</p>
            </div>
        </div>
    </div>
    <?php if ($showDisclaimer && !empty($disclaimer)): ?>
    <div id="calc-disclaimer" class="w-full mt-4 border-t border-slate-200/60 relative z-10 hidden">
        <p class="text-xs leading-relaxed text-slate-400 pt-4">
            <?php echo e($disclaimer); ?>
        </p>
    </div>
    <?php endif; ?>
</div>