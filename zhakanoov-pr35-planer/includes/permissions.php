<?php
// Функция возвращает роль: 'owner', 'manager', 'member' или null (нет доступа)
function getUserProjectRole($db, $project_id, $user_id) {
    // 1. Проверяем, не владелец ли это
    $stmt = $db->prepare("SELECT owner_id FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project && $project['owner_id'] == $user_id) {
        return 'owner';
    }

    // 2. Ищем пользователя в таблице участников
    $stmt = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($member) {
        return $member['role']; // вернет 'manager' или 'member'
    }

    return null; // Вообще нет доступа к проекту
}