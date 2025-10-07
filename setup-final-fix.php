<?php
/**
 * SETUP FINAL - Actualizare Parolă SuperAdmin
 * Rulează DOAR ODATĂ după upload, apoi ȘTERGE fișierul!
 */

// Securitate - cheie secretă
$secretKey = $_GET['key'] ?? '';
if ($secretKey !== 'setup2025hostico') {
    die("Access denied. Invalid key.");
}

// Afișează toate erorile pentru debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>SETUP FINAL - Hostico</title>";
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

echo "<h1>🚀 SETUP FINAL - DocArhiv pe Hostico</h1>";
echo "<p>Configurare finală și setare parolă SuperAdmin</p>";

try {
    // Detectăm path-ul către config
    $configPaths = [
        __DIR__ . '/config/config.php',
        __DIR__ . '/../config/config.php',
        '/home/rbcjgzba/public_html/config/config.php'
    ];
    
    $configFound = false;
    foreach ($configPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $configFound = true;
            echo "<div class='success'>✅ Config.php găsit și încărcat: $path</div>";
            break;
        }
    }
    
    if (!$configFound) {
        throw new Exception("Config.php nu a fost găsit în niciunul din path-urile: " . implode(', ', $configPaths));
    }
    
    // Configurare manuală DB dacă config.php nu definește constantele
    if (!defined('DB_HOST')) {
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'rbcjgzba_DocArhiv');
        define('DB_USER', 'rbcjgzba_nnoldi');
        define('DB_PASS', 'PetreIonel205!');
        define('DB_CHARSET', 'utf8mb4');
        
        // Opțiuni PDO
        if (!defined('DB_OPTIONS')) {
            define('DB_OPTIONS', serialize([
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]));
        }
        
        echo "<div class='warning'>⚠️ Constantele DB definite manual</div>";
    }
    
    // Verifică constantele DB
    echo "<div class='step'>";
    echo "<h3>📋 Verificare Configurare DB</h3>";
    echo "<table>";
    echo "<tr><th>Constantă</th><th>Valoare</th></tr>";
    echo "<tr><td>DB_HOST</td><td>" . DB_HOST . "</td></tr>";
    echo "<tr><td>DB_NAME</td><td>" . DB_NAME . "</td></tr>";
    echo "<tr><td>DB_USER</td><td>" . DB_USER . "</td></tr>";
    echo "<tr><td>DB_PASS</td><td>" . str_repeat('*', strlen(DB_PASS)) . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Test conexiune direct
    echo "<div class='step'>";
    echo "<h3>🔗 Test Conexiune Baza de Date</h3>";
    
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = defined('DB_OPTIONS') ? unserialize(DB_OPTIONS) : [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<p class='success'>✅ Conexiune la baza de date reușită!</p>";
    
    // Verifică și creează user superadmin
    echo "<h3>👤 Verificare/Creare SuperAdmin</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM superadmin_users WHERE username = 'superadmin'");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        // Creează user superadmin
        echo "<p class='warning'>⚠️ User SuperAdmin nu există. Îl creez...</p>";
        
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO superadmin_users (username, email, password, full_name, status) 
            VALUES ('superadmin', 'superadmin@arhiva.ro', :password, 'Super Administrator', 'active')
        ");
        $stmt->execute(['password' => $hashedPassword]);
        
        echo "<p class='success'>✅ User SuperAdmin creat cu succes!</p>";
    } else {
        // Actualizează parola existing user
        echo "<p class='warning'>ℹ️ User SuperAdmin există deja. Actualizez parola...</p>";
        
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE superadmin_users SET password = :password WHERE username = 'superadmin'");
        $stmt->execute(['password' => $hashedPassword]);
        
        echo "<p class='success'>✅ Parola SuperAdmin actualizată!</p>";
    }
    
    // Verifică parola
    echo "<h3>🔐 Verificare Autentificare</h3>";
    $stmt = $pdo->query("SELECT * FROM superadmin_users WHERE username = 'superadmin'");
    $user = $stmt->fetch();
    
    if ($user && password_verify('admin123', $user['password'])) {
        echo "<div class='success'>";
        echo "<h3>🎉 SUCCESS COMPLET!</h3>";
        echo "<p><strong>Sistemul DocArhiv este configurat și funcțional!</strong></p>";
        echo "</div>";
        
        echo "<div class='step'>";
        echo "<h3>📝 Credențiale de Acces</h3>";
        echo "<table>";
        echo "<tr class='highlight'><th>URL Login</th><td><a href='/login.php' target='_blank'>https://gusturidelatara.ro/login.php</a></td></tr>";
        echo "<tr class='highlight'><th>URL Direct</th><td><a href='/' target='_blank'>https://gusturidelatara.ro</a></td></tr>";
        echo "<tr class='highlight'><th>Username</th><td>superadmin</td></tr>";
        echo "<tr class='highlight'><th>Password</th><td>admin123</td></tr>";
        echo "<tr><th>Rol</th><td>Super Administrator</td></tr>";
        echo "<tr><th>Email</th><td>superadmin@arhiva.ro</td></tr>";
        echo "</table>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<h3>⚠️ IMPORTANT - Securitate!</h3>";
        echo "<p><strong>ACȚIUNI OBLIGATORII DUPĂ ACEST SETUP:</strong></p>";
        echo "<ol>";
        echo "<li><strong>ȘTERGE IMEDIAT acest fișier</strong>: <code>setup-final.php</code></li>";
        echo "<li><strong>Testează login-ul</strong>: <a href='/login.php' target='_blank'>Încearcă aici</a></li>";
        echo "<li><strong>Schimbă parola</strong> 'admin123' cu una sigură după primul login</li>";
        echo "<li><strong>Verifică permisiunile</strong> pe folderele storage/</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<p class='error'>❌ EROARE: Verificarea parolei a eșuat!</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ EROARE</h3>";
    echo "<p><strong>Mesaj:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Fișier:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linia:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</div></body></html>";