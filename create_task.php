<?php
session_start();
require_once 'database.php'; // Убедитесь, что путь правильный (возможно 'config/database.php')

// Если пользователь не авторизован, отправляем на логин
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = (new Database())->getConnection();

// Выбираем только те проекты, где текущий пользователь является владельцем
$stmt = $db->prepare("SELECT id, name FROM projects WHERE owner_id = :user_id ORDER BY name ASC");
$stmt->execute(['user_id' => $user_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fieldErrors = [];
$title = '';
$description = '';
$priority = '';
$deadline = '';
$project_id = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? '';
    $deadline = $_POST['deadline'] ?? null;
    $project_id = $_POST['project_id'] ?? null;


    if ($title === '' || strlen($title) > 200) {
        $fieldErrors['title'] = "Название обязательно и максимум 200 символов";
    }

    $allowed = ['low','medium','high'];
    if (!in_array($priority, $allowed)) {
        $fieldErrors['priority'] = "Некорректный приоритет";
    }

    if ($deadline && !strtotime($deadline)) {
        $fieldErrors['deadline'] = "Некорректная дата";
    }

// Было: "SELECT COUNT(*) FROM projects WHERE id = :id"
if ($project_id) {
    // Стало: проверяем не только ID проекта, но и его владельца!
    $check = $db->prepare("SELECT COUNT(*) FROM projects WHERE id = :id AND owner_id = :user_id");
    $check->execute([
        "id" => $project_id,
        "user_id" => $user_id
    ]);
    
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

  
        if ($deadline) {
            $stmt->bindValue(":deadline", $deadline);
        } else {
            $stmt->bindValue(":deadline", null, PDO::PARAM_NULL);
        }

        $stmt->execute();

        $success = "Задача успешно создана!";
 
        $title = $description = $priority = $deadline = $project_id = '';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Создание задачи</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
<div class="row justify-content-center">
<div class="col-md-7">
<div class="card p-4">

<h3 class="mb-4">Создание задачи</h3>

<?php if ($success): ?>
<div class="alert alert-success"><?=$success?></div>
<?php endif; ?>

<form method="POST">

<div class="mb-3">
    <label class="form-label">Название</label>
    <input type="text" class="form-control <?=isset($fieldErrors['title'])?'is-invalid':''?>"
           name="title" maxlength="200" value="<?=htmlspecialchars($title)?>" required>
    <?php if(isset($fieldErrors['title'])): ?>
        <div class="invalid-feedback"><?=$fieldErrors['title']?></div>
    <?php endif; ?>
</div>

<div class="mb-3">
    <label class="form-label">Описание</label>
    <textarea class="form-control <?=isset($fieldErrors['description'])?'is-invalid':''?>"
              name="description" rows="4"><?=htmlspecialchars($description)?></textarea>
    <?php if(isset($fieldErrors['description'])): ?>
        <div class="invalid-feedback"><?=$fieldErrors['description']?></div>
    <?php endif; ?>
</div>

<div class="mb-3">
    <label class="form-label">Приоритет</label>
    <select class="form-select <?=isset($fieldErrors['priority'])?'is-invalid':''?>" name="priority">
        <option value="low" <?=($priority==='low'?'selected':'')?>>Low</option>
        <option value="medium" <?=($priority==='medium'?'selected':'')?>>Medium</option>
        <option value="high" <?=($priority==='high'?'selected':'')?>>High</option>
    </select>
    <?php if(isset($fieldErrors['priority'])): ?>
        <div class="invalid-feedback"><?=$fieldErrors['priority']?></div>
    <?php endif; ?>
</div>

<div class="mb-3">
    <label class="form-label">Срок выполнения</label>
    <input type="datetime-local" class="form-control <?=isset($fieldErrors['deadline'])?'is-invalid':''?>"
           name="deadline" value="<?=htmlspecialchars($deadline)?>">
    <?php if(isset($fieldErrors['deadline'])): ?>
        <div class="invalid-feedback"><?=$fieldErrors['deadline']?></div>
    <?php endif; ?>
</div>

<div class="mb-3">
    <label class="form-label">Проект</label>
    <select class="form-select <?=isset($fieldErrors['project_id'])?'is-invalid':''?>" name="project_id">
        <option value="">-- выберите проект --</option>
        <?php foreach($projects as $p){ ?>
            <option value="<?=$p['id']?>" <?=($project_id==$p['id']?'selected':'')?>>
                <?=$p['name']?>
            </option>
        <?php } ?>
    </select>
    <?php if(isset($fieldErrors['project_id'])): ?>
        <div class="invalid-feedback"><?=$fieldErrors['project_id']?></div>
    <?php endif; ?>
</div>

<div class="d-flex justify-content-between">
    <a href="index.php" class="btn btn-secondary">Назад</a>
    <button class="btn btn-success">Создать задачу</button>
</div>

</form>
</div>
</div>
</div>
</div>
</body>
</html>