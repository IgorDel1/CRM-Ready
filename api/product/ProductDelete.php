<?php
//подключение к базе данных
require_once '../DB.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Начинаем транзакцию
        $DB->beginTransaction();
        
        // Сначала удаляем связанные записи из order_items
        $sql = "DELETE FROM order_items WHERE product_id = ?";
        $stmt = $DB->prepare($sql);
        $stmt->execute([$id]);
        
        // Затем удаляем сам продукт
        $sql = "DELETE FROM products WHERE id = ?";
        $stmt = $DB->prepare($sql);
        $stmt->execute([$id]);
        
        // Подтверждаем транзакцию
        $DB->commit();
        
        header('Location: ../../product.php');
        exit();
    } catch (PDOException $e) {
        // В случае ошибки откатываем транзакцию
        $DB->rollBack();
        // Можно добавить логирование ошибки
        header('Location: ../../product.php?error=delete_failed');
        exit();
    }
} else {
    header('Location: ../../product.php');
    exit();
}
?>