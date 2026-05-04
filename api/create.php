<?php
// Устанавливаем заголовки для JSON и CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 1. Подключаем БД (проверьте путь, если файл в другой папке)
require_once '../config/database.php'; 

// Разрешаем только POST-запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Получаем и декодируем JSON
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty body or invalid JSON']);
    exit;
}

// 2. Проверка обязательных полей (name и owner_id согласно вашей схеме)
$requiredFields = ['name', 'owner_id'];
$errors = [];

foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
        $errors[] = "Field '$field' is required";
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation failed', 'details' => $errors]);
    exit;
}

try {
    // Создаем объект класса Database из вашего файла
    $database = new Database();
    
    // Получаем подключение через метод getConnection()
    $pdo = $database->getConnection(); 

    if (!$pdo) {
        throw new Exception("Не удалось установить соединение с БД");
    }

    // Далее ваш SQL запрос...
    $sql = "INSERT INTO projects (name, description, visibility, owner_id) 
            VALUES (:name, :description, :visibility, :owner_id)";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':name'        => $data['name'],
        ':description' => $data['description'] ?? null,
        ':visibility'  => $data['visibility'] ?? 'private',
        ':owner_id'    => (int)$data['owner_id']
    ]);

    // ... остальной код вывода JSON

    $newId = $pdo->lastInsertId();

    http_response_code(201); // Created
    echo json_encode([
        'success' => true,
        'id'      => $newId,
        'message' => 'Project created successfully',
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    // Для разработки можно оставить $e->getMessage(), для продакшена лучше скрыть
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}