<?php
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

// создаём объект базы и получаем соединение
$database = new Database();
$pdo = $database->getConnection();

$userModel = new User($pdo);
$errors = [];

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email)) {
        $errors[] = 'Поле Email обязательно для заполнения';
    }
    if (empty($password)) {
        $errors[] = 'Поле Пароль обязательно для заполнения';
    }

    if (empty($errors)) {
        $user = $userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['email']    = $user['email'];

            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Неверный email или пароль!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <div class="card shadow">
                <div class="card-body p-4">

                    <h2 class="text-center mb-4">Вход</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" style="color:red; border:1px solid red; padding:10px; margin-bottom: 10px;">
                        <?php foreach ($errors as $e): ?>
                            <p class="mb-0"><?= htmlspecialchars($e) ?></p>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   required
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password"
                                   name="password"
                                   class="form-control"
                                   required>
                        </div>

                        <button class="btn btn-success w-100">Войти</button>
                    </form>

                    <p class="text-center mt-3">
                        Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
