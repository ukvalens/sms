<?php
require_once 'config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    $db = new Database();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test basic query
    $db->query("SELECT COUNT(*) as count FROM users");
    $result = $db->single();
    echo "<p>Total users: " . $result['count'] . "</p>";
    
    // Test each table
    $tables = ['users', 'students', 'teachers', 'classes', 'subjects', 'sections'];
    
    foreach($tables as $table) {
        try {
            $db->query("SELECT COUNT(*) as count FROM $table");
            $result = $db->single();
            echo "<p>$table: " . $result['count'] . " records</p>";
        } catch(Exception $e) {
            echo "<p style='color: red;'>Error with $table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Show sample data
    echo "<h3>Sample Users:</h3>";
    $db->query("SELECT id, username, email, role FROM users LIMIT 5");
    $users = $db->resultset();
    
    if($users) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
        foreach($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No users found</p>";
    }
    
} catch(Exception $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL is running</li>";
    echo "<li>Database 'school_management' exists</li>";
    echo "<li>Run install.php first</li>";
    echo "</ul>";
}
?>