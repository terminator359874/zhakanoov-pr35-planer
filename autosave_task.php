<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$task_id    = (int)($_POST['id'] ?? 0);
$title      = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority   = $_POST['priority'] ?? '';

if (!$task_id || $title === '' || strlen($title) > 200) {
    echo json_encode(['success' => false, 'error' => 'Некорректные данные']);
    exit;
}

if (mb_strlen($description) > 5000) {
    echo json_encode(['success' => false, 'error' => 'Описание превышает 5000 символов']);
    exit;
}

if (!in_array($priority, ['low', 'medium', 'high'])) {
    echo json_encode(['success' => false, 'error' => 'Некорректный приоритет']);
    exit;
}

try {
    $db = (new Database())->getConnection();

    // Проверяем права доступа
    $stmt = $db->prepare("
        SELECT t.id FROM tasks t
        JOIN projects p ON t.project_id = p.id
        LEFT JOIN project_members pm ON p.id = pm.project_id
        WHERE t.id = :task_id AND (p.owner_id = :user_id OR pm.user_id = :user_id)
        LIMIT 1
    ");
    $stmt->execute(['task_id' => $task_id, 'user_id' => $user_id]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Нет доступа']);
        exit;
    }

    $update = $db->prepare("UPDATE tasks SET title = ?, description = ?, priority = ? WHERE id = ?");
    $update->execute([$title, $description, $priority, $task_id]);

    echo json_encode(['success' => true, 'saved_at' => date('H:i:s')]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера']);
}
