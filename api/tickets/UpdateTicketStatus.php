<?php
session_start();
require_once '../DB.php';
require_once '../auth/AuthCheck.php';
require_once '../helpers/getUserType.php';

header('Content-Type: application/json');

// Проверяем права доступа
AuthCheck('', '../../login.php');
$userType = getUserType($DB);
if ($userType !== 'tech') {
    exit(json_encode(['success' => false, 'message' => 'Недостаточно прав']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = filter_input(INPUT_POST, 'ticketId', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    
    $allowedStatuses = ['waiting', 'work', 'complete'];
    
    if (!$ticketId || !in_array($status, $allowedStatuses)) {
        exit(json_encode(['success' => false, 'message' => 'Неверные параметры']));
    }

    try {
        $stmt = $DB->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $ticketId]);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
} 