/**
 * Контакты — скрипты для сайта
 */
(function() {
    'use strict';

    let isSubmitting = false;

    function initContacts() {
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (isSubmitting) return;
                sendContactForm(e.target);
            });
        }
    }

    function sendContactForm(form) {
        if (isSubmitting) return;
        isSubmitting = true;

        const fields = document.getElementById('formFields');
        const messagesContainer = document.getElementById('contacts-form-messages');
        if (!fields || !messagesContainer) {
            isSubmitting = false;
            return;
        }

        const container = messagesContainer.closest('.id-form-container') || messagesContainer.parentElement;
        const originalHeight = container.offsetHeight;
        const originalWidth = container.offsetWidth;

        container.style.minHeight = originalHeight + 'px';
        container.style.minWidth = originalWidth + 'px';

        messagesContainer.innerHTML = '';

        const formData = new FormData(form);
        formData.append('module', 'contacts');
        
        // Находим заголовок модуля
        const headerEl = document.querySelector('.contacts-module h2') || document.querySelector('.grid h2');
        formData.append('module_title', headerEl ? headerEl.textContent : 'Контактная форма');
        
        fields.classList.add('hidden');

        const loader = document.createElement('div');
        loader.id = 'form-loading-contacts';
        loader.className = 'w-full py-12 flex flex-col items-center justify-center text-center space-y-4 animate-fade-in';
        loader.innerHTML = `
            <span class="loader"></span>
            <p class="text-slate-500 font-medium text-sm tracking-wide uppercase">Отправка вашего обращения...</p>
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
            const loaderEl = document.getElementById('form-loading-contacts');
            if (loaderEl) loaderEl.remove();

            const isSuccess = data.status === 'success';
            const borderClass = isSuccess ? 'border-emerald-100' : 'border-slate-200';
            const icon = isSuccess ? 'icon-shield-check text-emerald-500' : 'icon-x text-slate-400';
            const text = isSuccess ? 'text-[var(--primary-dark)]' : 'text-slate-800';

            const msg = document.createElement('div');
            msg.className = `form-messages w-full bg-white border ${borderClass} p-8 rounded-2xl flex flex-col items-center justify-center text-center space-y-4 shadow-sm animate-fade-in`;
            msg.innerHTML = `
                <div class="w-14 h-14 rounded-full bg-slate-50 flex items-center justify-center shadow-inner">
                    <span class="${icon} text-2xl"></span>
                </div>
                <h4 class="text-xl font-bold ${text}">${data.head}</h4>
                <p class="text-slate-600 text-sm font-medium leading-relaxed max-w-sm">${data.message}</p>
            `;
            messagesContainer.appendChild(msg);

            if (isSuccess) form.reset();
        })
        .catch(function() {
            const loaderEl = document.getElementById('form-loading-contacts');
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
                container.style.minHeight = '';
                container.style.minWidth = '';
                isSubmitting = false;
            }, 10000);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initContacts);
    } else {
        initContacts();
    }
})();