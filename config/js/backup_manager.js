// ==================== ЦЕНТРАЛЬНЫЙ КОНТРОЛЛЕР СИСТЕМЫ БЭКАПОВ ====================

/**
 * 1. ИНТЕРАКТИВНОЕ ПЕРЕКЛЮЧЕНИЕ ПОЛЕЙ ФОРМЫ ПРИ ЖИВЫХ КЛИКАХ
 */
function toggleFmBackupFields() {
    const backupForm = document.getElementById('js-backup-form');
    const encryptToggle = document.getElementById('js-backup-encrypt-toggle');
    const passwordZone = document.getElementById('js-backup-password-zone');
    const passwordInput = document.getElementById('js-backup-password-input');
    const targetsZone = document.getElementById('js-backup-targets-zone');

    if (!backupForm) return;

    // Считываем активную радио-кнопку режима
    const activeTypeRadio = backupForm.querySelector('input[name="backup_type"]:checked');
    
    if (activeTypeRadio && activeTypeRadio.value === 'full') {
        if (targetsZone) {
            targetsZone.classList.add('hidden');
        }
    } else {
        if (targetsZone) {
            targetsZone.classList.remove('hidden');
        }
    }

    // Управление видимостью зоны пароля через ваш системный класс .hidden
    if (encryptToggle && encryptToggle.checked) {
        if (passwordZone) {
            passwordZone.classList.remove('hidden');
            if (passwordInput) passwordInput.required = true;
        }
    } else {
        if (passwordZone) {
            passwordZone.classList.add('hidden');
            if (passwordInput) {
                passwordInput.required = false;
                passwordInput.value = ''; // Очищаем поле при скрытии
            }
        }
    }
    
    // 1. Управление видимостью периодичности (завязано строго на Виртуальный Крон)
    const virtualCronToggle = backupForm.querySelector('input[name="cron_virtual"]');
    const cronPeriodZone = document.getElementById('js-backup-cron-period-zone');
    if (virtualCronToggle && cronPeriodZone) {
        if (virtualCronToggle.checked) {
            cronPeriodZone.classList.remove('hidden');
        } else {
            cronPeriodZone.classList.add('hidden');
        }
    }

    // 2. Управление видимостью ссылки (завязано строго на Системный Крон)
    const systemCronToggle = backupForm.querySelector('input[name="cron_enabled"]');
    const cronLinkZone = document.getElementById('js-backup-cron-link-zone');
    if (systemCronToggle && cronLinkZone) {
        if (systemCronToggle.checked) {
            cronLinkZone.classList.remove('hidden');
        } else {
            cronLinkZone.classList.add('hidden');
        }
    }

}

/**
 * 2. АСИНХРОННЫЙ ПОШАГОВЫЙ ЗАПУСК СБОРКИ АРХИВА
 */
async function startFmArchiveGeneration() {
    const progressZone = document.getElementById('js-backup-progress-zone');
    const statusText = document.getElementById('js-backup-status-text');
    const percentText = document.getElementById('js-backup-percent-text');
    const progressBar = document.getElementById('js-backup-progress-bar');
    const btn = document.querySelector('button[onclick="startFmArchiveGeneration()"]');
    const csrfTokenInput = document.getElementById('js-backup-csrf-token');

    if (!progressZone || !statusText || !percentText || !progressBar || !btn) return;

    btn.disabled = true;
    
    progressZone.classList.remove('hidden');

    statusText.innerText = 'Сканирование директорий и подсчет объектов...';
    percentText.innerText = '0%';
    progressBar.style.width = '0%';

    const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

    try {
        // --- ШАГ А: ИНИЦИАЛИЗАЦИЯ (Запрос к handleInitBackupProcess в admin_controller.php) ---
        let initData = new FormData();
        initData.append('init_backup_process', '1');
        initData.append('csrf_token', csrfToken);

        let initResponse = await fetch('index.php?tab=backups', {
            method: 'POST',
            body: initData
        });

        if (!initResponse.ok) throw new Error(`Ошибка инициализации сервера: ${initResponse.status}`);
        let initResult = await initResponse.json();

        if (initResult.error) {
            if (typeof showToast === 'function') showToast(`Ошибка: ${initResult.error}`, 'error');
            resetFmProgressInterface(btn, progressZone);
            return;
        }

        const totalSteps = parseInt(initResult.total_steps || 1);
        if (typeof showToast === 'function') showToast('Структура проекта готова. Сборка архива...', 'success');

        // --- ШАГ Б: ЦИКЛИЧЕСКАЯ ПОШАГОВАЯ АРХИВАЦИЯ (handleProcessBackupStep) ---
        let currentStep = 0;

        while (currentStep < totalSteps) {
            statusText.innerText = `Упаковка файлов... Шаг ${currentStep + 1} из ${totalSteps}`;

            let stepData = new FormData();
            stepData.append('process_backup_step', '1');
            stepData.append('step', currentStep.toString());
            stepData.append('csrf_token', csrfToken);

            let stepResponse = await fetch('index.php?tab=backups', {
                method: 'POST',
                body: stepData
            });

            if (!stepResponse.ok) throw new Error(`Ошибка на шаге ${currentStep + 1}: ${stepResponse.status}`);
            let stepResult = await stepResponse.json();

            if (stepResult.error) {
                if (typeof showToast === 'function') showToast(`Критический сбой архивации: ${stepResult.error}`, 'error');
                resetFmProgressInterface(btn, progressZone);
                return;
            }

            // Плавное обновление бегущей полосы прогресс-бара и цифры процентов
            let percent = parseInt(stepResult.current_percent || 0);
            percentText.innerText = `${percent}%`;
            progressBar.style.width = `${percent}%`;

            // Если бэкэнд рапортует, что это был финальный шаг — выходим из цикла
            if (stepResult.is_finished) {
                if (typeof showToast === 'function') showToast(stepResult.success || 'Архив успешно создан!', 'success');
                break;
            }

            currentStep = parseInt(stepResult.next_step || currentStep + 1);
            
            await new Promise(resolve => setTimeout(resolve, 150));
        }

        // Финал: перезагружаем страницу через 1.5 секунды, чтобы в таблице появился новый ZIP
        statusText.innerText = 'Сборка завершена! Обновление реестра архивов...';
        setTimeout(() => {
            window.location.reload();
        }, 1500);

    } catch (error) {
        if (typeof showToast === 'function') showToast(`Критическая ошибка AJAX: ${error.message}`, 'error');
        resetFmProgressInterface(btn, progressZone);
    }
}

/**
 * 3. ВСПОМОГАТЕЛЬНЫЙ СБРОС ИНТЕРФЕЙСА ПРИ СБОЯХ
 */
function resetFmProgressInterface(btn, zone) {
    if (btn) btn.disabled = false;
    if (zone) zone.classList.add('hidden');
}

// Инициализация живых слушателей событий
document.addEventListener('DOMContentLoaded', () => {
    const backupForm = document.getElementById('js-backup-form');
    if (backupForm) {
        // Слушаем только живые изменения полей пользователем
        backupForm.querySelectorAll('input[name="backup_type"]').forEach(radio => {
            radio.addEventListener('change', toggleFmBackupFields);
        });
        
        const encryptToggle = document.getElementById('js-backup-encrypt-toggle');
        if (encryptToggle) {
            encryptToggle.addEventListener('change', toggleFmBackupFields);
        }
    }
});

function showToast(message, type = 'success') {
    const container = document.getElementById('js-toast-container');
    if (!container) return;

    container.classList.remove('hidden');

    // Генерируем уникальную плашку в стилистике админки Tailwind
    const toast = document.createElement('div');
    if (type === 'success') {
        toast.className = 'p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center gap-3 animate-fade-in shadow-sm';
        toast.innerHTML = `<span class="icon-shield-check text-lg text-[var(--primary-color)]"></span> <div>${message}</div>`;
    } else {
        toast.className = 'p-4 text-sm text-rose-800 bg-rose-50 border border-rose-100 rounded-xl flex items-center gap-3 animate-fade-in shadow-sm';
        toast.innerHTML = `<span class="icon-x text-lg text-rose-500"></span> <div>${message}</div>`;
    }

    // Вставляем новую плашку наверх
    container.insertBefore(toast, container.firstChild);

    // Автоматически и плавно удаляем тост через 4 секунды
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        toast.style.transition = 'all 0.3s ease-in-out';
        setTimeout(() => {
            toast.remove();
            if (container.children.length === 0) {
                container.classList.add('hidden');
            }
        }, 300);
    }, 4000);
}

/**
 * 4. УПРАВЛЕНИЕ АРХИВАМИ: БЕЗОПАСНОЕ УДАЛЕНИЕ ФАЙЛА
 */
function deleteFmBackup(fileName) {
    if (!fileName) return;

    // Запрашиваем подтверждение у администратора
    if (!confirm(`Вы действительно хотите навсегда и безвозвратно удалить архив "${fileName}" с сервера?`)) {
        return;
    }

    const csrfTokenInput = document.getElementById('js-backup-csrf-token');
    const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

    // Создаем и на лету отправляем скрытую форму
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php?tab=backups';

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'delete_backup_action';
    actionInput.value = '1';

    const fileInput = document.createElement('input');
    fileInput.type = 'hidden';
    fileInput.name = 'delete_file';
    fileInput.value = fileName;

    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = 'csrf_token';
    tokenInput.value = csrfToken;

    form.appendChild(actionInput);
    form.appendChild(fileInput);
    form.appendChild(tokenInput);

    document.body.appendChild(form);
    form.submit(); // Делаем сабмит и уходим на серверный редирект
}

/**
 * 5. УПРАВЛЕНИЕ АРХИВАМИ: БЕЗОПАСНОЕ СКАЧИВАНИЕ ФАЙЛА НА ПК
 */
function downloadFmBackup(fileName) {
    if (!fileName) return;
    // Перенаправляем окно браузера на защищенный PHP-поток скачивания
    window.location.href = `index.php?tab=backups&download_file=${encodeURIComponent(fileName)}`;
}

/**
 * 6. УПРАВЛЕНИЕ АРХИВАМИ: ПОЛНОЕ ВОССТАНОВЛЕНИЕ (ОТКАТ) СИСТЕМЫ
 */
function restoreFmBackup(fileName, isEncrypted) {
    if (!fileName) return;

    // Глобальное предупреждение администратору
    let confirmMsg = `ВНИМАНИЕ!\nВы собираетесь восстановить систему из архива "${fileName}".\n`;
    confirmMsg += `Текущие файлы будут ПОЛНОСТЬЮ СТЕРТЫ и заменены данными из бэкапа!\n\n`;
    confirmMsg += `Вы уверены, что хотите продолжить откат системы?`;

    if (!confirm(confirmMsg)) {
        return;
    }

    let archivePassword = '';
    
    // Если PHP передал, что архив зашифрован — требуем пароль
    if (isEncrypted) {
        archivePassword = prompt('Внимание: Данный архив зашифрован (AES-256).\nВведите пароль для распаковки:', '');
        if (archivePassword === null) return; // Нажали "Отмена"
        archivePassword = archivePassword.trim();
        if (archivePassword === '') {
            if (typeof showToast === 'function') showToast('Ошибка: Пароль не может быть пустым!', 'error');
            return;
        }
    }

    const csrfTokenInput = document.getElementById('js-backup-csrf-token');
    const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

    // Генерируем скрытую POST-форму для нативного отката
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php?tab=backups';

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'restore_backup_action';
    actionInput.value = '1';

    const fileInput = document.createElement('input');
    fileInput.type = 'hidden';
    fileInput.name = 'restore_file';
    fileInput.value = fileName;

    const passInput = document.createElement('input');
    passInput.type = 'hidden';
    passInput.name = 'restore_password';
    passInput.value = archivePassword;

    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = 'csrf_token';
    tokenInput.value = csrfToken;

    form.appendChild(actionInput);
    form.appendChild(fileInput);
    form.appendChild(passInput);
    form.appendChild(tokenInput);

    document.body.appendChild(form);
    form.submit(); // Сабмитим форму, запуская handleRestoreBackup в контроллере
}

// Экспонируем функцию наружу в глобальное окно
window.restoreFmBackup = restoreFmBackup;
window.downloadFmBackup = downloadFmBackup;
window.deleteFmBackup = deleteFmBackup;
window.showToast = showToast;
window.toggleFmBackupFields = toggleFmBackupFields;
window.startFmArchiveGeneration = startFmArchiveGeneration;
