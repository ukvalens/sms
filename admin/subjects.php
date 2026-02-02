<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$message = '';
$error = '';

// Handle form submissions
if($_POST) {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add':
                $db->query("INSERT INTO subjects (name, code, description) VALUES (:name, :code, :description)");
                $db->bind(':name', $_POST['name']);
                $db->bind(':code', $_POST['code']);
                $db->bind(':description', $_POST['description']);
                
                if($db->execute()) {
                    header('Location: subjects.php?msg=Subject added successfully!');
                } else {
                    header('Location: subjects.php?msg=Failed to add subject.');
                }
                exit;
                break;
                
            case 'edit':
                $db->query("UPDATE subjects SET name = :name, code = :code, description = :description WHERE id = :id");
                $db->bind(':name', $_POST['name']);
                $db->bind(':code', $_POST['code']);
                $db->bind(':description', $_POST['description']);
                $db->bind(':id', $_POST['subject_id']);
                
                if($db->execute()) {
                    header('Location: subjects.php?msg=Subject updated successfully!');
                } else {
                    header('Location: subjects.php?msg=Failed to update subject.');
                }
                exit;
                break;
                
            case 'delete':
                $db->query("DELETE FROM subjects WHERE id = :id");
                $db->bind(':id', $_POST['subject_id']);
                if($db->execute()) {
                    header('Location: subjects.php?msg=Subject deleted successfully!');
                } else {
                    header('Location: subjects.php?msg=Failed to delete subject.');
                }
                exit;
                break;
        }
    }
}

// Check for message from redirect
if(isset($_GET['msg'])) {
    if(strpos($_GET['msg'], 'successfully') !== false) {
        $message = $_GET['msg'];
    } else {
        $error = $_GET['msg'];
    }
}

// Get subject for editing
$editSubject = null;
if(isset($_GET['edit'])) {
    $db->query("SELECT * FROM subjects WHERE id = :id");
    $db->bind(':id', $_GET['edit']);
    $editSubject = $db->single();
}

// Get all subjects
$db->query("SELECT * FROM subjects ORDER BY name");
$subjects = $db->resultset();

$content = '
<div class="page-header">
    <h2>Subject Management</h2>
    <button class="btn" onclick="showAddForm()">Add New Subject</button>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '
' . ($error ? '<div class="alert alert-error">' . $error . '</div>' : '') . '

<!-- Add Subject Modal -->
<div id="addModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>' . ($editSubject ? 'Edit Subject' : 'Add New Subject') . '</h3>
        <form method="POST">
            <input type="hidden" name="action" value="' . ($editSubject ? 'edit' : 'add') . '">
            ' . ($editSubject ? '<input type="hidden" name="subject_id" value="' . $editSubject['id'] . '">' : '') . '
            <div class="form-row">
                <div class="form-group">
                    <label>Subject Name:</label>
                    <input type="text" name="name" required class="form-control" 
                           value="' . ($editSubject['name'] ?? '') . '" 
                           placeholder="e.g., Mathematics">
                </div>
                <div class="form-group">
                    <label>Subject Code:</label>
                    <input type="text" name="code" required class="form-control" 
                           value="' . ($editSubject['code'] ?? '') . '" 
                           placeholder="e.g., MATH101">
                </div>
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" class="form-control" 
                          placeholder="Optional description">' . ($editSubject['description'] ?? '') . '</textarea>
            </div>
            <button type="submit" class="btn">' . ($editSubject ? 'Update Subject' : 'Add Subject') . '</button>
        </form>
    </div>
</div>

<!-- Search Bar -->
<div class="search-bar">
    <input type="text" id="searchInput" placeholder="Search subjects..." onkeyup="filterTable()">
</div>

<div class="data-table">
    <table id="subjectsTable">
        <thead>
            <tr>
                <th>Subject Name</th>
                <th>Subject Code</th>
                <th>Description</th>
                <th>Created Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';

foreach($subjects as $subject) {
    $content .= '
            <tr>
                <td>' . $subject['name'] . '</td>
                <td>' . $subject['code'] . '</td>
                <td>' . ($subject['description'] ?? 'N/A') . '</td>
                <td>' . date('Y-m-d', strtotime($subject['created_at'])) . '</td>
                <td>
                    <button class="btn-small btn-edit" onclick="editSubject(' . $subject['id'] . ')">Edit</button>
                    <button class="btn-small btn-delete" onclick="deleteSubject(' . $subject['id'] . ')">Delete</button>
                </td>
            </tr>';
}

$content .= '
        </tbody>
    </table>
</div>

<script>
function showAddForm() {
    document.getElementById("addModal").style.display = "block";
}

function closeModal() {
    document.getElementById("addModal").style.display = "none";
}

function editSubject(id) {
    window.location.href = "subjects.php?edit=" + id;
}

' . ($editSubject ? 'document.addEventListener("DOMContentLoaded", function() { showAddForm(); });' : '') . '

function deleteSubject(id) {
    if(confirm("Are you sure you want to delete this subject?")) {
        var form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="subject_id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function filterTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("subjectsTable");
    var tr = table.getElementsByTagName("tr");
    
    for (var i = 1; i < tr.length; i++) {
        var td = tr[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < td.length - 1; j++) {
            if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        tr[i].style.display = found ? "" : "none";
    }
}
</script>';

echo renderAdminLayout('Manage Subjects', $content);
?>