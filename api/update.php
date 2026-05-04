<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

// Разрешаем только PUT для обновления данных
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

// Для обновления обязательно нужен id
if (!$data || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Field id is required']);
    exit;
}

try {
    // 1. Подключаем файл с классом (проверьте путь!)
    require_once '../config/database.php'; 

    // 2. Создаем объект базы данных и получаем соединение
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not connect to database']);
        exit;
    }

    // 3. Проверяем существование записи
    $check = $pdo->prepare('SELECT id FROM tasks WHERE id = :id');
    $check->execute([':id' => $data['id']]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found']);
        exit;
    }

    // 4. Обновляем задачу
    $sql = "UPDATE tasks SET 
                title = :title, 
                description = :description, 
                priority = :priority, 
                deadline = :deadline, 
                project_id = :project_id, 
                assigned_to = :assigned_to, 
                status = :status 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

$stmt->execute([
    // Если в JSON пришло 'name', используем его, иначе ищем 'title'
    ':title'       => $data['title'] ?? $data['name'] ?? null, 
    ':description' => $data['description'] ?? null,
    ':priority'    => $data['priority'] ?? 'low', // Значение по умолчанию
    ':deadline'    => $data['deadline'] ?? null,
    ':project_id'  => $data['project_id'] ?? 1,   // ID существующего проекта
    ':assigned_to' => $data['assigned_to'] ?? null,
    ':status'      => $data['status'] ?? 'new',
    ':id'          => $data['id']
]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Task updated'], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}