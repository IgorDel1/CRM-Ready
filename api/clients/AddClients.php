<?php session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    $fields = ['fullname', 'email', 'phone', 'birthday'];
    $errors = [];
    $_SESSION['clients_error'] = '';
    foreach ($fields as $field) {
        if (!isset($formData[$field]) || empty($_POST[$field])) {
            $errors[$field][] = 'Field is required';
        }
    }

    if (!empty($errors)) {
        $errorList = '<ul>';
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $errorList .= '<li>' . ucfirst($field) . ': ' . $message . '</li>';
            }
        }
        $errorList .= '</ul>';
        
        $_SESSION['clients_error'] = $errorList;
        header('Location: ../../clients.php');
        exit;
    }

    // Функция для очистки данных
    function cleanData($fields) {
        $fields = trim($fields);
        $fields = stripslashes($fields);
        $fields = strip_tags($fields);
        $fields = htmlspecialchars($fields);
        return $fields;
    }

    // Очистка всех полей формы
    foreach ($formData as $key => $value) {
        $formData[$key] = cleanData($value);
        echo json_encode($formData);
    }

    $phone = $formData['phone'];
    
    // Подключение к базе данных
    require_once '../DB.php';
    
    // Проверка существования клиента по номеру телефона
    $existingClient = $DB->query(
        "SELECT id FROM clients WHERE phone='$phone'"
    )->fetchAll();

    if (!empty($existingClient)) {
        $_SESSION['clients_error'] = 'Клиент с таким номером телефона уже существует';
        header('Location: ../../clients.php');
        exit;
    }

    // Проверка userID
    if (!empty($formData['userID'])) {
        $_SESSION['clients_error'] = 'Поле userID должно быть пустым';
        header('Location: ../../clients.php');
        exit;
    }

    // Создаем массив соответствия полей формы и столбцов БД
    $dbFields = [
        'fullname' => 'name',
        'email' => 'email',
        'phone' => 'phone',
        'birthday' => 'birthday'
    ];

    // Преобразуем имена полей формы в имена столбцов БД
    $dbData = [];
    foreach ($formData as $formField => $value) {
        if (isset($dbFields[$formField])) {
            $dbData[$dbFields[$formField]] = $value;
        }
    }

    // Запись данных в базу данных
    $columns = implode(', ', array_keys($dbData));
    $values = "'" . implode("', '", array_values($dbData)) . "'";
    
    $query = "INSERT INTO clients ($columns) VALUES ($values)";
    $result = $DB->query($query);

    if ($result) {
        header('Location: ../../clients.php');
        exit;
    } else {
        $_SESSION['clients_error'] = 'Ошибка при добавлении клиента';
        header('Location: ../../clients.php');
        exit;
    }

    } else {
        echo json_encode(['error' => 'Invalid method']);
        exit;
    }

?>