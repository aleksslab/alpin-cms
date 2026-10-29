<?php
/**
 * Шаблон страницы технических работ (503).
 * Подключается из index.php, когда maintenance_enabled = true
 * и нет доступа (не админ, нет bypass-cookie).
 *
 * Доступные переменные (из index.php):
 * - $maintenanceMessage (string) — HTML/plain, уже через sanitizeHtml
 * - $maintenanceUntil (int|null) — Unix timestamp автоснятия
 * - $maintenanceSettings (array) — settings.json
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

$msg = trim((string)($maintenanceMessage ?? ''));
$until = isset($maintenanceUntil) ? (int)$maintenanceUntil : 0;
$showTimer = !empty($maintenanceShowTimer);
$hasTimer = $showTimer && $until > time();

$siteName = (string)($maintenanceSettings['name'] ?? (defined('SITE_NAME') ? SITE_NAME : 'Сайт'));
$siteLogo = (string)($maintenanceSettings['logo'] ?? (defined('SITE_LOGO') ? SITE_LOGO : ''));
$phone    = (string)($maintenanceSettings['phone'] ?? (defined('SITE_PHONE') ? SITE_PHONE : ''));
$email    = (string)($maintenanceSettings['email'] ?? (defined('SITE_EMAIL') ? SITE_EMAIL : ''));

$siteNamePlain = trim(strip_tags($siteName));
if ($siteNamePlain === '') {
    $siteNamePlain = 'Сайт';
}

$logoUrl = $siteLogo !== '' ? '/' . ltrim($siteLogo, '/') : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Сайт временно недоступен — <?php echo htmlspecialchars($siteNamePlain, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            color: #334155;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            line-height: 1.6;
        }
        .mw {
            max-width: 560px; width: 100%; background: #fff; border-radius: 24px;
            padding: 48px 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.04);
            border: 1px solid #f1f5f9; text-align: center;
        }
        .logo { margin-bottom: 32px; display: inline-block; }
        .logo img { max-height: 56px; width: auto; display: block; }
        .logo-text { font-size: 26px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em; }
        h1 { font-size: 28px; font-weight: 800; color: #0f172a; margin: 0 0 16px; line-height: 1.25; }
        .msg { color: #475569; font-size: 16px; margin: 0 0 32px; }
        .msg p { margin: 0 0 12px; }
        .msg p:last-child { margin-bottom: 0; }
        .msg h1, .msg h2, .msg h3, .msg h4 { color: #0f172a; margin: 16px 0 12px; font-size: 1.1em; }
        .msg a { color: #10b981; text-decoration: underline; }
        .msg ul, .msg ol { text-align: left; display: inline-block; padding-left: 20px; }
        .msg img { max-width: 100%; height: auto; border-radius: 12px; margin: 12px 0; }
        .timer { background: #f0fdf4; border: 1px solid #d1fae5; border-radius: 16px; padding: 20px; margin: 24px 0; }
        .timer-label { font-size: 12px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
        .timer-digits { font-family: 'Courier New', monospace; font-size: 28px; font-weight: 800; color: #065f46; line-height: 1; }
        .timer-digits span { display: inline-block; min-width: 2ch; }
        .timer-digits .sep { opacity: 0.4; margin: 0 4px; }
        .contacts { margin-top: 32px; padding-top: 24px; border-top: 1px solid #f1f5f9; font-size: 14px; color: #64748b; }
        .contacts a { color: #10b981; text-decoration: none; font-weight: 600; }
        .contacts a:hover { text-decoration: underline; }
        .contacts div + div { margin-top: 8px; }
        @media (max-width: 480px) {
            .mw { padding: 32px 24px; }
            h1 { font-size: 22px; }
            .timer-digits { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="mw">
        <div class="logo">
            <?php if ($logoUrl !== ''): ?>
                <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($siteNamePlain, ENT_QUOTES, 'UTF-8'); ?>">
            <?php else: ?>
                <div class="logo-text"><?php echo htmlspecialchars($siteNamePlain, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>

        <h1>Сайт временно недоступен</h1>

        <?php if ($msg !== ''): ?>
            <div class="msg">
                <?php echo $msg; ?>
            </div>
        <?php else: ?>
            <div class="msg">
                <p>Мы проводим технические работы. Скоро вернёмся.</p>
            </div>
        <?php endif; ?>

        <?php if ($hasTimer): ?>
            <div class="timer">
                <div class="timer-label">До открытия</div>
                <div class="timer-digits" id="mw-timer" data-until="<?php echo $until; ?>">
                    <span id="mw-d">--</span><span class="sep">:</span><span id="mw-h">--</span><span class="sep">:</span><span id="mw-m">--</span><span class="sep">:</span><span id="mw-s">--</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($email !== '' || $phone !== ''): ?>
            <div class="contacts">
                <?php if ($phone !== ''): ?>
                    <div>Телефон: <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $phone), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(formatPhone($phone), ENT_QUOTES, 'UTF-8'); ?></a></div>
                <?php endif; ?>
                <?php if ($email !== ''): ?>
                    <div>Email: <a href="mailto:<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></a></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($hasTimer): ?>
    <script>
    (function() {
        'use strict';
        var el = document.getElementById('mw-timer');
        if (!el) return;
        var until = parseInt(el.getAttribute('data-until'), 10);
        if (!until || isNaN(until)) return;

        var dEl = document.getElementById('mw-d');
        var hEl = document.getElementById('mw-h');
        var mEl = document.getElementById('mw-m');
        var sEl = document.getElementById('mw-s');

        function pad(n) { return n < 10 ? '0' + n : '' + n; }

        function tick() {
            var now = Math.floor(Date.now() / 1000);
            var diff = until - now;

            if (diff <= 0) {
                dEl.textContent = '00';
                hEl.textContent = '00';
                mEl.textContent = '00';
                sEl.textContent = '00';
                setTimeout(function() { window.location.reload(); }, 5000);
                return;
            }

            dEl.textContent = pad(Math.floor(diff / 86400));
            hEl.textContent = pad(Math.floor((diff % 86400) / 3600));
            mEl.textContent = pad(Math.floor((diff % 3600) / 60));
            sEl.textContent = pad(diff % 60);

            setTimeout(tick, 1000);
        }

        tick();
    })();
    </script>
    <?php endif; ?>
</body>
</html>