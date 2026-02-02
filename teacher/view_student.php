<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Student ID required']);
    exit;
}

$db = new Database();
$db->query("SELECT s.*, u.username, u.email, c.name as class_name, sec.name as section_name 
           FROM students s 
           JOIN users u ON s.user_id = u.id 
           LEFT JOIN classes c ON s.class_id = c.id 
           LEFT JOIN sections sec ON s.section_id = sec.id 
           WHERE s.id = :id");
$db->bind(':id', $_GET['id']);
$student = $db->single();

if (!$student) {
    echo json_encode(['error' => 'Student not found']);
    exit;
}

echo json_encode($student);
?>