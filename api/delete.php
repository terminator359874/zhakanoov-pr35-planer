<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// 1. Подключаем файл с классом
require_once '../config/database.php';

// 2. Проверяем метод
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// 3. Получаем данные
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!$data || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Field id is required']);
    exit;
}

try {
    // 4. Создаем объект Database ИМЕННО ЗДЕСЬ
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    // Проверяем наличие задачи
    $check = $db->prepare('SELECT id FROM tasks WHERE id = :id');
    $check->execute([':id' => $data['id']]);
    
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found']);
        exit;
    }

    // Удаляем задачу
    $stmt = $db->prepare('DELETE FROM tasks WHERE id = :id');
    $stmt->execute([':id' => $data['id']]);

    http_response_code(200);
    echo json_encode([
        'success' => true, 
        'message' => 'Task deleted',
        'id' => $data['id']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}