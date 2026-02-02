<?php
require_once 'config/database.php';

try {
    $db = new Database();
    
    echo "<h2>Adding Sample Data</h2>";
    
    // Add sample teachers
    $teachers = [
        ['john_doe', 'john@school.com', 'T001', 'M.Sc Mathematics', 'Mathematics'],
        ['jane_smith', 'jane@school.com', 'T002', 'M.A English', 'English Literature'],
        ['mike_wilson', 'mike@school.com', 'T003', 'M.Sc Physics', 'Physics']
    ];
    
    foreach($teachers as $teacher) {
        // Insert user
        $db->query("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'teacher')");
        $db->bind(':username', $teacher[0]);
        $db->bind(':email', $teacher[1]);
        $db->bind(':password', password_hash('password', PASSWORD_DEFAULT));
        
        if($db->execute()) {
            $userId = $db->lastInsertId();
            
            // Insert teacher record
            $db->query("INSERT INTO teachers (user_id, employee_id, qualification, specialization, joining_date) VALUES (:user_id, :employee_id, :qualification, :specialization, '2024-01-15')");
            $db->bind(':user_id', $userId);
            $db->bind(':employee_id', $teacher[2]);
            $db->bind(':qualification', $teacher[3]);
            $db->bind(':specialization', $teacher[4]);
            $db->execute();
            
            echo "<p>✓ Added teacher: " . $teacher[0] . "</p>";
        }
    }
    
    // Add sample students
    $students = [
        ['student1', 'student1@school.com', 'S001', 1, 1],
        ['student2', 'student2@school.com', 'S002', 1, 1],
        ['student3', 'student3@school.com', 'S003', 1, 2],
        ['student4', 'student4@school.com', 'S004', 2, 3],
        ['student5', 'student5@school.com', 'S005', 2, 4]
    ];
    
    foreach($students as $student) {
        // Insert user
        $db->query("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'student')");
        $db->bind(':username', $student[0]);
        $db->bind(':email', $student[1]);
        $db->bind(':password', password_hash('password', PASSWORD_DEFAULT));
        
        if($db->execute()) {
            $userId = $db->lastInsertId();
            
            // Insert student record
            $db->query("INSERT INTO students (user_id, roll_number, class_id, section_id, admission_date, gender) VALUES (:user_id, :roll_number, :class_id, :section_id, '2024-04-01', 'male')");
            $db->bind(':user_id', $userId);
            $db->bind(':roll_number', $student[2]);
            $db->bind(':class_id', $student[3]);
            $db->bind(':section_id', $student[4]);
            $db->execute();
            
            echo "<p>✓ Added student: " . $student[0] . "</p>";
        }
    }
    
    echo "<h3>Sample Data Added Successfully!</h3>";
    echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
    
} catch(Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>