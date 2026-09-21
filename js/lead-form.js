/**
 * Форма захвата лидов — скрипты для сайта
 */
(function() {
    'use strict';

    function initLeadForm() {
        const form = document.getElementById('leadForm');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Проверяем чекбокс политики
            const privacyCheckbox = form.querySelector('input[name="privacy_policy"]');
            if (privacyCheckbox && !privacyCheckbox.checked) {
                privacyCheckbox.style.borderColor = '#f43f5e';
                privacyCheckbox.focus();
                const label = privacyCheckbox.closest('.flex').querySelector('label');
                if (label) {
                    label.style.color = '#f43f5e';
                    setTimeout(function() {
                        label.style.color = '';
                        privacyCheckbox.style.borderColor = '';
                    }, 3000);
                }
                return;
            }
            
            sendLeadForm(e.target);
        });
    }

    function sendLeadForm(form) {
        const fields = document.getElementById('leadFormFields');
        const messagesContainer = document.getElementById('lead-form-messages');
        if (!fields || !messagesContainer) return;

        messagesContainer.innerHTML = '';

        const formData = new FormData(form);
        formData.append('module', 'lead-form');
        
        const headerEl = document.querySelector('.lead-form-module h2') || document.querySelector('.max-w-2xl.mx-auto h2');
        formData.append('module_title', headerEl ? headerEl.textContent : 'Форма захвата лидов');
        
        fields.classList.add('hidden');

        const loader = document.createElement('div');
        loader.id = 'form-loading-lead';
        loader.className = 'w-full py-12 flex flex-col items-center justify-center text-center space-y-4 animate-fade-in';
        loader.innerHTML = `
            <span class="loader"></span>
            <p class="text-slate-500 font-medium text-sm tracking-wide uppercase">Отправка...</p>
        `;
        messagesContainer.appendChild(loader);

        fetch('includes/mailer.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            if (!response.ok) throw new Error();
            return response.json();
        })
        .then(function(data) {
            const loaderEl = document.getElementById('form-loading-lead');
            if (loaderEl) loaderEl.remove();

            const isSuccess = data.status === 'success';
            const borderClass = isSuccess ? 'border-emerald-100' : 'border-slate-200';
            const icon = isSuccess ? 'icon-shield-check text-emerald-500' : 'icon-x text-slate-400';
            const text = isSuccess ? 'text-[var(--primary-dark)]' : 'text-slate-800';

            const module = form.closest('.max-w-2xl.mx-auto');
            const customMessage = module ? module.dataset.successMessage : '';
            const message = isSuccess && customMessage ? customMessage : data.message;

            const msg = document.createElement('div');
            msg.className = `form-messages w-full bg-white border ${borderClass} p-8 rounded-2xl flex flex-col items-center justify-center text-center space-y-4 shadow-sm animate-fade-in`;
            msg.innerHTML = `
                <div class="w-14 h-14 rounded-full bg-slate-50 flex items-center justify-center shadow-inner">
                    <span class="${icon} text-2xl"></span>
                </div>
                <h4 class="text-xl font-bold ${text}">${data.head}</h4>
                <p class="text-slate-600 text-sm font-medium leading-relaxed max-w-sm">${message}</p>
            `;
            messagesContainer.appendChild(msg);

            if (isSuccess) form.reset();
        })
        .catch(function() {
            const loaderEl = document.getElementById('form-loading-lead');
            if (loaderEl) loaderEl.remove();

            const msg = document.createElement('div');
            msg.className = 'form-messages w-full bg-white border border-slate-200 p-8 rounded-2xl flex flex-col items-center justify-center text-center space-y-4 shadow-sm';
            msg.innerHTML = `
                <div class="w-14 h-14 rounded-full bg-slate-50 flex items-center justify-center shadow-inner">
                    <span class="icon-x text-rose-500 text-2xl"></span>
                </div>
                <h4 class="text-xl font-bold text-slate-800">Ошибка соединения</h4>
                <p class="text-slate-600 text-sm font-medium leading-relaxed max-w-sm">Не удалось отправить обращение. Проверьте интернет или попробуйте позже.</p>
            `;
            messagesContainer.appendChild(msg);
        })
        .finally(function() {
            setTimeout(function() {
                messagesContainer.innerHTML = '';
                fields.classList.remove('hidden');
            }, 10000);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLeadForm);
    } else {
        initLeadForm();
    }
})();