<?php
session_start();
require 'config/database.php';

header('Content-Type: application/json');

// --- 0. ФУНКЦИЯ ЛОГИРОВАНИЯ (обязательно должна быть тут) ---
function logActivity($db, $projectId, $userId, $details, $taskId = null) {
    try {
        $stmt = $db->prepare("INSERT INTO project_activity (project_id, user_id, details, task_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$projectId, $userId, $details, $taskId]);
    } catch (Exception $e) {
        // Ошибка лога не должна прерывать работу основного скрипта
    }
}

try {
    // 1. Проверка авторизации
    if (!isset($_SESSION['user_id']) || !isset($_POST['id']) || !isset($_POST['status'])) {
        throw new Exception('Invalid request');
    }

    $database = new Database();
    $db = $database->getConnection();

    $task_id = (int)$_POST['id'];
    $status = $_POST['status'];
    $user_id = $_SESSION['user_id'];

    $allowed_statuses = ['new', 'working', 'progress', 'done'];
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception('Invalid status');
    }

    // 2. СНАЧАЛА получаем project_id, чтобы знать, куда писать лог
    $stmtTask = $db->prepare("SELECT project_id, title FROM tasks WHERE id = ?");
    $stmtTask->execute([$task_id]);
    $taskData = $stmtTask->fetch(PDO::FETCH_ASSOC);

    if (!$taskData) {
        throw new Exception('Задача не найдена');
    }

    // 3. ОБНОВЛЕНИЕ СТАТУСА (с проверкой прав)
    $stmt = $db->prepare("
        UPDATE tasks t
        INNER JOIN projects p ON t.project_id = p.id
        LEFT JOIN project_members pm ON p.id = pm.project_id
        SET t.status = :status
        WHERE t.id = :id 
          AND (p.owner_id = :user_id OR pm.user_id = :user_id)
    ");

    $stmt->execute([
        'status' => $status,
        'id' => $task_id,
        'user_id' => $user_id
    ]);

    if ($stmt->rowCount() > 0) {
        // 4. ЗАПИСЬ В ЛЕНТУ (если статус реально изменился)
        $statusLabels = ['new' => 'Новые', 'working' => 'В работе', 'progress' => 'В процессе', 'done' => 'Завершены'];
        $details = "изменил(а) статус задачи «" . $taskData['title'] . "» на «" . $statusLabels[$status] . "»";
        
        logActivity($db, $taskData['project_id'], $user_id, $details, $task_id);

        echo json_encode(['success' => true]);
    } else {
        // Если статус не изменился (перетащили в ту же колонку) или нет прав
        echo json_encode(['success' => true, 'info' => 'No changes made']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}