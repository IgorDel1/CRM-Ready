<?php
session_start();
require_once '../DB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (empty($id)) {
        header('Location: ../../orders.php');
        exit;
    }

    try {
        $stmt = $DB->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        header('Location: ../../orders.php');
    } catch (PDOException $e) {
        $_SESSION['orders_error'] = 'Ошибка при обновлении заказа: ' . $e->getMessage();
        header('Location: ../../orders.php');
    }
}
?> 