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
    if (!isset($_SESSION['user_id'])) throw new Exception('Вы не авторизованы.');
    
    $db = (new Database())->getConnection();
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $project_id = $_POST['project_id'];
    $current_user_id = $_SESSION['user_id'];

    if (!$email) throw new Exception('Некорректный email.');

    // 1. Проверка прав и лимита (как раньше)
    $stmt = $db->prepare("SELECT name FROM projects WHERE id = ? AND owner_id = ?");
    $stmt->execute([$project_id, $current_user_id]);
    $project = $stmt->fetch();

    if (!$project) throw new Exception('У вас нет прав или проект не найден.');

    // 2. Логика добавления в базу (оставляем ту же)
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $db->prepare("INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)");
        $stmt->execute([$project_id, $user['id']]);
    }

    // 3. ОТПРАВКА ПИСЬМА
    $mail = new PHPMailer(true);

    // Настройки сервера
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';         // SMTP сервер (например, Gmail)
    $mail->SMTPAuth   = true;
    $mail->Username   = 'rdd294428@gmail.com'; // ТВОЯ ПОЧТА
    $mail->Password   = 'wgfmtauhcjrelyil';    // ТВОЙ ПАРОЛЬ ПРИЛОЖЕНИЯ
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Получатели
    $mail->setFrom('your-bot-email@gmail.com', 'Task Planner Pro');
    $mail->addAddress($email); 

    // Содержание
    $mail->isHTML(true);
    $mail->Subject = "Приглашение в проект: " . $project['name'];
    $mail->Body    = "
        <h3>Вас пригласили!</h3>
        <p>Привет! Вас добавили в проект <b>" . htmlspecialchars($project['name']) . "</b>.</p>
        <p>Зайдите в свой личный кабинет, чтобы начать работу.</p>
        <br>
        <a href='http://твой-сайт.ru/login.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none;'>Перейти к задачам</a>
    ";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Приглашение и письмо отправлены!']);

} catch (Exception $e) {
    // Если ошибка в PHPMailer, она попадет сюда
    echo json_encode(['success' => false, 'error' => 'Ошибка: ' . $mail->ErrorInfo]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных.']);
}