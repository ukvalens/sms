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
                $db->query("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'teacher')");
                $db->bind(':username', $_POST['username']);
                $db->bind(':email', $_POST['email']);
                $db->bind(':password', password_hash($_POST['password'], PASSWORD_DEFAULT));
                
                if($db->execute()) {
                    $userId = $db->lastInsertId();
                    
                    $db->query("INSERT INTO teachers (user_id, employee_id, qualification, specialization, joining_date, phone, address) VALUES (:user_id, :employee_id, :qualification, :specialization, :joining_date, :phone, :address)");
                    $db->bind(':user_id', $userId);
                    $db->bind(':employee_id', $_POST['employee_id']);
                    $db->bind(':qualification', $_POST['qualification']);
                    $db->bind(':specialization', $_POST['specialization']);
                    $db->bind(':joining_date', $_POST['joining_date']);
                    $db->bind(':phone', $_POST['phone']);
                    $db->bind(':address', $_POST['address']);
                    
                    if($db->execute()) {
                        header('Location: teachers.php?msg=Teacher added successfully!');
                    } else {
                        header('Location: teachers.php?msg=Failed to create teacher record.');
                    }
                } else {
                    header('Location: teachers.php?msg=Failed to create user account.');
                }
                exit;
                break;
                
            case 'edit':
                $db->query("UPDATE users SET username = :username, email = :email WHERE id = (SELECT user_id FROM teachers WHERE id = :id)");
                $db->bind(':username', $_POST['username']);
                $db->bind(':email', $_POST['email']);
                $db->bind(':id', $_POST['teacher_id']);
                $db->execute();
                
                $db->query("UPDATE teachers SET employee_id = :employee_id, qualification = :qualification, specialization = :specialization, joining_date = :joining_date, phone = :phone, address = :address WHERE id = :id");
                $db->bind(':employee_id', $_POST['employee_id']);
                $db->bind(':qualification', $_POST['qualification']);
                $db->bind(':specialization', $_POST['specialization']);
                $db->bind(':joining_date', $_POST['joining_date']);
                $db->bind(':phone', $_POST['phone']);
                $db->bind(':address', $_POST['address']);
                $db->bind(':id', $_POST['teacher_id']);
                
                if($db->execute()) {
                    header('Location: teachers.php?msg=Teacher updated successfully!');
                } else {
                    header('Location: teachers.php?msg=Failed to update teacher.');
                }
                exit;
                break;
                
            case 'delete':
                // First get the user_id from teachers table
                $db->query("SELECT user_id FROM teachers WHERE id = :id");
                $db->bind(':id', $_POST['teacher_id']);
                $teacher = $db->single();
                
                if($teacher) {
                    $userId = $teacher['user_id'];
                } else {
                    // If no teacher record, use the ID directly as user_id
                    $userId = $_POST['teacher_id'];
                }
                
                // Delete related records first to avoid foreign key constraints
                $db->query("DELETE FROM messages WHERE sender_id = :user_id OR receiver_id = :user_id");
                $db->bind(':user_id', $userId);
                $db->execute();
                
                $db->query("DELETE FROM teacher_subjects WHERE teacher_id = :teacher_id");
                $db->bind(':teacher_id', $_POST['teacher_id']);
                $db->execute();
                
                // Now delete the user (this will cascade to teachers table)
                $db->query("DELETE FROM users WHERE id = :user_id");
                $db->bind(':user_id', $userId);
                if($db->execute()) {
                    header('Location: teachers.php?msg=Teacher deleted successfully!');
                } else {
                    header('Location: teachers.php?msg=Failed to delete teacher.');
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

// Get teacher for editing
$editTeacher = null;
if(isset($_GET['edit'])) {
    $db->query("SELECT t.*, u.username, u.email FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = :id");
    $db->bind(':id', $_GET['edit']);
    $editTeacher = $db->single();
}

// Get all teachers
$db->query("SELECT u.id as user_id, t.id, t.employee_id, u.username, u.email, t.qualification, t.specialization, t.joining_date, t.phone, t.address FROM users u LEFT JOIN teachers t ON u.id = t.user_id WHERE u.role = 'teacher' ORDER BY t.employee_id, u.username");
$teachers = $db->resultset();

echo renderAdminLayout('Manage Teachers', '
<div class="page-header">
    <h2>Teacher Management</h2>
    <button class="btn" onclick="showAddForm()">Add New Teacher</button>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '
' . ($error ? '<div class="alert alert-error">' . $error . '</div>' : '') . '

<div id="addModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>' . ($editTeacher ? 'Edit Teacher' : 'Add New Teacher') . '</h3>
        <form method="POST">
            <input type="hidden" name="action" value="' . ($editTeacher ? 'edit' : 'add') . '">
            ' . ($editTeacher ? '<input type="hidden" name="teacher_id" value="' . $editTeacher['id'] . '">' : '') . '
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="username" required class="form-control" value="' . ($editTeacher['username'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required class="form-control" value="' . ($editTeacher['email'] ?? '') . '">
            </div>
            ' . (!$editTeacher ? '<div class="form-group"><label>Password:</label><input type="password" name="password" required class="form-control"></div>' : '') . '
            <div class="form-group">
                <label>Employee ID:</label>
                <input type="text" name="employee_id" required class="form-control" value="' . ($editTeacher['employee_id'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Qualification:</label>
                <input type="text" name="qualification" class="form-control" value="' . ($editTeacher['qualification'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Specialization:</label>
                <input type="text" name="specialization" class="form-control" value="' . ($editTeacher['specialization'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Joining Date:</label>
                <input type="date" name="joining_date" required class="form-control" value="' . ($editTeacher['joining_date'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Phone:</label>
                <input type="text" name="phone" class="form-control" value="' . ($editTeacher['phone'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Address:</label>
                <textarea name="address" class="form-control">' . ($editTeacher['address'] ?? '') . '</textarea>
            </div>
            <button type="submit" class="btn">' . ($editTeacher ? 'Update Teacher' : 'Add Teacher') . '</button>
        </form>
    </div>
</div>

' . ($editTeacher ? '<script>document.addEventListener("DOMContentLoaded", function() { document.getElementById("addModal").style.display = "block"; });</script>' : '') . '

<div class="search-bar">
    <input type="text" id="searchInput" placeholder="Search teachers..." onkeyup="filterTable()">
</div>

<div class="data-table">
    <table id="teachersTable">
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Qualification</th>
                <th>Specialization</th>
                <th>Joining Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            ' . implode('', array_map(function($teacher) {
                return '<tr>
                    <td>' . ($teacher['employee_id'] ?? 'Not Set') . '</td>
                    <td>' . $teacher['username'] . '</td>
                    <td>' . $teacher['email'] . '</td>
                    <td>' . ($teacher['qualification'] ?? 'N/A') . '</td>
                    <td>' . ($teacher['specialization'] ?? 'N/A') . '</td>
                    <td>' . ($teacher['joining_date'] ?? 'N/A') . '</td>
                    <td>
                        <button class="btn-small btn-view" onclick="viewTeacher(' . ($teacher['id'] ?? '0') . ')">View</button>
                        <button class="btn-small btn-edit" onclick="editTeacher(' . ($teacher['id'] ?? '0') . ')">Edit</button>
                        <button class="btn-small btn-delete" onclick="deleteTeacher(' . ($teacher['id'] ?? '0') . ')">Delete</button>
                    </td>
                </tr>';
            }, $teachers)) . '
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

function viewTeacher(id) {
    if(id && id !== 0) {
        window.location.href = "teacher_view.php?id=" + id;
    } else {
        alert("Teacher ID not available");
    }
}

function editTeacher(id) {
    if(id && id !== 0) {
        window.location.href = "teachers.php?edit=" + id;
    } else {
        alert("Teacher ID not available");
    }
}

function deleteTeacher(id) {
    if(id && id !== 0) {
        if(confirm("Are you sure you want to delete this teacher?")) {
            var form = document.createElement("form");
            form.method = "POST";
            form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="teacher_id" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    } else {
        alert("Teacher ID not available");
    }
}

function filterTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("teachersTable");
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
</script>
');
?>