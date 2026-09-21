<?php
header('Content-Type: application/json');
$response = ['status' => '', 'head' => '', 'message' => ''];

session_start();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $response['status'] = 'error';
    $response['head'] = 'Ошибка безопасности!';
    $response['message'] = 'Произошла ошибка при проверке токена безопасности. Попробуйте позже.';
    die(json_encode($response));
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

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
        // Для quiz — если required_fields нет, значит все поля необязательные
    }

    if (!empty($errors)) {
        echo json_encode([
            'status' => 'error',
            'head' => 'Ошибка валидации',
            'message' => 'Заполните обязательные поля: ' . implode(', ', $errors)
        ]);
        exit;
    }
    
    $siteName = 'AlPinCMS';
    $siteEmail = 'info@alpincms.ru';
    
    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = "UTF-8";
        $mail->Encoding = 'base64';
        $mail->isSMTP();
        // Раскомментируй для реальной отправки
        //$mail->Host = 'smtp_host';
        $mail->SMTPAuth = true;
        $mail->Username = $siteEmail;
        // Раскомментируй для реальной отправки
        //$mail->Password = 'password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom($siteEmail, $siteName);
        $mail->addAddress($siteEmail, $siteName . ' Контакты');
        if (!empty($email)) {
            $mail->addReplyTo($email, $name);
        }

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

        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $body;

        // Раскомментируй для реальной отправки
        // $mail->send();

        $response['status'] = 'success';
        $response['head'] = 'Спасибо за обращение!';
        $response['message'] = !empty($successMessage) 
            ? $successMessage 
            : 'Ваше обращение успешно отправлено. Эксперты компании ' . $siteName . ' свяжутся с вами в ближайшее время.';
        
    } catch (Exception $e) {
        $response['status'] = 'error';
        $response['head'] = 'Ошибка при отправке!';
        $response['message'] = 'К сожалению, сервер временно перегружен и не смог обработать запрос. Пожалуйста, повторите попытку позже.';
    }
    
} else {
    $response['status'] = 'error';
    $response['message'] = 'Неверный метод запроса.';
}

echo json_encode($response);