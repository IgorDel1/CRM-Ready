<?php

function AuthCheck($successPath = '', $errorPath = '') {
    global $DB;
    
    // Изменяем пути для корректного подключения файлов
    require_once __DIR__ . '/../DB.php';
    require_once __DIR__ . '/LogoutUser.php';

    // Проверка наличия ключа token в $_SESSION
    if (!isset($_SESSION['token'])) {

        if ($errorPath) {
            header("Location: $errorPath");
        }

        return;
    }
    // Токен текущего пользователя
    $token = $_SESSION['token'];
    // Получение ИД администратора по текущему токену
    $adminID = $DB->query(
        "SELECT id FROM users WHERE token='$token'
    ")->fetchAll();
    if (empty($adminID) && $errorPath) {
        LogoutUser($errorPath, $DB);

        header("Location: $errorPath");
    }
    if (!empty($adminID) && $successPath) {
        header("Location: $successPath");
    }
}

?>