<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['project_id'])) exit;

$db = (new Database())->getConnection();
$project_id = (int)$_GET['project_id'];

// Берем владельца + участников
$stmt = $db->prepare("
    SELECT u.id, u.email FROM users u 
    JOIN projects p ON p.owner_id = u.id WHERE p.id = ?
    UNION
    SELECT u.id, u.email FROM users u 
    JOIN project_members pm ON pm.user_id = u.id WHERE pm.project_id = ?
");
$stmt->execute([$project_id, $project_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));