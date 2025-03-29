<?php
session_start();
require_once '../DB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $body = $_POST['body'] ?? '';
    $code_promo = $_POST['code_promo'] ?? '';
    $discount = (int)($_POST['discount'] ?? 0);
    $max_uses = (int)($_POST['max_uses'] ?? 0);
    $cancel_at = $_POST['cancel_at'] ?? '';
    
    $path_to_image = '';
    
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
            $path_to_image = 'uploads/promotions/' . $file_name;
        }
    }
    
    try {
        $stmt = $DB->prepare("
            INSERT INTO promotions (title, body, code_promo, discount, max_uses, path_to_image, cancel_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $title,
            $body,
            $code_promo,
            $discount,
            $max_uses,
            $path_to_image,
            $cancel_at
        ]);
        
        header('Location: ../../promotions.php?success=1');
        exit;
    } catch (PDOException $e) {
        $_SESSION['promotion_error'] = 'Ошибка при создании акции: ' . $e->getMessage();
        header('Location: ../../promotions.php?error=1');
        exit;
    }
} else {
    header('Location: ../../promotions.php');
    exit;
} 