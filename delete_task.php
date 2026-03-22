<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$task_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Удаляем только если пользователь имеет доступ к проекту
$stmt = $db->prepare("
    DELETE t FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON (p.id = pm.project_id AND pm.user_id = :user_id)
    WHERE t.id = :id 
      AND (p.owner_id = :user_id OR pm.user_id = :user_id)
");

$stmt->execute([
    'id' => $task_id,
    'user_id' => $user_id
]);

// После удаления возвращаемся на главную
header('Location: index.php');
exit;