<?php
session_start();
require 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] . ' 00:00:00' : date('Y-m-01 00:00:00');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] . ' 23:59:59' : date('Y-m-t 23:59:59');

try {
    $db = (new Database())->getConnection();

    $sql = "
        SELECT t.id, t.title, t.priority, t.status, t.deadline, p.name as project_name
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        LEFT JOIN project_members pm ON p.id = pm.project_id
        WHERE (p.owner_id = :user_id OR pm.user_id = :user_id)
          AND t.deadline >= :start_date 
          AND t.deadline <= :end_date
        GROUP BY t.id
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'user_id' => $user_id,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);

    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tasksByDay = [];
    foreach ($tasks as $task) {
        $day = date('Y-m-d', strtotime($task['deadline']));
        if (!isset($tasksByDay[$day])) {
            $tasksByDay[$day] = [];
        }
        $tasksByDay[$day][] = $task;
    }

    echo json_encode([
        'success' => true,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'tasksByDay' => $tasksByDay
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
