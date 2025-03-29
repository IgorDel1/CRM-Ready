<?php

require_once '../../vendor/autoload.php';
require_once '../DB.php';

// Явное подключение файлов Dompdf
require_once '../../vendor/dompdf/dompdf/src/Options.php';
require_once '../../vendor/dompdf/dompdf/src/Dompdf.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Проверка наличия ID заказа
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $orderID = $_GET['id'];

    $orderQuery = "SELECT o.id, o.order_date, o.total as orderTotal, c.name as clientName, 
    a.name as adminName
    FROM orders o
    JOIN clients c ON o.client_id = c.id
    JOIN users a ON o.admin = a.id
    WHERE o.id = ?";

    $stmt = $DB->prepare($orderQuery);
    $stmt->execute([$orderID]);
    $orderData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Получаем товары заказа
    $itemsQuery = "SELECT p.name, oi.quantity, (oi.quantity * oi.price) as total 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?";

    $stmt = $DB->prepare($itemsQuery);
    $stmt->execute([$orderID]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [
        "orderID" => $orderID,
        "orderDate" => $orderData['order_date'],
        "adminName" => $orderData['adminName'],
        "clientName" => $orderData['clientName'],
        "orderItems" => $orderItems,
        "orderTotal" => $orderData['orderTotal']
    ];
    
    // Создаем HTML шаблон чека
    $html = '
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            body { font-family: DejaVu Sans, sans-serif; }
            .header { text-align: center; margin-bottom: 20px; }
            .order-info { margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>Чек №' . $orderID . '</h2>
            <p>Дата: ' . $orderData['order_date'] . '</p>
        </div>
        <div class="order-info">
            <p>Клиент: ' . $orderData['clientName'] . '</p>
            <p>Администратор: ' . $orderData['adminName'] . '</p>
        </div>
        <table>
            <tr>
                <th>Наименование</th>
                <th>Количество</th>
                <th>Сумма</th>
            </tr>';
    
    foreach ($orderItems as $item) {
        $html .= '<tr>
            <td>' . $item['name'] . '</td>
            <td>' . $item['quantity'] . '</td>
            <td>' . $item['total'] . ' руб.</td>
        </tr>';
    }
    
    $html .= '
        </table>
        <p style="text-align: right; margin-top: 20px;">
            <strong>Итого: ' . $orderData['orderTotal'] . ' руб.</strong>
        </p>
    </body>
    </html>';

    // Инициализация с настройками
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Отправляем PDF в браузер
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Гавно-Чек' . $orderID . '.pdf"');
    echo $dompdf->output();
    exit();
}
?>