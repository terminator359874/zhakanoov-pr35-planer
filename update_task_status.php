<?php
session_start();
require 'config/database.php';

header('Content-Type: application/json');

// 1. Базовая проверка авторизации и данных
if (!isset($_SESSION['user_id']) || !isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$task_id = (int)$_POST['id'];
$status = $_POST['status'];
$user_id = $_SESSION['user_id'];

// 2. Защита: проверяем, что статус входит в список разрешенных
$allowed_statuses = ['new', 'working', 'progress', 'done'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

// 3. ОБНОВЛЕННЫЙ ЗАПРОС:
// Мы ищем задачу, соединяем её с проектом и проверяем таблицу участников.
// Задача обновится ТОЛЬКО если user_id совпадает с владельцем проекта 
// ИЛИ если user_id найден в таблице project_members для этого проекта.
$stmt = $db->prepare("
    UPDATE tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    SET t.status = :status
    WHERE t.id = :id 
      AND (p.owner_id = :user_id OR pm.user_id = :user_id)
");

$stmt->execute([
    'status' => $status,
    'id' => $task_id,
    'user_id' => $user_id
]);

// 4. Проверяем результат
// rowCount() покажет, была ли реально обновлена хоть одна строка.
// Если у пользователя нет прав, запрос ничего не найдет и вернет 0.
if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен или задача не найдена']);
}