<?php
// Получаем историю для конкретной задачи
$logStmt = $db->prepare("
    SELECT al.*, u.name as user_name 
    FROM activity_log al
    JOIN users u ON al.user_id = u.id
    WHERE al.task_id = :tid 
    ORDER BY al.created_at DESC LIMIT 10
");
$logStmt->execute(['tid' => $task_id]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mt-4">
    <h6>История изменений</h6>
    <div id="task-history" class="list-group list-group-flush border rounded" style="max-height: 200px; overflow-y: auto;">
        <?php foreach ($logs as $log): ?>
            <div class="list-group-item small py-2">
                <span class="text-primary fw-bold"><?= htmlspecialchars($log['user_name']) ?></span>: 
                <?= htmlspecialchars($log['message']) ?>
                <div class="text-muted" style="font-size: 0.7rem;"><?= date('d.m.Y H:i', strtotime($log['created_at'])) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>