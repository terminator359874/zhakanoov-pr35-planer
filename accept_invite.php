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

    // Получаем email текущего пользователя
    $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) throw new Exception('Пользователь не найден.');

    // Проверяем, есть ли приглашение "в ожидании"
    $stmt = $db->prepare("SELECT id, project_id FROM project_invitations WHERE id = ? AND to_email = ? AND status = 'pending'");
    $stmt->execute([$invite_id, $user['email']]);
    $invite = $stmt->fetch();

    if (!$invite) throw new Exception('Приглашение не найдено или уже обработано.');

    // Проверяем лимит участников (макс. 50)
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ?");
    $stmtCount->execute([$invite['project_id']]);
    if ((int)$stmtCount->fetchColumn() >= 50) {
        throw new Exception('Достигнут лимит участников проекта (максимум 50). Принятие приглашения невозможно.');
    }

    // Меняем статус на accepted
    $stmt = $db->prepare("UPDATE project_invitations SET status = 'accepted' WHERE id = ?");
    $stmt->execute([$invite_id]);

    // Добавляем в project_members
    $stmt = $db->prepare("INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?, ?)");
    $stmt->execute([$invite['project_id'], $user_id]);

    echo json_encode(['success' => true, 'message' => 'Приглашение принято.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных.']);
}
?>
