<?php
require_once '../DB.php';
require_once '../../vendor/autoload.php';
require_once '../../vendor/dompdf/dompdf/src/Options.php';
require_once '../../vendor/dompdf/dompdf/src/Dompdf.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $clientID = $_GET['id'];
    $dateFROM = $_GET['from'];
    $dateTO = $_GET['to'];

    // Преобразуем строки в объекты DateTime для корректного сравнения
    $dateFrom = new DateTime($dateFROM);
    $dateTo = new DateTime($dateTO);
    
    // Если выбран один день, устанавливаем конец дня для dateTO
    if ($dateFROM === $dateTO) {
        $dateTo->setTime(23, 59, 59);
    }

    // Форматируем даты для SQL запроса
    $dateFromSQL = $dateFrom->format('Y-m-d H:i:s');
    $dateToSQL = $dateTo->format('Y-m-d H:i:s');

    // Проверяем корректность дат
    if ($dateFrom > $dateTo) {
        session_start();
        $_SESSION['clients_error'] = 'ДАТУ НОРМАЛЬНО ПИШИ!';
        header('Location: ../../clients.php');
        exit;
    }

    // Получаем данные о клиенте
    $clientQuery = "SELECT name FROM clients WHERE id = ?";
    $stmt = $DB->prepare($clientQuery);
    $stmt->execute([$clientID]);
    $clientData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Получаем историю заказов клиента с учетом дат
    $ordersQuery = "SELECT o.id, o.order_date, o.total, o.status, oi.quantity, oi.price, p.name as product_name
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p ON oi.product_id = p.id
                    WHERE o.client_id = ? 
                    AND o.order_date >= ? 
                    AND o.order_date <= ?
                    ORDER BY o.order_date DESC";
    
    $stmt = $DB->prepare($ordersQuery);
    $stmt->execute([$clientID, $dateFromSQL, $dateToSQL]);
    $orderHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Формируем HTML для PDF с улучшенными стилями
    $html = <<<HTML
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            body { 
                font-family: DejaVu Sans, sans-serif;
                margin: 20px;
                color: #333;
            }
            .header { 
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 10px;
                border-bottom: 2px solid #eee;
            }
            .client-info { 
                margin-bottom: 30px;
                padding: 15px;
                background-color: #f8f9fa;
                border-radius: 5px;
            }
            table { 
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            th, td { 
                border: 1px solid #dee2e6;
                padding: 12px;
                text-align: center;
            }
            th {
                background-color: #f8f9fa;
                font-weight: bold;
            }
            td:nth-child(2) {
                text-align: left;
            }
            td:nth-child(4), td:nth-child(5) {
                text-align: right;
            }
            .total-row td {
                text-align: right;
                font-weight: bold;
                background-color: #f8f9fa;
            }
            .total-row td:first-child {
                text-align: right;
            }
            .order-header {
                margin: 20px 0;
                color: #2c3e50;
            }
            .status-active {
                color: #2ecc71;
                font-weight: bold;
            }
            .status-inactive {
                color: #e74c3c;
                font-weight: bold;
                text-align: center !important;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>История заказов клиента</h2>
            <p>Период: {$dateFROM} - {$dateTO}</p>
        </div>
        <div class="client-info">
            <p>Клиент: {$clientData['name']}</p>
        </div>
HTML;

    if (empty($orderHistory)) {
        $html .= '<div style="text-align: center; padding: 30px;">
                    <p>У данного клиента нет заказов за выбранный период</p>
                 </div>';
    } else {
        $currentOrderId = null;
        $orderTotal = 0;
        
        foreach ($orderHistory as $item) {
            if ($currentOrderId !== $item['id']) {
                // Закрываем предыдущую таблицу, если она существует
                if ($currentOrderId !== null) {
                    $html .= "<tr class='total-row'>
                                <td colspan='4' style='text-align: right;'><strong>Итого:</strong></td>
                                <td style='text-align: right;'><strong>{$orderTotal} руб.</strong></td>
                                <td></td>
                            </tr></table>";
                }
                
                $currentOrderId = $item['id'];
                $orderTotal = 0;
                
                $html .= "<h3 class='order-header'>Заказ №{$item['id']} от {$item['order_date']}</h3>
                         <table>
                            <tr>
                                <th>ID заказа</th>
                                <th>Товар</th>
                                <th>Количество</th>
                                <th>Цена</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                            </tr>";
            }
            
            $itemTotal = $item['quantity'] * $item['price'];
            $orderTotal += $itemTotal;
            
            $html .= "<tr>
                        <td style='text-align: center;'>{$item['id']}</td>
                        <td style='text-align: left;'>{$item['product_name']}</td>
                        <td style='text-align: center;'>{$item['quantity']}</td>
                        <td style='text-align: right;'>{$item['price']} руб.</td>
                        <td style='text-align: right;'>{$itemTotal} руб.</td>
                        <td style='text-align: center; color: " . ($item['status'] == 1 ? '#2ecc71' : '#e74c3c') . "; font-weight: bold;'>" . 
                        ($item['status'] == 1 ? 'Активен' : 'Неактивен') . "</td>
                    </tr>";
        }
        
        // Закрываем последнюю таблицу
        if ($currentOrderId !== null) {
            $html .= "<tr class='total-row'>
                        <td colspan='4' style='text-align: right;'><strong>Итого:</strong></td>
                        <td style='text-align: right;'><strong>{$orderTotal} руб.</strong></td>
                        <td></td>
                    </tr></table>";
        }
    }

    $html .= '</body></html>';

    // Генерируем PDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Отправляем PDF
    $filename = "История_заказов_" . $clientData['name'] . ".pdf";
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
    exit();
}
?>