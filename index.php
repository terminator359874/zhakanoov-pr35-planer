<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Task Planner</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
background:#f4f6f9;
}

.board-column{
background:white;
border-radius:10px;
padding:15px;
min-height:400px;
box-shadow:0 2px 5px rgba(0,0,0,0.05);
}

.task-card{
border-left:5px solid #0d6efd;
border-radius:8px;
padding:10px;
margin-bottom:10px;
background:#fff;
box-shadow:0 1px 3px rgba(0,0,0,0.1);
}

.task-high{
border-left-color:#dc3545;
}

.task-medium{
border-left-color:#ffc107;
}

.task-low{
border-left-color:#198754;
}

</style>

</head>

<body>

<nav class="navbar navbar-dark bg-dark">
<div class="container-fluid">

<span class="navbar-brand">Task Planner</span>

<a href="create_task.php" class="btn btn-success">
Создать задачу
</a>

</div>
</nav>


<div class="container mt-4">

<div class="row mb-3">

<div class="col-md-4">
<select class="form-select">
<option>Все задачи</option>
<option>Новые</option>
<option>В процессе</option>
<option>Завершенные</option>
<option>Отложенные</option>
</select>
</div>

<div class="col-md-4">
<input class="form-control" placeholder="Поиск задачи">
</div>

<div class="col-md-4 text-end">
<button class="btn btn-outline-secondary">Фильтр</button>
</div>

</div>


<div class="row g-3">

<div class="col-md-3">

<h5>Новые</h5>

<div class="board-column">

<div class="task-card task-high">
<strong>Сделать отчет</strong>
<br>
<small>Приоритет: высокий</small>
<br>
<button class="btn btn-sm btn-outline-primary mt-2">Редактировать</button>
<button class="btn btn-sm btn-outline-danger mt-2">Удалить</button>
</div>

<div class="task-card task-medium">
<strong>Написать документацию</strong>
<br>
<small>Приоритет: средний</small>
</div>

</div>

</div>


<div class="col-md-3">

<h5>В процессе</h5>

<div class="board-column">

<div class="task-card task-low">
<strong>Сверстать страницу</strong>
<br>
<small>Приоритет: низкий</small>
</div>

</div>

</div>


<div class="col-md-3">

<h5>Завершенные</h5>

<div class="board-column">

<div class="task-card">
<strong>Настроить БД</strong>
</div>

</div>

</div>


<div class="col-md-3">

<h5>Отложенные</h5>

<div class="board-column">

<div class="text-muted">
Нет задач
</div>

</div>

</div>


</div>

</div>

</body>
</html>