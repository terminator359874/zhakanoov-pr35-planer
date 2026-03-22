<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID задачи не указан.");
}

$task_id = $_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Получаем информацию о задаче
// SQL-запрос с двойной проверкой доступа
$stmt = $db->prepare("
    SELECT t.* FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE t.id = :task_id 
      AND (p.owner_id = :user_id OR pm.user_id = :user_id)
    LIMIT 1
");

$stmt->execute([
    'task_id' => $task_id,
    'user_id' => $_SESSION['user_id']
]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    // Если задача чужая или её нет — выдаем 404 или просто ошибку
    die("Ошибка: Задача не найдена или у вас нет прав доступа.");
}

// Получаем комментарии к этой задаче
$stmtComments = $db->prepare("
    SELECT tc.*, u.name as author_name 
    FROM task_comments tc 
    JOIN users u ON tc.user_id = u.id 
    WHERE tc.task_id = ? 
    ORDER BY tc.created_at ASC
");
$stmtComments->execute([$task_id]);
$comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($task['title']) ?> - Task Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .comment-box { background: white; border-radius: 8px; padding: 15px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a href="index.php" class="navbar-brand">← Назад к доске</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h2 class="card-title"><?= htmlspecialchars($task['title']) ?></h2>
                        <div>
                            <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn btn-outline-primary btn-sm">Редактировать</a>
                            <a href="delete_task.php?id=<?= $task['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Удалить задачу?')">Удалить</a>
                        </div>
                    </div>
                    
                    <h6 class="text-muted">Описание:</h6>
                    <p class="card-text">
                        <?= !empty($task['description']) ? nl2br(htmlspecialchars($task['description'])) : '<em>Нет описания</em>' ?>
                    </p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-4">Комментарии</h5>
                    
                    <div id="comments-list-<?= $task_id ?>">
                        <?php if (empty($comments)): ?>
                            <p id="no-comments-msg" class="text-muted">Пока нет комментариев.</p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-box">
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong><?= htmlspecialchars($comment['author_name']) ?></strong>
                                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></small>
                                    </div>
                                    <div class="mb-0"><?= nl2br(htmlspecialchars($comment['comment'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <hr>
                    
                    <form id="comment-form-<?= $task_id ?>" onsubmit="addComment(event, <?= $task_id ?>)">
                        <input type="hidden" name="task_id" value="<?= $task_id ?>">
                        <div class="mb-3">
                            <textarea name="comment" class="form-control" rows="3" required maxlength="500" placeholder="Напишите комментарий..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Отправить</button>
                        <div class="comment-error text-danger mt-2" style="display: none;"></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Детали</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Статус: <strong><?= htmlspecialchars($task['status']) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Приоритет: <strong><?= htmlspecialchars($task['priority']) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Дедлайн: <strong><?= $task['deadline'] ? date('d.m.Y H:i', strtotime($task['deadline'])) : 'Не указан' ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Тот самый скрипт для добавления комментариев без перезагрузки
async function addComment(event, taskId) {
    event.preventDefault();

    const form = document.getElementById(`comment-form-${taskId}`);
    const formData = new FormData(form);
    const errorDiv = form.querySelector('.comment-error');
    const submitBtn = form.querySelector('button[type="submit"]');

    errorDiv.style.display = 'none';
    submitBtn.disabled = true;

    try {
        const response = await fetch('add_comment.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            const commentsList = document.getElementById(`comments-list-${taskId}`);
            const noCommentsMsg = document.getElementById('no-comments-msg');
            if (noCommentsMsg) noCommentsMsg.remove(); // Убираем сообщение "Пока нет комментариев"

            const date = new Date(data.comment.created_at).toLocaleString('ru-RU', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            }).replace(',', '');
            
            const commentHTML = `
                <div class="comment-box">
                    <div class="d-flex justify-content-between mb-1">
                        <strong>${data.comment.author_name}</strong>
                        <small class="text-muted">${date}</small>
                    </div>
                    <div class="mb-0">${data.comment.comment}</div>
                </div>
            `;

            commentsList.insertAdjacentHTML('beforeend', commentHTML);
            form.reset();
        } else {
            errorDiv.textContent = data.error;
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'Ошибка соединения с сервером.';
        errorDiv.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
    }
}
</script>
</body>
</html>