<?php
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
            <h2>My Subjects</h2>
        </div>
        <div class="info-card">
            <h3>Profile Setup Required</h3>
            <p>Your student profile is not complete. Please contact the administrator to set up your class and section information.</p>
        </div>';
        echo renderStudentLayout('My Subjects', $content);
        exit;
    } else {
        die('Student record not found');
    }
}

// Get subjects for student's class
if($student['class_id']) {
    $db->query("SELECT DISTINCT s.id, s.name, s.code FROM subjects s JOIN teacher_subjects ts ON s.id = ts.subject_id WHERE ts.class_id = :class_id ORDER BY s.name");
    $db->bind(':class_id', $student['class_id']);
    $subjects = $db->resultset();
    
    if(!$subjects) {
        $subjects = [];
    }
} else {
    $subjects = [];
}

$content = '
<div class="page-header">
    <h2>My Subjects</h2>
</div>

<div class="student-info">
    <div class="info-card">
        <h3>Student Information</h3>
        <p><strong>Roll Number:</strong> ' . ($student['roll_number'] ?? 'N/A') . '</p>
        <p><strong>Class:</strong> ' . ($student['class_name'] ? $student['class_name'] . ' - ' . $student['section_name'] : 'Not assigned') . '</p>
        <p><strong>Total Subjects:</strong> ' . count($subjects) . '</p>
    </div>
</div>

<div class="subjects-grid">';

foreach($subjects as $subject) {
    $content .= '
    <div class="subject-card">
        <h4>' . $subject['name'] . '</h4>
        <p class="subject-code">' . $subject['code'] . '</p>
        <div class="subject-actions">
            <button class="btn-small btn-view" onclick="viewMaterials(' . $subject['id'] . ')">Study Materials</button>
            <button class="btn-small btn-edit" onclick="viewResults(' . $subject['id'] . ')">Results</button>
        </div>
    </div>';
}

$content .= '
</div>

<script>
function viewMaterials(subjectId) {
    window.location.href = "materials.php?subject_id=" + subjectId;
}

function viewResults(subjectId) {
    window.location.href = "results.php?subject_id=" + subjectId;
}
</script>';

echo renderStudentLayout('My Subjects', $content);
?>