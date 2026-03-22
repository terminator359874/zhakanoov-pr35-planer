<?php
session_start();
require 'config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Вы не авторизованы.');
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Неверный метод запроса.');
    }

    $db = (new Database())->getConnection();
    
    $project_id = $_POST['project_id'] ?? null;
    // Очищаем и проверяем корректность email
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $current_user_id = $_SESSION['user_id'];

    if (!$project_id) {
        throw new Exception('Проект не указан.');
    }
    if (!$email) {
        throw new Exception('Пожалуйста, введите корректный email-адрес.');
    }

    // 1. Проверяем, есть ли права (приглашать может только владелец проекта)
    $stmt = $db->prepare("SELECT owner_id FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project || $project['owner_id'] != $current_user_id) {
        throw new Exception('У вас нет прав для приглашения участников в этот проект.');
    }

    // 2. Проверяем лимит: максимум 50 участников
    $stmt = $db->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $member_count = $stmt->fetchColumn();

    if ($member_count >= 50) {
        throw new Exception('Достигнут лимит: в проекте не может быть больше 50 участников.');
    }

    // 3. Ищем пользователя по email
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ВАЖНО: Мы не выдаем ошибку, если пользователя нет. Это защита от перебора email-ов.
    if ($user) {
        $new_user_id = $user['id'];
        
        // Проверяем, не состоит ли он уже в проекте
        $stmt = $db->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $new_user_id]);
        $already_member = $stmt->fetch();

        if (!$already_member) {
            // Добавляем участника в проект
            $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'member')");
            $stmt->execute([$project_id, $new_user_id]);
            
            // Если у тебя на сервере настроен почтовый агент, раскомментируй строку ниже:
            // mail($email, "Приглашение в проект", "Вас добавили в проект в Task Planner. Зайдите в систему, чтобы увидеть его.");
        }
    }

    // Возвращаем один и тот же ответ всегда, чтобы не палить базу данных
    echo json_encode([
        'success' => true, 
        'message' => 'Приглашение обработано! Если пользователь зарегистрирован, он увидит проект.'
    ]);

} catch (PDOException $e) {
    // Скрываем SQL-ошибки
    echo json_encode(['success' => false, 'error' => 'Произошла ошибка при сохранении. Попробуйте позже.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>