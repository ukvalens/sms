<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$message = '';

// Handle file upload
if($_POST && isset($_POST['action']) && $_POST['action'] == 'upload') {
    $uploadDir = '../uploads/materials/';
    if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileName = time() . '_' . $_FILES['file']['name'];
    $filePath = $uploadDir . $fileName;
    
    if(move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        $db->query("INSERT INTO study_materials (title, description, subject_id, class_id, file_path, file_type, uploaded_by) VALUES (:title, :description, :subject_id, :class_id, :file_path, :file_type, :uploaded_by)");
        $db->bind(':title', $_POST['title']);
        $db->bind(':description', $_POST['description']);
        $db->bind(':subject_id', $_POST['subject_id']);
        $db->bind(':class_id', $_POST['class_id']);
        $db->bind(':file_path', 'uploads/materials/' . $fileName);
        $db->bind(':file_type', pathinfo($fileName, PATHINFO_EXTENSION));
        $db->bind(':uploaded_by', $_SESSION['user_id']);
        
        if($db->execute()) {
            $message = 'Study material uploaded successfully!';
        }
    }
}

// Get teacher's materials
$db->query("SELECT sm.*, s.name as subject_name, c.name as class_name FROM study_materials sm JOIN subjects s ON sm.subject_id = s.id JOIN classes c ON sm.class_id = c.id WHERE sm.uploaded_by = :user_id ORDER BY sm.uploaded_at DESC");
$db->bind(':user_id', $_SESSION['user_id']);
$materials = $db->resultset();

// Get subjects and classes for form
$db->query("SELECT * FROM subjects ORDER BY name");
$subjects = $db->resultset();

$db->query("SELECT * FROM classes ORDER BY name");
$classes = $db->resultset();

$content = '
<div class="page-header">
    <h2>Study Materials</h2>
    <button class="btn" onclick="showUploadForm()">Upload Material</button>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '

<div id="uploadModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Upload Study Material</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <div class="form-group">
                <label>Title:</label>
                <input type="text" name="title" required class="form-control">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Subject:</label>
                    <select name="subject_id" required class="form-control">';

foreach($subjects as $subject) {
    $content .= '<option value="' . $subject['id'] . '">' . $subject['name'] . '</option>';
}

$content .= '</select>
                </div>
                <div class="form-group">
                    <label>Class:</label>
                    <select name="class_id" required class="form-control">';

foreach($classes as $class) {
    $content .= '<option value="' . $class['id'] . '">' . $class['name'] . '</option>';
}

$content .= '</select>
                </div>
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label>File:</label>
                <input type="file" name="file" required class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx">
            </div>
            <button type="submit" class="btn">Upload</button>
        </form>
    </div>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Subject</th>
                <th>Class</th>
                <th>File Type</th>
                <th>Downloads</th>
                <th>Uploaded</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';

foreach($materials as $material) {
    $content .= '
            <tr>
                <td>' . $material['title'] . '</td>
                <td>' . $material['subject_name'] . '</td>
                <td>' . $material['class_name'] . '</td>
                <td>' . strtoupper($material['file_type']) . '</td>
                <td>' . $material['download_count'] . '</td>
                <td>' . date('M d, Y', strtotime($material['uploaded_at'])) . '</td>
                <td>
                    <button class="btn-small btn-view" onclick="downloadFile(\'' . $material['file_path'] . '\')">Download</button>
                    <button class="btn-small btn-delete" onclick="deleteMaterial(' . $material['id'] . ')">Delete</button>
                </td>
            </tr>';
}

$content .= '
        </tbody>
    </table>
</div>

<script>
function showUploadForm() {
    document.getElementById("uploadModal").style.display = "block";
}

function closeModal() {
    document.getElementById("uploadModal").style.display = "none";
}

function downloadFile(path) {
    window.open("../" + path, "_blank");
}

function deleteMaterial(id) {
    if(confirm("Are you sure you want to delete this material?")) {
        alert("Delete functionality will be implemented");
    }
}
</script>';

echo renderTeacherLayout('Study Materials', $content);
?>