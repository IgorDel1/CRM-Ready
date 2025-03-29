<?php
require_once '../DB.php';

header('Content-Type: application/json');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $DB->prepare("SELECT * FROM promotions WHERE id = ?");
        $stmt->execute([$id]);
        
        $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($promotion) {
            echo json_encode($promotion);
        } else {
            echo json_encode(['error' => 'Акция не найдена']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Ошибка при получении данных: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Не указан ID акции']);
} 