<?php
require_once 'config/database.php';

try {
    $db = new Database();
    
    // Read the SQL file
    $sql = file_get_contents('database/add_exam_questions.sql');
    
    // Execute the SQL
    $db->query($sql);
    $result = $db->execute();
    
    if($result) {
        echo "✅ Database setup completed successfully!<br>";
        echo "📝 exam_questions table has been created.<br>";
        echo "🎯 Question creation functionality is now available.<br><br>";
        echo "<a href='teacher/exams.php' style='color: #0077B6;'>Go to Exams Page</a>";
    } else {
        echo "❌ Database setup failed.";
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>