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

// ... код авторизации и проверки ID ...

$stmt = $db->prepare("
    SELECT t.*, u.email as executor_email, u.name as executor_name 
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.id = :task_id 
      AND (p.owner_id = :user_id OR pm.user_id = :user_id)
    LIMIT 1
");
$stmt->execute(['task_id' => $task_id, 'user_id' => $_SESSION['user_id']]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

// ... дальше проверка на !$task ...

if (!$task) {
    die("Ошибка: Задача не найдена или у вас нет прав доступа.");
}

$stmtComments = $db->prepare("
    SELECT tc.*, u.name as author_name 
    FROM task_comments tc 
    JOIN users u ON tc.user_id = u.id 
    WHERE tc.task_id = ? 
    ORDER BY tc.created_at ASC
");
$stmtComments->execute([$task_id]);
$comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
// Получаем историю изменений именно для этой задачи
$stmtHistory = $db->prepare("
    SELECT a.*, u.name as user_name, u.email as user_email
    FROM project_activity a
    JOIN users u ON a.user_id = u.id
    WHERE a.task_id = ?
    ORDER BY a.created_at DESC
");
$stmtHistory->execute([$task_id]);
$history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
$statusLabels   = ['new' => 'Новые', 'working' => 'В работе', 'progress' => 'В процессе', 'done' => 'Завершены'];
$priorityLabels = ['low' => 'Низкий', 'medium' => 'Средний', 'high' => 'Высокий'];
$priorityColors = ['low' => 'var(--green)', 'medium' => 'var(--yellow)', 'high' => 'var(--red)'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($task['title']) ?> — Task Planner</title>
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
            --yellow:    #e8a000;
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
/* HISTORY STYLES */
.history-wrap {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
    padding-left: 12px;
    border-left: 2px solid var(--border);
}

.history-item {
    position: relative;
    font-size: 12px;
    color: var(--text);
}

.history-item::before {
    content: "";
    position: absolute;
    left: -17px;
    top: 5px;
    width: 8px;
    height: 8px;
    background: var(--border);
    border-radius: 50%;
    border: 2px solid var(--bg);
}

.history-meta {
    color: var(--text-dim);
    font-size: 11px;
    margin-bottom: 2px;
}

.history-user { font-weight: 600; color: var(--text-head); }
.history-date { font-family: 'JetBrains Mono', monospace; margin-left: 6px; }
.history-details { line-height: 1.4; }
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
            letter-spacing: 0.05em;
            margin-right: 12px;
            text-decoration: none;
        }
        .topbar-sep { width: 1px; height: 20px; background: var(--border); }
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
            gap: 5px;
            transition: background .15s, border-color .15s, color .15s;
        }
        .topbar-btn:hover { background: var(--surface2); border-color: #b0b8c8; color: var(--text-head); }
        .topbar-btn.primary { border-color: var(--accent); color: var(--accent); }
        .topbar-btn.primary:hover { background: rgba(43,107,230,.08); }
        .topbar-btn.danger { border-color: #f5c6c6; color: var(--red); }
        .topbar-btn.danger:hover { background: rgba(229,57,53,.06); border-color: var(--red); }
        .topbar-spacer { flex: 1; }

        /* BREADCRUMB BAR */
        .breadbar {
            height: 36px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 6px;
            font-size: 12px;
            color: var(--text-dim);
        }
        .breadbar a { color: var(--accent); text-decoration: none; }
        .breadbar a:hover { text-decoration: underline; }
        .breadbar-sep { color: var(--border); }
        .breadbar-current { color: var(--text-head); font-weight: 500; }
        .task-id-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 3px;
            padding: 1px 6px;
            color: var(--text-dim);
        }

        /* CONTENT */
        .page-content {
            flex: 1;
            display: flex;
            gap: 0;
            overflow: hidden;
        }

        /* MAIN PANEL */
        .main-panel {
            flex: 1;
            overflow-y: auto;
            padding: 20px 24px;
            border-right: 1px solid var(--border);
        }
        .main-panel::-webkit-scrollbar { width: 4px; }
        .main-panel::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        .task-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-head);
            line-height: 1.4;
            margin-bottom: 16px;
        }

        .section-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-dim);
            margin-bottom: 8px;
        }

        .desc-block {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 14px 16px;
            font-size: 13px;
            line-height: 1.6;
            color: var(--text);
            margin-bottom: 24px;
            min-height: 60px;
        }
        .desc-block em { color: var(--text-dim); }

        /* COMMENTS */
        .comments-wrap { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }

        .comment-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 14px;
        }
        .comment-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        .comment-author {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-head);
        }
        .comment-date {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            color: var(--text-dim);
        }
        .comment-text {
            font-size: 12px;
            line-height: 1.5;
            color: var(--text);
        }

        /* COMMENT FORM */
        .comment-form-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 12px 14px;
        }
        .tp-textarea {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text);
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 12px;
            padding: 8px 10px;
            resize: vertical;
            outline: none;
            transition: border-color .15s;
            min-height: 72px;
        }
        .tp-textarea:focus { border-color: var(--accent); }
        .tp-textarea::placeholder { color: var(--text-dim); }

        /* SIDEBAR */
        .side-panel {
            width: 240px;
            min-width: 240px;
            overflow-y: auto;
            padding: 20px 16px;
            background: var(--surface);
        }
        .side-panel::-webkit-scrollbar { width: 3px; }
        .side-panel::-webkit-scrollbar-thumb { background: var(--border); }

        .detail-row {
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-key {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-dim);
        }
        .detail-val {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-head);
        }
        .priority-dot {
            display: inline-block;
            width: 8px; height: 8px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        .mono { font-family: 'JetBrains Mono', monospace; font-size: 11px; }

        .side-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 16px;
        }

        .error-msg { font-size: 11px; color: var(--red); margin-top: 6px; }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <a href="index.php" class="topbar-brand">⬡ Task Planner</a>
    <div class="topbar-sep"></div>
    <span class="task-id-badge">#<?= $task['id'] ?></span>
    <div class="topbar-spacer"></div>
    <a href="edit_task.php?id=<?= $task['id'] ?>" class="topbar-btn primary">Редактировать</a>
    <a href="delete_task.php?id=<?= $task['id'] ?>" class="topbar-btn danger"
       onclick="return confirm('Удалить задачу?')">Удалить</a>
</div>

<!-- BREADCRUMB -->
<div class="breadbar">
    <a href="index.php">Доска</a>
    <span class="breadbar-sep">›</span>
    <span class="breadbar-current"><?= htmlspecialchars($task['title']) ?></span>
</div>

<!-- CONTENT -->
<div class="page-content">

    <!-- MAIN -->
    <div class="main-panel">
        <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>

        <div class="section-label">Описание</div>
        <div class="desc-block">
            <?= !empty($task['description'])
                ? nl2br(htmlspecialchars($task['description']))
                : '<em>Нет описания</em>' ?>
        </div>
<div class="section-label">История изменений</div>
<div class="history-wrap" id="history-list-<?= $task_id ?>">
    <?php if (empty($history)): ?>
        <div style="font-size:12px; color:var(--text-dim);">История изменений пуста.</div>
    <?php else: ?>
        <?php foreach ($history as $item): ?>
            <div class="history-item">
                <div class="history-meta">
                    <span class="history-user"><?= htmlspecialchars($item['user_name'] ?: $item['user_email']) ?></span>
                    <span class="history-date"><?= date('d.m H:i', strtotime($item['created_at'])) ?></span>
                </div>
                <div class="history-details">
                    <?= htmlspecialchars($item['details']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
        <div class="section-label">Комментарии</div>
        <div class="comments-wrap" id="comments-list-<?= $task_id ?>">
            <?php if (empty($comments)): ?>
                <div id="no-comments-msg" style="font-size:12px;color:var(--text-dim);">Пока нет комментариев.</div>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                <div class="comment-item">
                    <div class="comment-meta">
                        <span class="comment-author"><?= htmlspecialchars($c['author_name']) ?></span>
                        <span class="comment-date"><?= date('d.m.Y H:i', strtotime($c['created_at'])) ?></span>
                    </div>
                    <div class="comment-text"><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="comment-form-wrap">
            <form id="comment-form-<?= $task_id ?>" onsubmit="addComment(event, <?= $task_id ?>)" style="display:flex;flex-direction:column;gap:8px;">
                <input type="hidden" name="task_id" value="<?= $task_id ?>">
                <textarea name="comment" class="tp-textarea" rows="3" maxlength="500"
                          placeholder="Напишите комментарий..." required></textarea>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="submit" class="topbar-btn primary">Отправить</button>
                    <div class="comment-error error-msg" style="display:none;"></div>
                </div>
            </form>
        </div>
    </div>

    <!-- SIDEBAR -->
    <div class="side-panel">
        <div class="detail-row">
            <span class="detail-key">Статус</span>
            <span class="detail-val"><?= $statusLabels[$task['status']] ?? $task['status'] ?></span>
        </div>
        <div class="detail-row">
    <span class="detail-key">Исполнитель</span>
    <span class="detail-val">
        <?php if ($task['executor_email']): ?>
            <span style="color: var(--accent); font-weight: 600;">👤</span> 
            <?= htmlspecialchars($task['executor_name'] ?: $task['executor_email']) ?>
        <?php else: ?>
            <span class="text-dim">Не назначен</span>
        <?php endif; ?>
    </span>
</div>
        <div class="detail-row">
            <span class="detail-key">Приоритет</span>
            <span class="detail-val">
                <span class="priority-dot" style="background:<?= $priorityColors[$task['priority']] ?? '#aaa' ?>;"></span>
                <?= $priorityLabels[$task['priority']] ?? $task['priority'] ?>
            </span>
        </div>
        
        <div class="detail-row">
            <span class="detail-key">Дедлайн</span>
            <span class="detail-val mono">
                <?= $task['deadline'] ? date('d.m.Y H:i', strtotime($task['deadline'])) : '—' ?>
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-key">Создана</span>
            <span class="detail-val mono">
                <?= date('d.m.Y', strtotime($task['created_at'])) ?>
            </span>
        </div>

        <div class="side-actions">
            <a href="edit_task.php?id=<?= $task['id'] ?>" class="topbar-btn primary" style="justify-content:center;">Редактировать</a>
            <a href="delete_task.php?id=<?= $task['id'] ?>" class="topbar-btn danger" style="justify-content:center;"
               onclick="return confirm('Удалить задачу?')">Удалить</a>
            <a href="index.php" class="topbar-btn" style="justify-content:center;">← На доску</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function addComment(event, taskId) {
    event.preventDefault();
    const form      = document.getElementById(`comment-form-${taskId}`);
    const formData  = new FormData(form);
    const errorDiv  = form.querySelector('.comment-error');
    const submitBtn = form.querySelector('button[type="submit"]');

    errorDiv.style.display = 'none';
    submitBtn.disabled = true;

    try {
        const response = await fetch('add_comment.php', { method: 'POST', body: formData });
        const data     = await response.json();

        if (data.success) {
            const list = document.getElementById(`comments-list-${taskId}`);
            const noMsg = document.getElementById('no-comments-msg');
            if (noMsg) noMsg.remove();

            const date = new Date(data.comment.created_at).toLocaleString('ru-RU', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            }).replace(',', '');

            list.insertAdjacentHTML('beforeend', `
                <div class="comment-item">
                    <div class="comment-meta">
                        <span class="comment-author">${data.comment.author_name}</span>
                        <span class="comment-date">${date}</span>
                    </div>
                    <div class="comment-text">${data.comment.comment.replace(/\n/g,'<br>')}</div>
                </div>
            `);
            form.reset();
        } else {
            errorDiv.textContent    = data.error;
            errorDiv.style.display  = 'block';
        }
    } catch {
        errorDiv.textContent   = 'Ошибка соединения с сервером.';
        errorDiv.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
    }
}
// Функция обновления истории
async function refreshHistory() {
    const historyContainer = document.getElementById('history-list-<?= $task_id ?>');
    if (!historyContainer) return;

    try {
        const response = await fetch(`get_task_history.php?task_id=<?= $task_id ?>`);
        if (response.ok) {
            const html = await response.text();
            if (html.trim() !== "") {
                historyContainer.innerHTML = html;
            }
        }
    } catch (e) { console.error("History refresh failed"); }
}

// Обновляем каждые 5 секунд
setInterval(refreshHistory, 5000);

// Вызываем обновление сразу после добавления комментария (на всякий случай)
// Вставь вызов refreshHistory() внутрь своей функции addComment после data.success
</script>
</body>
</html>