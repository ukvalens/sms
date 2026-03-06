<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $db->query("ALTER TABLE users ADD COLUMN photo VARCHAR(255) AFTER status");
    $db->execute();
    echo "SUCCESS: Photo column added to users table!";
} catch(Exception $e) {
    if(strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column already exists - no action needed.";
    } else {
        echo "ERROR: " . $e->getMessage();
    }
}
?>
