<?php
require_once '../DB.php';

try {
    // Находим акции с некорректными датами
    $stmt = $DB->query("SELECT id, cancel_at FROM promotions WHERE cancel_at LIKE '%.-0001%' OR cancel_at LIKE '%-0001%'");
    $badDatePromotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($badDatePromotions)) {
        // Устанавливаем новую дату (текущая + 30 дней)
        $newDate = date('Y-m-d', strtotime('+30 days'));
        
        foreach ($badDatePromotions as $promo) {
            $DB->prepare("UPDATE promotions SET cancel_at = ? WHERE id = ?")
                ->execute([$newDate, $promo['id']]);
            echo "Исправлена дата для акции ID {$promo['id']}: {$promo['cancel_at']} -> {$newDate}<br>";
        }
        
        echo "<hr>Все некорректные даты были исправлены.";
    } else {
        echo "Некорректных дат не найдено.";
    }
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
} 