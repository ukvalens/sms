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
                // Check if username already exists
                $db->query("SELECT id FROM users WHERE username = :username");
                $db->bind(':username', $_POST['username']);
                if($db->single()) {
                    header('Location: students.php?msg=Username already exists.');
                    exit;
                }
                
                // Check if email already exists
                $db->query("SELECT id FROM users WHERE email = :email");
                $db->bind(':email', $_POST['email']);
                if($db->single()) {
                    header('Location: students.php?msg=Email already exists.');
                    exit;
                }
                
                $db->query("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'student')");
                $db->bind(':username', $_POST['username']);
                $db->bind(':email', $_POST['email']);
                $db->bind(':password', password_hash($_POST['password'], PASSWORD_DEFAULT));
                
                if($db->execute()) {
                    $userId = $db->lastInsertId();
                    
                    $db->query("INSERT INTO students (user_id, roll_number, class_id, section_id, admission_date, date_of_birth, gender, address, phone) VALUES (:user_id, :roll_number, :class_id, :section_id, :admission_date, :date_of_birth, :gender, :address, :phone)");
                    $db->bind(':user_id', $userId);
                    $db->bind(':roll_number', $_POST['roll_number']);
                    $db->bind(':class_id', $_POST['class_id']);
                    $db->bind(':section_id', $_POST['section_id']);
                    $db->bind(':admission_date', $_POST['admission_date']);
                    $db->bind(':date_of_birth', $_POST['date_of_birth']);
                    $db->bind(':gender', $_POST['gender']);
                    $db->bind(':address', $_POST['address']);
                    $db->bind(':phone', $_POST['phone']);
                    
                    if($db->execute()) {
                        header('Location: students.php?msg=Student added successfully!');
                    } else {
                        header('Location: students.php?msg=Failed to create student record.');
                    }
                } else {
                    header('Location: students.php?msg=Failed to create user account.');
                }
                exit;
                break;
                
            case 'edit':
                $db->query("UPDATE users SET username = :username, email = :email WHERE id = (SELECT user_id FROM students WHERE id = :id)");
                $db->bind(':username', $_POST['username']);
                $db->bind(':email', $_POST['email']);
                $db->bind(':id', $_POST['student_id']);
                $db->execute();
                
                $db->query("UPDATE students SET roll_number = :roll_number, class_id = :class_id, section_id = :section_id, admission_date = :admission_date, date_of_birth = :date_of_birth, gender = :gender, address = :address, phone = :phone WHERE id = :id");
                $db->bind(':roll_number', $_POST['roll_number']);
                $db->bind(':class_id', $_POST['class_id']);
                $db->bind(':section_id', $_POST['section_id']);
                $db->bind(':admission_date', $_POST['admission_date']);
                $db->bind(':date_of_birth', $_POST['date_of_birth']);
                $db->bind(':gender', $_POST['gender']);
                $db->bind(':address', $_POST['address']);
                $db->bind(':phone', $_POST['phone']);
                $db->bind(':id', $_POST['student_id']);
                
                if($db->execute()) {
                    header('Location: students.php?msg=Student updated successfully!');
                } else {
                    header('Location: students.php?msg=Failed to update student.');
                }
                exit;
                break;
                
            case 'delete':
                $db->query("DELETE FROM users WHERE id = (SELECT user_id FROM students WHERE id = :id)");
                $db->bind(':id', $_POST['student_id']);
                if($db->execute()) {
                    header('Location: students.php?msg=Student deleted successfully!');
                } else {
                    header('Location: students.php?msg=Failed to delete student.');
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

// Get student for editing
$editStudent = null;
if(isset($_GET['edit'])) {
    $db->query("SELECT s.*, u.username, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = :id");
    $db->bind(':id', $_GET['edit']);
    $editStudent = $db->single();
}

// Get all students
$db->query("SELECT s.*, u.username, u.email, c.name as class_name, sec.name as section_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN classes c ON s.class_id = c.id LEFT JOIN sections sec ON s.section_id = sec.id ORDER BY s.roll_number");
$students = $db->resultset();

if(!$students) {
    $students = [];
}

// Get classes and sections
$db->query("SELECT * FROM classes ORDER BY name");
$classes = $db->resultset();

$db->query("SELECT * FROM sections ORDER BY name");
$sections = $db->resultset();

echo renderAdminLayout('Manage Students', '
<div class="page-header">
    <h2>Student Management</h2>
    <button class="btn" onclick="showAddForm()">Add New Student</button>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '
' . ($error ? '<div class="alert alert-error">' . $error . '</div>' : '') . '

<div id="addModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>' . ($editStudent ? 'Edit Student' : 'Add New Student') . '</h3>
        <form method="POST">
            <input type="hidden" name="action" value="' . ($editStudent ? 'edit' : 'add') . '">
            ' . ($editStudent ? '<input type="hidden" name="student_id" value="' . $editStudent['id'] . '">' : '') . '
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="username" required class="form-control" value="' . ($editStudent['username'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required class="form-control" value="' . ($editStudent['email'] ?? '') . '">
            </div>
            ' . (!$editStudent ? '<div class="form-group"><label>Password:</label><input type="password" name="password" required class="form-control"></div>' : '') . '
            <div class="form-group">
                <label>Roll Number:</label>
                <input type="text" name="roll_number" required class="form-control" value="' . ($editStudent['roll_number'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Class:</label>
                <select name="class_id" required class="form-control">
                    ' . implode('', array_map(function($class) use ($editStudent) {
                        $selected = ($editStudent && $editStudent['class_id'] == $class['id']) ? 'selected' : '';
                        return '<option value="' . $class['id'] . '" ' . $selected . '>' . $class['name'] . '</option>';
                    }, $classes)) . '
                </select>
            </div>
            <div class="form-group">
                <label>Section:</label>
                <select name="section_id" required class="form-control">
                    ' . implode('', array_map(function($section) use ($editStudent) {
                        $selected = ($editStudent && $editStudent['section_id'] == $section['id']) ? 'selected' : '';
                        return '<option value="' . $section['id'] . '" ' . $selected . '>' . $section['name'] . '</option>';
                    }, $sections)) . '
                </select>
            </div>
            <div class="form-group">
                <label>Admission Date:</label>
                <input type="date" name="admission_date" required class="form-control" value="' . ($editStudent['admission_date'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Date of Birth:</label>
                <input type="date" name="date_of_birth" class="form-control" value="' . ($editStudent['date_of_birth'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Gender:</label>
                <select name="gender" class="form-control">
                    <option value="male"' . (($editStudent['gender'] ?? '') == 'male' ? ' selected' : '') . '>Male</option>
                    <option value="female"' . (($editStudent['gender'] ?? '') == 'female' ? ' selected' : '') . '>Female</option>
                    <option value="other"' . (($editStudent['gender'] ?? '') == 'other' ? ' selected' : '') . '>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Phone:</label>
                <input type="text" name="phone" class="form-control" value="' . ($editStudent['phone'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Address:</label>
                <textarea name="address" class="form-control">' . ($editStudent['address'] ?? '') . '</textarea>
            </div>
            <button type="submit" class="btn">' . ($editStudent ? 'Update Student' : 'Add Student') . '</button>
        </form>
    </div>
</div>

' . ($editStudent ? '<script>document.addEventListener("DOMContentLoaded", function() { document.getElementById("addModal").style.display = "block"; });</script>' : '') . '

<div class="search-bar">
    <input type="text" id="searchInput" placeholder="Search students..." onkeyup="filterTable()">
</div>

<div class="data-table">
    <table id="studentsTable">
        <thead>
            <tr>
                <th>Roll Number</th>
                <th>Name</th>
                <th>Email</th>
                <th>Class</th>
                <th>Section</th>
                <th>Admission Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            ' . implode('', array_map(function($student) {
                return '<tr>
                    <td>' . $student['roll_number'] . '</td>
                    <td>' . $student['username'] . '</td>
                    <td>' . $student['email'] . '</td>
                    <td>' . ($student['class_name'] ?? 'Not assigned') . '</td>
                    <td>' . ($student['section_name'] ?? 'Not assigned') . '</td>
                    <td>' . $student['admission_date'] . '</td>
                    <td>
                        <button class="btn-small btn-edit" onclick="editStudent(' . $student['id'] . ')">Edit</button>
                        <button class="btn-small btn-delete" onclick="deleteStudent(' . $student['id'] . ')">Delete</button>
                    </td>
                </tr>';
            }, $students)) . '
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

function editStudent(id) {
    window.location.href = "students.php?edit=" + id;
}

function deleteStudent(id) {
    if(confirm("Are you sure you want to delete this student?")) {
        var form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function filterTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("studentsTable");
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