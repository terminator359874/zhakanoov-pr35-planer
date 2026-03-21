<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Получаем проекты
$stmt = $db->prepare("SELECT id, name FROM projects WHERE owner_id = :user_id ORDER BY name");
$stmt->execute(['user_id' => $user_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Task Planner Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        body { background: #f4f6f9; }
        .board-column {
            background: #ebedf0;
            border-radius: 10px;
            padding: 10px;
            min-height: 400px;
            transition: background-color 0.2s;
        }
        /* Подсветка колонки при перетаскивании над ней */
        .sortable-ghost { opacity: 0.4; background: #d1d4d9 !important; }
        .sortable-chosen { background: #f8f9fa; }
        
        .task-card {
            border-left: 5px solid #0d6efd;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            cursor: grab;
            transition: transform 0.1s, opacity 0.3s;
        }
        .task-card:active { cursor: grabbing; }
        
        /* Эффект сохранения */
        .task-updating {
            opacity: 0.6;
            pointer-events: none;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(0.98); }
            100% { transform: scale(1); }
        }

        .task-high { border-left-color: #dc3545; }
        .task-medium { border-left-color: #ffc107; }
        .task-low { border-left-color: #198754; }
        
        .toast-container { z-index: 1060; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <span class="navbar-brand">Task Planner</span>
        <div>
            <a href="create_task.php" class="btn btn-success btn-sm">Создать задачу</a>
            <a href="create_project.php" class="btn btn-primary btn-sm">Создать проект</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Выйти</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <?php if (count($projects) === 0): ?>
        <div class="alert alert-info">У вас пока нет проектов. Создайте новый!</div>
    <?php endif; ?>

    <?php foreach ($projects as $project): ?>
        <div class="project-section mb-5">
            <h4 class="mb-3 border-bottom pb-2"><?= htmlspecialchars($project['name']) ?></h4>
            <div class="row g-3">
                <?php
                $stmt = $db->prepare("SELECT * FROM tasks WHERE project_id = :pid ORDER BY created_at DESC");
                $stmt->execute(['pid' => $project['id']]);
                $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $kanban = ['new' => [], 'working' => [], 'progress' => [], 'done' => []];
                foreach ($tasks as $task) { $kanban[$task['status']][] = $task; }

                $titles = ['new' => 'Новые', 'working' => 'В работе', 'progress' => 'В процессе', 'done' => 'Завершены'];

                foreach ($kanban as $status => $status_tasks): ?>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="m-0 text-uppercase fw-bold text-secondary" style="font-size: 0.8rem;">
                                <?= $titles[$status] ?> 
                                <span class="badge bg-light text-dark ms-1"><?= count($status_tasks) ?></span>
                            </h6>
                        </div>
                        <div class="board-column" 
                             data-status="<?= $status ?>" 
                             data-project-id="<?= $project['id'] ?>">
                            
                            <?php foreach ($status_tasks as $task): ?>
                                <div class="task-card task-<?= $task['priority'] ?>" data-id="<?= $task['id'] ?>">
                                    <div class="fw-bold mb-1"><?= htmlspecialchars($task['title']) ?></div>
                                    <?php if(!empty($task['description'])): ?>
                                        <div class="text-muted small mb-2"><?= mb_strimwidth(htmlspecialchars($task['description']), 0, 80, "...") ?></div>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="badge bg-light text-dark border small" style="font-size: 0.65rem;">
                                            <?= $task['deadline'] ? date('d.m', strtotime($task['deadline'])) : 'Нет срока' ?>
                                        </span>
                                        <div>
                                            <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn btn-sm p-0 px-1 text-primary">✎</a>
                                            <a href="delete_task.php?id=<?= $task['id'] ?>" class="btn btn-sm p-0 px-1 text-danger">✖</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="saveToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">Изменения сохранены</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toastEl = document.getElementById('saveToast');
    const toast = new bootstrap.Toast(toastEl, { delay: 2000 });
    const toastMsg = document.getElementById('toastMessage');

    // Инициализируем перетаскивание для каждой колонки
    document.querySelectorAll('.board-column').forEach(column => {
        new Sortable(column, {
            group: 'tasks-' + column.getAttribute('data-project-id'), // разрешаем перенос только внутри одного проекта
            animation: 200,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                // Если статус не изменился (перетащили внутри той же колонки), ничего не делаем
                if (evt.from === evt.to) return;

                const taskId = evt.item.getAttribute('data-id');
                const newStatus = evt.to.getAttribute('data-status');
                const card = evt.item;

                // 1. Визуальный эффект начала сохранения
                card.classList.add('task-updating');
                
                // 2. Отправка данных на сервер
                const formData = new FormData();
                formData.append('id', taskId);
                formData.append('status', newStatus);

                fetch('update_task_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    card.classList.remove('task-updating');
                    if (data.success) {
                        toastMsg.innerText = "Статус обновлен";
                        toastEl.classList.replace('bg-danger', 'bg-dark');
                    } else {
                        toastMsg.innerText = "Ошибка доступа";
                        toastEl.classList.replace('bg-dark', 'bg-danger');
                        // Опционально: вернуть карточку назад, если ошибка
                        location.reload(); 
                    }
                    toast.show();
                })
                .catch(err => {
                    card.classList.remove('task-updating');
                    toastMsg.innerText = "Связь потеряна";
                    toastEl.classList.replace('bg-dark', 'bg-danger');
                    toast.show();
                });
            }
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>