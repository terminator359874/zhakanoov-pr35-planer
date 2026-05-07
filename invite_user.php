<?php
session_start();
require 'config/database.php';

// Подключаем файлы PHPMailer (укажи правильные пути!)
require 'libs/PHPMailer/Exception.php';
require 'libs/PHPMailer/PHPMailer.php';
require 'libs/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']))
        throw new Exception('Вы не авторизованы.');

    $db = (new Database())->getConnection();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $project_id = $_POST['project_id'];
    $current_user_id = $_SESSION['user_id'];

    if (!$email)
        throw new Exception('Некорректный email.');

    // 1. Проверка прав и лимита (как раньше)
    $stmt = $db->prepare("SELECT name FROM projects WHERE id = ? AND owner_id = ?");
    $stmt->execute([$project_id, $current_user_id]);
    $project = $stmt->fetch();

    if (!$project)
        throw new Exception('У вас нет прав или проект не найден.');

    // 2. Логика добавления в базу: вставляем в project_invitations
    // Сначала проверим, является ли пользователь уже участником проекта
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $is_registered = false;
    if ($user) {
        $is_registered = true;
        $stmt = $db->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user['id']]);
        if ($stmt->fetch()) {
            throw new Exception('Пользователь уже состоит в этом проекте.');
        }
    }

    // Проверяем, есть ли уже активное приглашение
    $stmt = $db->prepare("SELECT id FROM project_invitations WHERE project_id = ? AND to_email = ? AND status = 'pending'");
    $stmt->execute([$project_id, $email]);
    if ($stmt->fetch()) {
        throw new Exception('Приглашение уже отправлено этому пользователю и ожидает ответа.');
    }

    // Проверяем лимит участников (макс. 50)
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ?");
    $stmtCount->execute([$project_id]);
    if ((int)$stmtCount->fetchColumn() >= 50) {
        throw new Exception('Достигнут лимит участников проекта (максимум 50).');
    }

    // Создаем приглашение
    $stmt = $db->prepare("INSERT INTO project_invitations (project_id, from_user_id, to_email, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$project_id, $current_user_id, $email]);

    // 3. ОТПРАВКА ПИСЬМА
    $mail = new PHPMailer(true);

    // Настройки сервера
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';         // SMTP сервер (например, Gmail)
    $mail->SMTPAuth = true;
    $mail->Username = 'rdd294428@gmail.com'; // ТВОЯ ПОЧТА
    $mail->Password = 'fnlpjboqkruplwrt';    // ТВОЙ ПАРОЛЬ ПРИЛОЖЕНИЯ
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    // Получатели
    $mail->setFrom('your-bot-email@gmail.com', 'Task Planner Pro');
    $mail->addAddress($email);

    // Содержание
    $mail->isHTML(true);
    $mail->Subject = "Приглашение в проект: " . $project['name'];
    $mail->Body = "
        <h3>Вас пригласили!</h3>
        <p>Привет! Вас добавили в проект <b>" . htmlspecialchars($project['name']) . "</b>.</p>
        <p>Зайдите в свой личный кабинет, чтобы начать работу.</p>
        <br>
        <a href='http://zhakanoov-pr35-planer/register.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none;'>Перейти к задачам</a>
    ";

    $mail->send();

    if (!$is_registered) {
        echo json_encode([
            'success' => true,
            'message' => 'Письмо отправлено, но пользователь с таким email ещё не зарегистрирован.',
            'is_warning' => true
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Приглашение и письмо отправлены!'
        ]);
    }

} catch (Exception $e) {
    if (isset($mail) && $mail instanceof PHPMailer && !empty($mail->ErrorInfo)) {
        echo json_encode(['success' => false, 'error' => 'Ошибка почты: ' . $mail->ErrorInfo]);
    } else {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных.']);
}