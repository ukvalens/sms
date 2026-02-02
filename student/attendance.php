<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();

// Get current student info
$db->query("SELECT id FROM students WHERE user_id = :user_id");
$db->bind(':user_id', $_SESSION['user_id']);
$student = $db->single();

// Get attendance records
$db->query("SELECT * FROM attendance WHERE student_id = :student_id ORDER BY date DESC LIMIT 30");
$db->bind(':student_id', $student['id']);
$attendance = $db->resultset();

// Calculate statistics
$totalDays = count($attendance);
$presentDays = count(array_filter($attendance, fn($a) => $a['status'] == 'present'));
$absentDays = $totalDays - $presentDays;
$attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;

$content = '
<div class="page-header">
    <h2>My Attendance</h2>
</div>

<div class="attendance-summary">
    <div class="stat-card">
        <div class="stat-number">' . $totalDays . '</div>
        <div class="stat-label">Total Days</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color: #2A9D8F;">' . $presentDays . '</div>
        <div class="stat-label">Present</div>
    </div>
    <div class="stat-card">
        <div class="stat-number" style="color: #E63946;">' . $absentDays . '</div>
        <div class="stat-label">Absent</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">' . $attendancePercentage . '%</div>
        <div class="stat-label">Attendance</div>
    </div>
</div>

<div class="attendance-calendar">
    <h3>Recent Attendance (Last 30 Days)</h3>
    <div class="calendar-grid">';

foreach($attendance as $record) {
    $statusClass = $record['status'] == 'present' ? 'present' : 'absent';
    $content .= '
        <div class="calendar-day ' . $statusClass . '">
            <div class="day-date">' . date('M d', strtotime($record['date'])) . '</div>
            <div class="day-status">' . ucfirst($record['status']) . '</div>
        </div>';
}

$content .= '
    </div>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Status</th>
                <th>Marked At</th>
            </tr>
        </thead>
        <tbody>';

foreach($attendance as $record) {
    $statusColor = $record['status'] == 'present' ? '#2A9D8F' : '#E63946';
    
    $content .= '
            <tr>
                <td>' . date('M d, Y', strtotime($record['date'])) . '</td>
                <td>' . date('l', strtotime($record['date'])) . '</td>
                <td><span style="color: ' . $statusColor . '; font-weight: bold;">' . ucfirst($record['status']) . '</span></td>
                <td>' . date('H:i', strtotime($record['marked_at'])) . '</td>
            </tr>';
}

$content .= '
        </tbody>
    </table>
</div>';

echo renderStudentLayout('My Attendance', $content);
?>