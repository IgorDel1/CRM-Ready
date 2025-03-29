<?php
session_start();

// Используем относительные пути от текущей директории
require_once '../DB.php';
require_once '../auth/AuthCheck.php';

// Проверяем авторизацию
AuthCheck('../../clients.php', '../../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

    if ($id > 0 && !empty($name) && !empty($email) && !empty($phone)) {
        try {
            $stmt = $DB->prepare("UPDATE clients SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $id]);
            
            header('Location: ../../clients.php?success=updated');
            exit;
        } catch (PDOException $e) {
            header('Location: ../../clients.php?error=update_failed');
            exit;
        }
    } else {
        header('Location: ../../clients.php?error=invalid_data');
        exit;
    }
}

// Если что-то пошло не так, возвращаемся на страницу клиентов
header('Location: ../../clients.php');
exit; 