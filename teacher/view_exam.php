<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    if(!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'error' => 'Exam ID required']);
        exit;
    }

    $db = new Database();

    // Get exam details
    $db->query("SELECT e.*, c.name as class_name, s.name as subject_name 
               FROM exams e 
               JOIN classes c ON e.class_id = c.id 
               JOIN subjects s ON e.subject_id = s.id 
               WHERE e.id = :id AND e.created_by = :user_id");
    $db->bind(':id', $_GET['id']);
    $db->bind(':user_id', $_SESSION['user_id']);
    $exam = $db->single();

    if(!$exam) {
        echo json_encode(['success' => false, 'error' => 'Exam not found']);
        exit;
    }

    // Get question count (handle if table doesn't exist)
    $questionCount = 0;
    try {
        $db->query("SELECT COUNT(*) as count FROM questions WHERE exam_id = :exam_id");
        $db->bind(':exam_id', $_GET['id']);
        $questionResult = $db->single();
        $questionCount = $questionResult ? $questionResult['count'] : 0;
    } catch (Exception $e) {
        // Questions table doesn't exist, set count to 0
        $questionCount = 0;
    }

    // Get student count for this class
    $db->query("SELECT COUNT(*) as count FROM students WHERE class_id = :class_id");
    $db->bind(':class_id', $exam['class_id']);
    $studentResult = $db->single();
    $studentCount = $studentResult ? $studentResult['count'] : 0;

    // Format date and check if expired
    $examDateTime = $exam['exam_date'];
    $exam['exam_date'] = date('M d, Y H:i', strtotime($examDateTime));
    $exam['is_expired'] = strtotime($examDateTime) < time();
    $exam['question_count'] = $questionCount;
    $exam['student_count'] = $studentCount;

    echo json_encode([
        'success' => true,
        'exam' => $exam
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>