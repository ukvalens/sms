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
            case 'add_class':
                $db->query("INSERT INTO classes (name, description) VALUES (:name, :description)");
                $db->bind(':name', $_POST['name']);
                $db->bind(':description', $_POST['description']);
                
            case 'edit_class':
                $db->query("UPDATE classes SET name = :name, description = :description WHERE id = :id");
                $db->bind(':name', $_POST['name']);
                $db->bind(':description', $_POST['description']);
                $db->bind(':id', $_POST['class_id']);
                
                if($db->execute()) {
                    header('Location: classes.php?msg=Class updated successfully!');
                } else {
                    header('Location: classes.php?msg=Failed to update class.');
                }
                exit;
                break;
                
            case 'add_section':
                $db->query("INSERT INTO sections (class_id, name, capacity) VALUES (:class_id, :name, :capacity)");
                $db->bind(':class_id', $_POST['class_id']);
                $db->bind(':name', $_POST['name']);
                $db->bind(':capacity', $_POST['capacity']);
                
                if($db->execute()) {
                    $message = 'Section added successfully!';
                } else {
                    $error = 'Failed to add section.';
                }
                break;
                
            case 'delete_class':
                try {
                    // Start transaction and disable foreign key checks
                    $db->query("SET FOREIGN_KEY_CHECKS = 0");
                    $db->execute();
                    
                    // Delete students
                    $db->query("DELETE FROM students WHERE class_id = :class_id");
                    $db->bind(':class_id', $_POST['class_id']);
                    $db->execute();
                    
                    // Delete sections
                    $db->query("DELETE FROM sections WHERE class_id = :class_id");
                    $db->bind(':class_id', $_POST['class_id']);
                    $db->execute();
                    
                    // Delete class
                    $db->query("DELETE FROM classes WHERE id = :id");
                    $db->bind(':id', $_POST['class_id']);
                    $db->execute();
                    
                    // Re-enable foreign key checks
                    $db->query("SET FOREIGN_KEY_CHECKS = 1");
                    $db->execute();
                    
                    $message = 'Class deleted successfully!';
                } catch(Exception $e) {
                    // Re-enable foreign key checks on error
                    $db->query("SET FOREIGN_KEY_CHECKS = 1");
                    $db->execute();
                    $error = 'Failed to delete class.';
                }
                break;
        }
    }
}

// Get class for editing
$editClass = null;
if(isset($_GET['edit'])) {
    $db->query("SELECT * FROM classes WHERE id = :id");
    $db->bind(':id', $_GET['edit']);
    $editClass = $db->single();
}

// Get all classes with section count
$db->query("
    SELECT c.*, COUNT(s.id) as section_count 
    FROM classes c 
    LEFT JOIN sections s ON c.id = s.class_id 
    GROUP BY c.id 
    ORDER BY c.name
");
$classes = $db->resultset();

$content = '
<div class="page-header">
    <h2>Class Management</h2>
    <div>
        <button class="btn" onclick="showAddClassForm()">Add New Class</button>
        <button class="btn" onclick="showAddSectionForm()">Add Section</button>
    </div>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '
' . ($error ? '<div class="alert alert-error">' . $error . '</div>' : '') . '

<!-- Add Class Modal -->
<div id="addClassModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(\"addClassModal\")">&times;</span>
        <h3>' . ($editClass ? 'Edit Class' : 'Add New Class') . '</h3>
        <form method="POST">
            <input type="hidden" name="action" value="' . ($editClass ? 'edit_class' : 'add_class') . '">
            ' . ($editClass ? '<input type="hidden" name="class_id" value="' . $editClass['id'] . '">' : '') . '
            <div class="form-group">
                <label>Class Name:</label>
                <input type="text" name="name" required class="form-control" placeholder="e.g., Class 11" value="' . ($editClass['name'] ?? '') . '">
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" class="form-control" placeholder="Optional description">' . ($editClass['description'] ?? '') . '</textarea>
            </div>
            <button type="submit" class="btn">' . ($editClass ? 'Update Class' : 'Add Class') . '</button>
        </form>
    </div>
</div>

<!-- Add Section Modal -->
<div id="addSectionModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(\"addSectionModal\")">&times;</span>
        <h3>Add New Section</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_section">
            <div class="form-group">
                <label>Select Class:</label>
                <select name="class_id" required class="form-control">';
                
foreach($classes as $class) {
    $content .= '<option value="' . $class['id'] . '">' . $class['name'] . '</option>';
}

$content .= '</select>
            </div>
            <div class="form-group">
                <label>Section Name:</label>
                <input type="text" name="name" required class="form-control" placeholder="e.g., A, B, C">
            </div>
            <div class="form-group">
                <label>Capacity:</label>
                <input type="number" name="capacity" value="40" class="form-control">
            </div>
            <button type="submit" class="btn">Add Section</button>
        </form>
    </div>
</div>

<!-- Search Bar -->
<div class="search-bar">
    <input type="text" id="searchInput" placeholder="Search classes..." onkeyup="filterTable()">
</div>

<div class="data-table">
    <table id="classesTable">
        <thead>
            <tr>
                <th>Class Name</th>
                <th>Description</th>
                <th>Sections</th>
                <th>Created Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';

foreach($classes as $class) {
    $content .= '
            <tr>
                <td>' . $class['name'] . '</td>
                <td>' . ($class['description'] ?? 'N/A') . '</td>
                <td>' . $class['section_count'] . '</td>
                <td>' . date('Y-m-d', strtotime($class['created_at'])) . '</td>
                <td>
                    <button class="btn-small btn-edit" onclick="editClass(' . $class['id'] . ')">Edit</button>
                    <button class="btn-small btn-view" onclick="viewSections(' . $class['id'] . ')">View Sections</button>
                    <button class="btn-small btn-delete" onclick="deleteClass(' . $class['id'] . ')">Delete</button>
                </td>
            </tr>';
}

$content .= '
        </tbody>
    </table>
</div>

<script>
function showAddClassForm() {
    document.getElementById("addClassModal").style.display = "block";
}

' . ($editClass ? 'document.addEventListener("DOMContentLoaded", function() { showAddClassForm(); });' : '') . '

function showAddSectionForm() {
    document.getElementById("addSectionModal").style.display = "block";
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
}

function editClass(id) {
    window.location.href = "classes.php?edit=" + id;
}

function viewSections(classId) {
    window.location.href = "sections.php?class_id=" + classId;
}

function deleteClass(id) {
    if(confirm("Are you sure you want to delete this class? This will also delete all sections.")) {
        var form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `<input type="hidden" name="action" value="delete_class"><input type="hidden" name="class_id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function filterTable() {
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("classesTable");
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

echo renderAdminLayout('Manage Classes', $content);
?>