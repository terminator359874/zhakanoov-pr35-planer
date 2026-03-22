<?php
session_start();
require 'config/database.php'; // Правильный путь к твоей БД

header('Content-Type: application/json');

try {
    // Проверка авторизации (как у тебя в index.php)
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Вы не авторизованы.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Неверный метод запроса.');
    }

    $database = new Database();
    $db = $database->getConnection(); // Используем твою переменную $db

    $task_id = $_POST['task_id'] ?? null;
    $comment_text = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (!$task_id || empty($comment_text)) {
        throw new Exception('Комментарий не может быть пустым.');
    }

    if (mb_strlen($comment_text) > 500) {
        throw new Exception('Комментарий слишком длинный. Максимум 500 символов.');
    }

    // Сохраняем в БД (используем $db вместо $pdo)
    $stmt = $db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$task_id, $user_id, $comment_text]);
    
    $comment_id = $db->lastInsertId();

    // Получаем созданный комментарий
    $stmt = $db->prepare("
        SELECT tc.comment, tc.created_at, u.name as author_name 
        FROM task_comments tc 
        JOIN users u ON tc.user_id = u.id 
        WHERE tc.id = ?
    ");
    $stmt->execute([$comment_id]);
    $new_comment = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'comment' => $new_comment]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Произошла ошибка базы данных при сохранении.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>