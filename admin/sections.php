<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$classId = $_GET['class_id'] ?? 0;

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

$content = '
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
                    <button class="btn-small btn-view">View Students</button>
                    <button class="btn-small btn-edit">Edit</button>
                    <button class="btn-small btn-delete">Delete</button>
                </td>
            </tr>';
}

$content .= '
        </tbody>
    </table>
</div>';

echo renderAdminLayout('Class Sections', $content);
?>