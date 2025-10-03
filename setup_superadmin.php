<?php
require_once 'config/config.php';

try {
    $db = new Database();
    
    // Verific dacă există tabelul superadmin_users
    $tables = $db->query("SHOW TABLES LIKE 'superadmin_users'")->fetchAll();
    if (empty($tables)) {
        echo "Tabelul superadmin_users nu există. Îl creez...\n";
        
        $createTable = "
        CREATE TABLE superadmin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            INDEX idx_username (username),
            INDEX idx_email (email)
        ) ENGINE=InnoDB
        ";
        
        $db->query($createTable)->execute();
        echo "Tabel creat cu succes!\n";
    }
    
    // Verific dacă există SuperAdmin
    $existing = $db->query("SELECT * FROM superadmin_users WHERE username = 'superadmin'")->fetch();
    if ($existing) {
        echo "SuperAdmin existe deja cu ID: " . $existing['id'] . "\n";
        echo "Username: " . $existing['username'] . "\n";
        echo "Email: " . $existing['email'] . "\n";
    } else {
        echo "Creez SuperAdmin...\n";
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $db->query("INSERT INTO superadmin_users (username, email, password, full_name) VALUES (?, ?, ?, ?)")
           ->bind(1, 'superadmin')
           ->bind(2, 'superadmin@arhiva.ro')
           ->bind(3, $password)
           ->bind(4, 'Super Administrator');
        
        if ($db->execute()) {
            echo "SuperAdmin creat cu succes!\n";
            echo "Username: superadmin\n";
            echo "Password: admin123\n";
        } else {
            echo "Eroare la crearea SuperAdmin!\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>