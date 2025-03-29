<?php session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    $fields = ['name', 'description', 'price', 'quantity'];
    $errors = [];
    $_SESSION['product_error'] = '';
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
        
        $_SESSION['product_error'] = $errorList;
        header('Location: ../../product.php');
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

    $name = $formData['name'];
    
    // Подключение к базе данных
    require_once '../DB.php';
    
    // Проверка существования клиента по номеру телефона
    $existingClient = $DB->query(
        "SELECT id FROM products WHERE name='$name'"
    )->fetchAll();

    if (!empty($existingClient)) {
        $_SESSION['product_error'] = 'Товар с таким названием уже существует';
        header('Location: ../../product.php');
        exit;
    }

    // Проверка userID
    if (!empty($formData['userID'])) {
        $_SESSION['product_error'] = 'Поле userID должно быть пустым';
        header('Location: ../../product.php');
        exit;
    }

    // Создаем массив соответствия полей формы и столбцов БД
    $dbFields = [
        'name' => 'name',
        'description' => 'description',
        'price' => 'price',
        'quantity' => 'stock'
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
    
    $query = "INSERT INTO products ($columns) VALUES ($values)";
    $result = $DB->query($query);

    if ($result) {
        header('Location: ../../product.php');
        exit;
    } else {
        $_SESSION['product_error'] = 'Ошибка при добавлении товара';
        header('Location: ../../product.php');
        exit;
    }

    } else {
        echo json_encode(['error' => 'Invalid method']);
        exit;
    }

?>