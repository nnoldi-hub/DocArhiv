<?php
require_once 'config/config.php';

try {
    $db = new Database();
    
    // Verific dacă există deja SuperAdmin
    $existing = $db->query("SELECT id FROM users WHERE username = 'superadmin' OR role = 1")->fetch();
    if ($existing) {
        echo "SuperAdmin already exists with ID: " . $existing['id'] . "\n";
        exit;
    }
    
    // Creez utilizatorul SuperAdmin
    $username = 'superadmin';
    $email = 'superadmin@example.com';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $full_name = 'Super Administrator';
    $role = ROLE_SUPERADMIN; // 1
    
    $db->query("INSERT INTO users (username, email, password, full_name, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())")
       ->bind(1, $username)
       ->bind(2, $email)
       ->bind(3, $password)
       ->bind(4, $full_name)
       ->bind(5, $role);
    $result = $db->execute();
    
    if ($result) {
        echo "SuperAdmin created successfully!\n";
        echo "Username: superadmin\n";
        echo "Password: admin123\n";
        echo "Email: superadmin@example.com\n";
    } else {
        echo "Error creating SuperAdmin\n";
        echo "Result: " . var_export($result, true) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>