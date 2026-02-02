<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$teacherId = $_GET['id'] ?? 0;

// Get teacher details
$db->query("SELECT t.*, u.username, u.email FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = :id");
$db->bind(':id', $teacherId);
$teacher = $db->single();

if(!$teacher) {
    header('Location: teachers.php');
    exit;
}

// Get assigned subjects
$db->query("SELECT DISTINCT s.name as subject_name, c.name as class_name, sec.name as section_name FROM teacher_subjects ts JOIN subjects s ON ts.subject_id = s.id JOIN classes c ON ts.class_id = c.id JOIN sections sec ON ts.section_id = sec.id WHERE ts.teacher_id = :teacher_id");
$db->bind(':teacher_id', $teacherId);
$assignments = $db->resultset();

$content = '
<div class="page-header">
    <h2>Teacher Details</h2>
    <a href="teachers.php" class="btn">Back to Teachers</a>
</div>

<div class="teacher-profile">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                ' . strtoupper(substr($teacher['username'], 0, 1)) . '
            </div>
            <div class="profile-info">
                <h3>' . $teacher['username'] . '</h3>
                <p class="employee-id">Employee ID: ' . $teacher['employee_id'] . '</p>
                <p class="email">' . $teacher['email'] . '</p>
            </div>
        </div>
        
        <div class="profile-details">
            <div class="detail-row">
                <span class="label">Qualification:</span>
                <span class="value">' . ($teacher['qualification'] ?? 'N/A') . '</span>
            </div>
            <div class="detail-row">
                <span class="label">Specialization:</span>
                <span class="value">' . ($teacher['specialization'] ?? 'N/A') . '</span>
            </div>
            <div class="detail-row">
                <span class="label">Joining Date:</span>
                <span class="value">' . date('M d, Y', strtotime($teacher['joining_date'])) . '</span>
            </div>
            <div class="detail-row">
                <span class="label">Phone:</span>
                <span class="value">' . ($teacher['phone'] ?? 'N/A') . '</span>
            </div>
            <div class="detail-row">
                <span class="label">Address:</span>
                <span class="value">' . ($teacher['address'] ?? 'N/A') . '</span>
            </div>
        </div>
    </div>
    
    <div class="assignments-card">
        <h3>Class Assignments</h3>
        <div class="assignments-list">';

if(empty($assignments)) {
    $content .= '<p class="no-assignments">No class assignments yet.</p>';
} else {
    foreach($assignments as $assignment) {
        $content .= '
            <div class="assignment-item">
                <span class="subject">' . $assignment['subject_name'] . '</span>
                <span class="class-section">' . $assignment['class_name'] . ' - ' . $assignment['section_name'] . '</span>
            </div>';
    }
}

$content .= '
        </div>
    </div>
</div>';

echo renderAdminLayout('Teacher Profile', $content);
?>