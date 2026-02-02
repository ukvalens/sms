<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();

// Get fee terms with class info
$db->query("
    SELECT ft.*, c.name as class_name 
    FROM fee_terms ft 
    JOIN classes c ON ft.class_id = c.id 
    ORDER BY ft.due_date DESC
");
$feeTerms = $db->resultset();

if(empty($feeTerms)) {
    $content = '
    <div class="page-header">
        <h2>Fee Management</h2>
        <button class="btn" onclick="showAddForm()">Add Fee Term</button>
    </div>
    
    <div class="alert alert-info">
        <p>No fee terms found. Create fee structures using the form above.</p>
    </div>';
    
    echo renderAdminLayout('Fee Management', $content);
    return;
}

$content = '
<div class="page-header">
    <h2>Fee Management</h2>
    <button class="btn" onclick="showAddForm()">Add Fee Term</button>
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

foreach($feeTerms as $fee) {
    $content .= '
            <tr>
                <td>' . $fee['name'] . '</td>
                <td>' . ucfirst($fee['type']) . '</td>
                <td>' . $fee['class_name'] . '</td>
                <td>₹' . number_format($fee['amount'], 2) . '</td>
                <td>' . $fee['due_date'] . '</td>
                <td>
                    <button class="btn-small btn-edit">Edit</button>
                    <button class="btn-small btn-view" onclick="viewPayments(' . $fee['id'] . ')">View Payments</button>
                    <button class="btn-small btn-delete">Delete</button>
                </td>
            </tr>';
}

$content .= '
        </tbody>
    </table>
</div>

<script>
function showAddForm() {
    alert("Add Fee Term form will be implemented");
}

function viewPayments(feeId) {
    alert("View payments for fee ID: " + feeId);
}
</script>';

echo renderAdminLayout('Fee Management', $content);
?>