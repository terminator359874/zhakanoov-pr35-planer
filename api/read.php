<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if ($pdo === null) {
        throw new Exception("Не удалось подключиться к базе данных");
    }


    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;


    $stmt = $pdo->prepare('SELECT * FROM tasks LIMIT :limit');
    

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}