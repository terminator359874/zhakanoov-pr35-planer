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
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $visibility  = $_POST['visibility'] ?? 'private';
    $owner_id    = $_SESSION['user_id'];

    $stmtCount = $db->prepare("SELECT COUNT(*) FROM projects WHERE owner_id = :owner_id");
    $stmtCount->execute(['owner_id' => $owner_id]);
    $projectCount = $stmtCount->fetchColumn();

    if (empty($name) || mb_strlen($name) > 200) {
        $error = "Название проекта обязательно и не должно превышать 200 символов.";
    } elseif ($projectCount >= 100) {
        $error = "Вы достигли лимита! Максимум 100 проектов. Удалите неактуальные, чтобы создать новые.";
    } else {
        $stmt = $db->prepare("
            INSERT INTO projects (name, description, visibility, owner_id)
            VALUES (:name, :description, :visibility, :owner_id)
        ");
        $stmt->execute([
            'name'        => $name,
            'description' => $description,
            'visibility'  => $visibility,
            'owner_id'    => $owner_id
        ]);
        $project_id = $db->lastInsertId();

        $stmt2 = $db->prepare("INSERT INTO project_members (user_id, project_id, role) VALUES (:user_id, :project_id, 'owner')");
        $stmt2->execute(['user_id' => $owner_id, 'project_id' => $project_id]);

        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создание проекта — Task Planner</title>
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
        .topbar-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: .07em;
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
            gap: 5px;
            transition: background .15s, border-color .15s, color .15s;
        }
        .topbar-btn:hover { background: var(--surface2); border-color: #b0b8c8; color: var(--text-head); }
        .topbar-btn.primary {
            border-color: var(--accent);
            background: var(--accent);
            color: #fff;
        }
        .topbar-btn.primary:hover { background: #1f58d0; border-color: #1f58d0; }

        /* FORM LAYOUT */
        .form-page {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 32px 16px;
        }
        .form-card {
            width: 100%;
            max-width: 480px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }
        .form-card-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
        }
        .form-card-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-head);
        }
        .form-card-body { padding: 20px; display: flex; flex-direction: column; gap: 16px; }

        /* FIELDS */
        .field-group { display: flex; flex-direction: column; gap: 5px; }
        .field-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-dim);
        }
        .tp-input, .tp-select, .tp-textarea {
            width: 100%;
            height: 34px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text-head);
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 13px;
            padding: 0 10px;
            outline: none;
            transition: border-color .15s, background .15s;
        }
        .tp-textarea {
            height: auto;
            padding: 8px 10px;
            resize: vertical;
        }
        .tp-input:focus, .tp-select:focus, .tp-textarea:focus {
            border-color: var(--accent);
            background: var(--surface);
        }
        .tp-input::placeholder, .tp-textarea::placeholder { color: var(--text-dim); }

        /* VISIBILITY TOGGLE */
        .vis-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .vis-option { display: none; }
        .vis-label {
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 5px;
            cursor: pointer;
            transition: border-color .15s, background .15s;
            background: var(--surface2);
        }
        .vis-label:hover { border-color: #b0b8c8; background: var(--surface); }
        .vis-option:checked + .vis-label {
            border-color: var(--accent);
            background: rgba(43,107,230,.06);
        }
        .vis-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-head);
        }
        .vis-desc {
            font-size: 11px;
            color: var(--text-dim);
        }

        /* ALERT */
        .tp-alert {
            padding: 10px 14px;
            border-radius: 4px;
            font-size: 12px;
            border: 1px solid;
        }
        .tp-alert.error { background: #fef2f2; border-color: #f5c6c6; color: var(--red); }

        .form-card-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <a href="index.php" class="topbar-brand">⬡ Task Planner</a>
    <div class="topbar-sep"></div>
    <span class="topbar-title">Новый проект</span>
    <div class="topbar-spacer"></div>
    <a href="index.php" class="topbar-btn">← На доску</a>
</div>

<!-- FORM -->
<div class="form-page">
    <div class="form-card">
        <div class="form-card-header">
            <span class="form-card-title">Создание проекта</span>
        </div>

        <form method="POST">
        <div class="form-card-body">

            <?php if (!empty($error)): ?>
                <div class="tp-alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Название -->
            <div class="field-group">
                <label class="field-label">Название</label>
                <input type="text" name="name" class="tp-input"
                       maxlength="200" placeholder="Введите название проекта" required>
            </div>

            <!-- Описание -->
            <div class="field-group">
                <label class="field-label">Описание</label>
                <textarea name="description" class="tp-textarea" rows="3"
                          placeholder="Краткое описание проекта (необязательно)"></textarea>
            </div>

            <!-- Видимость -->
            <div class="field-group">
                <label class="field-label">Тип доступа</label>
                <div class="vis-group">
                    <input type="radio" name="visibility" id="vis-private" value="private" class="vis-option" checked>
                    <label for="vis-private" class="vis-label">
                        <span class="vis-name">🔒 Приватный</span>
                        <span class="vis-desc">Только участники</span>
                    </label>

                    <input type="radio" name="visibility" id="vis-public" value="public" class="vis-option">
                    <label for="vis-public" class="vis-label">
                        <span class="vis-name">🌐 Публичный</span>
                        <span class="vis-desc">Виден всем</span>
                    </label>
                </div>
            </div>

        </div><!-- /form-card-body -->

        <div class="form-card-footer">
            <a href="index.php" class="topbar-btn">Отмена</a>
            <button type="submit" class="topbar-btn primary">Создать проект</button>
        </div>
        </form>
    </div>
</div>

</body>
</html>