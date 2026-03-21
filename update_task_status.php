<?php
session_start();
require 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$task_id = (int)$_POST['id'];
$status = $_POST['status'];
$user_id = $_SESSION['user_id'];

// Массив разрешенных статусов для защиты от инъекций значений
$allowed_statuses = ['new', 'working', 'progress', 'done'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

// Проверяем, что задача принадлежит проекту, владельцем которого является текущий пользователь
$stmt = $db->prepare("
    UPDATE tasks t
    INNER JOIN projects p ON t.project_id = p.id
    SET t.status = :status
    WHERE t.id = :id AND p.owner_id = :user_id
");

$result = $stmt->execute([
    'status' => $status,
    'id' => $task_id,
    'user_id' => $user_id
]);

echo json_encode(['success' => $result]);