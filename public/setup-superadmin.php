<?php
/**
 * Setup SuperAdmin pentru Hostico
 * Accesează: https://gusturidelatara.ro/public/setup-superadmin.php
 * După setup, șterge acest fișier!
 */

require_once '../config/config.php';

// Securitate de bază
$setup_key = $_GET['key'] ?? '';
if ($setup_key !== 'setup2025') {
    die('Acces interzis! Adaugă ?key=setup2025 în URL.');
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Setup SuperAdmin - DocArhiv</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h1 { color: #2563eb; }
    </style>
</head>
<body>
    <h1>🔧 Setup SuperAdmin - DocArhiv</h1>
    
    <?php
    try {
        $db = new Database();
        
        echo '<div class="info"><strong>📊 Verificare bază de date...</strong></div><br>';
        
        // Verifică conexiunea
        echo '<div class="success">✓ Conexiune la baza de date OK</div><br>';
        
        // Verifică tabelul superadmin_users
        $tables = $db->query("SHOW TABLES LIKE 'superadmin_users'")->fetchAll();
        
        if (empty($tables)) {
            echo '<div class="info">⚠️ Tabelul superadmin_users nu există. Îl creez...</div><br>';
            
            $createTable = "
            CREATE TABLE IF NOT EXISTS superadmin_users (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $db->query($createTable)->execute();
            echo '<div class="success">✓ Tabel superadmin_users creat cu succes!</div><br>';
        } else {
            echo '<div class="success">✓ Tabelul superadmin_users există deja</div><br>';
        }
        
        // Verifică dacă există SuperAdmin
        $existing = $db->query("SELECT * FROM superadmin_users WHERE username = 'superadmin'")->fetch();
        
        if ($existing) {
            echo '<div class="info">⚠️ SuperAdmin există deja. Îl actualizez cu parolă nouă...</div><br>';
            
            // Actualizează parola
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $db->query("UPDATE superadmin_users SET password = ?, status = 'active' WHERE username = 'superadmin'")
               ->bind(1, $password)
               ->execute();
            
            echo '<div class="success">✓ SuperAdmin actualizat!</div><br>';
        } else {
            echo '<div class="info">📝 Creez SuperAdmin nou...</div><br>';
            
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $db->query("INSERT INTO superadmin_users (username, email, password, full_name, status) VALUES (?, ?, ?, ?, 'active')")
               ->bind(1, 'superadmin')
               ->bind(2, 'superadmin@arhiva.ro')
               ->bind(3, $password)
               ->bind(4, 'Super Administrator')
               ->execute();
            
            echo '<div class="success">✓ SuperAdmin creat cu succes!</div><br>';
        }
        
        // Afișează credențiale
        echo '<div class="success">';
        echo '<h2>🎉 Setup Complet!</h2>';
        echo '<h3>Credențiale SuperAdmin:</h3>';
        echo '<pre>';
        echo "URL Login: https://gusturidelatara.ro/public/login.php\n";
        echo "Username: superadmin\n";
        echo "Password: admin123\n";
        echo '</pre>';
        echo '<p><strong>⚠️ IMPORTANT:</strong> După login, schimbă parola și <strong>șterge acest fișier (setup-superadmin.php)</strong> din server!</p>';
        echo '</div>';
        
        // Verifică și afișează utilizatorii existenți
        $users = $db->query("SELECT id, username, email, full_name, created_at, status FROM superadmin_users")->fetchAll();
        
        if ($users) {
            echo '<br><div class="info">';
            echo '<h3>📋 Utilizatori SuperAdmin în baza de date:</h3>';
            echo '<table border="1" cellpadding="10" style="width:100%; border-collapse: collapse;">';
            echo '<tr><th>ID</th><th>Username</th><th>Email</th><th>Nume Complet</th><th>Status</th><th>Creat la</th></tr>';
            foreach ($users as $user) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($user['id']) . '</td>';
                echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                echo '<td>' . htmlspecialchars($user['full_name']) . '</td>';
                echo '<td>' . htmlspecialchars($user['status']) . '</td>';
                echo '<td>' . htmlspecialchars($user['created_at']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">';
        echo '<h2>❌ Eroare:</h2>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    }
    ?>
    
    <br>
    <div class="info">
        <h3>🔗 Link-uri Utile:</h3>
        <ul>
            <li><a href="login.php">Pagină Login</a></li>
            <li><a href="../">Homepage</a></li>
        </ul>
    </div>
</body>
</html>