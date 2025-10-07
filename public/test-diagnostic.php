<?php
/**
 * Test minimal pentru identificare eroare
 */

// Activează TOATE erorile
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test Diagnostic</title></head><body>";
echo "<h1>Test Diagnostic - DocArhiv</h1>";

// Test 1: PHP funcționează
echo "<h2>✓ Test 1: PHP funcționează</h2>";
echo "<p>Versiune PHP: " . phpversion() . "</p>";

// Test 2: Încarcă config
echo "<h2>Test 2: Încărcare config.php...</h2>";
try {
    require_once '../config/config.php';
    echo "<p style='color:green'>✓ Config.php încărcat cu succes!</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>✗ Eroare la încărcare config.php:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    die();
}

// Test 3: Conexiune bază de date
echo "<h2>Test 3: Conexiune bază de date...</h2>";
try {
    $db = new Database();
    echo "<p style='color:green'>✓ Conexiune la baza de date OK!</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>✗ Eroare conexiune bază de date:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    die();
}

// Test 4: Query simplu
echo "<h2>Test 4: Test query simplu...</h2>";
try {
    $result = $db->query("SELECT 1 as test")->fetch();
    echo "<p style='color:green'>✓ Query test OK: " . $result['test'] . "</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>✗ Eroare query:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    die();
}

// Test 5: Verifică tabelul superadmin_users
echo "<h2>Test 5: Verificare tabel superadmin_users...</h2>";
try {
    $tables = $db->query("SHOW TABLES LIKE 'superadmin_users'")->fetchAll();
    if (empty($tables)) {
        echo "<p style='color:orange'>⚠️ Tabelul superadmin_users NU există!</p>";
    } else {
        echo "<p style='color:green'>✓ Tabelul superadmin_users există</p>";
        
        // Test 6: Citește utilizatori
        echo "<h2>Test 6: Citire utilizatori SuperAdmin...</h2>";
        $users = $db->query("SELECT id, username, email, status FROM superadmin_users")->fetchAll();
        
        if (empty($users)) {
            echo "<p style='color:orange'>⚠️ Nu există utilizatori în superadmin_users!</p>";
        } else {
            echo "<p style='color:green'>✓ Găsiți " . count($users) . " utilizatori:</p>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Status</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['status']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Test 7: Test parola
        echo "<h2>Test 7: Test verificare parolă...</h2>";
        $testUser = $db->query("SELECT username, password FROM superadmin_users WHERE username = 'superadmin'")->fetch();
        
        if ($testUser) {
            echo "<p>Username găsit: <strong>" . htmlspecialchars($testUser['username']) . "</strong></p>";
            echo "<p>Hash parolă (primele 20 caractere): <code>" . htmlspecialchars(substr($testUser['password'], 0, 20)) . "...</code></p>";
            
            // Test verificare parolă
            $testPassword = 'admin123';
            if (password_verify($testPassword, $testUser['password'])) {
                echo "<p style='color:green'>✓ Verificare parolă 'admin123' - SUCCES!</p>";
            } else {
                echo "<p style='color:red'>✗ Verificare parolă 'admin123' - EȘUAT!</p>";
                echo "<p>Trebuie actualizată parola în phpMyAdmin cu:</p>";
                echo "<pre>UPDATE superadmin_users SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'superadmin';</pre>";
            }
        } else {
            echo "<p style='color:red'>✗ Nu s-a găsit utilizatorul 'superadmin'!</p>";
        }
    }
} catch (Throwable $e) {
    echo "<p style='color:red'>✗ Eroare la verificare tabel:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    die();
}

echo "<hr>";
echo "<h2>✓ Toate testele completate!</h2>";
echo "<p><a href='login-debug.php'>Încearcă pagina de login</a></p>";
echo "</body></html>";
?>