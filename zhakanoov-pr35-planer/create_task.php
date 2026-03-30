<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = (new Database())->getConnection();

$stmt = $db->prepare("SELECT id, name FROM projects WHERE owner_id = :user_id ORDER BY name ASC");
$stmt->execute(['user_id' => $user_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fieldErrors = [];
$title = $description = $priority = $deadline = $project_id = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = $_POST['priority'] ?? '';
    $deadline    = $_POST['deadline'] ?? null;
    $project_id  = $_POST['project_id'] ?? null;

    if ($title === '' || strlen($title) > 200) {
        $fieldErrors['title'] = "Название обязательно и максимум 200 символов";
    }
    if (!in_array($priority, ['low','medium','high'])) {
        $fieldErrors['priority'] = "Некорректный приоритет";
    }
    if ($deadline && !strtotime($deadline)) {
        $fieldErrors['deadline'] = "Некорректная дата";
    }
    if ($project_id) {
        $check = $db->prepare("SELECT COUNT(*) FROM projects WHERE id = :id AND owner_id = :user_id");
        $check->execute(["id" => $project_id, "user_id" => $user_id]);
        if ($check->fetchColumn() == 0) {
            $fieldErrors['project_id'] = "Выбранный проект не существует или у вас нет к нему доступа";
        }
    } else {
        $fieldErrors['project_id'] = "Выберите проект";
    }

    if (!$fieldErrors) {
        $stmt = $db->prepare("
            INSERT INTO tasks(title, description, priority, deadline, project_id)
            VALUES(:title, :description, :priority, :deadline, :project_id)
        ");
        $stmt->bindValue(":title", $title);
        $stmt->bindValue(":description", $description);
        $stmt->bindValue(":priority", $priority);
        $stmt->bindValue(":project_id", $project_id);
        $stmt->bindValue(":deadline", $deadline ?: null, $deadline ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        $success = "Задача успешно создана!";
        $title = $description = $priority = $deadline = $project_id = '';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создание задачи — Task Planner</title>
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
        .topbar-btn.primary:hover { background: #1f58d0; border-color: #1f58d0; color: #fff; }

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
            max-width: 560px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }
        .form-card-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
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
        .tp-input.is-invalid, .tp-select.is-invalid, .tp-textarea.is-invalid {
            border-color: var(--red);
        }
        .field-error { font-size: 11px; color: var(--red); }

        /* ROW 2-COL */
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        /* SUCCESS / ERROR ALERTS */
        .tp-alert {
            padding: 10px 14px;
            border-radius: 4px;
            font-size: 12px;
            border: 1px solid;
        }
        .tp-alert.success { background: #f0faf4; border-color: #a8d5b8; color: var(--green); }
        .tp-alert.error   { background: #fef2f2; border-color: #f5c6c6; color: var(--red); }

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
    <span class="topbar-title">Новая задача</span>
    <div class="topbar-spacer"></div>
    <a href="index.php" class="topbar-btn">← На доску</a>
</div>

<!-- FORM -->
<div class="form-page">
    <div class="form-card">
        <div class="form-card-header">
            <span class="form-card-title">Создание задачи</span>
        </div>

        <form method="POST">
        <div class="form-card-body">

            <?php if ($success): ?>
                <div class="tp-alert success"><?= $success ?></div>
            <?php endif; ?>

            <!-- Название -->
            <div class="field-group">
                <label class="field-label">Название</label>
                <input type="text" name="title" class="tp-input <?= isset($fieldErrors['title']) ? 'is-invalid' : '' ?>"
                       maxlength="200" value="<?= htmlspecialchars($title) ?>" placeholder="Введите название задачи" required>
                <?php if (isset($fieldErrors['title'])): ?>
                    <span class="field-error"><?= $fieldErrors['title'] ?></span>
                <?php endif; ?>
            </div>

            <!-- Описание -->
            <div class="field-group">
                <label class="field-label">Описание</label>
                <textarea name="description" class="tp-textarea <?= isset($fieldErrors['description']) ? 'is-invalid' : '' ?>"
                          rows="4" placeholder="Опишите задачу..."><?= htmlspecialchars($description) ?></textarea>
            </div>

            <!-- Приоритет + Дедлайн -->
            <div class="field-row">
                <div class="field-group">
                    <label class="field-label">Приоритет</label>
                    <select name="priority" class="tp-select <?= isset($fieldErrors['priority']) ? 'is-invalid' : '' ?>">
                        <option value="low"    <?= $priority === 'low'    ? 'selected' : '' ?>>Низкий</option>
                        <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Средний</option>
                        <option value="high"   <?= $priority === 'high'   ? 'selected' : '' ?>>Высокий</option>
                    </select>
                    <?php if (isset($fieldErrors['priority'])): ?>
                        <span class="field-error"><?= $fieldErrors['priority'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="field-group">
                    <label class="field-label">Дедлайн</label>
                    <input type="datetime-local" name="deadline"
                           class="tp-input <?= isset($fieldErrors['deadline']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($deadline) ?>">
                    <?php if (isset($fieldErrors['deadline'])): ?>
                        <span class="field-error"><?= $fieldErrors['deadline'] ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Проект -->
            <div class="field-group">
                <label class="field-label">Проект</label>
                <select name="project_id" class="tp-select <?= isset($fieldErrors['project_id']) ? 'is-invalid' : '' ?>">
                    <option value="">— выберите проект —</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $project_id == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($fieldErrors['project_id'])): ?>
                    <span class="field-error"><?= $fieldErrors['project_id'] ?></span>
                <?php endif; ?>
            </div>

        </div><!-- /form-card-body -->

        <div class="form-card-footer">
            <a href="index.php" class="topbar-btn">Отмена</a>
            <button type="submit" class="topbar-btn primary">Создать задачу</button>
        </div>
        </form>
    </div>
</div>

</body>
</html>