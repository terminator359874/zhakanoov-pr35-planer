<?php
session_start();
require 'config/database.php';
require 'includes/permissions.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) throw new Exception("Не авторизован");

    $db = (new Database())->getConnection();
    
    $project_id = $_POST['project_id'] ?? null;
    $target_user_id = $_POST['user_id'] ?? null;
    $new_role = $_POST['role'] ?? null; // 'manager' или 'member'
    $current_user_id = $_SESSION['user_id'];

    if (!in_array($new_role, ['manager', 'member'])) {
        throw new Exception("Недопустимая роль");
    }

    // 1. Проверяем, что текущий пользователь — ВЛАДЕЛЕЦ проекта
    $my_role = getUserProjectRole($db, $project_id, $current_user_id);
    if ($my_role !== 'owner') {
        throw new Exception("Только владелец проекта может менять роли!");
    }

    // 2. Проверяем, что мы не пытаемся изменить роль самому владельцу
    // (Хотя владелец и так не в таблице members, но защита не помешает)
    $stmt = $db->prepare("SELECT owner_id FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if ($project['owner_id'] == $target_user_id) {
        throw new Exception("Нельзя изменить роль владельца проекта!");
    }

    // 3. Обновляем роль
    $stmt = $db->prepare("UPDATE project_members SET role = ? WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$new_role, $project_id, $target_user_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}