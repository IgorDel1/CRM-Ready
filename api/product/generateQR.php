<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../DB.php';

// Явное подключение файлов Dompdf
require_once '../../vendor/dompdf/dompdf/src/Options.php';
require_once '../../vendor/dompdf/dompdf/src/Dompdf.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Проверка наличия ID заказа
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $DB->prepare("SELECT id, name, description, price FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        // Используем heredoc синтаксис для более чистого форматирования HTML
        $htmlContent = <<<HTML
<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Информация о товаре</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .product-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .product-title {
            color: #333;
            font-size: 24px;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .product-id {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .product-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .product-price {
            color: #2ecc71;
            font-size: 20px;
            font-weight: bold;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class='product-card'>
        <div class='product-id'>ID товара: {$product['id']}</div>
        <h1 class='product-title'>{$product['name']}</h1>
        <div class='product-description'>{$product['description']}</div>
        <div class='product-price'>{$product['price']} ₽</div>
    </div>
</body>
</html>
HTML;
        
        $qrCode = new QrCode($htmlContent);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        header('Content-Type: ' . $result->getMimeType());
        echo $result->getString();
    } else {
        echo "Товар не найден";
    }
}