<?php
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'Это тестовая версия страницы. База данных пока не подключена.';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-lg border-0">
                <div class="card-body p-5">

                    <div class="text-center mb-4">
                        <span class="badge bg-primary rounded-circle p-3" style="font-size:2rem">👤</span>
                        <h3 class="mt-3">Создайте аккаунт</h3>
                        <p class="text-muted">Демонстрационная форма</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">

                        <div class="form-floating mb-3">
                            <input type="text"
                                   class="form-control"
                                   id="username"
                                   name="username"
                                   placeholder="Имя"
                                   required>
                            <label for="username">👤 Имя пользователя</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email"
                                   class="form-control"
                                   id="email"
                                   name="email"
                                   placeholder="Email"
                                   required>
                            <label for="email">📧 Email адрес</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password"
                                   class="form-control"
                                   id="password"
                                   name="password"
                                   placeholder="Пароль"
                                   required>
                            <label for="password">🔒 Пароль</label>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="password"
                                   class="form-control"
                                   id="confirm"
                                   name="confirm"
                                   placeholder="Повтор"
                                   required>
                            <label for="confirm">🔒 Повторите пароль</label>
                        </div>

                        <button type="submit"
                                class="btn btn-primary btn-lg w-100">
                            Зарегистрироваться
                        </button>

                    </form>

                    <div class="d-flex align-items-center my-4">
                        <hr class="flex-grow-1">
                        <span class="mx-2 text-muted">или</span>
                        <hr class="flex-grow-1">
                    </div>

                    <a href="#"
                       class="btn btn-outline-secondary w-100">
                        Войти в существующий аккаунт
                    </a>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>