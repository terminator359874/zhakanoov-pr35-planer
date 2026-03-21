<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $visibility = $_POST['visibility'] ?? 'private';
    $owner_id = $_SESSION['user_id'];

    // 0️⃣ Проверяем текущее количество проектов у пользователя
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM projects WHERE owner_id = :owner_id");
    $stmtCount->execute(['owner_id' => $owner_id]);
    $projectCount = $stmtCount->fetchColumn();

    // Простая валидация + Проверка лимита
    if (empty($name) || mb_strlen($name) > 200) {
        $error = "Название проекта обязательно и не должно превышать 200 символов.";
    } elseif ($projectCount >= 100) {
        // Ошибка, если проектов уже 100 или больше
        $error = "Вы достигли лимита! Максимум можно создать 100 проектов. Удалите неактуальные проекты, чтобы создать новые.";
    } else {
        // 1️⃣ Создаём проект
        $stmt = $db->prepare("
            INSERT INTO projects (name, description, visibility, owner_id)
            VALUES (:name, :description, :visibility, :owner_id)
        ");
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'visibility' => $visibility,
            'owner_id' => $owner_id
        ]);

        // Получаем id созданного проекта
        $project_id = $db->lastInsertId();

        // 2️⃣ Добавляем владельца в project_members
        $stmt2 = $db->prepare("
            INSERT INTO project_members (user_id, project_id, role)
            VALUES (:user_id, :project_id, 'owner')
        ");
        $stmt2->execute([
            'user_id' => $owner_id,
            'project_id' => $project_id
        ]);

        // Перенаправляем на главную
        header('Location: index.php');
        exit;
    }
}
?>

<!-- HTML форма для создания проекта -->
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Создать проект</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
<h3>Создать проект</h3>
<?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
<form method="POST">
    <div class="mb-3">
        <label>Название</label>
        <input type="text" name="name" class="form-control" maxlength="200" required>
    </div>
    <div class="mb-3">
        <label>Описание</label>
        <textarea name="description" class="form-control"></textarea>
    </div>
    <div class="mb-3">
        <label>Тип доступа</label>
        <select name="visibility" class="form-select">
            <option value="private">Приватный</option>
            <option value="public">Публичный</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Создать проект</button>
</form>
</div>
</body>
</html>