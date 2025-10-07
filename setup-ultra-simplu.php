<?php
/**
 * SETUP ULTRA-SIMPLU - Bypass config.php
 * Configurare directă fără dependențe
 */

// Securitate
$secretKey = $_GET['key'] ?? '';
if ($secretKey !== 'setup2025hostico') {
    die("Access denied. Invalid key.");
}

// Afișează toate erorile
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>SETUP ULTRA-SIMPLU</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 30px auto; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
.success { color: #155724; background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 15px 0; }
.error { color: #721c24; background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 15px 0; }
.warning { color: #856404; background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 15px 0; }
.step { background: #e9ecef; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
th { background: #f8f9fa; }
.highlight { background: #fff3cd; font-weight: bold; }
</style>";
echo "</head><body><div class='container'>";

echo "<h1>🚀 SETUP ULTRA-SIMPLU - DocArhiv</h1>";
echo "<p>Configurare directă fără config.php</p>";

try {
    // Configurare directă
    $host = 'localhost';
    $dbname = 'rbcjgzba_DocArhiv';
    $username = 'rbcjgzba_nnoldi';
    $password = 'PetreIonel205!';
    
    echo "<div class='step'>";
    echo "<h3>📋 Credențiale DB</h3>";
    echo "<table>";
    echo "<tr><th>Host</th><td>$host</td></tr>";
    echo "<tr><th>Database</th><td>$dbname</td></tr>";
    echo "<tr><th>Username</th><td>$username</td></tr>";
    echo "<tr><th>Password</th><td>" . str_repeat('*', strlen($password)) . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Verifică extensiile PHP
    echo "<div class='step'>";
    echo "<h3>🔧 Verificare PHP</h3>";
    echo "<p><strong>Versiune PHP:</strong> " . phpversion() . "</p>";
    
    $extensions = ['pdo', 'pdo_mysql', 'mysqli', 'session'];
    foreach ($extensions as $ext) {
        $loaded = extension_loaded($ext);
        $status = $loaded ? "✅" : "❌";
        $color = $loaded ? "green" : "red";
        echo "<p style='color: $color'>$status <strong>$ext:</strong> " . ($loaded ? "Loaded" : "NOT loaded") . "</p>";
    }
    echo "</div>";
    
    // Test conexiune cu mysqli (dacă PDO nu merge)
    echo "<div class='step'>";
    echo "<h3>🔗 Test Conexiune DB</h3>";
    
    if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
        // Test cu PDO
        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            echo "<p class='success'>✅ Conexiune PDO reușită!</p>";
            $usesPDO = true;
        } catch (Exception $e) {
            echo "<p class='error'>❌ PDO Error: " . $e->getMessage() . "</p>";
            $usesPDO = false;
        }
    } else {
        $usesPDO = false;
    }
    
    if (!$usesPDO && extension_loaded('mysqli')) {
        // Test cu MySQLi
        $mysqli = new mysqli($host, $username, $password, $dbname);
        if ($mysqli->connect_error) {
            throw new Exception("MySQLi Error: " . $mysqli->connect_error);
        }
        echo "<p class='success'>✅ Conexiune MySQLi reușită!</p>";
        $usesMySQL = true;
    }
    
    if (!$usesPDO && !isset($usesMySQL)) {
        throw new Exception("Nici PDO nici MySQLi nu sunt disponibile!");
    }
    
    // Verifică și setează SuperAdmin
    echo "<h3>👤 Configurare SuperAdmin</h3>";
    
    if ($usesPDO) {
        // Folosește PDO
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM superadmin_users WHERE username = 'superadmin'");
        $result = $stmt->fetch();
        $userExists = $result['count'] > 0;
        
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        
        if (!$userExists) {
            $stmt = $pdo->prepare("
                INSERT INTO superadmin_users (username, email, password, full_name, status) 
                VALUES ('superadmin', 'superadmin@arhiva.ro', ?, 'Super Administrator', 'active')
            ");
            $stmt->execute([$hashedPassword]);
            echo "<p class='success'>✅ User SuperAdmin creat!</p>";
        } else {
            $stmt = $pdo->prepare("UPDATE superadmin_users SET password = ? WHERE username = 'superadmin'");
            $stmt->execute([$hashedPassword]);
            echo "<p class='success'>✅ Parola SuperAdmin actualizată!</p>";
        }
        
        // Verifică autentificarea
        $stmt = $pdo->query("SELECT password FROM superadmin_users WHERE username = 'superadmin'");
        $user = $stmt->fetch();
        
    } else {
        // Folosește MySQLi
        $result = $mysqli->query("SELECT COUNT(*) as count FROM superadmin_users WHERE username = 'superadmin'");
        $row = $result->fetch_assoc();
        $userExists = $row['count'] > 0;
        
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        
        if (!$userExists) {
            $stmt = $mysqli->prepare("
                INSERT INTO superadmin_users (username, email, password, full_name, status) 
                VALUES ('superadmin', 'superadmin@arhiva.ro', ?, 'Super Administrator', 'active')
            ");
            $stmt->bind_param('s', $hashedPassword);
            $stmt->execute();
            echo "<p class='success'>✅ User SuperAdmin creat!</p>";
        } else {
            $stmt = $mysqli->prepare("UPDATE superadmin_users SET password = ? WHERE username = 'superadmin'");
            $stmt->bind_param('s', $hashedPassword);
            $stmt->execute();
            echo "<p class='success'>✅ Parola SuperAdmin actualizată!</p>";
        }
        
        // Verifică autentificarea
        $result = $mysqli->query("SELECT password FROM superadmin_users WHERE username = 'superadmin'");
        $user = $result->fetch_assoc();
    }
    
    if (password_verify('admin123', $user['password'])) {
        echo "<div class='success'>";
        echo "<h3>🎉 SUCCESS COMPLET!</h3>";
        echo "<p><strong>Sistemul DocArhiv este configurat și funcțional!</strong></p>";
        echo "</div>";
        
        echo "<div class='step'>";
        echo "<h3>📝 Credențiale de Acces</h3>";
        echo "<table>";
        echo "<tr class='highlight'><th>URL Homepage</th><td><a href='/' target='_blank'>https://gusturidelatara.ro</a></td></tr>";
        echo "<tr class='highlight'><th>URL Login</th><td><a href='/login.php' target='_blank'>https://gusturidelatara.ro/login.php</a></td></tr>";
        echo "<tr class='highlight'><th>URL Public</th><td><a href='/public/' target='_blank'>https://gusturidelatara.ro/public/</a></td></tr>";
        echo "<tr class='highlight'><th>Username</th><td>superadmin</td></tr>";
        echo "<tr class='highlight'><th>Password</th><td>admin123</td></tr>";
        echo "</table>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<h3>⚠️ NEXT STEPS</h3>";
        echo "<ol>";
        echo "<li><strong>TESTEAZĂ LOGIN:</strong> <a href='/login.php' target='_blank'>Click aici pentru login</a></li>";
        echo "<li><strong>ȘTERGE acest fișier</strong> după test successful</li>";
        echo "<li><strong>Schimbă parola</strong> după primul login</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<p class='error'>❌ EROARE la verificarea parolei!</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ EROARE</h3>";
    echo "<p><strong>Mesaj:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></body></html>";