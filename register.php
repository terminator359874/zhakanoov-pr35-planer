<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

$database = new Database();
$pdo = $database->getConnection();

$userModel = new User($pdo);

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm'] ?? '');

    if (empty($name)) {
        $errors[] = 'Имя пользователя обязательно для заполнения';
    } elseif (mb_strlen($name) > 255) {
        $errors[] = 'Имя пользователя не должно превышать 255 символов';
    }

    if (empty($email)) {
        $errors[] = 'Email обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email!';
    } elseif ($userModel->emailExists($email)) {
        $errors[] = 'Пользователь с таким email уже зарегистрирован!';
    }

    if (empty($password)) {
        $errors[] = 'Пароль обязателен для заполнения';
    } elseif (mb_strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов!';
    } elseif ($password !== $confirm) {
        $errors[] = 'Пароли не совпадают!';
    }

    if (empty($errors)) {
        if ($userModel->register($name, $email, $password)) {
            $message = 'Регистрация успешна! Теперь вы можете войти.';
        } else {
            $errors[] = 'Ошибка при регистрации.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-lg border-0">
                <div class="card-body p-5">

                    <h3 class="text-center mb-4">Создайте аккаунт</h3>

                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" style="color:red; border:1px solid red; padding:10px; margin-bottom: 10px;">
                        <?php foreach ($errors as $e): ?>
                            <p class="mb-0"><?= htmlspecialchars($e) ?></p>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">Имя пользователя</label>
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   required
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>

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
                                   required minlength="6">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Повторите пароль</label>
                            <input type="password"
                                   name="confirm"
                                   class="form-control"
                                   required minlength="6">
                        </div>

                        <button class="btn btn-primary w-100">
                            Зарегистрироваться
                        </button>

                    </form>

                    <p class="text-center mt-3">
                        Уже есть аккаунт? <a href="login.php">Войти</a>
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
