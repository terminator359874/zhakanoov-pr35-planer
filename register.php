<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

$database = new Database();
$pdo = $database->getConnection();

$userModel = new User($pdo);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Заполните все поля!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email!';
    } elseif (mb_strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов!';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают!';
    } elseif ($userModel->emailExists($email)) {
        $error = 'Пользователь с таким email уже зарегистрирован!';
    } else {

        if ($userModel->register($name, $email, $password)) {
            $message = 'Регистрация успешна! Теперь вы можете войти.';
        } else {
            $error = 'Ошибка при регистрации.';
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

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
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