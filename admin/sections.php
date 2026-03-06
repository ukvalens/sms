<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$classId = $_GET['class_id'] ?? 0;
$message = '';

// Handle form submissions
if($_POST) {
    if(isset($_POST['edit_section'])) {
        $db->query("UPDATE sections SET name = :name, capacity = :capacity WHERE id = :id");
        $db->bind(':name', $_POST['name']);
        $db->bind(':capacity', $_POST['capacity']);
        $db->bind(':id', $_POST['section_id']);
        if($db->execute()) {
            $message = '<div class="alert alert-success">Section updated successfully!</div>';
        }
    } elseif(isset($_POST['delete_section'])) {
        $db->query("DELETE FROM sections WHERE id = :id");
        $db->bind(':id', $_POST['section_id']);
        if($db->execute()) {
            $message = '<div class="alert alert-success">Section deleted successfully!</div>';
        }
    }
}

// Get class info
$db->query("SELECT * FROM classes WHERE id = :id");
$db->bind(':id', $classId);
$class = $db->single();

if(!$class) {
    header('Location: classes.php');
    exit;
}

// Get sections for this class
$db->query("SELECT * FROM sections WHERE class_id = :class_id ORDER BY name");
$db->bind(':class_id', $classId);
$sections = $db->resultset();

$content = $message . '
<div class="page-header">
    <h2>Sections for ' . $class['name'] . '</h2>
    <a href="classes.php" class="btn">Back to Classes</a>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Section Name</th>
                <th>Capacity</th>
                <th>Current Students</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';

foreach($sections as $section) {
    // Get student count for this section
    $db->query("SELECT COUNT(*) as count FROM students WHERE section_id = :section_id");
    $db->bind(':section_id', $section['id']);
    $studentCount = $db->single()['count'];
    
    $content .= '
            <tr>
                <td>' . $section['name'] . '</td>
                <td>' . $section['capacity'] . '</td>
                <td>' . $studentCount . '</td>
                <td>
                    <button class="btn-small btn-view" onclick="viewStudents(' . $section['id'] . ')">View Students</button>
                    <button class="btn-small btn-edit" onclick="editSection(' . $section['id'] . ', \'' . addslashes($section['name']) . '\', ' . $section['capacity'] . ')">Edit</button>
                    <button class="btn-small btn-delete" onclick="deleteSection(' . $section['id'] . ')">Delete</button>
                </td>
            </tr>';
}

$content .= '
        </tbody>
    </table>
</div>

<script>
function viewStudents(sectionId) {
    window.location.href = "/sms/admin/students.php?section_id=" + sectionId;
}

function editSection(id, name, capacity) {
    const newName = prompt("Edit Section Name:", name);
    if(newName && newName !== name) {
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `<input name="edit_section" value="1"><input name="section_id" value="${id}"><input name="name" value="${newName}"><input name="capacity" value="${capacity}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteSection(id) {
    if(confirm("Are you sure you want to delete this section?")) {
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `<input name="delete_section" value="1"><input name="section_id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>';

echo renderAdminLayout('Class Sections', $content);
?>