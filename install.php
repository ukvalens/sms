<?php
// Database Installation Script

try {
    // Connect to MySQL without database
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS school_management");
    $pdo->exec("USE school_management");
    
    // Read SQL file and split by semicolon
    $sql = file_get_contents('database/school_management.sql');
    $statements = explode(';', $sql);
    
    // Execute each statement
    foreach($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^(CREATE DATABASE|USE)/', $statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<h2>Database Setup Complete!</h2>";
    echo "<p>School Management System database created with all tables.</p>";
    echo "<p>Default admin: admin@school.com / password</p>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>