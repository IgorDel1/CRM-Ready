<?php session_start();

// Вход по логину / паролю админа

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Если username есть - записываем username , иначе ''
    $login = isset($_POST['username']) ? $_POST['username'] : '';
    // Если password есть - записываем password , иначе ''
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Проверка , пришли ли данные

    $_SESSION['login-errors'] = [];

    if (!$login) {
        $_SESSION['login-errors']['login'] = 'Field is required';
    }

    if (!$password) {
        $_SESSION['login-errors']['password'] = 'Field is required';
    }

    if (!$login || !$password) {
        header('Location: ../../login.php');
        exit;
    }
    
    // Фильтрация данных
    function clearData($input) {
        $cleaned = strip_tags($input);
        $cleaned = trim($cleaned);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        return $cleaned;
    }
    $login = clearData($login);
    $password = clearData($password);

    // Проверка логина

    require_once '../DB.php';

    $userID = $DB->query(
        "SELECT id FROM users WHERE login='$login'
    ")->fetchAll();
    // Пользователя нет -> ошибка + выход на страницу
    if (empty($userID)) {
        $_SESSION['login-errors']['login'] = 'User not found';
        header('Location: ../../login.php');
        exit;
    }

    // Проверка пароля

    $userID = $DB->query(
        "SELECT id FROM users WHERE login='$login' AND password='$password'
    ")->fetchAll();
    if (empty($userID)) {
        $_SESSION['login-errors']['password'] = 'Wrong password';
        header('Location: ../../login.php');
        exit;
    }

    // Генерация токена (записб в сессию , запись в бд)

    $uniquerString = time();
    $token = base64_encode(
        "login=$login&password=$password&unique=$uniquerString"
    );
    // Записать в сессию в поле token
    $_SESSION['token'] = $token;
    // Записать в БД в поле токен
    $DB->query("
        UPDATE users SET token = '$token' 
        WHERE login = '$login' AND password = '$password' 
    ")->fetchAll();
    header('Location: ../../clients.php');
} else {
    echo json_encode([
        "error" => 'Неверный запрос',
    ]);
}

?>