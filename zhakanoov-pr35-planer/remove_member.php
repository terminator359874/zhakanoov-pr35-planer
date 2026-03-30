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
    $current_user_id = $_SESSION['user_id'];

    if (!$project_id || !$target_user_id) throw new Exception("Недостаточно данных");

    // 1. Проверяем права: только владелец может удалять
    $my_role = getUserProjectRole($db, $project_id, $current_user_id);
    if ($my_role !== 'owner') {
        throw new Exception("Только владелец проекта может удалять участников.");
    }

    // 2. Дополнительная защита: нельзя удалить владельца (самого себя)
    $stmt = $db->prepare("SELECT owner_id FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if ($project['owner_id'] == $target_user_id) {
        throw new Exception("Нельзя удалить владельца проекта.");
    }

    // 3. Удаляем из таблицы участников
    $stmt = $db->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $target_user_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}