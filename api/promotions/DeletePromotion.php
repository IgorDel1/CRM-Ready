<?php
session_start();
require_once '../DB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    try {
        // Сначала получаем информацию о пути к изображению
        $stmt = $DB->prepare("SELECT path_to_image FROM promotions WHERE id = ?");
        $stmt->execute([$id]);
        $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Удаляем изображение, если оно существует
        if ($promotion && !empty($promotion['path_to_image']) && file_exists('../../' . $promotion['path_to_image'])) {
            unlink('../../' . $promotion['path_to_image']);
        }
        
        // Удаляем запись из базы данных
        $stmt = $DB->prepare("DELETE FROM promotions WHERE id = ?");
        $stmt->execute([$id]);
        
        header('Location: ../../promotions.php?success=3');
        exit;
    } catch (PDOException $e) {
        $_SESSION['promotion_error'] = 'Ошибка при удалении акции: ' . $e->getMessage();
        header('Location: ../../promotions.php?error=3');
        exit;
    }
} else {
    header('Location: ../../promotions.php');
    exit;
} 