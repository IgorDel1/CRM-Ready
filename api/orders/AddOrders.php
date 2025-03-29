<?php session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = $_POST;
    $fields = ['client', 'products'];
    $errors = [];

    if ($formData['client'] === 'new') {
        $fields[] = 'email';
    }
    
    $_SESSION['orders_error'] = '';

    foreach ($fields as $field) {
        if (!isset($formData[$field]) || empty($_POST[$field])) {
            $errors[$field][] = 'Gavno ne rabotaet';
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
        
        $_SESSION['orders_error'] = $errorList;
        header('Location: ../../orders.php');
        exit;
    }

    require_once '../DB.php';
    //ид выбранных товаров
    $products = $formData['products'];
    
    //получаем сумму выбранных товаров через SQL
    $placeholders = str_repeat('?,', count($products) - 1) . '?';
    $stmt = $DB->prepare("SELECT SUM(price) as total FROM products WHERE id IN ($placeholders)");
    $stmt->execute($products);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'];

    //все товары из бд (нужны для последующего использования)
    $allProducts = $DB->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);

    $clientID = $formData['client'] === 'new' ? time() : $formData['client'];

    if ($formData['client'] === 'new') {
        $stmt = $DB->prepare("INSERT INTO clients (id, name, email, phone, birthday) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $clientID,
            $formData['name'] ?? 'не указано',
            $formData['email'],
            'не указано', // значение по умолчанию для phone
            'не указано'  // значение по умолчанию для birthday
        ]);
    }

    $stmt = $DB->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->execute([$_SESSION['token']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $adminID = $result['id'];

    // 1. создание заказа с полями
    $orders = [
        'id' => time(),
        'client_id' => $clientID,
        'total' => $total,
        'admin' => $adminID,
        'status' => 1  // добавляем статус активного заказа
    ];

    //код акции (code_promo)
    $promo = $_POST['promo'] ?? '';
    $promoInfo = []; // информация о акции
    $promotion_id = null;

    // Проверяем промокод только если он был введен
    if (!empty($promo)) {
        // 1. получить информацию о акции и записать в promoInfo (по code_promo)
        // 2. проверить активна ли акция (uses < max_uses, cancel_at < текущей даты)
        $promoInfo = $DB->query(   
            "SELECT * FROM promotions WHERE code_promo = '$promo'" 
            )->fetchALL();

        if (empty($promoInfo)) {
            $_SESSION['orders_error'] = 'Промокод не существует';
            header('Location: ../../orders.php');
            exit;
        }

        if ($promoInfo[0]['uses'] >= $promoInfo[0]['max_uses']) {
            $_SESSION['orders_error'] = 'Акция закончена';
            header('Location: ../../orders.php');
            exit;
        }

        // Получаем promotion_id из таблицы promotions только если промокод валиден
        $promotion_id = $promoInfo[0]['id'];
    }

    try {
        $stmt = $DB->prepare(
            "INSERT INTO orders (id, client_id, order_date, total, status, admin, promotion_id) 
             VALUES (?, ?, NOW(), ?, ?, ?, ?)"
        );
        $result = $stmt->execute([
            $orders['id'],
            $clientID,
            $total,
            $orders['status'],  // передаем статус в запрос
            $adminID,
            $promotion_id
        ]);

        if (!$result) {
            throw new Exception('Ошибка при добавлении заказа');
        }

        // Обновляем количество использований промокода
        if ($promotion_id) {
            $stmt = $DB->prepare("UPDATE promotions SET uses = uses + 1 WHERE id = ?");
            $stmt->execute([$promotion_id]);
        }

        // 2. Добавление элементов заказа в order_items
        $stmt = $DB->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
        );

        foreach ($products as $product_id) {
            // Находим информацию о продукте
            $productInfo = array_filter($allProducts, function($p) use ($product_id) {
                return $p['id'] == $product_id;
            });
            $productInfo = reset($productInfo);

            // Добавляем запись в order_items
            $stmt->execute([
                $orders['id'],      // order_id
                $product_id,        // product_id
                1,                  // quantity (по умолчанию 1)
                $productInfo['price'] // price
            ]);
        }

        // Редирект на страницу заказов после успешного создания
        header('Location: ../../orders.php');
        exit;

    } catch (Exception $e) {
        $_SESSION['orders_error'] = 'Ошибка при создании заказа: ' . $e->getMessage();
        header('Location: ../../orders.php');
        exit;
    }

} else {
    echo json_encode(['error' => 'Invalid method']);
    exit;
}

?>