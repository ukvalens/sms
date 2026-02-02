<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$message = '';
$error = '';
$classId = $_GET['class_id'] ?? 0;
$sectionId = $_GET['section_id'] ?? 0;
$teacherId = $_SESSION['user_id'];

// Handle attendance submission
if($_POST && isset($_POST['attendance'])) {
    $date = $_POST['date'];
    $success = true;
    
    foreach($_POST['attendance'] as $studentId => $status) {
        // Check if attendance already exists for this date
        $db->query("SELECT id FROM attendance WHERE student_id = :student_id AND date = :date");
        $db->bind(':student_id', $studentId);
        $db->bind(':date', $date);
        $existing = $db->single();
        
        if($existing) {
            // Update existing record
            $db->query("UPDATE attendance SET status = :status, marked_by = :marked_by WHERE student_id = :student_id AND date = :date");
        } else {
            // Insert new record
            $db->query("INSERT INTO attendance (student_id, date, status, marked_by) VALUES (:student_id, :date, :status, :marked_by)");
        }
        
        $db->bind(':student_id', $studentId);
        $db->bind(':date', $date);
        $db->bind(':status', $status);
        $db->bind(':marked_by', $teacherId);
        
        if(!$db->execute()) {
            $success = false;
        }
    }
    
    if($success) {
        $message = 'Attendance marked successfully for ' . date('M d, Y', strtotime($date)) . '!';
    } else {
        $error = 'Failed to mark attendance for some students.';
    }
}

// Get teacher's assigned classes
$db->query("SELECT DISTINCT c.id as class_id, c.name as class_name, s.id as section_id, s.name as section_name
           FROM teacher_subjects ts
           JOIN teachers t ON ts.teacher_id = t.id
           JOIN classes c ON ts.class_id = c.id
           JOIN sections s ON ts.section_id = s.id
           WHERE t.user_id = :teacher_id
           ORDER BY c.name, s.name");
$db->bind(':teacher_id', $teacherId);
$assignedClasses = $db->resultset();

// Get students for selected class/section
$students = [];
$classInfo = null;
if($classId && $sectionId) {
    // Get class info
    $db->query("SELECT c.name as class_name, s.name as section_name 
               FROM classes c 
               JOIN sections s ON s.class_id = c.id 
               WHERE c.id = :class_id AND s.id = :section_id");
    $db->bind(':class_id', $classId);
    $db->bind(':section_id', $sectionId);
    $classInfo = $db->single();
    
    // Get students
    $db->query("SELECT s.id, s.roll_number, u.username 
               FROM students s 
               JOIN users u ON s.user_id = u.id 
               WHERE s.class_id = :class_id AND s.section_id = :section_id 
               ORDER BY s.roll_number");
    $db->bind(':class_id', $classId);
    $db->bind(':section_id', $sectionId);
    $students = $db->resultset();
    
    // Debug: If no students found, try without section filter
    if(empty($students)) {
        $db->query("SELECT s.id, s.roll_number, u.username 
                   FROM students s 
                   JOIN users u ON s.user_id = u.id 
                   WHERE s.class_id = :class_id 
                   ORDER BY s.roll_number");
        $db->bind(':class_id', $classId);
        $students = $db->resultset();
    }
    
    // Get today's attendance if exists
    $todayAttendance = [];
    $db->query("SELECT student_id, status FROM attendance WHERE date = :date AND student_id IN (SELECT id FROM students WHERE class_id = :class_id AND section_id = :section_id)");
    $db->bind(':date', date('Y-m-d'));
    $db->bind(':class_id', $classId);
    $db->bind(':section_id', $sectionId);
    $attendanceRecords = $db->resultset();
    
    foreach($attendanceRecords as $record) {
        $todayAttendance[$record['student_id']] = $record['status'];
    }
}

$content = '
<div class="page-header">
    <h2>Mark Attendance</h2>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '
' . ($error ? '<div class="alert alert-error">' . $error . '</div>' : '') . '

<div class="info-card">
    <h3>Select Class & Section</h3>
    <div class="form-row">
        <select onchange="selectClass()" id="classSelect" class="form-control">
            <option value="">Select Class & Section</option>';

foreach($assignedClasses as $class) {
    $selected = ($classId == $class['class_id'] && $sectionId == $class['section_id']) ? 'selected' : '';
    $content .= '<option value="' . $class['class_id'] . ',' . $class['section_id'] . '" ' . $selected . '>' . $class['class_name'] . ' - ' . $class['section_name'] . '</option>';
}

$content .= '
        </select>
    </div>
</div>';

if($classId && $sectionId && !empty($students)) {
    $content .= '
    <div class="info-card">
        <h3>Attendance for ' . ($classInfo['class_name'] ?? '') . ' - ' . ($classInfo['section_name'] ?? '') . '</h3>
        
        <form method="POST">
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="date" value="' . date('Y-m-d') . '" required class="form-control" style="max-width: 200px;">
            </div>
            
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Roll No.</th>
                            <th>Student Name</th>
                            <th>Present</th>
                            <th>Absent</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach($students as $student) {
        $currentStatus = $todayAttendance[$student['id']] ?? 'present';
        $presentChecked = ($currentStatus === 'present') ? 'checked' : '';
        $absentChecked = ($currentStatus === 'absent') ? 'checked' : '';
        
        $content .= '
                        <tr>
                            <td>' . ($student['roll_number'] ?? 'N/A') . '</td>
                            <td>' . $student['username'] . '</td>
                            <td><input type="radio" name="attendance[' . $student['id'] . ']" value="present" ' . $presentChecked . '></td>
                            <td><input type="radio" name="attendance[' . $student['id'] . ']" value="absent" ' . $absentChecked . '></td>
                        </tr>';
    }
    
    $content .= '
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn">Mark Attendance</button>
                <button type="button" class="btn" onclick="markAllPresent()">Mark All Present</button>
                <button type="button" class="btn" onclick="markAllAbsent()">Mark All Absent</button>
            </div>
        </form>
    </div>';
} elseif($classId && $sectionId) {
    $content .= '
    <div class="alert alert-error">No students found in the selected class and section.</div>';
}

$content .= '
<script>
function selectClass() {
    const select = document.getElementById("classSelect");
    const value = select.value;
    if(value) {
        const [classId, sectionId] = value.split(",");
        window.location.href = "attendance.php?class_id=" + classId + "&section_id=" + sectionId;
    }
}

function markAllPresent() {
    const radios = document.querySelectorAll("input[type=radio][value=present]");
    radios.forEach(radio => radio.checked = true);
}

function markAllAbsent() {
    const radios = document.querySelectorAll("input[type=radio][value=absent]");
    radios.forEach(radio => radio.checked = true);
}
</script>';

echo renderTeacherLayout('Mark Attendance', $content);
?>