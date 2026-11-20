<?php
/**
 * Почтовый агент: SMTP-конфигурация и отправка писем.
 *
 * Конфиг лежит отдельно от `settings.json` — в `config/data/mail_config.json`.
 * Пароль хранится только в зашифрованном виде (`password_enc`) и расшифровывается
 * общим хелпером `cms_decrypt_secret()`.
 *
 * Функции используются ядром (эндпоинт `includes/mailer.php`) и плагинами,
 * которым нужно отправить письмо без HTTP-запроса к самому себе.
 */

if (!defined('APP_ROOT')) {
    die('Доступ запрещен');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Путь к файлу конфигурации почтового агента.
 *
 * @return string
 */
function mail_config_path(): string {
    return APP_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'mail_config.json';
}

/**
 * Значения по умолчанию.
 *
 * @return array
 */
function mail_config_defaults(): array {
    return [
        'enabled'     => false,
        'host'        => '',
        'port'        => 587,
        'encryption'  => 'tls',
        'auth'        => true,
        'username'    => '',
        'password_enc' => '',
        'from_email'  => '',
        'from_name'   => '',
        'to_email'    => '',
        'timeout'     => 15,
        'last_test_at' => 0,
        'last_error'  => '',
    ];
}

/**
 * Читает конфигурацию почтового агента.
 *
 * @param bool $refresh true — перечитать файл, игнорируя кеш в памяти
 * @return array
 */
function get_mail_config(bool $refresh = false): array {
    static $cache = null;

    if ($cache !== null && !$refresh) {
        return $cache;
    }

    $data = [];
    $path = mail_config_path();

    if (is_file($path)) {
        $decoded = json_decode((string)@file_get_contents($path), true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    $cache = array_merge(mail_config_defaults(), $data);

    return $cache;
}

/**
 * Сохраняет конфигурацию почтового агента.
 *
 * @param array $config
 * @return bool
 */
function save_mail_config(array $config): bool {
    $config = array_merge(mail_config_defaults(), $config);

    return safeFileWrite(
        mail_config_path(),
        json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

/**
 * Возвращает e-mail сайта из настроек (для подстановок по умолчанию).
 *
 * @return string
 */
function mail_site_email(): string {
    $path = APP_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'settings.json';

    if (!is_file($path)) {
        return '';
    }

    $settings = json_decode((string)@file_get_contents($path), true);

    return is_array($settings) ? trim((string)($settings['email'] ?? '')) : '';
}

/**
 * Возвращает название сайта из настроек.
 *
 * @return string
 */
function mail_site_name(): string {
    $path = APP_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'settings.json';

    if (!is_file($path)) {
        return 'Сайт';
    }

    $settings = json_decode((string)@file_get_contents($path), true);
    $name = is_array($settings) ? trim(strip_tags((string)($settings['name'] ?? ''))) : '';

    return $name !== '' ? $name : 'Сайт';
}

/**
 * Проверяет, готов ли агент к отправке.
 *
 * @param array|null $config Конфиг или null — взять сохранённый
 * @return bool
 */
function mail_agent_is_configured(?array $config = null): bool {
    $config = $config ?? get_mail_config();

    if (empty($config['enabled'])) {
        return false;
    }

    if (trim((string)($config['host'] ?? '')) === '') {
        return false;
    }

    if ((int)($config['port'] ?? 0) < 1) {
        return false;
    }

    return mail_resolve_from($config)[0] !== '';
}

/**
 * Модули, которым для работы нужен почтовый агент.
 *
 * Признак `requires_mail` объявляет сам модуль в `manifest.json`, и он попадает
 * в реестр `config/data/modules.json`. Ядро не хранит список ID: какие модули
 * установлены и как они называются, знает только реестр. Плагины могут
 * дополнить список фильтром `mail_dependent_modules`.
 *
 * @return array [string $moduleId => string $mode] mode: `form` — без почты
 *               скрывается только форма, `module` — модуль не выводится целиком
 */
function mail_dependent_modules(): array {
    $modules = [];

    foreach (getModulesData() as $id => $data) {
        $mode = $data['requires_mail'] ?? '';

        if ($mode === 'form' || $mode === 'module' || $mode === true) {
            $modules[(string)$id] = $mode === 'form' ? 'form' : 'module';
        }
    }

    /**
     * Позволяет расширениям объявить зависимость своего модуля от почты,
     * например ['my-form' => 'module', 'my-contacts' => 'form'].
     */
    $modules = apply_filters('mail_dependent_modules', $modules);

    if (!is_array($modules)) {
        return [];
    }

    $result = [];

    foreach ($modules as $id => $mode) {
        $result[(string)$id] = $mode === 'form' ? 'form' : 'module';
    }

    return $result;
}

/**
 * Возвращает пару «отправитель»: e-mail и имя.
 *
 * Если в конфиге пусто, подставляется e-mail и название сайта.
 *
 * @param array $config
 * @return array [string $email, string $name]
 */
function mail_resolve_from(array $config): array {
    $email = trim((string)($config['from_email'] ?? ''));
    if ($email === '') {
        $email = mail_site_email();
    }

    $name = trim((string)($config['from_name'] ?? ''));
    if ($name === '') {
        $name = mail_site_name();
    }

    return [$email, $name];
}

/**
 * Отправляет письмо через настроенный SMTP.
 *
 * @param string     $subject       Тема
 * @param string     $htmlBody      HTML-тело
 * @param string     $replyToEmail  Reply-To (необязательно)
 * @param string     $replyToName   Имя для Reply-To
 * @param array|null $config        Конфиг для проверки/теста; null — сохранённый
 * @return array ['success' => bool, 'error' => string]
 */
function send_site_mail(string $subject, string $htmlBody, string $replyToEmail = '', string $replyToName = '', ?array $config = null): array {
    $config = $config ?? get_mail_config(true);

    if (!mail_agent_is_configured($config)) {
        return ['success' => false, 'error' => 'Почтовый агент не настроен.'];
    }

    $password = '';
    $encrypted = trim((string)($config['password_enc'] ?? ''));

    if ($encrypted !== '') {
        $password = cms_decrypt_secret($encrypted, 'cms_mail_smtp');
        if ($password === '') {
            return ['success' => false, 'error' => 'Не удалось расшифровать пароль SMTP.'];
        }
    }

    [$fromEmail, $fromName] = mail_resolve_from($config);
    $toEmail = trim((string)($config['to_email'] ?? ''));
    if ($toEmail === '') {
        $toEmail = $fromEmail;
    }

    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Проверьте адреса отправителя и получателя.'];
    }

    $mailerDir = APP_ROOT . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR;
    require_once $mailerDir . 'Exception.php';
    require_once $mailerDir . 'PHPMailer.php';
    require_once $mailerDir . 'SMTP.php';

    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isSMTP();
        $mail->Host = trim((string)$config['host']);
        $mail->Port = (int)$config['port'];
        $mail->Timeout = max(5, min(60, (int)($config['timeout'] ?? 15)));

        $encryption = (string)($config['encryption'] ?? 'tls');
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
        }

        $mail->SMTPAuth = !empty($config['auth']);
        if ($mail->SMTPAuth) {
            $mail->Username = trim((string)($config['username'] ?? ''));
            $mail->Password = $password;
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);

        if ($replyToEmail !== '' && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyToEmail, $replyToName);
        }

        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;

        $mail->send();

        return ['success' => true, 'error' => ''];
    } catch (Exception $e) {
        $error = trim((string)$mail->ErrorInfo);

        return ['success' => false, 'error' => $error !== '' ? $error : 'SMTP-сервер отклонил отправку.'];
    }
}

/**
 * Собирает конфиг из POST формы «Почта».
 *
 * Пустой пароль означает «не менять», `mail_clear_password` — удалить.
 * Поля `from_email` / `to_email` остаются пустыми, если админ их не заполнил:
 * подстановка e-mail сайта происходит при отправке.
 *
 * @param array $post
 * @return array ['config' => array, 'error' => string]
 */
function mail_config_from_post(array $post): array {
    $current = get_mail_config(true);

    $host = trim((string)($post['mail_host'] ?? ''));
    if ($host !== '' && !preg_match('/^[a-z0-9\.\-]+$/i', $host)) {
        return ['config' => [], 'error' => 'Некорректный SMTP-хост.'];
    }

    $port = (int)($post['mail_port'] ?? 587);
    if ($port < 1 || $port > 65535) {
        return ['config' => [], 'error' => 'Порт должен быть от 1 до 65535.'];
    }

    $encryption = (string)($post['mail_encryption'] ?? 'tls');
    if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
        $encryption = 'tls';
    }

    $fromEmail = trim((string)($post['mail_from_email'] ?? ''));
    if ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return ['config' => [], 'error' => 'Некорректный e-mail отправителя.'];
    }

    $toEmail = trim((string)($post['mail_to_email'] ?? ''));
    if ($toEmail !== '' && !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['config' => [], 'error' => 'Некорректный e-mail получателя.'];
    }

    $timeout = (int)($post['mail_timeout'] ?? 15);
    $timeout = max(5, min(60, $timeout));

    $passwordEnc = (string)($current['password_enc'] ?? '');
    $newPassword = (string)($post['mail_password'] ?? '');

    if (!empty($post['mail_clear_password'])) {
        $passwordEnc = '';
    } elseif ($newPassword !== '') {
        $passwordEnc = cms_encrypt_secret($newPassword, 'cms_mail_smtp');
        if ($passwordEnc === '') {
            return ['config' => [], 'error' => 'Не удалось зашифровать пароль SMTP.'];
        }
    }

    $config = [
        'enabled'      => !empty($post['mail_enabled']),
        'host'         => $host,
        'port'         => $port,
        'encryption'   => $encryption,
        'auth'         => !empty($post['mail_auth']),
        'username'     => trim((string)($post['mail_username'] ?? '')),
        'password_enc' => $passwordEnc,
        'from_email'   => $fromEmail,
        'from_name'    => trim((string)($post['mail_from_name'] ?? '')),
        'to_email'     => $toEmail,
        'timeout'      => $timeout,
        'last_test_at' => (int)($current['last_test_at'] ?? 0),
        'last_error'   => (string)($current['last_error'] ?? ''),
    ];

    return ['config' => $config, 'error' => ''];
}

/**
 * Сохраняет настройки почты из POST формы «Настройки сайта».
 *
 * @param array $post
 * @return array ['success' => bool, 'error' => string]
 */
function mail_save_from_post(array $post): array {
    $built = mail_config_from_post($post);

    if ($built['error'] !== '') {
        return ['success' => false, 'error' => $built['error']];
    }

    if (!save_mail_config($built['config'])) {
        return ['success' => false, 'error' => 'Не удалось записать mail_config.json.'];
    }

    return ['success' => true, 'error' => ''];
}

/**
 * Отправляет тестовое письмо с текущими значениями формы.
 *
 * Сохранённый конфиг не меняется; в него пишутся только время теста
 * и последняя ошибка.
 *
 * @param array $post
 * @return array ['success' => bool, 'error' => string]
 */
function mail_test_from_post(array $post): array {
    $built = mail_config_from_post($post);

    if ($built['error'] !== '') {
        return ['success' => false, 'error' => $built['error']];
    }

    $config = $built['config'];
    $config['enabled'] = true;

    $result = send_site_mail(
        'Проверка почтового агента — ' . mail_site_name(),
        '<p>Это тестовое письмо от почтового агента AlPin CMS.</p><p>Если вы его получили, отправка форм работает.</p>',
        '',
        '',
        $config
    );

    $saved = get_mail_config(true);
    $saved['last_test_at'] = time();
    $saved['last_error'] = $result['success'] ? '' : (string)$result['error'];
    save_mail_config($saved);

    return $result;
}