<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config/database.php';
$db = new Database();
$db->query("DESCRIBE fee_payments");
$columns = $db->resultset();
echo "fee_payments columns:\n";
foreach($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}
?>
