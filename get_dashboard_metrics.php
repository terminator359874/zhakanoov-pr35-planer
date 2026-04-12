<?php
// get_dashboard_metrics.php
error_reporting(0); // Описание: Не указывать технические ошибки
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$cacheDir = __DIR__ . '/cache';

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}

$cacheFile = $cacheDir . "/dashboard_metrics_{$user_id}.json";
$cacheTTL = 10; // 10 секунд кеш для авто-обновления

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTTL)) {
    echo file_get_contents($cacheFile);
    exit;
}

require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();

    // Задачи только в проектах пользователя
    // Исключая старше 12 месяцев
    $stmt = $db->prepare("
        SELECT 
            COUNT(t.id) as total,
            SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN t.status = 'progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN t.deadline < CURDATE() AND t.status != 'done' THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN t.priority = 'high' THEN 1 ELSE 0 END) as priority_high,
            SUM(CASE WHEN t.priority = 'medium' THEN 1 ELSE 0 END) as priority_medium,
            SUM(CASE WHEN t.priority = 'low' THEN 1 ELSE 0 END) as priority_low
        FROM tasks t
        WHERE t.project_id IN (
            SELECT p.id 
            FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE p.owner_id = :user_id OR pm.user_id = :user_id
        )
        AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    ");
    
    $stmt->execute(['user_id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $data = [
        'total' => (int)$result['total'],
        'completed' => (int)$result['completed'],
        'in_progress' => (int)$result['in_progress'],
        'overdue' => (int)$result['overdue'],
        'priorities' => [
            'high' => (int)$result['priority_high'],
            'medium' => (int)$result['priority_medium'],
            'low' => (int)$result['priority_low']
        ]
    ];

    $json = json_encode($data);
    @file_put_contents($cacheFile, $json);

    echo $json;

} catch (Exception $e) {
    // Не показываем ошибку пользователю, возвращаем нули
    echo json_encode([
        'total' => 0, 
        'completed' => 0, 
        'in_progress' => 0, 
        'overdue' => 0,
        'priorities' => ['high' => 0, 'medium' => 0, 'low' => 0]
    ]);
}
?>
