<?php
session_start();
require 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $db = (new Database())->getConnection();

    $now = isset($_GET['now']) ? $_GET['now'] : date('Y-m-d H:i:s');
    $end = date('Y-m-d H:i:s', strtotime($now) + 15 * 60);

    $sql = "
        SELECT DISTINCT t.id, t.title, t.deadline, p.name as project_name
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        LEFT JOIN project_members pm ON p.id = pm.project_id
        WHERE (p.owner_id = :user_id OR pm.user_id = :user_id)
          AND t.status != 'done'
          AND t.deadline > :now 
          AND t.deadline <= :end
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute(['user_id' => $user_id, 'now' => $now, 'end' => $end]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'tasks' => $tasks]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
