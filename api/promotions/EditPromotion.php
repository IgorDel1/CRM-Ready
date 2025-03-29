<?php
session_start();
require_once '../DB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = $_POST['title'] ?? '';
    $body = $_POST['body'] ?? '';
    $code_promo = $_POST['code_promo'] ?? '';
    $discount = (int)($_POST['discount'] ?? 0);
    $max_uses = (int)($_POST['max_uses'] ?? 0);
    $cancel_at = $_POST['cancel_at'] ?? '';
    $current_image = $_POST['current_image'] ?? '';
    
    // Проверяем, является ли дата корректной
    if (!strtotime($cancel_at) || strtotime($cancel_at) < 0) {
        $cancel_at = date('Y-m-d', strtotime('+30 days'));
    }
    
    $path_to_image = $current_image;
    
    // Обработка загруженного изображения
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/promotions/';
        
        // Создаем директорию, если не существует
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $upload_file = $upload_dir . $file_name;
        
        // Перемещаем файл в папку uploads
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
            // Удаляем старое изображение, если оно есть
            if (!empty($current_image) && file_exists('../../' . $current_image)) {
                unlink('../../' . $current_image);
            }
            
            $path_to_image = 'uploads/promotions/' . $file_name;
        }
    }
    
    try {
        $stmt = $DB->prepare("
            UPDATE promotions 
            SET title = ?, body = ?, code_promo = ?, discount = ?, 
                max_uses = ?, path_to_image = ?, cancel_at = ? 
            WHERE id = ?
        ");
        
        $stmt->execute([
            $title,
            $body,
            $code_promo,
            $discount,
            $max_uses,
            $path_to_image,
            $cancel_at,
            $id
        ]);
        
        header('Location: ../../promotions.php?success=2');
        exit;
    } catch (PDOException $e) {
        $_SESSION['promotion_error'] = 'Ошибка при обновлении акции: ' . $e->getMessage();
        header('Location: ../../promotions.php?error=2');
        exit;
    }
} else {
    header('Location: ../../promotions.php');
    exit;
} 