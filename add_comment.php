<?php
session_start();
require 'config/database.php';

header('Content-Type: application/json');

// --- ФУНКЦИЯ ЛОГИРОВАНИЯ ---
function logActivity($db, $projectId, $userId, $details, $taskId = null) {
    $stmt = $db->prepare("INSERT INTO project_activity (project_id, user_id, details, task_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$projectId, $userId, $details, $taskId]);
}

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Вы не авторизованы.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Неверный метод запроса.');
    }

    $database = new Database();
    $db = $database->getConnection();

    $task_id = $_POST['task_id'] ?? null;
    $comment_text = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (!$task_id || empty($comment_text)) {
        throw new Exception('Комментарий не может быть пустым.');
    }

    if (mb_strlen($comment_text) > 500) {
        throw new Exception('Комментарий слишком длинный. Максимум 500 символов.');
    }

    // Сохраняем комментарий
    $stmt = $db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$task_id, $user_id, $comment_text]);
    
    $comment_id = $db->lastInsertId();

    // Получаем созданный комментарий для вывода
    $stmt = $db->prepare("
        SELECT tc.comment, tc.created_at, u.name as author_name 
        FROM task_comments tc 
        JOIN users u ON tc.user_id = u.id 
        WHERE tc.id = ?
    ");
    $stmt->execute([$comment_id]);
    $new_comment = $stmt->fetch(PDO::FETCH_ASSOC);

    // НОВОЕ: Получаем данные задачи для ленты активности
    $stmtTask = $db->prepare("SELECT project_id, title FROM tasks WHERE id = ?");
    $stmtTask->execute([$task_id]);
    $taskData = $stmtTask->fetch(PDO::FETCH_ASSOC);

    if ($taskData) {
        // Записываем в лог
        logActivity($db, $taskData['project_id'], $user_id, "добавил(а) комментарий к задаче: " . htmlspecialchars($taskData['title']), $task_id);
    }

    echo json_encode(['success' => true, 'comment' => $new_comment]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Произошла ошибка базы данных при сохранении.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>