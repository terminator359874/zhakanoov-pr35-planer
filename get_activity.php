<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) exit;

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'];
$project_id = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;

if ($project_id) {
    // Лента для конкретного проекта
    $stmt = $db->prepare("
        SELECT a.*, u.name, u.email 
        FROM project_activity a
        JOIN users u ON a.user_id = u.id
        WHERE a.project_id = :pid
        ORDER BY a.created_at DESC LIMIT 15
    ");
    $stmt->execute(['pid' => $project_id]);
} else {
    // Общая лента для всех проектов, к которым есть доступ
    $stmt = $db->prepare("
        SELECT a.*, u.name, u.email, p.name as project_name
        FROM project_activity a
        JOIN users u ON a.user_id = u.id
        JOIN projects p ON a.project_id = p.id
        LEFT JOIN project_members pm ON p.id = pm.project_id
        WHERE p.owner_id = :uid OR pm.user_id = :uid
        ORDER BY a.created_at DESC LIMIT 15
    ");
    $stmt->execute(['uid' => $user_id]);
}

$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($activities)) {
    echo "<div style='font-size: 12px; color: var(--text-dim);'>Пока нет событий.</div>";
    exit;
}

// Выводим HTML
foreach ($activities as $act) {
    $time = date('d.m H:i', strtotime($act['created_at']));
    $userName = htmlspecialchars($act['name'] ?: $act['email']);
    
    // Если мы на общей доске, показываем название проекта
    $projectLabel = (!$project_id && isset($act['project_name'])) 
        ? "<div style='font-size: 10px; color: var(--accent); margin-bottom: 2px;'>".htmlspecialchars($act['project_name'])."</div>" 
        : "";

    echo "
    <div style='margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border);'>
        {$projectLabel}
        <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;'>
            <strong style='font-size: 12px; color: var(--text-head);'>{$userName}</strong>
            <span style='font-size: 10px; color: var(--text-dim); font-family: \"JetBrains Mono\", monospace;'>{$time}</span>
        </div>
        <div style='font-size: 12px; color: var(--text); line-height: 1.4;'>".htmlspecialchars($act['details'])."</div>
    </div>";
}
?>