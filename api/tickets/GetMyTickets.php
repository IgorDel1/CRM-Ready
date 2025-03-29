<?php
session_start();
require_once '../DB.php';
require_once '../auth/AuthCheck.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['token'])) {
        throw new Exception('Пользователь не авторизован');
    }

    // Получаем информацию о пользователе
    $stmt = $DB->prepare("SELECT id, type FROM users WHERE token = ?");
    $stmt->execute([$_SESSION['token']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Пользователь не найден');
    }

    // В зависимости от типа пользователя формируем запрос
    if ($user['type'] === 'tech' || $user['type'] === 'admin') {
        // Для техподдержки и администраторов показываем все тикеты
        $query = "SELECT t.*, u.name as client_name 
                 FROM tickets t 
                 LEFT JOIN users u ON t.clients = u.id 
                 ORDER BY t.created_at DESC";
        $stmt = $DB->prepare($query);
        $stmt->execute();
    } else {
        // Для обычных пользователей показываем только их тикеты
        $query = "SELECT t.*, u.name as client_name 
                 FROM tickets t 
                 LEFT JOIN users u ON t.clients = u.id 
                 WHERE t.clients = ? 
                 ORDER BY t.created_at DESC";
        $stmt = $DB->prepare($query);
        $stmt->execute([$user['id']]);
    }

    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($tickets);

} catch (Exception $e) {
    error_log('GetMyTickets error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 