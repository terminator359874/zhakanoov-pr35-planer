<?php
session_start();
require 'config/database.php';
require 'includes/permissions.php'; // Подключаем нашу функцию

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$db = (new Database())->getConnection();
$task_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// 1. Узнаем, к какому проекту относится задача
$stmt = $db->prepare("SELECT project_id FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    die("Задача не найдена.");
}

// 2. Получаем роль пользователя в этом проекте
$role = getUserProjectRole($db, $task['project_id'], $user_id);

// 3. ПРОВЕРКА ПРАВ: Удалять могут только Владелец и Менеджер
if ($role !== 'owner' && $role !== 'manager') {
    die("Ошибка доступа: у вас нет прав на удаление задач в этом проекте.");
}

// 4. Если проверка пройдена — удаляем
$stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);

header("Location: index.php");
exit;