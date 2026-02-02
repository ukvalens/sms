<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$subjectId = $_GET['subject_id'] ?? 0;

// Get current student info
$db->query("SELECT class_id FROM students WHERE user_id = :user_id");
$db->bind(':user_id', $_SESSION['user_id']);
$student = $db->single();

// Get study materials
if($subjectId) {
    $db->query("SELECT sm.*, s.name as subject_name, u.username as uploaded_by FROM study_materials sm JOIN subjects s ON sm.subject_id = s.id JOIN users u ON sm.uploaded_by = u.id WHERE sm.subject_id = :subject_id AND sm.class_id = :class_id ORDER BY sm.uploaded_at DESC");
    $db->bind(':subject_id', $subjectId);
    $db->bind(':class_id', $student['class_id']);
} else {
    $db->query("SELECT sm.*, s.name as subject_name, u.username as uploaded_by FROM study_materials sm JOIN subjects s ON sm.subject_id = s.id JOIN users u ON sm.uploaded_by = u.id WHERE sm.class_id = :class_id ORDER BY sm.uploaded_at DESC");
    $db->bind(':class_id', $student['class_id']);
}

$materials = $db->resultset();

$content = '
<div class="page-header">
    <h2>Study Materials</h2>
    <a href="subjects.php" class="btn">Back to Subjects</a>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Subject</th>
                <th>Description</th>
                <th>Uploaded By</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';

foreach($materials as $material) {
    $content .= '
            <tr>
                <td>' . $material['title'] . '</td>
                <td>' . $material['subject_name'] . '</td>
                <td>' . ($material['description'] ?: 'N/A') . '</td>
                <td>' . $material['uploaded_by'] . '</td>
                <td>' . date('M d, Y', strtotime($material['uploaded_at'])) . '</td>
                <td>
                    <button class="btn-small btn-view" onclick="downloadMaterial(\'' . $material['file_path'] . '\', ' . $material['id'] . ')">Download</button>
                </td>
            </tr>';
}

$content .= '
        </tbody>
    </table>
</div>

<script>
function downloadMaterial(path, id) {
    // Update download count
    fetch("../api/download.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({material_id: id})
    });
    
    // Open file
    window.open("../" + path, "_blank");
}
</script>';

echo renderStudentLayout('Study Materials', $content);
?>