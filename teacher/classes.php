<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();

// Get current teacher ID
$db->query("SELECT id FROM teachers WHERE user_id = :user_id");
$db->bind(':user_id', $_SESSION['user_id']);
$teacher = $db->single();

if(!$teacher) {
    $content = '<div class="alert alert-error">Teacher profile not found. Please contact admin.</div>';
    echo renderTeacherLayout('My Classes', $content);
    exit;
}

$teacherId = $teacher['id'];

// Get teacher's assigned classes
$db->query("
    SELECT DISTINCT c.id, c.name as class_name, sec.id as section_id, sec.name as section_name,
           s.name as subject_name, COUNT(st.id) as student_count
    FROM teacher_subjects ts
    JOIN classes c ON ts.class_id = c.id
    JOIN sections sec ON ts.section_id = sec.id
    JOIN subjects s ON ts.subject_id = s.id
    LEFT JOIN students st ON st.class_id = c.id AND st.section_id = sec.id
    WHERE ts.teacher_id = :teacher_id
    GROUP BY c.id, sec.id, s.id
    ORDER BY c.name, sec.name
");
$db->bind(':teacher_id', $teacherId);
$classes = $db->resultset();

$content = '
<div class="page-header">
    <h2>My Classes</h2>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number">' . count($classes) . '</div>
        <div class="stat-label">Assigned Classes</div>
    </div>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Class</th>
                <th>Section</th>
                <th>Subject</th>
                <th>Students</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';

if(empty($classes)) {
    $content .= '<tr><td colspan="5" style="text-align: center; color: #6C757D;">No classes assigned yet. Contact admin for class assignments.</td></tr>';
} else {
    foreach($classes as $class) {
        $content .= '
            <tr>
                <td>' . $class['class_name'] . '</td>
                <td>' . $class['section_name'] . '</td>
                <td>' . $class['subject_name'] . '</td>
                <td>' . $class['student_count'] . '</td>
                <td>
                    <button class="btn-small btn-view" onclick="viewStudents(' . $class['id'] . ', ' . $class['section_id'] . ')">View Students</button>
                    <button class="btn-small btn-edit" onclick="markAttendance(' . $class['id'] . ', ' . $class['section_id'] . ')">Attendance</button>
                </td>
            </tr>';
    }
}

$content .= '
        </tbody>
    </table>
</div>

<script>
function viewStudents(classId, sectionId) {
    window.location.href = "students.php?class_id=" + classId + "&section_id=" + sectionId;
}

function markAttendance(classId, sectionId) {
    window.location.href = "attendance.php?class_id=" + classId + "&section_id=" + sectionId;
}
</script>';

echo renderTeacherLayout('My Classes', $content);
?>