<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if(!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Message ID required']);
    exit;
}

$db = new Database();

// Get message details
$db->query("SELECT m.*, u.username as sender_name 
           FROM messages m 
           JOIN users u ON m.sender_id = u.id 
           WHERE m.id = :id AND m.receiver_id = :user_id");
$db->bind(':id', $_GET['id']);
$db->bind(':user_id', $_SESSION['user_id']);
$message = $db->single();

if(!$message) {
    echo json_encode(['success' => false, 'error' => 'Message not found']);
    exit;
}

// Mark as read
$db->query("UPDATE messages SET is_read = 1 WHERE id = :id");
$db->bind(':id', $_GET['id']);
$db->execute();

// Format date
$message['sent_at'] = date('M d, Y H:i', strtotime($message['sent_at']));

echo json_encode([
    'success' => true,
    'message' => $message
]);
?>