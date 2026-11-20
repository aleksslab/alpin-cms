<?php
/**
 * Эндпоинт отправки писем из модулей (contacts, lead-form, quiz).
 *
 * Транспорт и SMTP-настройки живут в `config/core/mail.php`; здесь только
 * проверка CSRF, разбор полей формы, сборка письма и JSON-ответ. Формат
 * ответа прежний: `{status, head, message}`.
 */

header('Content-Type: application/json');
$response = ['status' => '', 'head' => '', 'message' => ''];

require_once __DIR__ . '/../config/core/security.php';
require_once __DIR__ . '/../config/core/mail.php';

session_start();

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $response['status'] = 'error';
    $response['head'] = 'Ошибка безопасности!';
    $response['message'] = 'Произошла ошибка при проверке токена безопасности. Попробуйте позже.';
    die(json_encode($response));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Определяем модуль
    $module = trim($_POST['module'] ?? 'contacts');
    $moduleTitle = trim($_POST['module_title'] ?? '');

    // Общие поля
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Для модуля contacts
    $petType = trim($_POST['pet_type'] ?? '');
    $successMessage = trim($_POST['success_message'] ?? '');

    // Для модуля quiz
    $answers = $_POST['answers'] ?? '';
    $quizTitle = trim($_POST['title'] ?? '');

    // Получаем список обязательных полей
    $requiredFields = json_decode($_POST['required_fields'] ?? '[]', true);
    $errors = [];

    if (!empty($requiredFields)) {
        foreach ($requiredFields as $field) {
            if (empty(trim($_POST[$field] ?? ''))) {
                $errors[] = $field;
            }
        }
    } else {
        // Если required_fields не передан — проверяем только для модулей, где email обязателен
        if ($module === 'contacts' || $module === 'lead-form') {
            if (empty($email)) $errors[] = 'email';
        }
    }

    if (!empty($errors)) {
        echo json_encode([
            'status' => 'error',
            'head' => 'Ошибка валидации',
            'message' => 'Заполните обязательные поля: ' . implode(', ', $errors)
        ]);
        exit;
    }

    $siteName = mail_site_name();

    $body = '';
    $subject = '';

    switch ($module) {
        case 'quiz':
            $subject = 'Новая заявка с квиза ' . $siteName . (!empty($quizTitle) ? ' - ' . $quizTitle : '');
            $body = '<h2>Заявка с квиза ' . $siteName . '</h2>';
            if (!empty($quizTitle)) $body .= '<h3>' . htmlspecialchars($quizTitle) . '</h3>';
            $body .= '<hr>';
            if (!empty($name)) $body .= '<p><strong>Имя:</strong> ' . htmlspecialchars($name) . '</p>';
            if (!empty($email)) $body .= '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>';
            if (!empty($phone)) $body .= '<p><strong>Телефон:</strong> ' . htmlspecialchars($phone) . '</p>';
            if (!empty($answers)) {
                $answersData = json_decode($answers, true);
                if (is_array($answersData) && !empty($answersData)) {
                    $body .= '<hr><h3>Ответы на квиз:</h3>';
                    foreach ($answersData as $q => $a) {
                        $label = str_replace('q', 'Вопрос ', $q);
                        $value = is_array($a) ? implode(', ', $a) : $a;
                        $body .= '<p><strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($value) . '</p>';
                    }
                }
            }
            if (!empty($message)) {
                $body .= '<hr><p><strong>Дополнительная информация:</strong><br>' . nl2br(htmlspecialchars($message)) . '</p>';
            }
            break;

        case 'contacts':
            $subject = 'Новое сообщение с сайта ' . $siteName . (!empty($moduleTitle) ? ' - ' . $moduleTitle : ' - Контактная форма');
            $body = '<h2>Сообщение с сайта ' . $siteName . '</h2>';
            if (!empty($moduleTitle)) $body .= '<h3>' . htmlspecialchars($moduleTitle) . '</h3>';
            $body .= '<hr>';
            if (!empty($name)) $body .= '<p><strong>Имя:</strong> ' . htmlspecialchars($name) . '</p>';
            if (!empty($email)) $body .= '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>';
            if (!empty($phone)) $body .= '<p><strong>Телефон:</strong> ' . htmlspecialchars($phone) . '</p>';
            if (!empty($petType)) $body .= '<p><strong>Тип питомца:</strong> ' . htmlspecialchars($petType) . '</p>';
            if (!empty($message)) $body .= '<p><strong>Сообщение:</strong><br>' . nl2br(htmlspecialchars($message)) . '</p>';
            break;

        case 'lead-form':
        default:
            $subject = 'Новое сообщение с сайта ' . $siteName . (!empty($moduleTitle) ? ' - ' . $moduleTitle : ' - Форма захвата лидов');
            $body = '<h2>Сообщение с сайта ' . $siteName . '</h2>';
            if (!empty($moduleTitle)) $body .= '<h3>' . htmlspecialchars($moduleTitle) . '</h3>';
            $body .= '<hr>';
            if (!empty($name)) $body .= '<p><strong>Имя:</strong> ' . htmlspecialchars($name) . '</p>';
            if (!empty($email)) $body .= '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>';
            if (!empty($phone)) $body .= '<p><strong>Телефон:</strong> ' . htmlspecialchars($phone) . '</p>';
            if (!empty($message)) $body .= '<p><strong>Сообщение:</strong><br>' . nl2br(htmlspecialchars($message)) . '</p>';
            break;
    }

    $result = send_site_mail($subject, $body, $email, $name);

    if ($result['success']) {
        $response['status'] = 'success';
        $response['head'] = 'Спасибо за обращение!';
        $response['message'] = !empty($successMessage)
            ? $successMessage
            : 'Ваше обращение успешно отправлено. Эксперты компании ' . $siteName . ' свяжутся с вами в ближайшее время.';
    } else {
        // Причина остаётся в админке и журнале, посетителю — понятный текст.
        $response['status'] = 'error';
        $response['head'] = 'Не удалось отправить';
        $response['message'] = 'Сообщение не отправлено. Попробуйте позже или свяжитесь с нами по телефону.';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Неверный метод запроса.';
}

echo json_encode($response);