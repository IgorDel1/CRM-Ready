<?php
session_start();
require_once '../DB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $stock = $_POST['quantity'] ?? '';
    
    if (empty($id)) {
        header('Location: ../../product.php');
        exit;
    }

    try {
        $stmt = $DB->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $stock, $id]);
        
        header('Location: ../../product.php');
    } catch (PDOException $e) {
        $_SESSION['product_error'] = 'Ошибка при обновлении товара: ' . $e->getMessage();
        header('Location: ../../product.php');
    }
}
?> 