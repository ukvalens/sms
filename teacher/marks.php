<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$message = '';
$examId = $_GET['exam_id'] ?? 0;

// Handle marks submission
if($_POST && isset($_POST['marks'])) {
    foreach($_POST['marks'] as $studentId => $marks) {
        if($marks !== '') {
            $status = $marks >= $_POST['pass_marks'] ? 'pass' : 'fail';
            
            $db->query("INSERT INTO exam_results (exam_id, student_id, marks_obtained, status, submitted_by) VALUES (:exam_id, :student_id, :marks_obtained, :status, :submitted_by) ON DUPLICATE KEY UPDATE marks_obtained = :marks_obtained, status = :status");
            $db->bind(':exam_id', $examId);
            $db->bind(':student_id', $studentId);
            $db->bind(':marks_obtained', $marks);
            $db->bind(':status', $status);
            $db->bind(':submitted_by', $_SESSION['user_id']);
            $db->execute();
        }
    }
    $message = 'Marks entered successfully!';
}

// Get exam details
$db->query("SELECT e.*, c.name as class_name, s.name as subject_name FROM exams e JOIN classes c ON e.class_id = c.id JOIN subjects s ON e.subject_id = s.id WHERE e.id = :exam_id");
$db->bind(':exam_id', $examId);
$exam = $db->single();

if(!$exam) {
    header('Location: exams.php');
    exit;
}

// Get students for this exam
$db->query("SELECT st.id, st.roll_number, u.username, er.marks_obtained FROM students st JOIN users u ON st.user_id = u.id LEFT JOIN exam_results er ON st.id = er.student_id AND er.exam_id = :exam_id WHERE st.class_id = :class_id ORDER BY st.roll_number");
$db->bind(':exam_id', $examId);
$db->bind(':class_id', $exam['class_id']);
$students = $db->resultset();

$content = '
<div class="page-header">
    <h2>Enter Marks - ' . $exam['name'] . '</h2>
    <a href="exams.php" class="btn">Back to Exams</a>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '

<div class="exam-info">
    <div class="info-card">
        <h3>Exam Details</h3>
        <p><strong>Subject:</strong> ' . $exam['subject_name'] . '</p>
        <p><strong>Class:</strong> ' . $exam['class_name'] . '</p>
        <p><strong>Date:</strong> ' . $exam['exam_date'] . '</p>
        <p><strong>Max Marks:</strong> ' . $exam['max_marks'] . '</p>
        <p><strong>Pass Marks:</strong> ' . $exam['pass_marks'] . '</p>
    </div>
</div>

<form method="POST">
    <input type="hidden" name="pass_marks" value="' . $exam['pass_marks'] . '">
    
    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>Roll Number</th>
                    <th>Student Name</th>
                    <th>Marks (out of ' . $exam['max_marks'] . ')</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

foreach($students as $student) {
    $marks = $student['marks_obtained'] ?? '';
    $status = '';
    if($marks !== '') {
        $status = $marks >= $exam['pass_marks'] ? '<span style="color: #2A9D8F;">Pass</span>' : '<span style="color: #E63946;">Fail</span>';
    }
    
    $content .= '
                <tr>
                    <td>' . $student['roll_number'] . '</td>
                    <td>' . $student['username'] . '</td>
                    <td>
                        <input type="number" name="marks[' . $student['id'] . ']" value="' . $marks . '" 
                               min="0" max="' . $exam['max_marks'] . '" class="form-control" style="width: 100px;">
                    </td>
                    <td>' . $status . '</td>
                </tr>';
}

$content .= '
            </tbody>
        </table>
    </div>
    
    <button type="submit" class="btn">Save Marks</button>
</form>';

echo renderTeacherLayout('Enter Marks', $content);
?>