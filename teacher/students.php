<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$classId = $_GET['class_id'] ?? null;
$sectionId = $_GET['section_id'] ?? null;

if (!$classId || !$sectionId) {
    header('Location: classes.php');
    exit;
}

// Get class and section info
$db->query("SELECT c.name as class_name, s.name as section_name 
           FROM classes c 
           JOIN sections s ON s.class_id = c.id 
           WHERE c.id = :class_id AND s.id = :section_id");
$db->bind(':class_id', $classId);
$db->bind(':section_id', $sectionId);
$classInfo = $db->single();

// Get students in this class/section
$db->query("SELECT s.id, s.roll_number, u.username, u.email, s.admission_date, s.phone, s.address
           FROM students s 
           JOIN users u ON s.user_id = u.id 
           WHERE s.class_id = :class_id AND s.section_id = :section_id 
           ORDER BY s.roll_number");
$db->bind(':class_id', $classId);
$db->bind(':section_id', $sectionId);
$students = $db->resultset();

// If no students found with section filter, try with just class
if(empty($students)) {
    $db->query("SELECT s.id, s.roll_number, u.username, u.email, s.admission_date, s.phone, s.address
               FROM students s 
               JOIN users u ON s.user_id = u.id 
               WHERE s.class_id = :class_id 
               ORDER BY s.roll_number");
    $db->bind(':class_id', $classId);
    $students = $db->resultset();
}

$content = '
<div class="page-header">
    <h2>Students - ' . ($classInfo['class_name'] ?? 'Unknown') . ' (' . ($classInfo['section_name'] ?? 'Unknown') . ')</h2>
    <div>
        <a href="classes.php" class="btn">Back to Classes</a>
    </div>
</div>

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
                <th>Phone</th>
                <th>Admission Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';

foreach($students as $student) {
    $content .= '
            <tr>
                <td>' . ($student['roll_number'] ?? 'N/A') . '</td>
                <td>' . $student['username'] . '</td>
                <td>' . $student['email'] . '</td>
                <td>' . ($student['phone'] ?? 'N/A') . '</td>
                <td>' . ($student['admission_date'] ? date('Y-m-d', strtotime($student['admission_date'])) : 'N/A') . '</td>
                <td>
                    <button class="btn-small btn-view" onclick="viewStudent(' . $student['id'] . ')">View</button>
                </td>
            </tr>';
}

$content .= '
        </tbody>
    </table>
</div>

<div id="studentModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closeStudentModal()">&times;</span>
        <h3>Student Profile</h3>
        <div id="studentDetails"></div>
    </div>
</div>

<script>
function viewStudent(id) {
    fetch("view_student.php?id=" + id)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if(data.error) {
                alert(data.error);
                return;
            }
            
            var html = "<div>";
            html += "<h4>Personal Information</h4>";
            html += "<p><strong>Name:</strong> " + data.username + "</p>";
            html += "<p><strong>Roll Number:</strong> " + (data.roll_number || "N/A") + "</p>";
            html += "<p><strong>Email:</strong> " + data.email + "</p>";
            html += "<p><strong>Phone:</strong> " + (data.phone || "N/A") + "</p>";
            html += "<p><strong>Address:</strong> " + (data.address || "N/A") + "</p>";
            html += "<p><strong>Admission Date:</strong> " + (data.admission_date || "N/A") + "</p>";
            html += "<h4>Academic Information</h4>";
            html += "<p><strong>Class:</strong> " + (data.class_name || "N/A") + "</p>";
            html += "<p><strong>Section:</strong> " + (data.section_name || "N/A") + "</p>";
            html += "</div>";
            
            document.getElementById("studentDetails").innerHTML = html;
            document.getElementById("studentModal").style.display = "block";
        })
        .catch(function() { alert("Failed to load student details"); });
}

function closeStudentModal() {
    document.getElementById("studentModal").style.display = "none";
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
</script>';

echo renderTeacherLayout('Class Students', $content);
?>