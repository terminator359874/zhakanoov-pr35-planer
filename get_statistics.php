<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || empty($_GET['project_id'])) {
    exit;
}

$db = (new Database())->getConnection();
$project_id = (int)$_GET['project_id'];

// SQL-запрос: собираем всех участников проекта и считаем их завершенные задачи
$stmt = $db->prepare("
    SELECT u.id, u.name, u.email, COUNT(t.id) as completed_tasks
    FROM (
        -- Владелец проекта
        SELECT owner_id as user_id FROM projects WHERE id = :pid
        UNION
        -- Приглашенные участники
        SELECT user_id FROM project_members WHERE project_id = :pid
    ) as members
    JOIN users u ON members.user_id = u.id
    -- Присоединяем только завершенные задачи этого проекта
    LEFT JOIN tasks t ON u.id = t.assigned_to AND t.project_id = :pid AND t.status = 'done'
    GROUP BY u.id, u.name, u.email
    ORDER BY completed_tasks DESC
");

$stmt->execute(['pid' => $project_id]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$stats) {
    echo "<div class='text-muted small'>Нет участников</div>";
    exit;
}

// Выводим HTML со статистикой
foreach ($stats as $row) {
    $name = htmlspecialchars($row['name'] ?: $row['email']);
    $count = (int)$row['completed_tasks'];
    
    // Если задач 0, делаем бейджик серым, если больше 0 — зеленым
    $badgeColor = $count > 0 ? '#1e9e52' : '#8b95a5';
    
    echo "
    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 12px; border-bottom: 1px dashed #eee; padding-bottom: 4px;'>
        <span style='color: var(--text-head); font-weight: 500;'>👤 {$name}</span>
        <span style='background: {$badgeColor}; color: white; padding: 2px 8px; border-radius: 12px; font-weight: 600; font-size: 11px;'>
            {$count}
        </span>
    </div>";
}
?>