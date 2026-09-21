/**
 * Калькулятор корма — скрипты для сайта
 */
(function() {
    'use strict';

    function initCalculator() {
        if (typeof window.setPetType !== 'undefined') return;
        
        let currentPetType = 'dog';

        window.setPetType = function(type) {
            currentPetType = type;
            const dogBtn = document.getElementById('pet-dog');
            const catBtn = document.getElementById('pet-cat');
            const range = document.getElementById('weight-range');

            if (type === 'dog') {
                dogBtn.className = "flex-1 py-4 px-6 rounded-2xl flex flex-col items-center justify-center gap-3 border transition-all border-[var(--primary-color)] bg-emerald-50 text-[var(--primary-dark)] shadow-sm cursor-pointer";
                catBtn.className = "flex-1 py-4 px-6 rounded-2xl flex flex-col items-center justify-center gap-3 border transition-all border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50 cursor-pointer";
                range.max = 80;
            } else {
                catBtn.className = "flex-1 py-4 px-6 rounded-2xl flex flex-col items-center justify-center gap-3 border transition-all border-[var(--primary-color)] bg-emerald-50 text-[var(--primary-dark)] shadow-sm cursor-pointer";
                dogBtn.className = "flex-1 py-4 px-6 rounded-2xl flex flex-col items-center justify-center gap-3 border transition-all border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50 cursor-pointer";
                range.max = 20;
            }

            let currentValue = Number(range.value);
            let maxValid = Number(range.max);
            if (currentValue > maxValid) {
                range.value = maxValid;
            }
            updateWeight(range.value);
        };

        window.updateWeight = function(val) {
            document.getElementById('weight-val').innerText = val + ' кг';
            
            const range = document.getElementById('weight-range');
            const min = Number(range.min) || 0;
            const max = Number(range.max) || 100;
            const current = Number(val);

            let perc = ((current - min) / (max - min)) * 100;
            const thumbWidth = 16;
            let offset = thumbWidth * (0.5 - perc / 100);
            let absOffsetStr = Math.abs(offset).toFixed(2);
            let percStr = perc.toFixed(2);
            let sign = offset >= 0 ? '+' : '-';
            let bgPos = `calc(${percStr}% ${sign} ${absOffsetStr}px)`;

            range.style.background = `linear-gradient(to right, var(--primary-color) ${bgPos}, rgb(241 245 249 / var(--tw-bg-opacity, 1)) ${bgPos})`;
            
            checkAndReset();
        };

        window.calculateFeed = function() {
            const weight = Number(document.getElementById('weight-range').value);
            const age = document.getElementById('age-select').value;
            const activity = document.getElementById('activity-select').value;
            let base = 12;

            if (currentPetType === 'dog') {
                if (weight <= 5) base = 22;
                else if (weight <= 20) base = 15;
                else if (weight <= 40) base = 11;
                else base = 9;
            } else {
                if (weight <= 3) base = 15;
            }

            let ageMult = age === 'puppy' ? 1.5 : (age === 'senior' ? 0.8 : 1);
            let actMult = activity === 'low' ? 0.8 : (activity === 'high' ? 1.4 : 1);
            const result = Math.round(weight * base * ageMult * actMult);

            document.getElementById('calc-result-box').innerHTML = `
                <h3 class="text-sm font-bold uppercase tracking-widest text-slate-500 mb-4">Суточная норма</h3>
                <div class="text-6xl font-black text-[var(--primary-dark)] mb-6">${result} <span class="text-2xl font-semibold text-slate-400">г</span></div>
                <p class="text-sm text-slate-600 font-medium bg-white/60 p-4 rounded-2xl backdrop-blur-sm">Разделите на 2-3 приема пищи в день.</p>
            `;
            document.getElementById('calc-disclaimer').classList.remove('hidden');
        };

        window.checkAndReset = function() {
            const disclaimer = document.getElementById('calc-disclaimer');
            if (disclaimer && !disclaimer.classList.contains('hidden')) {
                disclaimer.classList.add('hidden');
                document.getElementById('calc-result-box').innerHTML = `
                    <p class="text-slate-500 font-medium leading-relaxed">
                        Заполните данные и нажмите кнопку расчета, чтобы получить рекомендацию.
                    </p>
                `;
            }
        };

        // ===== ИНИЦИАЛИЗАЦИЯ ПРИ ЗАГРУЗКЕ =====
        // Устанавливаем начальное значение ползунка
        var range = document.getElementById('weight-range');
        if (range) {
            // Обновляем прогресс-бар при загрузке
            setTimeout(function() {
                updateWeight(range.value);
            }, 100);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCalculator);
    } else {
        initCalculator();
    }
})();