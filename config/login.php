<div class="login-page-wrapper relative">
    <div class="text-center mb-8 animate-fade-in">
        <div class="text-4xl font-black tracking-tight text-slate-800 select-none">
            AlPin<span class="text-[var(--primary-color)]">CMS</span>
        </div>
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2 bg-slate-200/50 inline-block px-2.5 py-1 rounded-md border border-slate-200/80">Панель управления</div>
    </div>
    
    
    <?php // КАРТОЧКА А: Вход в систему; ?>
    <div id="login-box" class="bg-white p-8 md:p-10 rounded-[2.5rem] shadow-[0_10px_40px_rgb(0,0,0,0.02)] border border-slate-100 animate-fade-in">
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Вход в систему</h2>
        <p class="text-slate-500 text-sm mb-6">Введите доступы для конфигурации.</p>
        
        <?php if (!empty($error)): ?>
            <div class="p-4 mb-4 text-sm text-rose-800 bg-rose-50 border border-rose-100 rounded-xl"><?php echo e($error); ?></div>
        <?php endif; ?>
            
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="p-4 mb-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-100 rounded-xl">Доступы успешно изменены! Войдите, используя новые учетные данные.</div>
        <?php endif; ?>

        <form method="POST" action="index.php" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Имя пользователя</label>
                <input type="text" name="admin_login" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Пароль</label>
                <input type="password" name="admin_password" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" />
            </div>
            <button type="submit" name="login_submit" class="w-full bg-[var(--primary-color)] text-white font-bold py-3 rounded-xl hover:bg-[var(--primary-dark)] transition-all cursor-pointer shadow-md shadow-[var(--primary-color)]/10">Войти</button>
        </form>
        
        <?php if (!file_exists(CMS_CREDS_FILE)): ?>
        <div class="text-center mt-6">
            <button id="show-gen-btn" class="text-xs font-semibold text-slate-400 hover:text-slate-600 transition-colors cursor-pointer underline">Конфигурация ключей</button>
        </div>
        <?php endif; ?>
    </div>

    <?php // КАРТОЧКА Б: Скрытый генератор; ?>
    <div id="generator-box" class="bg-white p-8 md:p-10 rounded-[2.5rem] shadow-[0_10px_40px_rgb(0,0,0,0.02)] border border-slate-100 hidden animate-fade-in">
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Ключ авторизации</h2>
        <p class="text-slate-500 text-sm mb-6">Генерация уникального идентификатора для конфигурации окружения.</p>
        
        <form method="POST" action="index.php" class="space-y-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Придумайте пароль</label>
                <input type="password" name="new_password" required autocomplete="new-password" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" />
            </div>
            <button type="submit" name="generate_submit" class="w-full border border-slate-200 text-slate-700 font-bold py-3 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-all cursor-pointer">Сгенерировать ключ</button>
        </form>

        <?php if (!empty($generatedHash)): ?>
            <div class="pt-4 border-t border-slate-100 space-y-2">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Код безопасности:</label>
                <textarea readonly class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-700 resize-none h-24 select-all focus:outline-none"><?php echo e($generatedHash); ?></textarea>
                <p class="text-[10px] text-slate-400 leading-normal">Запишите полученный код в конфигурацию системы.</p>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-6">
            <button id="show-login-btn" class="text-xs font-semibold text-slate-400 hover:text-slate-600 transition-colors cursor-pointer underline">Вернуться к авторизации</button>
        </div>
    </div>
    
</div>

<?php // Сценарий-мост: авторазворот при генерации ключа; ?>
<?php if (!empty($generatedHash)): ?>
    <script>
        document.getElementById('login-box').classList.add('hidden');
        document.getElementById('generator-box').classList.remove('hidden');
    </script>
<?php endif; ?>
