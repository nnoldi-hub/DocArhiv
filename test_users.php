<?php
require_once 'config/config.php';

echo "Testing DB connection...\n";
try {
    $db = new Database();
    echo "Connected successfully.\n";
    
    $users = $db->query('SELECT id, username, email, role FROM users WHERE role = 1')->fetchAll();
    echo "Found " . count($users) . " SuperAdmin users:\n";
    
    foreach ($users as $user) {
        echo "- Username: " . $user['username'] . ", Email: " . $user['email'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>