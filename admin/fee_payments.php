<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$fee_id = $_GET['fee_id'] ?? 0;

// Get fee term details
$db->query("SELECT ft.*, c.name as class_name FROM fee_terms ft JOIN classes c ON ft.class_id = c.id WHERE ft.id = ?");
$db->bind(1, $fee_id);
$feeTerm = $db->single();

if (!$feeTerm) {
    header('Location: fees.php');
    exit;
}

// Get payments for this fee term
$db->query("
    SELECT fp.*, u.username, s.roll_number 
    FROM fee_payments fp 
    JOIN students s ON fp.student_id = s.id 
    JOIN users u ON s.user_id = u.id
    WHERE fp.fee_term_id = ? 
    ORDER BY fp.payment_date DESC
");
$db->bind(1, $fee_id);
$payments = $db->resultset();

// Get students who haven't paid
$db->query("
    SELECT s.*, u.username FROM students s 
    JOIN users u ON s.user_id = u.id
    WHERE s.class_id = ? 
    AND s.id NOT IN (SELECT student_id FROM fee_payments WHERE fee_term_id = ?)
");
$db->bind(1, $feeTerm['class_id']);
$db->bind(2, $fee_id);
$unpaidStudents = $db->resultset();

$content = '
<div class="page-header">
    <h2>Fee Payments - ' . htmlspecialchars($feeTerm['name']) . '</h2>
    <div>
        <span class="stat-label">Class: ' . htmlspecialchars($feeTerm['class_name']) . '</span>
        <span class="stat-label">Amount: FRW ' . number_format($feeTerm['amount'], 0) . '</span>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number">' . count($payments) . '</div>
        <div class="stat-label">Paid Students</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">' . count($unpaidStudents) . '</div>
        <div class="stat-label">Pending Students</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">FRW ' . number_format(array_sum(array_column($payments, 'amount')), 0) . '</div>
        <div class="stat-label">Total Collected</div>
    </div>
</div>';

if (!empty($payments)) {
    $content .= '
    <div class="data-table">
        <h3>Paid Students</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Roll Number</th>
                    <th>Amount</th>
                    <th>Payment Date</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach($payments as $payment) {
        $content .= '
                <tr>
                    <td>' . htmlspecialchars($payment['username']) . '</td>
                    <td>' . htmlspecialchars($payment['roll_number']) . '</td>
                    <td>FRW ' . number_format($payment['amount'], 0) . '</td>
                    <td>' . date('M d, Y', strtotime($payment['payment_date'])) . '</td>
                    <td>' . ucfirst($payment['payment_method']) . '</td>
                </tr>';
    }
    
    $content .= '
            </tbody>
        </table>
    </div>';
}

if (!empty($unpaidStudents)) {
    $content .= '
    <div class="data-table">
        <h3>Pending Students</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Roll Number</th>
                    <th>Amount Due</th>
                    <th>Days Overdue</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach($unpaidStudents as $student) {
        $daysOverdue = max(0, (time() - strtotime($feeTerm['due_date'])) / (60*60*24));
        $content .= '
                <tr>
                    <td>' . htmlspecialchars($student['username']) . '</td>
                    <td>' . htmlspecialchars($student['roll_number']) . '</td>
                    <td>FRW ' . number_format($feeTerm['amount'], 0) . '</td>
                    <td>' . ($daysOverdue > 0 ? floor($daysOverdue) . ' days' : 'Not due') . '</td>
                </tr>';
    }
    
    $content .= '
            </tbody>
        </table>
    </div>';
}

echo renderAdminLayout('Fee Payments', $content);
?>