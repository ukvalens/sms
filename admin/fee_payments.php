<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$fee_id = $_GET['fee_id'] ?? 0;

// Handle payment submission
if($_POST && isset($_POST['action']) && $_POST['action'] == 'add_payment') {
    session_start();
    $db->query("INSERT INTO fee_payments (student_id, fee_term_id, amount_paid, payment_date, payment_method, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
    $db->bind(1, $_POST['student_id']);
    $db->bind(2, $_POST['fee_term_id']);
    $db->bind(3, $_POST['amount']);
    $db->bind(4, $_POST['payment_date']);
    $db->bind(5, $_POST['payment_method']);
    $db->bind(6, $_SESSION['user_id']);
    if($db->execute()) {
        header('Location: fee_payments.php?fee_id=' . $_POST['fee_term_id'] . '&msg=Payment recorded successfully');
        exit;
    }
}

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

$msg = '';
if(isset($_GET['msg'])) {
    $msg = '<div class="alert alert-success">' . htmlspecialchars($_GET['msg']) . '</div>';
}

$content = $msg . '
<div id="paymentModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Record Fee Payment</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_payment">
            <input type="hidden" name="fee_term_id" value="' . $fee_id . '">
            <div class="form-group">
                <label>Student:</label>
                <select name="student_id" required class="form-control">';

if(!empty($unpaidStudents)) {
    foreach($unpaidStudents as $student) {
        $content .= '<option value="' . $student['id'] . '">' . htmlspecialchars($student['username']) . ' (' . $student['roll_number'] . ')</option>';
    }
} else {
    $content .= '<option value="">All students have paid</option>';
}

$content .= '
                </select>
            </div>
            <div class="form-group">
                <label>Amount:</label>
                <input type="number" name="amount" value="' . $feeTerm['amount'] . '" required class="form-control">
            </div>
            <div class="form-group">
                <label>Payment Method:</label>
                <select name="payment_method" required class="form-control">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
            <div class="form-group">
                <label>Payment Date:</label>
                <input type="date" name="payment_date" value="' . date('Y-m-d') . '" required class="form-control">
            </div>
            <button type="submit" class="btn"' . (empty($unpaidStudents) ? ' disabled' : '') . '>Record Payment</button>
        </form>
    </div>
</div>

<div class="page-header">
    <h2>Fee Payments - ' . htmlspecialchars($feeTerm['name']) . '</h2>
    <div>
        <span class="stat-label">Class: ' . htmlspecialchars($feeTerm['class_name']) . '</span>
        <span class="stat-label">Amount: FRW ' . number_format($feeTerm['amount'], 0) . '</span>
        <button class="btn" onclick="showPaymentForm()"' . (empty($unpaidStudents) ? ' disabled' : '') . '>Record Payment</button>
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
        <div class="stat-number">FRW ' . number_format(array_sum(array_column($payments, 'amount_paid')), 0) . '</div>
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
                    <td>FRW ' . number_format($payment['amount_paid'], 0) . '</td>
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

$content .= '
<script>
function showPaymentForm() {
    document.getElementById("paymentModal").style.display = "block";
}

function closeModal() {
    document.getElementById("paymentModal").style.display = "none";
}
</script>';

echo renderAdminLayout('Fee Payments', $content);
?>
