<?php
require_once 'database.php';

$db = (new Database())->getConnection();

$query = $db->query("SELECT id,name FROM projects");
$projects = $query->fetchAll(PDO::FETCH_ASSOC);

$errors=[];
$title='';
$description='';
$priority='';
$deadline='';
$project_id='';

if($_SERVER['REQUEST_METHOD']==='POST'){

$title=trim($_POST['title'] ?? '');
$description=trim($_POST['description'] ?? '');
$priority=$_POST['priority'] ?? '';
$deadline=$_POST['deadline'] ?? null;
$project_id=$_POST['project_id'] ?? null;

if($title=='' || strlen($title)>200){
$errors[]="Название обязательно и максимум 200 символов";
}

$allowed=['low','medium','high'];
if(!in_array($priority,$allowed)){
$errors[]="Некорректный приоритет";
}

if($deadline && !strtotime($deadline)){
$errors[]="Некорректная дата";
}

if(!$errors){

$stmt=$db->prepare("
INSERT INTO tasks(title,description,priority,deadline,project_id)
VALUES(:title,:description,:priority,:deadline,:project_id)
");

$stmt->execute([
"title"=>$title,
"description"=>$description,
"priority"=>$priority,
"deadline"=>$deadline,
"project_id"=>$project_id
]);

$success="Задача создана";

$title='';
$description='';
$priority='';
$deadline='';
$project_id='';

}
}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>Создание задачи</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
background:#f4f6f9;
}

.card{
border-radius:12px;
box-shadow:0 3px 10px rgba(0,0,0,0.08);
}

</style>

</head>

<body>

<div class="container mt-5">

<div class="row justify-content-center">

<div class="col-md-7">

<div class="card p-4">

<h3 class="mb-4">Создание задачи</h3>

<?php if($errors): ?>

<div class="alert alert-danger">

<?php foreach($errors as $e){ echo $e."<br>"; } ?>

</div>

<?php endif; ?>

<?php if(!empty($success)): ?>

<div class="alert alert-success">
<?=$success?>
</div>

<?php endif; ?>


<form method="POST">

<div class="mb-3">

<label class="form-label">Название</label>

<input type="text" class="form-control" name="title" maxlength="200" value="<?=htmlspecialchars($title)?>" required>

</div>


<div class="mb-3">

<label class="form-label">Описание</label>

<textarea class="form-control" name="description" rows="4"><?=htmlspecialchars($description)?></textarea>

</div>


<div class="mb-3">

<label class="form-label">Приоритет</label>

<select class="form-select" name="priority">

<option value="low">Low</option>
<option value="medium">Medium</option>
<option value="high">High</option>

</select>

</div>


<div class="mb-3">

<label class="form-label">Срок выполнения</label>

<input type="datetime-local" class="form-control" name="deadline" value="<?=htmlspecialchars($deadline)?>">

</div>


<div class="mb-3">

<label class="form-label">Проект</label>

<select class="form-select" name="project_id">

<?php foreach($projects as $p){ ?>

<option value="<?=$p['id']?>"><?=$p['name']?></option>

<?php } ?>

</select>

</div>


<div class="d-flex justify-content-between">

<a href="index.php" class="btn btn-secondary">
Назад
</a>

<button class="btn btn-success">
Создать задачу
</button>

</div>

</form>

</div>

</div>

</div>

</div>

</body>

</html>