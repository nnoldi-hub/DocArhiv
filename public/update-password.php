<?php
/**
 * Script pentru actualizare parolă SuperAdmin
 * Rulează o singură dată apoi ȘTERGE fișierul!
 */

// Afișează toate erorile
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Setări DB pentru Hostico
$host = 'localhost';
$dbname = 'rbcjgzba_DocArhiv';
$username = 'rbcjgzba_nnoldi';
$password = 'PetreIonel205!';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Actualizare Parolă</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
.success { color: green; background: #d4edda; padding: 15px; border: 1px solid green; border-radius: 5px; }
.error { color: red; background: #f8d7da; padding: 15px; border: 1px solid red; border-radius: 5px; }
.info { color: blue; background: #d1ecf1; padding: 15px; border: 1px solid blue; border-radius: 5px; margin: 20px 0; }
pre { background: #f4f4f4; padding: 10px; border-left: 3px solid #333; }
</style>";
echo "</head><body>";

echo "<h1>🔐 Actualizare Parolă SuperAdmin</h1>";

try {
    // Conectare la baza de date
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p class='success'>✅ Conexiune la baza de date reușită!</p>";
    
    // Verifică user-ul curent
    echo "<h2>📋 Verificare User Curent</h2>";
    $stmt = $pdo->query("SELECT id, username, email, status FROM superadmin_users WHERE username = 'superadmin'");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<table border='1' cellpadding='10' cellspacing='0'>";
        echo "<tr><th>ID</th><td>{$user['id']}</td></tr>";
        echo "<tr><th>Username</th><td>{$user['username']}</td></tr>";
        echo "<tr><th>Email</th><td>{$user['email']}</td></tr>";
        echo "<tr><th>Status</th><td>{$user['status']}</td></tr>";
        echo "</table>";
        
        // Generează hash-ul noii parole
        $newPassword = 'ArhivaSuper0508!';
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        
        echo "<h2>🔑 Actualizare Parolă</h2>";
        echo "<p><strong>Noua parolă:</strong> $newPassword</p>";
        echo "<p><strong>Noul hash:</strong> <code>$newHash</code></p>";
        
        // Actualizează parola
        $stmt = $pdo->prepare("UPDATE superadmin_users SET password = :password WHERE username = 'superadmin'");
        $result = $stmt->execute(['password' => $newHash]);
        
        if ($result) {
            echo "<div class='success'>";
            echo "<h3>✅ SUCCESS! Parola a fost actualizată!</h3>";
            echo "<p><strong>Credențiale de autentificare:</strong></p>";
            echo "<ul>";
            echo "<li><strong>URL:</strong> <a href='https://gusturidelatara.ro/login.php' target='_blank'>https://gusturidelatara.ro/login.php</a></li>";
            echo "<li><strong>Username:</strong> superadmin</li>";
            echo "<li><strong>Password:</strong> admin123</li>";
            echo "</ul>";
            echo "</div>";
            
            // Verifică că noua parolă funcționează
            echo "<h2>✔️ Verificare Parolă Nouă</h2>";
            $stmt = $pdo->query("SELECT password FROM superadmin_users WHERE username = 'superadmin'");
            $updatedUser = $stmt->fetch();
            
            if (password_verify($newPassword, $updatedUser['password'])) {
                echo "<p class='success'>✅ <strong>VERIFICARE REUȘITĂ!</strong> Parola 'admin123' funcționează corect!</p>";
            } else {
                echo "<p class='error'>❌ EROARE: Verificarea parolei a eșuat!</p>";
            }
            
            // Instrucțiuni finale
            echo "<div class='info'>";
            echo "<h3>⚠️ IMPORTANT - Securitate!</h3>";
            echo "<p><strong>ACUM ȘTERGE ACEST FIȘIER IMEDIAT!</strong></p>";
            echo "<ol>";
            echo "<li>Mergi în cPanel File Manager</li>";
            echo "<li>Navighează la: <code>public_html/public/</code></li>";
            echo "<li>Șterge fișierul: <code>update-password.php</code></li>";
            echo "</ol>";
            echo "<p><strong>De asemenea, șterge și fișierele de test:</strong></p>";
            echo "<ul>";
            echo "<li>test-db-direct.php</li>";
            echo "<li>test-diagnostic.php</li>";
            echo "<li>login-debug.php</li>";
            echo "<li>setup-superadmin.php</li>";
            echo "<li>test-css.php</li>";
            echo "<li>asset-proxy.php</li>";
            echo "</ul>";
            echo "</div>";
            
        } else {
            echo "<p class='error'>❌ EROARE: Actualizarea a eșuat!</p>";
        }
        
    } else {
        echo "<p class='error'>❌ User 'superadmin' nu a fost găsit în baza de date!</p>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ EROARE PDO:</h3>";
    echo "<p><strong>Mesaj:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Cod:</strong> " . $e->getCode() . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ EROARE:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
