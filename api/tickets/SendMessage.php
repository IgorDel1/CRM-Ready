<?php
require_once '../DB.php';
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['token'])) {
        throw new Exception('Пользователь не авторизован');
    }

    if (!isset($_POST['ticket_id']) || !isset($_POST['message'])) {
        throw new Exception('Отсутствуют необходимые данные');
    }

    // Получаем информацию о пользователе
    $stmt = $DB->prepare("SELECT id, type FROM users WHERE token = ?");
    $stmt->execute([$_SESSION['token']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Пользователь не найден');
    }

    $ticketId = $_POST['ticket_id'];
    $message = $_POST['message'];
    $userId = $user['id'];

    // Добавляем сообщение
    $stmt = $DB->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$ticketId, $userId, $message]);

    echo json_encode([
        'success' => true,
        'message' => 'Сообщение успешно отправлено',
        'sender_type' => $user['type']
    ]);

} catch (Exception $e) {
    error_log('Chat error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>