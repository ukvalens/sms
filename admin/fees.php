<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$message = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_fee'])) {
        $db->query("INSERT INTO fee_terms (name, type, class_id, amount, due_date) VALUES (?, ?, ?, ?, ?)");
        $db->bind(1, $_POST['name']);
        $db->bind(2, $_POST['type']);
        $db->bind(3, $_POST['class_id']);
        $db->bind(4, $_POST['amount']);
        $db->bind(5, $_POST['due_date']);
        if ($db->execute()) {
            header('Location: fees.php?added=1');
            exit;
        }
    }
}

if(isset($_GET['added'])) {
    $message = '<div class="alert alert-success">Fee term added successfully!</div>';
}

// Get classes for dropdown
$db->query("SELECT * FROM classes ORDER BY name");
$classes = $db->resultset();

// Get fee terms with class info
$db->query("
    SELECT ft.*, c.name as class_name 
    FROM fee_terms ft 
    JOIN classes c ON ft.class_id = c.id 
    ORDER BY ft.due_date DESC
");
$feeTerms = $db->resultset();

$content = $message . '
<div class="page-header">
    <h2>Fee Management</h2>
    <button class="btn" onclick="showAddForm()">Add Fee Term</button>
</div>

<div id="addFeeModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Add Fee Term</h3>
        <form method="POST">
            <div class="form-group">
                <label>Fee Name:</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Type:</label>
                <select name="type" required>
                    <option value="tuition">Tuition</option>
                    <option value="exam">Exam</option>
                    <option value="library">Library</option>
                    <option value="transport">Transport</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Class:</label>
                <select name="class_id" required>
                    <option value="">Select Class</option>';

foreach($classes as $class) {
    $content .= '<option value="' . $class['id'] . '">' . $class['name'] . '</option>';
}

$content .= '
                </select>
            </div>
            <div class="form-group">
                <label>Amount:</label>
                <input type="number" name="amount" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Due Date:</label>
                <input type="date" name="due_date" required>
            </div>
            <button type="submit" name="add_fee" class="btn">Add Fee Term</button>
        </form>
    </div>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Fee Name</th>
                <th>Type</th>
                <th>Class</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';

if(empty($feeTerms)) {
    $content .= '<tr><td colspan="6" style="text-align:center;">No fee terms found</td></tr>';
} else {
    foreach($feeTerms as $fee) {
        $content .= '
            <tr>
                <td>' . htmlspecialchars($fee['name']) . '</td>
                <td>' . ucfirst($fee['type']) . '</td>
                <td>' . htmlspecialchars($fee['class_name']) . '</td>
                <td>FRW ' . number_format($fee['amount'], 0) . '</td>
                <td>' . date('M d, Y', strtotime($fee['due_date'])) . '</td>
                <td>
                    <button class="btn-small btn-view" onclick="viewPayments(' . $fee['id'] . ')">View Payments</button>
                </td>
            </tr>';
    }
}

$content .= '
        </tbody>
    </table>
</div>

<script>
function showAddForm() {
    document.getElementById("addFeeModal").style.display = "block";
}

function closeModal() {
    document.getElementById("addFeeModal").style.display = "none";
}

function viewPayments(feeId) {
    window.location.href = "fee_payments.php?fee_id=" + feeId;
}

window.onclick = function(event) {
    var modal = document.getElementById("addFeeModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>';

echo renderAdminLayout('Fee Management', $content);
?>