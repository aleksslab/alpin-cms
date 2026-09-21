<?php
if (!defined('APP_ROOT') || empty($_SESSION['admin_auth'])) {
    die('Доступ запрещен');
}

// 1. Считываем текущие учетные данные из JSON в один проход
$creds = file_exists(CMS_CREDS_FILE) ? (json_decode(file_get_contents(CMS_CREDS_FILE), true) ?? []) : [];

// 2. Считываем настройки и логи брутфорса из JSON
$bfSettings = [
    'max_attempts' => 5,
    'lockout_time' => 15,
    'permanent_trigger_count' => 3,
    'permanent_trigger_period' => 24
];
$attemptsList = [];

if (file_exists(CMS_ATTEMPTS_FILE)) {
    $bfData = json_decode(file_get_contents(CMS_ATTEMPTS_FILE), true) ?? [];
    if (isset($bfData['settings'])) {
        $bfSettings = array_merge($bfSettings, $bfData['settings']);
    }
    if (isset($bfData['attempts'])) {
        $attemptsList = $bfData['attempts'];
    }
}
?>

<h1 class="text-3xl font-bold text-slate-800 mb-2">Настройки безопасности</h1>
<p class="text-slate-500 text-sm mb-8">Управление учетными данными администратора и параметрами защиты от перебора паролей (Anti-Brute Force).</p>

<?php // ГЛАВНАЯ ФОРМА: УЧЕТНЫЕ ДАННЫЕ И НАСТРОЙКИ ЛИМИТОВ ; ?>
<form id="security-edit-form" method="POST" action="index.php?tab=security" class="space-y-6 mb-6">
    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
    <?php // БЛОК 1: АДМИН ПАНЕЛЬ (трехколоночный ряд на обновленных флекс-классах) ?>
    <div class="space-y-4 bg-slate-50 p-6 rounded-2xl border border-slate-100">
        <h3 class="text-lg font-bold text-slate-800 border-b border-slate-200/60 pb-3">Учетная запись администратора</h3>

        <div class="editor-row">
            <div class="editor-field space-y-2">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Имя пользователя (Логин)</label>
                <input type="text" name="sec_login" value="<?php echo e($creds['login'] ?? 'admin'); ?>" required autocomplete="off" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" />
            </div>
            
            <div class="editor-field space-y-2">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Новый пароль (оставьте пустым, если не меняете)</label>
                <input type="password" id="sec-password" name="sec_password" autocomplete="new-password" placeholder="••••••••" class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" />
            </div>
            
            <div class="editor-field space-y-2">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Таймаут сессии (в минутах)</label>
                <input type="number" name="bf_session_timeout" value="<?php echo intval($bfSettings['session_timeout'] ?? 20); ?>" min="1" max="1440" required class="w-full px-5 py-3.5 bg-white border border-slate-200 rounded-xl text-base text-slate-800 focus:outline-none focus:border-[var(--primary-color)]" />
            </div>
        </div>
    </div>

    <?php // БЛОК 2: НАСТРОЙКИ ANTI-BRUTE FORCE (адаптивный вид на базе геометрии editor-row) ; ?>
    <div class="space-y-4 bg-slate-50 p-6 rounded-2xl border border-slate-100">
        <h3 class="text-lg font-bold text-slate-800 border-b border-slate-200/60 pb-3">Параметры интеллектуальной блокировки</h3>
        
        <?php // Карточка 1: Порог временного бана ; ?>
        <div class="bg-white p-5 rounded-xl border border-slate-200/60 shadow-sm space-y-2">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Порог временного бана</label>
            <div class="editor-row">
                <div class="editor-field">
                    <input type="number" name="bf_max_attempts" value="<?php echo intval($bfSettings['max_attempts']); ?>" min="1" max="20" required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-base text-slate-800 font-semibold focus:outline-none focus:border-[var(--primary-color)] transition-colors" />
                </div>
                <div class="editor-action !w-auto flex items-center h-[54px] pb-1">
                    <span class="text-sm font-semibold text-slate-400 whitespace-nowrap">попыток</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-400">Ошибки ввода до включения таймера блокировки.</p>
        </div>

        <?php // Карточка 2: Длительность таймера ; ?>
        <div class="bg-white p-5 rounded-xl border border-slate-200/60 shadow-sm space-y-2">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Длительность таймера</label>
            <div class="editor-row">
                <div class="editor-field">
                    <input type="number" name="bf_lockout_time" value="<?php echo intval($bfSettings['lockout_time']); ?>" min="1" max="1440" required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-base text-slate-800 font-semibold focus:outline-none focus:border-[var(--primary-color)] transition-colors" />
                </div>
                <div class="editor-action !w-auto flex items-center h-[54px] pb-1">
                    <span class="text-sm font-semibold text-slate-400 whitespace-nowrap">минут</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-400">Время ограничения доступа к панели управления.</p>
        </div>

        <?php // Карточка 3: Порог вечной блокировки ; ?>
        <div class="bg-rose-50/20 p-5 rounded-xl border border-rose-100 shadow-sm space-y-2">
            <label class="text-[10px] font-black text-rose-500 uppercase tracking-wider block">Порог вечной блокировки</label>
            <div class="editor-row">
                <div class="editor-field">
                    <input type="number" name="bf_perm_count" value="<?php echo intval($bfSettings['permanent_trigger_count']); ?>" min="1" max="10" required class="w-full px-5 py-3.5 bg-slate-50 border border-rose-200 rounded-xl text-base text-slate-800 font-semibold focus:outline-none focus:border-rose-500 transition-colors" />
                </div>
                <div class="editor-action !w-auto flex items-center h-[54px] pb-1">
                    <span class="text-sm font-semibold text-rose-400/80 whitespace-nowrap">банов</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-400">Количество временных банов до перевода хоста в вечный бан.</p>
        </div>

        <?php // Карточка 4: Окно отслеживания ; ?>
        <div class="bg-rose-50/20 p-5 rounded-xl border border-rose-100 shadow-sm space-y-2">
            <label class="text-[10px] font-black text-rose-500 uppercase tracking-wider block">Окно отслеживания</label>
            <div class="editor-row">
                <div class="editor-field">
                    <input type="number" name="bf_perm_period" value="<?php echo intval($bfSettings['permanent_trigger_period']); ?>" min="1" max="168" required class="w-full px-5 py-3.5 bg-slate-50 border border-rose-200 rounded-xl text-base text-slate-800 font-semibold focus:outline-none focus:border-rose-500 transition-colors" />
                </div>
                <div class="editor-action !w-auto flex items-center h-[54px] pb-1">
                    <span class="text-sm font-semibold text-rose-400/80 whitespace-nowrap">часов</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-400">Временной интервал, внутри которого суммируются баны.</p>
        </div>
    </div>

    <div class="pt-2 flex justify-end">
        <button type="submit" name="save_security" class="w-full sm:w-auto bg-[var(--primary-color)] text-white font-bold px-8 py-4 rounded-xl hover:bg-[var(--primary-dark)] transition-all cursor-pointer shadow-lg shadow-[var(--primary-color)]/20 text-base">
            Сохранить все настройки
        </button>
    </div>
</form>

<?php // БЛОК 3: МЕНЕДЖЕР IP-АДРЕСОВ ; ?>
<div class="space-y-6 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm mt-8">
    
    <div class="space-y-4 border-b border-slate-100 pb-5">
        <?php // Ряд 1: Заголовок и описание на всю ширину ; ?>
        <div>
            <h3 class="text-lg font-bold text-slate-800">Журнал блокировок и менеджер IP</h3>
            <p class="text-slate-400 text-xs mt-0.5">Список заблокированных роботов и постоянный черный список.</p>
        </div>
        
        <?php // Ряд 2: Изолированная форма ручного бана ; ?>
        <form method="POST" action="index.php?tab=security" style="display: flex !important; flex-direction: row !important; align-items: center !important; gap: 16px !important; width: 100% !important; max-width: 576px !important; margin-top: 16px !important; border-bottom: none;">
            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
            <div style="flex: 1 1 0% !important;">
                <input type="text" name="manual_ban_ip" placeholder="Например, 95.21.34.12" required pattern="^((25[0-5]|2[0-4][0-9]|[0-1]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[0-1]?[0-9][0-9]?)$" class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-base focus:outline-none focus:border-rose-500 transition-colors" style="height: 32px;" />
            </div>
            <div style="flex: none !important;">
                <button type="submit" name="action_manual_ban" class="btn-rose text-white font-bold px-6 rounded-xl transition-all text-sm cursor-pointer shadow-md shadow-rose-600/10 whitespace-nowrap" style="height: 34px !important; display: flex !important; align-items: center !important; justify-content: center !important;">
                    Заблокировать
                </button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden">
        
        <?php // 1. ДЕСКТОПНАЯ ВЕРСИЯ: Классическая таблица (Использует CSS класс .desktop-only) ; ?>
        <table class="w-full text-left border-collapse desktop-only">
            <thead>
                <tr class="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    <th class="pb-3 pl-2">IP-Адрес</th>
                    <th class="pb-3">Текущий Статус</th>
                    <th class="pb-3">Счетчик попыток</th>
                    <th class="pb-3">Осталось времени бана</th>
                    <th class="pb-3 text-right pr-2">Действие</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm text-slate-700">
                <?php if (empty($attemptsList)): ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400 italic">Активные блокировки отсутствуют. Журнал пуст.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    foreach ($attemptsList as $ip => $data): 
                        $isPermanent = !empty($data['permanent']);
                        $timeDiff = ($bfSettings['lockout_time'] * 60) - (time() - $data['last_time']);
                        $isLocked = ($data['count'] >= $bfSettings['max_attempts'] && $timeDiff > 0);
                        
                        if (!$isPermanent && !$isLocked) {
                            continue;
                        }
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 pl-2 font-mono font-medium text-slate-800"><?php echo e($ip); ?></td>
                            <td class="py-3.5">
                                <?php if ($isPermanent): ?>
                                    <span class="badge-permanent">Постоянный</span>
                                <?php else: ?>
                                    <span class="badge-temporary">Временный</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 font-mono text-xs text-slate-500">
                                <?php echo intval($data['count']); ?> / <?php echo intval($bfSettings['max_attempts']); ?> <span class="text-[10px] text-slate-400 ml-1">(банов: <?php echo intval($data['bans_count'] ?? 0); ?>)</span>
                            </td>
                            <td class="py-3.5 text-xs font-medium text-slate-600">
                                <?php 
                                if ($isPermanent) {
                                    echo '<span class="text-rose-500 font-bold">Никогда (вручную)</span>';
                                } else {
                                    echo ceil($timeDiff / 60) . ' мин.';
                                }
                                ?>
                            </td>
                            <td class="py-3.5 text-right pr-2">
                                <form method="POST" action="index.php?tab=security" onsubmit="return confirm('Вы уверены, что хотите разблокировать этот IP-адрес?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                    <input type="hidden" name="unban_ip" value="<?php echo e($ip); ?>">
                                    <button type="submit" name="action_unban" class="btn-unban text-xs transition-colors">
                                        Разблокировать
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php // 2. МОБИЛЬНАЯ ВЕРСИЯ: Компактные карточки (Использует CSS класс .mobile-only) ; ?>
        <div class="mobile-only space-y-4">
            <?php if (empty($attemptsList)): ?>
                <div class="py-6 text-center text-slate-400 italic text-sm">Активные блокировки отсутствуют. Журнал пуст.</div>
            <?php else: ?>
                <?php 
                foreach ($attemptsList as $ip => $data): 
                    $isPermanent = !empty($data['permanent']);
                    $timeDiff = ($bfSettings['lockout_time'] * 60) - (time() - $data['last_time']);
                    $isLocked = ($data['count'] >= $bfSettings['max_attempts'] && $timeDiff > 0);
                    if (!$isPermanent && !$isLocked) { continue; }
                ?>
                    <div class="p-4 bg-slate-50 border border-slate-200/60 rounded-xl space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200/40 pb-2">
                            <span class="font-mono font-bold text-slate-800 text-sm"><?php echo e($ip); ?></span>
                            <?php if ($isPermanent): ?>
                                <span class="badge-permanent">Постоянный</span>
                            <?php else: ?>
                                <span class="badge-temporary">Временный</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 text-xs text-slate-500">
                            <div>
                                <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-semibold">Попытки</span>
                                <span class="font-mono font-medium text-slate-700"><?php echo intval($data['count']); ?> / <?php echo intval($bfSettings['max_attempts']); ?></span>
                                <span class="text-[10px] text-slate-400 block">(банов: <?php echo intval($data['bans_count'] ?? 0); ?>)</span>
                            </div>
                            <div>
                                <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-semibold">Осталось времени</span>
                                <span class="font-medium text-slate-700">
                                    <?php echo $isPermanent ? '<span class="text-rose-500 font-bold">Никогда (вручную)</span>' : ceil($timeDiff / 60) . ' мин.'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-200/40 flex justify-end">
                            <form method="POST" action="index.php?tab=security" onsubmit="return confirm('Вы уверены, что хотите разблокировать этот IP-адрес?');" class="w-full">
                                <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                <input type="hidden" name="unban_ip" value="<?php echo e($ip); ?>">
                                <button type="submit" name="action_unban" class="btn-unban-mobile transition-all">
                                    Разблокировать
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>
