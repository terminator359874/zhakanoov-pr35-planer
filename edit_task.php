<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = (new Database())->getConnection();

// Функция логирования
function logActivity($db, $projectId, $userId, $details, $taskId = null) {
    try {
        $stmt = $db->prepare("INSERT INTO project_activity (project_id, user_id, details, task_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$projectId, $userId, $details, $taskId]);
    } catch (Exception $e) {}
}

$task_id = $_GET['id'] ?? $_POST['id'] ?? null;
if (!$task_id) {
    die("ID задачи не указан.");
}

// 1. Получаем текущие данные задачи + проверяем доступ
$stmt = $db->prepare("
    SELECT t.*, p.owner_id 
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE t.id = :task_id 
      AND (p.owner_id = :user_id OR pm.user_id = :user_id)
    LIMIT 1
");
$stmt->execute(['task_id' => $task_id, 'user_id' => $user_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    die("Задача не найдена или у вас нет прав на её редактирование.");
}

$project_id = $task['project_id'];

// 2. Получаем список участников проекта для назначения исполнителя
$stmtMembers = $db->prepare("
    SELECT u.id, u.email FROM users u 
    JOIN projects p ON p.owner_id = u.id WHERE p.id = ?
    UNION
    SELECT u.id, u.email FROM users u 
    JOIN project_members pm ON pm.user_id = u.id WHERE pm.project_id = ?
");
$stmtMembers->execute([$project_id, $project_id]);
$members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

$fieldErrors = [];
$success = '';

// 3. Обработка сохранения формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = $_POST['priority'] ?? '';
    $deadline    = $_POST['deadline'] ?? null;
    $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;

    if ($title === '' || strlen($title) > 200) { $fieldErrors['title'] = "Название обязательно"; }
    if (!in_array($priority, ['low','medium','high'])) { $fieldErrors['priority'] = "Некорректный приоритет"; }
    
    // Получаем текущее значение дедлайна из БД для сравнения
    $original_deadline = $task['deadline'] ? date('Y-m-d\TH:i', strtotime($task['deadline'])) : null;
    
    if ($deadline && $deadline !== $original_deadline && strtotime($deadline) < time()) { 
        $fieldErrors['deadline'] = "Новый дедлайн не может быть в прошлом"; 
    }

    if (!$fieldErrors) {
        $updateStmt = $db->prepare("
            UPDATE tasks 
            SET title = :title, description = :description, priority = :priority, deadline = :deadline, assigned_to = :assigned_to
            WHERE id = :id
        ");
        $updateStmt->bindValue(":title", $title);
        $updateStmt->bindValue(":description", $description);
        $updateStmt->bindValue(":priority", $priority);
        $updateStmt->bindValue(":assigned_to", $assigned_to, $assigned_to ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $updateStmt->bindValue(":deadline", $deadline ?: null, $deadline ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $updateStmt->bindValue(":id", $task_id);
        
        if ($updateStmt->execute()) {
            logActivity($db, $project_id, $user_id, "отредактировал(а) задачу", $task_id);
            
            $task['title'] = $title;
            $task['description'] = $description;
            $task['priority'] = $priority;
            $task['deadline'] = $deadline;
            $task['assigned_to'] = $assigned_to;
            
            $success = "Задача успешно обновлена!";
        }
    }
}

// Форматируем дату дедлайна
$formatted_deadline = $task['deadline'] ? date('Y-m-d\TH:i', strtotime($task['deadline'])) : '';

// 4. ПОЛУЧАЕМ ИСТОРИЮ ИЗМЕНЕНИЙ (из рабочей таблицы project_activity)
$logStmt = $db->prepare("
    SELECT a.*, u.name as user_name, u.email 
    FROM project_activity a
    JOIN users u ON a.user_id = u.id
    WHERE a.task_id = :tid 
    ORDER BY a.created_at DESC LIMIT 15
");
$logStmt->execute(['tid' => $task_id]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование задачи — Task Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg:        #f0f2f5;
            --surface:   #ffffff;
            --surface2:  #f7f8fa;
            --border:    #dde1e7;
            --text:      #3d4452;
            --text-dim:  #8b95a5;
            --text-head: #1a1f2e;
            --accent:    #2b6be6;
            --red:       #e53935;
            --green:     #1e9e52;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 13px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* TOPBAR */
        .topbar {
            height: 44px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 8px;
            flex-shrink: 0;
        }
        .topbar-brand {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 500;
            color: var(--accent);
            text-decoration: none;
        }
        .topbar-sep { width: 1px; height: 20px; background: var(--border); }
        .topbar-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-dim);
            text-transform: uppercase;
        }
        .topbar-spacer { flex: 1; }
        .topbar-btn {
            height: 28px;
            padding: 0 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: .15s;
        }
        .topbar-btn:hover { background: var(--surface2); color: var(--text-head); }
        .topbar-btn.primary { border-color: var(--accent); background: var(--accent); color: #fff; }
        .topbar-btn.primary:hover { background: #1f58d0; }

        /* FORM LAYOUT */
        .form-page {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 32px 16px;
            gap: 24px; /* Отступ между формой и историей */
        }
        .form-card {
            width: 100%;
            max-width: 560px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .form-card-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            font-weight: 600;
            color: var(--text-head);
        }
        .form-card-body { padding: 20px; display: flex; flex-direction: column; gap: 16px; }

        /* FIELDS */
        .field-group { display: flex; flex-direction: column; gap: 5px; }
        .field-label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-dim); }
        .tp-input, .tp-select, .tp-textarea {
            width: 100%;
            height: 34px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text-head);
            padding: 0 10px;
            outline: none;
        }
        .tp-textarea { height: auto; padding: 8px 10px; resize: vertical; }
        .tp-input:focus, .tp-select:focus, .tp-textarea:focus { border-color: var(--accent); background: var(--surface); }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        .tp-alert { padding: 10px 14px; border-radius: 4px; font-size: 12px; border: 1px solid; }
        .tp-alert.success { background: #f0faf4; border-color: #a8d5b8; color: var(--green); }

        .form-card-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
        }
        
        /* Стили для скролла истории */
        .history-scroll {
            max-height: 400px; 
            overflow-y: auto;
        }
        .history-scroll::-webkit-scrollbar { width: 4px; }
        .history-scroll::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
    </style>
</head>
<body>

<div class="topbar">
    <a href="index.php" class="topbar-brand">⬡ Task Planner</a>
    <div class="topbar-sep"></div>
    <span class="topbar-title">Редактирование задачи #<?= $task['id'] ?></span>
    <div class="topbar-spacer"></div>
    <a href="view_task.php?id=<?= $task['id'] ?>" class="topbar-btn">← Назад к задаче</a>
</div>

<div class="form-page">
    
    <div class="form-card">
        <div class="form-card-header">Изменить детали задачи</div>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $task['id'] ?>">
            
            <div class="form-card-body">
                <?php if ($success): ?>
                    <div class="tp-alert success"><?= $success ?></div>
                <?php endif; ?>

                <div class="field-group">
                    <label class="field-label">Название</label>
                    <input type="text" name="title" class="tp-input" maxlength="200" 
                           value="<?= htmlspecialchars($task['title']) ?>" required>
                    <?php if (isset($fieldErrors['title'])): ?>
                        <span style="color:var(--red); font-size:11px;"><?= $fieldErrors['title'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="field-group">
                    <label class="field-label">Описание</label>
                    <textarea name="description" id="descInput" class="tp-textarea" rows="4" maxlength="5000"><?= htmlspecialchars($task['description']) ?></textarea>
                    <div style="display:flex;justify-content:space-between;margin-top:3px;">
                        <span id="autoSaveStatus" style="font-size:10px;color:var(--text-dim);"></span>
                        <span id="descCount" style="font-size:10px;color:var(--text-dim);">0 / 5000</span>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-group">
                        <label class="field-label">Приоритет</label>
                        <select name="priority" class="tp-select">
                            <option value="low" <?= $task['priority'] === 'low' ? 'selected' : '' ?>>Низкий</option>
                            <option value="medium" <?= $task['priority'] === 'medium' ? 'selected' : '' ?>>Средний</option>
                            <option value="high" <?= $task['priority'] === 'high' ? 'selected' : '' ?>>Высокий</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Дедлайн</label>
                        <input type="datetime-local" name="deadline" data-original-val="<?= $formatted_deadline ?>"
                               class="tp-input <?= isset($fieldErrors['deadline']) ? 'is-invalid' : '' ?>" 
                               value="<?= $formatted_deadline ?>">
                        <?php if (isset($fieldErrors['deadline'])): ?>
                            <span style="color:var(--red); font-size:11px;"><?= $fieldErrors['deadline'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Исполнитель</label>
                    <select name="assigned_to" class="tp-select">
                        <option value="">-- Не назначено --</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $task['assigned_to'] == $m['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="form-card-footer">
                <a href="view_task.php?id=<?= $task['id'] ?>" class="topbar-btn">Отмена</a>
                <button type="submit" class="topbar-btn primary">Сохранить изменения</button>
            </div>
        </form>
    </div>

    <div class="form-card" style="width: 320px; flex-shrink: 0;">
        <div class="form-card-header">История изменений</div>
        <div class="form-card-body history-scroll" style="padding: 12px 16px;">
            <?php if (empty($logs)): ?>
                <div style="font-size: 12px; color: var(--text-dim);">История пуста</div>
            <?php else: ?>
                <div class="list-group list-group-flush border rounded">
                    <?php foreach ($logs as $log): ?>
                        <div class="list-group-item small py-2">
                            <span class="text-primary fw-bold">
                                <?= htmlspecialchars($log['user_name'] ?: $log['email']) ?>
                            </span>: 
                            <?= htmlspecialchars($log['details']) ?>
                            <div class="text-muted" style="font-size: 0.7rem;">
                                <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Счётчик символов
const descInput = document.getElementById('descInput');
const descCount = document.getElementById('descCount');
const autoSaveStatus = document.getElementById('autoSaveStatus');
if (descInput && descCount) {
    const updateCount = () => { descCount.textContent = descInput.value.length + ' / 5000'; };
    descInput.addEventListener('input', updateCount);
    updateCount();
}

// Автосохранение (debounce 2с)
let autoSaveTimer = null;
function triggerAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(async () => {
        const form = document.querySelector('form');
        const fd = new FormData(form);
        if (autoSaveStatus) { autoSaveStatus.textContent = 'Сохранение...'; autoSaveStatus.style.color = 'var(--text-dim)'; }
        try {
            const r = await fetch('autosave_task.php', { method: 'POST', body: fd });
            const data = await r.json();
            if (autoSaveStatus) {
                if (data.success) {
                    autoSaveStatus.textContent = 'Автосохранено в ' + data.saved_at;
                    autoSaveStatus.style.color = 'var(--green)';
                } else {
                    autoSaveStatus.textContent = 'Ошибка: ' + data.error;
                    autoSaveStatus.style.color = 'var(--red)';
                }
            }
        } catch (e) {
            if (autoSaveStatus) { autoSaveStatus.textContent = 'Сеть недоступна'; autoSaveStatus.style.color = 'var(--red)'; }
        }
    }, 2000);
}

// Навешиваем автосохранение на изменение полей
const autoSaveFields = document.querySelectorAll('input[name="title"], textarea[name="description"], select[name="priority"]');
autoSaveFields.forEach(el => el.addEventListener('input', triggerAutoSave));

// Валидация дедлайна
document.querySelector('form').addEventListener('submit', function(e) {
    const deadlineInput = document.querySelector('input[name="deadline"]');
    if (deadlineInput && deadlineInput.value) {
        const selectedDate = new Date(deadlineInput.value);
        const originalVal = deadlineInput.getAttribute('data-original-val');
        if (selectedDate < new Date() && deadlineInput.value !== originalVal) {
            e.preventDefault();
            alert('Новое время дедлайна не может быть в прошлом!');
            deadlineInput.focus();
        }
    }
});
</script>
</body>
</html>