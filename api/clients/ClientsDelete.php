<?php
//подключение к базе данных
require_once '../DB.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    // Удаление клиента из базы данных
    $sql = "DELETE FROM clients WHERE id = ?";
    $stmt = $DB->prepare($sql);
    $stmt->execute([$id]);
    
    // Перенаправление на страницу clients
    header('Location: ../../clients.php');
    exit();
} else {
    header('Location: ../../clients.php');
    exit();
}
?>