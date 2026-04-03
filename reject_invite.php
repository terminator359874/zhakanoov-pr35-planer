<?php
session_start();
require 'config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) throw new Exception('Вы не авторизованы.');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Неверный метод запроса.');

    $db = (new Database())->getConnection();
    $invite_id = $_POST['invite_id'] ?? null;
    $user_id = $_SESSION['user_id'];

    if (!$invite_id) throw new Exception('Не указан ID приглашения.');

    // Получаем email пользователя
    $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) throw new Exception('Пользователь не найден.');

    // Обновляем статус приглашения на declined
    $stmt = $db->prepare("UPDATE project_invitations SET status = 'declined' WHERE id = ? AND to_email = ? AND status = 'pending'");
    $stmt->execute([$invite_id, $user['email']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Приглашение не найдено или уже обработано.');
    }

    echo json_encode(['success' => true, 'message' => 'Приглашение отклонено.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных.']);
}
?>
