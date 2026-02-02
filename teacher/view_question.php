<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Question ID required']);
    exit;
}

$db = new Database();
$db->query("SELECT * FROM exam_questions WHERE id = :id");
$db->bind(':id', $_GET['id']);
$question = $db->single();

if (!$question) {
    echo json_encode(['error' => 'Question not found']);
    exit;
}

echo json_encode($question);
?>