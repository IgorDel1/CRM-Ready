<?php
session_start();
require_once '../DB.php';
require_once '../auth/AuthCheck.php';

// Проверяем авторизацию
AuthCheck('../../clients.php', '../../login.php');

// Проверяем, что получены все необходимые данные
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Базовая валидация
    if (empty($fullname) || empty($email) || empty($phone)) {
        header("Location: ../../clients.php?error=empty_fields");
        exit;
    }

    // Валидация email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../../clients.php?error=invalid_email");
        exit;
    }

    // Валидация телефона (простая проверка на наличие только цифр и некоторых спецсимволов)
    if (!preg_match("/^[0-9+\-\(\)\s]+$/", $phone)) {
        header("Location: ../../clients.php?error=invalid_phone");
        exit;
    }

    try {
        // Проверяем существование клиента
        $checkStmt = $DB->prepare("SELECT id FROM clients WHERE id = ?");
        $checkStmt->execute([$id]);
        
        if (!$checkStmt->fetch()) {
            header("Location: ../../clients.php?error=client_not_found");
            exit;
        }

        // Проверяем уникальность email (исключая текущего клиента)
        $emailCheckStmt = $DB->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
        $emailCheckStmt->execute([$email, $id]);
        
        if ($emailCheckStmt->fetch()) {
            header("Location: ../../clients.php?error=email_exists");
            exit;
        }

        // Обновляем данные клиента
        $stmt = $DB->prepare("UPDATE clients SET fullname = ?, email = ?, phone = ? WHERE id = ?");
        $result = $stmt->execute([$fullname, $email, $phone, $id]);

        if ($result) {
            header("Location: ../../clients.php?success=edit");
            exit;
        } else {
            header("Location: ../../clients.php?error=update_failed");
            exit;
        }
    } catch (PDOException $e) {
        // Логируем ошибку (в реальном проекте)
        // error_log($e->getMessage());
        header("Location: ../../clients.php?error=system");
        exit;
    }
} else {
    header("Location: ../../clients.php?error=invalid_request");
    exit;
} 