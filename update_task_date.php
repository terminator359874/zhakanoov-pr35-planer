<?php
session_start();
require 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$task_id = $data['task_id'] ?? null;
$new_date = $data['new_date'] ?? null; // format Y-m-d

if (!$task_id || !$new_date) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

// Basic validation for new_date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date format']);
    exit;
}

try {
    $db = (new Database())->getConnection();

    // Check permissions and get current deadline
    $sql = "
        SELECT t.id, t.deadline
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        LEFT JOIN project_members pm ON p.id = pm.project_id
        WHERE t.id = :task_id AND (p.owner_id = :user_id OR pm.user_id = :user_id)
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute(['task_id' => $task_id, 'user_id' => $user_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        echo json_encode(['success' => false, 'error' => 'Task not found or access denied']);
        exit;
    }

    $current_deadline = $task['deadline']; // ex: '2026-04-14 15:30:00'
    $time_part = '00:00:00';
    if ($current_deadline && strlen($current_deadline) >= 19) {
        $time_part = substr($current_deadline, 11, 8);
    }

    $new_deadline = $new_date . ' ' . $time_part;

    if (strtotime($new_deadline) < time()) {
        echo json_encode(['success' => false, 'error' => 'Дедлайн не может быть в прошлом']);
        exit;
    }

    $updateSql = "UPDATE tasks SET deadline = :deadline WHERE id = :id";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->execute(['deadline' => $new_deadline, 'id' => $task_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
