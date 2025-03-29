<?php
require_once '../DB.php';
session_start();

$ticketId = $_GET['ticket_id'];
$query = "SELECT tm.*, u.name as sender_name, u.type as sender_type 
          FROM ticket_messages tm
          LEFT JOIN users u ON tm.user_id = u.id
          WHERE tm.ticket_id = ?
          ORDER BY tm.created_at ASC";

$stmt = $DB->prepare($query);
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($messages);
?>