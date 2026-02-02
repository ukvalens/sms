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
            case 'assign':
                // First check if teacher exists in teachers table
                $db->query("SELECT id FROM teachers WHERE id = :teacher_id");
                $db->bind(':teacher_id', $_POST['teacher_id']);
                if(!$db->single()) {
                    $error = 'Selected teacher does not exist in the system.';
                    break;
                }
                
                // Check if assignment already exists
                $db->query("SELECT id FROM teacher_subjects WHERE teacher_id = :teacher_id AND subject_id = :subject_id AND class_id = :class_id AND section_id = :section_id");
                $db->bind(':teacher_id', $_POST['teacher_id']);
                $db->bind(':subject_id', $_POST['subject_id']);
                $db->bind(':class_id', $_POST['class_id']);
                $db->bind(':section_id', $_POST['section_id']);
                
                if($db->single()) {
                    $error = 'This teacher is already assigned to this subject and class/section.';
                } else {
                    $db->query("INSERT INTO teacher_subjects (teacher_id, subject_id, class_id, section_id) VALUES (:teacher_id, :subject_id, :class_id, :section_id)");
                    $db->bind(':teacher_id', $_POST['teacher_id']);
                    $db->bind(':subject_id', $_POST['subject_id']);
                    $db->bind(':class_id', $_POST['class_id']);
                    $db->bind(':section_id', $_POST['section_id']);
                    
                    if($db->execute()) {
                        $message = 'Teacher assigned successfully!';
                    } else {
                        $error = 'Failed to assign teacher.';
                    }
                }
                break;
                
            case 'remove':
                $db->query("DELETE FROM teacher_subjects WHERE id = :id");
                $db->bind(':id', $_POST['assignment_id']);
                if($db->execute()) {
                    $message = 'Assignment removed successfully!';
                } else {
                    $error = 'Failed to remove assignment.';
                }
                break;
        }
    }
}

// Get all teachers
$db->query("SELECT t.id, t.employee_id, u.username FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.role = 'teacher' ORDER BY t.employee_id, u.username");
$teachers = $db->resultset();

// Get all subjects
$db->query("SELECT * FROM subjects ORDER BY name");
$subjects = $db->resultset();

// Get all classes
$db->query("SELECT * FROM classes ORDER BY name");
$classes = $db->resultset();

// Get all sections
$db->query("SELECT s.*, c.name as class_name FROM sections s JOIN classes c ON s.class_id = c.id ORDER BY c.name, s.name");
$sections = $db->resultset();

// Get current assignments
$db->query("
    SELECT ts.id, t.employee_id, u.username as teacher_name, 
           sub.name as subject_name, c.name as class_name, sec.name as section_name
    FROM teacher_subjects ts
    JOIN teachers t ON ts.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN subjects sub ON ts.subject_id = sub.id
    JOIN classes c ON ts.class_id = c.id
    JOIN sections sec ON ts.section_id = sec.id
    ORDER BY u.username, c.name, sec.name
");
$assignments = $db->resultset();

$content = '
<div class="page-header">
    <h2>Teacher Class Assignments</h2>
    <button class="btn" onclick="showAssignForm()">Assign Teacher</button>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '
' . ($error ? '<div class="alert alert-error">' . $error . '</div>' : '') . '

<!-- Assign Teacher Modal -->
<div id="assignModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Assign Teacher to Class</h3>
        <form method="POST">
            <input type="hidden" name="action" value="assign">
            <div class="form-group">
                <label>Select Teacher:</label>
                <select name="teacher_id" required class="form-control">
                    <option value="">Choose Teacher...</option>';
                    
foreach($teachers as $teacher) {
    $content .= '<option value="' . $teacher['id'] . '">' . $teacher['employee_id'] . ' - ' . $teacher['username'] . '</option>';
}

$content .= '</select>
            </div>
            <div class="form-group">
                <label>Select Subject:</label>
                <select name="subject_id" required class="form-control">
                    <option value="">Choose Subject...</option>';
                    
foreach($subjects as $subject) {
    $content .= '<option value="' . $subject['id'] . '">' . $subject['name'] . ' (' . $subject['code'] . ')</option>';
}

$content .= '</select>
            </div>
            <div class="form-group">
                <label>Select Class:</label>
                <select name="class_id" required class="form-control" onchange="updateSections()">
                    <option value="">Choose Class...</option>';
                    
foreach($classes as $class) {
    $content .= '<option value="' . $class['id'] . '">' . $class['name'] . '</option>';
}

$content .= '</select>
            </div>
            <div class="form-group">
                <label>Select Section:</label>
                <select name="section_id" required class="form-control" id="sectionSelect">
                    <option value="">Choose Section...</option>
                </select>
            </div>
            <button type="submit" class="btn">Assign Teacher</button>
        </form>
    </div>
</div>

<!-- Search Bar -->
<div class="search-bar">
    <input type="text" id="searchInput" placeholder="Search assignments..." onkeyup="filterTable()">
</div>

<div class="data-table">
    <table id="assignmentsTable">
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Teacher Name</th>
                <th>Subject</th>
                <th>Class</th>
                <th>Section</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';

foreach($assignments as $assignment) {
    $content .= '
            <tr>
                <td>' . $assignment['employee_id'] . '</td>
                <td>' . $assignment['teacher_name'] . '</td>
                <td>' . $assignment['subject_name'] . '</td>
                <td>' . $assignment['class_name'] . '</td>
                <td>' . $assignment['section_name'] . '</td>
                <td>
                    <button class="btn-small btn-delete" onclick="removeAssignment(' . $assignment['id'] . ')">Remove</button>
                </td>
            </tr>';
}

$content .= '
        </tbody>
    </table>
</div>

<script>
// Store sections data for dynamic loading
const sectionsData = ' . json_encode($sections) . ';

function showAssignForm() {
    document.getElementById("assignModal").style.display = "block";
}

function closeModal() {
    document.getElementById("assignModal").style.display = "none";
}

function updateSections() {
    const classId = document.querySelector("select[name=\'class_id\']").value;
    const sectionSelect = document.getElementById("sectionSelect");
    
    // Clear existing options
    sectionSelect.innerHTML = "<option value=\"\">Choose Section...</option>";
    
    if (classId) {
        // Filter sections for selected class
        const classSections = sectionsData.filter(section => section.class_id == classId);
        
        classSections.forEach(section => {
            const option = document.createElement("option");
            option.value = section.id;
            option.textContent = section.name;
            sectionSelect.appendChild(option);
        });
    }
}

function removeAssignment(id) {
    if(confirm("Are you sure you want to remove this assignment?")) {
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `<input type="hidden" name="action" value="remove"><input type="hidden" name="assignment_id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function filterTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toUpperCase();
    const table = document.getElementById("assignmentsTable");
    const tr = table.getElementsByTagName("tr");
    
    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName("td");
        let found = false;
        for (let j = 0; j < td.length - 1; j++) {
            if (td[j] && td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        tr[i].style.display = found ? "" : "none";
    }
}
</script>';

echo renderAdminLayout('Teacher Assignments', $content);
?>