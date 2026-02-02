<?php
require_once 'config/database.php';

$db = new Database();

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #0077B6; color: white; }
tr:nth-child(even) { background-color: #f2f2f2; }
h2 { color: #0077B6; border-bottom: 2px solid #E6F2F1; padding-bottom: 10px; }
.count { background: #00BFA6; color: white; padding: 5px 10px; border-radius: 5px; }
</style>";

echo "<h1>School Management System - Database Content</h1>";

// Display all tables with data
$tables = [
    'users' => 'SELECT id, username, email, role, status, created_at FROM users',
    'students' => 'SELECT s.id, s.roll_number, u.username, c.name as class, sec.name as section, s.admission_date FROM students s JOIN users u ON s.user_id = u.id JOIN classes c ON s.class_id = c.id JOIN sections sec ON s.section_id = sec.id',
    'teachers' => 'SELECT t.id, t.employee_id, u.username, t.qualification, t.specialization, t.joining_date FROM teachers t JOIN users u ON t.user_id = u.id',
    'classes' => 'SELECT id, name, description, created_at FROM classes',
    'sections' => 'SELECT s.id, s.name, c.name as class_name, s.capacity FROM sections s JOIN classes c ON s.class_id = c.id',
    'subjects' => 'SELECT id, name, code, description FROM subjects',
    'fee_terms' => 'SELECT ft.id, ft.name, ft.type, c.name as class_name, ft.amount, ft.due_date FROM fee_terms ft JOIN classes c ON ft.class_id = c.id',
    'announcements' => 'SELECT id, title, priority, target_audience, created_at FROM announcements',
    'study_materials' => 'SELECT sm.id, sm.title, s.name as subject, c.name as class, sm.uploaded_at FROM study_materials sm JOIN subjects s ON sm.subject_id = s.id JOIN classes c ON sm.class_id = c.id',
    'library_books' => 'SELECT id, title, author, isbn, total_copies, available_copies FROM library_books'
];

foreach($tables as $tableName => $query) {
    try {
        $db->query($query);
        $results = $db->resultset();
        
        echo "<h2>" . ucwords(str_replace('_', ' ', $tableName)) . " <span class='count'>" . count($results) . " records</span></h2>";
        
        if(empty($results)) {
            echo "<p style='color: #E63946;'>No data found in this table.</p>";
            continue;
        }
        
        echo "<table>";
        
        // Table headers
        echo "<tr>";
        foreach(array_keys($results[0]) as $column) {
            echo "<th>" . ucwords(str_replace('_', ' ', $column)) . "</th>";
        }
        echo "</tr>";
        
        // Table data
        foreach($results as $row) {
            echo "<tr>";
            foreach($row as $value) {
                echo "<td>" . ($value ?? 'N/A') . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        
    } catch(Exception $e) {
        echo "<h2>" . ucwords(str_replace('_', ' ', $tableName)) . "</h2>";
        echo "<p style='color: #E63946;'>Error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr><p><strong>Total Tables:</strong> " . count($tables) . "</p>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>