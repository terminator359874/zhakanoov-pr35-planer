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

$stmt = $db->prepare("
    SELECT p.id, p.name 
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE p.owner_id = :user_id OR pm.user_id = :user_id
    GROUP BY p.id
    ORDER BY p.name
");
$stmt->execute(['user_id' => $user_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtEmail = $db->prepare("SELECT email FROM users WHERE id = ?");
$stmtEmail->execute([$user_id]);
$currentUserEmail = $stmtEmail->fetchColumn();

// Получаем приглашения
$stmtInv = $db->prepare("
    SELECT pi.id, p.name as project_name, u.name as from_user_name 
    FROM project_invitations pi
    JOIN projects p ON p.id = pi.project_id
    JOIN users u ON u.id = pi.from_user_id
    WHERE pi.to_email = :email AND pi.status = 'pending'
    ORDER BY pi.created_at DESC
");
$stmtInv->execute(['email' => $currentUserEmail]);
$invitations = $stmtInv->fetchAll(PDO::FETCH_ASSOC);
$invitesCount = count($invitations);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Task Planner Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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
            --col-w:     260px;
            --sidebar-w: 280px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 13px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── TOPBAR ── */
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

        /* ── PROJECT TABS ── */
        .proj-tabs {
            height: 38px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: stretch;
            padding: 0 16px;
            gap: 2px;
            overflow-x: auto;
            flex-shrink: 0;
        }
        .proj-tabs::-webkit-scrollbar { height: 3px; }
        .proj-tabs::-webkit-scrollbar-thumb { background: var(--border); }
        .proj-tab {
            height: 100%;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-dim);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
            transition: color .15s, border-color .15s;
        }
        .proj-tab:hover { color: var(--text); }
        .proj-tab.active { color: var(--text-head); border-bottom-color: var(--accent); }
        .proj-tab .tab-count {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 0 6px;
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
        }

        /* ── MAIN AREA ── */
        .main-area {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        /* ── BOARD SIDE ── */
        .board-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── BOARD TOOLBAR ── */
        .board-toolbar {
            height: 38px;
            background: var(--bg);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 8px;
            flex-shrink: 0;
        }
        .board-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-dim);
        }
        .board-toolbar-spacer { flex: 1; }
        .invite-wrap { display: flex; align-items: center; gap: 6px; }
        .invite-wrap input {
            height: 26px;
            width: 200px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text);
            padding: 0 8px;
            font-size: 12px;
            outline: none;
            transition: border-color .15s;
        }
        .invite-wrap input:focus { border-color: var(--accent); }
        .invite-wrap input::placeholder { color: var(--text-dim); }
        .invite-msg { font-size: 11px; }
        .members-btn {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 3px;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-dim);
            cursor: pointer;
            transition: all .15s;
        }
        .members-btn:hover { border-color: #b0b8c8; color: var(--text); }

        /* ── BOARD CANVAS ── */
        .board-canvas {
            flex: 1;
            overflow-x: auto;
            overflow-y: hidden;
            display: flex;
            padding: 12px 16px;
            gap: 8px;
        }
        .board-canvas::-webkit-scrollbar { height: 6px; }
        .board-canvas::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        /* ── KANBAN COLUMN ── */
        .kanban-col {
            width: var(--col-w);
            min-width: var(--col-w);
            display: flex;
            flex-direction: column;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
        }
        .col-header {
            height: 36px;
            display: flex;
            align-items: center;
            padding: 0 10px;
            gap: 7px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .col-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .dot-new      { background: #9ca3af; }
        .dot-progress { background: var(--accent); }
        .dot-done     { background: var(--green); }
        .dot-deferred { background: var(--yellow); }
        .col-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-head);
            text-transform: uppercase;
            letter-spacing: .06em;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .col-count {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--text-dim);
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 3px;
            padding: 0 5px;
        }
        .col-body {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-height: 120px;
        }
        .col-body::-webkit-scrollbar { width: 3px; }
        .col-body::-webkit-scrollbar-thumb { background: var(--border); }
        .empty-col {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dim);
            font-size: 11px;
            user-select: none;
        }

        /* ── TASK CARD ── */
        .task-card {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 8px 10px;
            cursor: grab;
            transition: border-color .15s, box-shadow .15s, opacity .2s;
            position: relative;
        }
        .task-card:hover { border-color: #b0b8c8; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
        .task-card:active { cursor: grabbing; }
        .task-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            border-radius: 4px 0 0 4px;
        }
        .task-high::before   { background: var(--red); }
        .task-medium::before { background: var(--yellow); }
        .task-low::before    { background: var(--green); }
        .task-id {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            color: var(--text-dim);
            margin-bottom: 4px;
        }
        .task-title {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-head);
            line-height: 1.4;
            margin-bottom: 6px;
        }
        .task-desc {
            font-size: 11px;
            color: var(--text-dim);
            line-height: 1.4;
            margin-bottom: 6px;
        }
        .task-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4px;
        }
        .task-deadline {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            color: var(--text-dim);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 3px;
            padding: 1px 5px;
        }
        .task-open {
            font-size: 10px;
            color: var(--accent);
            text-decoration: none;
            padding: 2px 6px;
            border: 1px solid rgba(43,107,230,.25);
            border-radius: 3px;
            transition: background .15s, border-color .15s;
        }
        .task-open:hover { background: rgba(43,107,230,.08); border-color: var(--accent); }

        .sortable-ghost { opacity: .3; }
        .task-updating  { opacity: .5; pointer-events: none; }

        /* ── ACTIVITY SIDEBAR ── */
        .activity-sidebar {
    width: var(--sidebar-w);
    min-width: var(--sidebar-w);
    background: var(--surface);
    border-left: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    /* Добавьте эти строки: */
    transition: margin-right 0.3s ease;
}

.activity-sidebar.collapsed {
    margin-right: calc(var(--sidebar-w) * -1); /* Уводит сайдбар вправо */
}
        .sidebar-section {
            display: flex;
            flex-direction: column;
        }
        .sidebar-section.stats {
            flex-shrink: 0;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-section.activity {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            height: 34px;
            padding: 0 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-dim);
            flex-shrink: 0;
        }
        .sidebar-body {
            padding: 10px 14px;
            overflow-y: auto;
            font-size: 12px;
            color: var(--text);
        }
        .sidebar-body::-webkit-scrollbar { width: 3px; }
        .sidebar-body::-webkit-scrollbar-thumb { background: var(--border); }
        .sidebar-section.stats .sidebar-body  { max-height: 160px; }
        .sidebar-section.activity .sidebar-body { flex: 1; }

        /* ── MODAL ── */
        .modal-content {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
        }
        .modal-header { border-bottom-color: var(--border); }

        /* ── TOAST ── */
        .toast-container { z-index: 1060; }
        #saveToast { font-size: 12px; }
        
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <span class="topbar-brand">⬡ Task Planner</span>
    <div class="topbar-sep"></div>
    <a href="create_task.php" class="topbar-btn primary">+ Задача</a>
    <a href="create_project.php" class="topbar-btn">+ Проект</a>
    <a href="calendar.php" class="topbar-btn" style="color:var(--accent); border-color:var(--accent);">📅 Календарь</a>
    <div class="topbar-spacer"></div>
    <?php if ($invitesCount > 0): ?>
    <button class="topbar-btn" style="border-color:var(--yellow); color:var(--yellow);" data-bs-toggle="modal" data-bs-target="#invitesModal">
        🔔 Приглашения (<?= $invitesCount ?>)
    </button>
    <?php else: ?>
    <button class="topbar-btn" data-bs-toggle="modal" data-bs-target="#invitesModal">🔔 Приглашения (0)</button>
    <?php endif; ?>
    <button class="topbar-btn" onclick="toggleSidebar()">📊 Активность</button>
    <a href="logout.php" class="topbar-btn danger">Выйти</a>
</div>

<?php if (count($projects) === 0): ?>
<div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--text-dim);">
    У вас пока нет проектов. <a href="create_project.php" style="color:var(--accent);margin-left:6px;">Создайте новый →</a>
</div>
<?php else: ?>

<!-- PROJECT TABS -->
<div class="proj-tabs" id="projTabs">
<?php foreach ($projects as $i => $project):
    $stmt2 = $db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = :pid");
    $stmt2->execute(['pid' => $project['id']]);
    $total = $stmt2->fetchColumn();
?>
    <div class="proj-tab <?= $i === 0 ? 'active' : '' ?>"
         onclick="switchProject(<?= $project['id'] ?>, this)">
        <?= htmlspecialchars($project['name']) ?>
        <span class="tab-count"><?= $total ?></span>
    </div>
<?php endforeach; ?>
</div>

<!-- MAIN AREA -->
<div class="main-area">

    <!-- BOARD SIDE -->
    <div class="board-side">
    <?php foreach ($projects as $i => $project):
        $stmt = $db->prepare("SELECT * FROM tasks WHERE project_id = :pid ORDER BY created_at DESC");
        $stmt->execute(['pid' => $project['id']]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $kanban = ['new' => [], 'progress' => [], 'done' => [], 'deferred' => []];
        foreach ($tasks as $task) { $kanban[$task['status']][] = $task; }
        $titles = ['new' => 'Новая', 'progress' => 'В процессе', 'done' => 'Завершена', 'deferred' => 'Отложена'];
        $dots   = ['new' => 'dot-new', 'progress' => 'dot-progress', 'done' => 'dot-done', 'deferred' => 'dot-deferred'];
    ?>
    <div class="project-board" id="board-<?= $project['id'] ?>"
         style="display:<?= $i === 0 ? 'flex' : 'none' ?>;flex-direction:column;flex:1;overflow:hidden;">

        <div class="board-toolbar">
            <span class="board-label"><?= htmlspecialchars($project['name']) ?></span>
            <div class="board-toolbar-spacer"></div>
            <form class="invite-wrap" onsubmit="inviteUser(event, <?= $project['id'] ?>)" style="margin:0;">
                <input type="email" name="email" placeholder="Email для приглашения" required>
                <button type="submit" class="topbar-btn primary">Пригласить</button>
            </form>
            <div id="invite-msg-<?= $project['id'] ?>" class="invite-msg" style="display:none;"></div>
            <button class="members-btn" onclick="openMembersModal(<?= $project['id'] ?>)">👥 Участники</button>
        </div>

        <div class="board-canvas">
            <?php foreach ($kanban as $status => $status_tasks): ?>
            <div class="kanban-col">
                <div class="col-header">
                    <span class="col-dot <?= $dots[$status] ?>"></span>
                    <span class="col-title"><?= $titles[$status] ?></span>
                    <span class="col-count"><?= count($status_tasks) ?></span>
                </div>
                <div class="col-body"
                     data-status="<?= $status ?>"
                     data-project-id="<?= $project['id'] ?>">
                    <?php if (empty($status_tasks)): ?>
                        <div class="empty-col">—</div>
                    <?php endif; ?>
                    <?php foreach ($status_tasks as $task): ?>
                    <div class="task-card task-<?= $task['priority'] ?>" data-id="<?= $task['id'] ?>">
                        <div class="task-id">#<?= $task['id'] ?></div>
                        <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                        <?php if (!empty($task['description'])): ?>
                        <div class="task-desc"><?= mb_strimwidth(htmlspecialchars($task['description']), 0, 70, '…') ?></div>
                        <?php endif; ?>
                        <div class="task-footer">
                            <span class="task-deadline">
                                <?= $task['deadline'] ? date('d.m', strtotime($task['deadline'])) : 'без срока' ?>
                            </span>
                            <a href="view_task.php?id=<?= $task['id'] ?>" class="task-open">открыть →</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div><!-- /board-side -->

    <!-- ACTIVITY SIDEBAR -->
    <div class="activity-sidebar">
        <div class="sidebar-section stats">
            <div class="sidebar-header">📊 Топ исполнителей</div>
            <div class="sidebar-body" id="stats-feed-content">
                <span style="color:var(--text-dim);">Загрузка...</span>
            </div>
        </div>
        <div class="sidebar-section activity">
            <div class="sidebar-header">🕒 Активность проекта</div>
            <div class="sidebar-body" id="activity-feed-content">
                <span style="color:var(--text-dim);">Загрузка...</span>
            </div>
        </div>
    </div>

</div><!-- /main-area -->

<?php endif; ?>

<!-- TOAST -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="saveToast" class="toast align-items-center text-white border-0"
         style="background:#1a1f2e;" role="alert" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">Статус обновлён</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- INVITES MODAL -->
<div class="modal" id="invitesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:14px;">Ваши приглашения</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (count($invitations) === 0): ?>
                    <div style="text-align:center;color:var(--text-dim);font-size:12px;">У вас нет новых приглашений.</div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach($invitations as $inv): ?>
                        <div style="border:1px solid var(--border); border-radius:4px; padding:10px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong><?= htmlspecialchars($inv['project_name']) ?></strong><br>
                                <span style="font-size:11px; color:var(--text-dim);">От: <?= htmlspecialchars($inv['from_user_name']) ?></span>
                            </div>
                            <div style="display:flex; gap:6px;">
                                <button class="topbar-btn primary" onclick="handleInvite(<?= $inv['id'] ?>, 'accept')">Принять</button>
                                <button class="topbar-btn danger" onclick="handleInvite(<?= $inv['id'] ?>, 'reject')">Отклонить</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MEMBERS MODAL -->
<div class="modal" id="membersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-size:14px;">Участники проекта</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="membersModalBody">
                <div style="text-align:center;color:var(--text-dim);font-size:12px;">Загрузка...</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── PROJECT TABS ──
function switchProject(projectId, tabEl) {
    document.querySelectorAll('.proj-tab').forEach(t => t.classList.remove('active'));
    tabEl.classList.add('active');
    document.querySelectorAll('.project-board').forEach(b => b.style.display = 'none');
    const board = document.getElementById('board-' + projectId);
    if (board) board.style.display = 'flex';
    updateSidebar(projectId);
}

// ── DRAG & DROP ──
document.addEventListener('DOMContentLoaded', function () {
    const toastEl  = document.getElementById('saveToast');
    const toast    = new bootstrap.Toast(toastEl, { delay: 2000 });
    const toastMsg = document.getElementById('toastMessage');

    document.querySelectorAll('.col-body').forEach(col => {
        new Sortable(col, {
            group: 'tasks-' + col.getAttribute('data-project-id'),
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                if (evt.from === evt.to) return;
                const taskId    = evt.item.getAttribute('data-id');
                const newStatus = evt.to.getAttribute('data-status');
                const card      = evt.item;
                card.classList.add('task-updating');
                const fd = new FormData();
                fd.append('id', taskId);
                fd.append('status', newStatus);
                fetch('update_task_status.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        card.classList.remove('task-updating');
                        toastMsg.innerText       = data.success ? 'Статус обновлён' : 'Ошибка доступа';
                        toastEl.style.background = data.success ? '#1a1f2e' : '#c0392b';
                        if (!data.success) location.reload();
                        toast.show();
                    })
                    .catch(() => {
                        card.classList.remove('task-updating');
                        toastMsg.innerText       = 'Связь потеряна';
                        toastEl.style.background = '#c0392b';
                        toast.show();
                    });
            }
        });
    });

    // первый запуск сайдбара
    setTimeout(() => {
        const active = document.querySelector('.project-board[style*="flex"]');
        if (active) updateSidebar(active.id.replace('board-', ''));
    }, 800);
});

// ── SIDEBAR ──
async function updateSidebar(projectId) {
    try {
        const [resActivity, resStats] = await Promise.all([
            fetch('get_activity.php?project_id='   + projectId),
            fetch('get_statistics.php?project_id=' + projectId)
        ]);
        if (resActivity.ok) document.getElementById('activity-feed-content').innerHTML = await resActivity.text();
        if (resStats.ok)    document.getElementById('stats-feed-content').innerHTML    = await resStats.text();
    } catch (e) {
        console.warn('Sidebar update failed:', e);
    }
}
setInterval(() => {
    const active = document.querySelector('.project-board[style*="flex"]');
    if (active) updateSidebar(active.id.replace('board-', ''));
}, 10000);

// ── INVITE ──
async function inviteUser(event, projectId) {
    event.preventDefault();
    const form   = event.target;
    const fd     = new FormData(form);
    fd.append('project_id', projectId);
    const msgDiv = document.getElementById('invite-msg-' + projectId);
    const btn    = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    msgDiv.style.display = 'none';
    try {
        const r    = await fetch('invite_user.php', { method: 'POST', body: fd });
        const data = await r.json();
        msgDiv.style.display = 'block';
        if (data.success) {
            msgDiv.style.color = data.is_warning ? 'var(--red)' : 'var(--green)';
            msgDiv.textContent = data.message;
            form.reset();
            setTimeout(() => { msgDiv.style.display = 'none'; }, 4000);
        } else {
            msgDiv.style.color = 'var(--red)';
            msgDiv.textContent = data.error;
        }
    } catch {
        msgDiv.style.display = 'block';
        msgDiv.style.color   = 'var(--red)';
        msgDiv.textContent   = 'Ошибка сети.';
    } finally { btn.disabled = false; }
}

// ── MEMBERS ──
async function openMembersModal(projectId) {
    const modal     = new bootstrap.Modal(document.getElementById('membersModal'));
    const modalBody = document.getElementById('membersModalBody');
    modalBody.innerHTML = '<div style="text-align:center;color:var(--text-dim);font-size:12px;">Загрузка...</div>';
    modal.show();
    try {
        const r = await fetch('get_members.php?project_id=' + projectId);
        modalBody.innerHTML = await r.text();
    } catch {
        modalBody.innerHTML = '<div style="color:var(--red);">Ошибка загрузки.</div>';
    }
}

async function changeUserRole(projectId, userId, newRole) {
    const fd = new FormData();
    fd.append('project_id', projectId);
    fd.append('user_id', userId);
    fd.append('role', newRole);
    const r    = await fetch('update_role.php', { method: 'POST', body: fd });
    const data = await r.json();
    if (!data.success) { alert('Ошибка: ' + data.error); openMembersModal(projectId); }
}

async function removeUserFromProject(projectId, userId, email) {
    if (!confirm('Удалить ' + email + ' из проекта?')) return;
    const fd = new FormData();
    fd.append('project_id', projectId);
    fd.append('user_id', userId);
    const r    = await fetch('remove_member.php', { method: 'POST', body: fd });
    const data = await r.json();
    if (data.success) openMembersModal(projectId);
    else alert('Ошибка: ' + data.error);
}
function toggleSidebar() {
    const sidebar = document.querySelector('.activity-sidebar');
    sidebar.classList.toggle('collapsed');
}

// ── INVITES ──
async function handleInvite(inviteId, action) {
    const fd = new FormData();
    fd.append('invite_id', inviteId);
    
    // action == 'accept' => 'accept_invite.php'
    // action == 'reject' => 'reject_invite.php'
    const endpoint = action === 'accept' ? 'accept_invite.php' : 'reject_invite.php';
    
    try {
        const r = await fetch(endpoint, { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            location.reload(); 
        } else {
            alert('Ошибка: ' + data.error);
        }
    } catch {
        alert('Ошибка сети.');
    }
}
</script>
</body>
</html>