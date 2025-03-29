<?php session_start();

require_once 'api/auth/AuthCheck.php';

AuthCheck('clients.php');

// Сделать :
// Вывод ошибки для пароля
// Покрасить ошибку в красный цвет и сделать поменьше

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM | Авторизация</title>
    <link rel="stylesheet" href="styles/settings.css">
    <link rel="stylesheet" href="styles/pages/login.css">
</head>
<body>
    <header>
        <div class="container">
            <form action="api/auth/AuthUser.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Логин</label>
                    <input type="text" id="username" name="username">
                    <p class="error">
                        <?php
                            if (isset($_SESSION['login-errors'])) {
                                $errors = $_SESSION['login-errors'];

                                echo isset($errors['login']) ? $errors['login'] : '';
                            }
                        ?>
                    </p>
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password">
                    <p class="error">
                        <?php
                            if (isset($_SESSION['login-errors'])) {
                                $errors = $_SESSION['login-errors'];

                                echo isset($errors['password']) ? $errors['password'] : '';
                            }
                        ?>
                    </p>
                </div>
                <button type="submit" class="login-button">Войти</button>
            </form>
        </div>
    </header>
</body>
</html>