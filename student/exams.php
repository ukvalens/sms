<?php
// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();

// Get current student info
$db->query("SELECT s.*, c.name as class_name, sec.name as section_name FROM students s LEFT JOIN classes c ON s.class_id = c.id LEFT JOIN sections sec ON s.section_id = sec.id WHERE s.user_id = :user_id");
$db->bind(':user_id', $_SESSION['user_id']);
$student = $db->single();

if(!$student) {
    // Try to find user without student record
    $db->query("SELECT username FROM users WHERE id = :user_id AND role = 'student'");
    $db->bind(':user_id', $_SESSION['user_id']);
    $user = $db->single();
    
    if($user) {
        $content = '
        <div class="page-header">
            <h2>My Exams & Assignments</h2>
        </div>
        <div class="info-card">
            <h3>Profile Setup Required</h3>
            <p>Your student profile is not complete. Please contact the administrator to set up your class and section information.</p>
        </div>';
        echo renderStudentLayout('My Exams', $content);
        exit;
    } else {
        die('Student record not found');
    }
}

// Get scheduled exams for student's class
if($student['class_id']) {
    $db->query("SELECT e.*, s.name as subject_name, c.name as class_name 
               FROM exams e 
               JOIN subjects s ON e.subject_id = s.id 
               JOIN classes c ON e.class_id = c.id 
               WHERE e.class_id = :class_id 
               ORDER BY e.exam_date DESC");
    $db->bind(':class_id', $student['class_id']);
    $exams = $db->resultset();
    
    if(!$exams) {
        $exams = [];
    }
} else {
    $exams = [];
}

$content = '
<div class="page-header">
    <h2>My Exams & Assignments</h2>
    <button class="btn-small" onclick="location.reload()">Refresh</button>
</div>

<div class="info-card">
    <h3>Student Information</h3>
    <p><strong>Name:</strong> ' . $_SESSION['username'] . '</p>
    <p><strong>Class:</strong> ' . ($student['class_name'] ? $student['class_name'] . ' - ' . $student['section_name'] : 'Not assigned') . '</p>
    <p><strong>Roll Number:</strong> ' . ($student['roll_number'] ?? 'N/A') . '</p>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Exam Name</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Max Marks</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';

if(empty($exams)) {
    $content .= '<tr><td colspan="5" style="text-align: center; color: #6C757D;">No exams scheduled</td></tr>';
} else {
    foreach($exams as $exam) {
        $examDate = date('Y-m-d H:i', strtotime($exam['exam_date']));
        $currentDate = date('Y-m-d H:i');
        
        // Check if exam was recently updated (within last 24 hours)
        $updatedRecently = false;
        if(isset($exam['updated_at'])) {
            $updatedTime = strtotime($exam['updated_at']);
            $updatedRecently = (time() - $updatedTime) < 86400; // 24 hours
        }
        
        if($examDate > $currentDate) {
            $status = '<span style="color: #0077B6; font-weight: bold;">Upcoming</span>';
            if($updatedRecently) {
                $status .= ' <span style="color: #00BFA6; font-size: 0.8em;">(Updated)</span>';
            }
        } else {
            $status = '<span style="color: #6C757D;">Completed</span>';
        }
        
        $content .= '
            <tr' . ($updatedRecently ? ' style="background-color: #F8FFFE;"' : '') . '>
                <td>' . $exam['name'] . '</td>
                <td>' . $exam['subject_name'] . '</td>
                <td>' . date('M d, Y H:i', strtotime($exam['exam_date'])) . '</td>
                <td>' . $exam['max_marks'] . '</td>
                <td>' . $status . '</td>
            </tr>';
    }
}

$content .= '
        </tbody>
    </table>
</div>

<div class="info-card">
    <h3>Exam Guidelines</h3>
    <ul>
        <li>Be present 15 minutes before the exam starts</li>
        <li>Bring necessary stationery and ID card</li>
        <li>Mobile phones are not allowed in the exam hall</li>
        <li>Follow all exam rules and regulations</li>
        <li>Contact your class teacher for any queries</li>
        <li><strong>Check regularly for exam schedule updates - rescheduled exams are highlighted</strong></li>
    </ul>
    <p style="margin-top: 15px; color: #6C757D; font-size: 0.9em;">Last updated: ' . date('M d, Y H:i') . ' | <a href="javascript:location.reload()" style="color: #00BFA6;">Refresh page</a></p>
</div>';

echo renderStudentLayout('My Exams', $content);
?>