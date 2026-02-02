<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();

// Handle report generation
if(isset($_GET['generate'])) {
    $reportType = $_GET['generate'];
    
    switch($reportType) {
        case 'student-list':
            generateStudentListReport($db);
            break;
        case 'class-wise':
            generateClassWiseReport($db);
            break;
        case 'attendance':
            generateAttendanceReport($db);
            break;
        case 'fee-collection':
            generateFeeCollectionReport($db);
            break;
        case 'system-stats':
            generateSystemStatsReport($db);
            break;
    }
    exit;
}

function generateStudentListReport($db) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="student_list_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Roll Number', 'Name', 'Email', 'Class', 'Section', 'Admission Date', 'Phone']);
    
    $db->query("
        SELECT s.roll_number, u.username, u.email, c.name as class_name, sec.name as section_name, s.admission_date, s.phone
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        JOIN classes c ON s.class_id = c.id 
        JOIN sections sec ON s.section_id = sec.id 
        ORDER BY s.roll_number
    ");
    $students = $db->resultset();
    
    foreach($students as $student) {
        fputcsv($output, $student);
    }
    fclose($output);
}

function generateClassWiseReport($db) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="class_wise_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Class', 'Section', 'Total Students', 'Male', 'Female']);
    
    $db->query("
        SELECT c.name as class_name, sec.name as section_name, 
               COUNT(s.id) as total,
               SUM(CASE WHEN s.gender = 'male' THEN 1 ELSE 0 END) as male_count,
               SUM(CASE WHEN s.gender = 'female' THEN 1 ELSE 0 END) as female_count
        FROM classes c 
        JOIN sections sec ON c.id = sec.class_id
        LEFT JOIN students s ON sec.id = s.section_id
        GROUP BY c.id, sec.id
        ORDER BY c.name, sec.name
    ");
    $data = $db->resultset();
    
    foreach($data as $row) {
        fputcsv($output, [$row['class_name'], $row['section_name'], $row['total'], $row['male_count'], $row['female_count']]);
    }
    fclose($output);
}

function generateAttendanceReport($db) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Roll Number', 'Class', 'Total Days', 'Present Days', 'Attendance %']);
    
    $db->query("
        SELECT u.username, s.roll_number, c.name as class_name,
               COUNT(a.id) as total_days,
               SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
               ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2) as attendance_percentage
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN classes c ON s.class_id = c.id
        LEFT JOIN attendance a ON s.id = a.student_id
        GROUP BY s.id
        ORDER BY s.roll_number
    ");
    $data = $db->resultset();
    
    foreach($data as $row) {
        fputcsv($output, [$row['username'], $row['roll_number'], $row['class_name'], $row['total_days'], $row['present_days'], $row['attendance_percentage'] . '%']);
    }
    fclose($output);
}

function generateFeeCollectionReport($db) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="fee_collection_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Fee Term', 'Class', 'Total Amount', 'Collected Amount', 'Outstanding']);
    
    $db->query("
        SELECT ft.name as fee_name, c.name as class_name, ft.amount as total_amount,
               COALESCE(SUM(fp.amount_paid), 0) as collected_amount,
               (ft.amount - COALESCE(SUM(fp.amount_paid), 0)) as outstanding
        FROM fee_terms ft
        JOIN classes c ON ft.class_id = c.id
        LEFT JOIN fee_payments fp ON ft.id = fp.fee_term_id
        GROUP BY ft.id
        ORDER BY ft.due_date DESC
    ");
    $data = $db->resultset();
    
    foreach($data as $row) {
        fputcsv($output, [$row['fee_name'], $row['class_name'], $row['total_amount'], $row['collected_amount'], $row['outstanding']]);
    }
    fclose($output);
}

function generateSystemStatsReport($db) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="system_stats_' . date('Y-m-d') . '.json"');
    
    $stats = [];
    
    // Get various counts
    $db->query("SELECT COUNT(*) as count FROM students");
    $stats['total_students'] = $db->single()['count'];
    
    $db->query("SELECT COUNT(*) as count FROM teachers");
    $stats['total_teachers'] = $db->single()['count'];
    
    $db->query("SELECT COUNT(*) as count FROM classes");
    $stats['total_classes'] = $db->single()['count'];
    
    $db->query("SELECT COUNT(*) as count FROM subjects");
    $stats['total_subjects'] = $db->single()['count'];
    
    $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'parent'");
    $stats['total_parents'] = $db->single()['count'];
    
    $stats['generated_on'] = date('Y-m-d H:i:s');
    
    echo json_encode($stats, JSON_PRETTY_PRINT);
}

$content = '
<div class="page-header">
    <h2>Reports & Analytics</h2>
</div>

<div class="reports-grid">
    <div class="report-card">
        <h3>Student Reports</h3>
        <ul>
            <li><a href="?generate=student-list">Complete Student List (CSV)</a></li>
            <li><a href="?generate=class-wise">Class-wise Student Report (CSV)</a></li>
            <li><a href="?generate=attendance">Attendance Report (CSV)</a></li>
        </ul>
    </div>
    
    <div class="report-card">
        <h3>Academic Reports</h3>
        <ul>
            <li><a href="#" onclick="generateReport(\'exam-results\')">Exam Results (Coming Soon)</a></li>
            <li><a href="#" onclick="generateReport(\'subject-wise\')">Subject-wise Performance (Coming Soon)</a></li>
            <li><a href="#" onclick="generateReport(\'teacher-load\')">Teacher Workload (Coming Soon)</a></li>
        </ul>
    </div>
    
    <div class="report-card">
        <h3>Financial Reports</h3>
        <ul>
            <li><a href="?generate=fee-collection">Fee Collection Report (CSV)</a></li>
            <li><a href="#" onclick="generateReport(\'outstanding\')">Outstanding Fees (Coming Soon)</a></li>
            <li><a href="#" onclick="generateReport(\'monthly-income\')">Monthly Income (Coming Soon)</a></li>
        </ul>
    </div>
    
    <div class="report-card">
        <h3>System Reports</h3>
        <ul>
            <li><a href="?generate=system-stats">System Statistics (JSON)</a></li>
            <li><a href="#" onclick="generateReport(\'user-activity\')">User Activity Log (Coming Soon)</a></li>
            <li><a href="#" onclick="generateReport(\'backup-status\')">Backup Status (Coming Soon)</a></li>
        </ul>
    </div>
</div>

<div class="report-summary">
    <h3>Quick Statistics</h3>
    <div class="stats-grid">';

// Get quick stats
$db->query("SELECT COUNT(*) as count FROM students");
$studentCount = $db->single()['count'];

$db->query("SELECT COUNT(*) as count FROM teachers");
$teacherCount = $db->single()['count'];

$db->query("SELECT COUNT(*) as count FROM classes");
$classCount = $db->single()['count'];

$db->query("SELECT SUM(amount) as total FROM fee_terms");
$totalFees = $db->single()['total'] ?? 0;

$content .= '
    <div class="stat-card">
        <div class="stat-number">' . $studentCount . '</div>
        <div class="stat-label">Total Students</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">' . $teacherCount . '</div>
        <div class="stat-label">Total Teachers</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">' . $classCount . '</div>
        <div class="stat-label">Total Classes</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">₹' . number_format($totalFees, 0) . '</div>
        <div class="stat-label">Total Fee Structure</div>
    </div>
</div>
</div>

<script>
function generateReport(type) {
    alert("Report generation for " + type + " will be implemented in future updates.");
}
</script>';

echo renderAdminLayout('Reports', $content);
?>