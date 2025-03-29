<?php
session_start();
require_once '../DB.php';
require_once '../auth/AuthCheck.php';

AuthCheck('../../clients.php', '../../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Получаем ID пользователя из базы данных по токену
    $token = $_SESSION['token'] ?? null;
    
    if (!$token) {
        header('Location: ../../clients.php?ticket_status=error');
        exit;
    }
    
    try {
        // Получаем ID пользователя по токену
        $stmt = $DB->prepare("SELECT id FROM users WHERE token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            header('Location: ../../clients.php?ticket_status=error');
            exit;
        }
        
        // Создаем тикет с ID пользователя как клиента И как администратора
        $stmt = $DB->prepare("INSERT INTO tickets (type, message, status, clients, admin, created_at, file_path) 
                             VALUES (?, ?, 'waiting', ?, ?, NOW(), ?)");
        
        $filePathForDB = null;
        
        // Проверяем, был ли загружен файл
        if (isset($_FILES['ticket_file']) && $_FILES['ticket_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/tickets/';
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileInfo = pathinfo($_FILES['ticket_file']['name']);
            $extension = strtolower($fileInfo['extension']);
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'mp3', 'mp4', 'doc', 'docx', 'txt'];
            
            if (!in_array($extension, $allowedExtensions)) {
                header('Location: ../../clients.php?ticket_status=invalid_file');
                exit;
            }
            
            $maxFileSize = 50 * 1024 * 1024; // 50MB
            if ($_FILES['ticket_file']['size'] > $maxFileSize) {
                header('Location: ../../clients.php?ticket_status=file_too_large');
                exit;
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['ticket_file']['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['ticket_file']['tmp_name'], $filePath)) {
                $filePathForDB = 'uploads/tickets/' . $fileName;
            } else {
                header('Location: ../../clients.php?ticket_status=upload_error');
                exit;
            }
        }
        
        // Теперь передаем ID пользователя дважды - как clients и как admin
        $stmt->execute([$type, $message, $user['id'], $user['id'], $filePathForDB]);
        
        header('Location: ../../clients.php?ticket_status=success');
        exit;
    } catch (PDOException $e) {
        error_log('Ticket creation error: ' . $e->getMessage());
        header('Location: ../../clients.php?ticket_status=error');
        exit;
    }
} else {
    header('Location: ../../clients.php');
    exit;
} 