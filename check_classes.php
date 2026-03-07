<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/database.php';
$db = new Database();
$db->query("SELECT id, name FROM classes");
$classes = $db->resultset();
echo "Classes:\n";
foreach($classes as $c) {
    echo "ID: {$c['id']}, Name: {$c['name']}\n";
}
$db->query("SELECT id, name FROM sections");
$sections = $db->resultset();
echo "\nSections:\n";
foreach($sections as $s) {
    echo "ID: {$s['id']}, Name: {$s['name']}\n";
}
?>
