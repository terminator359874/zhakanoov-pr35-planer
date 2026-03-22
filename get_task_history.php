<?php
session_start();
require 'config/database.php';

$task_id = $_GET['task_id'] ?? null;
if (!$task_id) exit;

$db = (new Database())->getConnection();
$stmt = $db->prepare("
    SELECT a.*, u.name as user_name, u.email as user_email
    FROM project_activity a
    JOIN users u ON a.user_id = u.id
    WHERE a.task_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$task_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($history as $item) {
    $date = date('d.m H:i', strtotime($item['created_at']));
    $user = htmlspecialchars($item['user_name'] ?: $item['user_email']);
    echo "
    <div class='history-item'>
        <div class='history-meta'>
            <span class='history-user'>{$user}</span>
            <span class='history-date'>{$date}</span>
        </div>
        <div class='history-details'>".htmlspecialchars($item['details'])."</div>
    </div>";
}