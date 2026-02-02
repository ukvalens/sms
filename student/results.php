<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();

// Get current student info
$db->query("SELECT id FROM students WHERE user_id = :user_id");
$db->bind(':user_id', $_SESSION['user_id']);
$student = $db->single();

// Get exam results
$db->query("SELECT er.*, e.name as exam_name, e.max_marks, e.pass_marks, e.exam_date, s.name as subject_name FROM exam_results er JOIN exams e ON er.exam_id = e.id JOIN subjects s ON e.subject_id = s.id WHERE er.student_id = :student_id ORDER BY e.exam_date DESC");
$db->bind(':student_id', $student['id']);
$results = $db->resultset();

$content = '
<div class="page-header">
    <h2>Exam Results</h2>
</div>

<div class="results-summary">
    <div class="stat-card">
        <div class="stat-number">' . count($results) . '</div>
        <div class="stat-label">Total Exams</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">' . count(array_filter($results, fn($r) => $r['status'] == 'pass')) . '</div>
        <div class="stat-label">Passed</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">' . count(array_filter($results, fn($r) => $r['status'] == 'fail')) . '</div>
        <div class="stat-label">Failed</div>
    </div>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Exam</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Marks</th>
                <th>Max Marks</th>
                <th>Percentage</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';

foreach($results as $result) {
    $percentage = round(($result['marks_obtained'] / $result['max_marks']) * 100, 2);
    $statusColor = $result['status'] == 'pass' ? '#2A9D8F' : '#E63946';
    
    $content .= '
            <tr>
                <td>' . $result['exam_name'] . '</td>
                <td>' . $result['subject_name'] . '</td>
                <td>' . date('M d, Y', strtotime($result['exam_date'])) . '</td>
                <td>' . $result['marks_obtained'] . '</td>
                <td>' . $result['max_marks'] . '</td>
                <td>' . $percentage . '%</td>
                <td><span style="color: ' . $statusColor . '; font-weight: bold;">' . ucfirst($result['status']) . '</span></td>
            </tr>';
}

$content .= '
        </tbody>
    </table>
</div>';

echo renderStudentLayout('Exam Results', $content);
?>