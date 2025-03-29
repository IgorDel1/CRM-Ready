<?php
//подключение к базе данных
require_once '../DB.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Обновляем статус заказа на неактивный (0)
        $sql = "UPDATE orders SET status = '0' WHERE id = ?";
        $stmt = $DB->prepare($sql);
        $stmt->execute([$id]);
        
        header('Location: ../../Orders.php');
        exit();
    } catch (PDOException $e) {
        // В случае ошибки
        header('Location: ../../Orders.php?error=status_update_failed');
        exit();
    }
} else {
    header('Location: ../../Orders.php');
    exit();
}
?>