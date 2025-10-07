<?php
/**
 * Test Direct Conexiune Bază de Date
 * Testează conexiunea direct fără să folosească Database class
 */

// Afișează toate erorile
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Test DB Direct</title></head><body>";
echo "<h1>Test Conexiune Directă Bază de Date</h1>";

// Setări DB pentru Hostico
$host = 'localhost';
$dbname = 'rbcjgzba_DocArhiv';
$username = 'rbcjgzba_nnoldi';
$password = 'PetreIonel205!';

// Test alternative database names
$possibleDbNames = [
    'rbcjgzba_DocArhiv',
    'rbcjgzba_docarhiv',
    'rbcjgzba_DocArhiv',
    'DocArhiv',
    'docarhiv'
];

echo "<h2>Credențiale Folosite:</h2>";
echo "<ul>";
echo "<li><strong>Host:</strong> $host</li>";
echo "<li><strong>Database:</strong> $dbname</li>";
echo "<li><strong>Username:</strong> $username</li>";
echo "<li><strong>Password:</strong> " . str_repeat('*', strlen($password)) . "</li>";
echo "</ul>";

echo "<hr>";

// Test 1: Test conexiune basic
echo "<h2>Test 1: Conexiune PDO Basic</h2>";
try {
    $dsn = "mysql:host=$host;charset=utf8mb4";
    echo "<p>DSN: $dsn</p>";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p style='color: green;'>✅ <strong>Conexiune la MySQL Server reușită!</strong></p>";
    
    // Test 2: Verifică dacă baza de date există
    echo "<h2>Test 2: Lista Toate Bazele de Date</h2>";
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Găsite <strong>" . count($databases) . "</strong> baze de date:</p>";
    echo "<ul>";
    foreach ($databases as $db) {
        $highlight = (stripos($db, 'DocArhiv') !== false || stripos($db, 'docarhiv') !== false) ? ' style="color: red; font-weight: bold;"' : '';
        echo "<li$highlight>$db</li>";
    }
    echo "</ul>";
    
    // Test 3: Găsește numele corect
    echo "<h2>Test 3: Testează Nume Posibile</h2>";
    $correctDbName = null;
    foreach ($possibleDbNames as $testName) {
        $stmt = $pdo->query("SHOW DATABASES LIKE '$testName'");
        $result = $stmt->fetch();
        if ($result) {
            echo "<p style='color: green;'>✅ Găsit: <strong>$testName</strong></p>";
            $correctDbName = $testName;
            break;
        } else {
            echo "<p style='color: gray;'>❌ Nu există: $testName</p>";
        }
    }
    
    if (!$correctDbName) {
        echo "<p style='color: red;'>❌ Nicio variantă nu funcționează! Vezi lista de mai sus pentru numele exact.</p>";
        exit;
    }
    
    $dbname = $correctDbName;
    
    echo "<h2>Test 4: Verifică Baza de Date Corectă</h2>";
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "<p style='color: green;'>✅ <strong>Baza de date '$dbname' există și e accesibilă!</strong></p>";
        
        // Test 5: Conectare la baza de date specifică
        echo "<h2>Test 5: Conectare la Baza de Date Specifică</h2>";
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        echo "<p>DSN: $dsn</p>";
        
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        echo "<p style='color: green;'>✅ <strong>Conexiune la baza de date '$dbname' reușită!</strong></p>";
        
        // Test 6: Verifică tabelele
        echo "<h2>Test 6: Liste Tabele</h2>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<p>Găsite <strong>" . count($tables) . "</strong> tabele:</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
            
            // Test 7: Verifică tabelul superadmin_users
            echo "<h2>Test 7: Verifică Tabelul superadmin_users</h2>";
            if (in_array('superadmin_users', $tables)) {
                echo "<p style='color: green;'>✅ <strong>Tabelul 'superadmin_users' există!</strong></p>";
                
                // Test 8: Citește userii
                echo "<h2>Test 8: Citește Useri din superadmin_users</h2>";
                $stmt = $pdo->query("SELECT id, username, email, is_active, created_at FROM superadmin_users");
                $users = $stmt->fetchAll();
                
                if (count($users) > 0) {
                    echo "<p>Găsiți <strong>" . count($users) . "</strong> utilizatori:</p>";
                    echo "<table border='1' cellpadding='5' cellspacing='0'>";
                    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Active</th><th>Created</th></tr>";
                    foreach ($users as $user) {
                        echo "<tr>";
                        echo "<td>{$user['id']}</td>";
                        echo "<td>{$user['username']}</td>";
                        echo "<td>{$user['email']}</td>";
                        echo "<td>" . ($user['is_active'] ? 'Da' : 'Nu') . "</td>";
                        echo "<td>{$user['created_at']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    
                    // Test 9: Verifică parola
                    echo "<h2>Test 9: Test Autentificare</h2>";
                    $stmt = $pdo->prepare("SELECT * FROM superadmin_users WHERE username = :username");
                    $stmt->execute(['username' => 'superadmin']);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        echo "<p>✅ User 'superadmin' găsit!</p>";
                        echo "<p><strong>Hash parola din DB:</strong> " . substr($user['password'], 0, 30) . "...</p>";
                        
                        $testPassword = 'admin123';
                        $hashToTest = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
                        
                        echo "<p><strong>Parola de test:</strong> $testPassword</p>";
                        echo "<p><strong>Hash așteptat:</strong> $hashToTest</p>";
                        
                        if (password_verify($testPassword, $user['password'])) {
                            echo "<p style='color: green; font-size: 20px;'>✅ <strong>AUTENTIFICARE REUȘITĂ!</strong></p>";
                            echo "<p>Parola 'admin123' e corectă pentru user 'superadmin'!</p>";
                        } else {
                            echo "<p style='color: red;'>❌ <strong>Parola NU se potrivește!</strong></p>";
                            echo "<p>Hash-ul din DB nu corespunde cu 'admin123'</p>";
                            echo "<p>Trebuie să actualizezi parola în phpMyAdmin cu:</p>";
                            echo "<pre>UPDATE superadmin_users SET password = '$hashToTest' WHERE username = 'superadmin';</pre>";
                        }
                    } else {
                        echo "<p style='color: red;'>❌ User 'superadmin' NU există!</p>";
                    }
                } else {
                    echo "<p style='color: orange;'>⚠️ Tabelul 'superadmin_users' e gol!</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Tabelul 'superadmin_users' NU există!</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Baza de date nu conține tabele!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ <strong>Baza de date '$dbname' NU există!</strong></p>";
        echo "<p>Verifică în cPanel dacă baza de date a fost creată corect.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ <strong>EROARE PDO:</strong></p>";
    echo "<pre style='background: #ffeeee; padding: 10px; border: 1px solid red;'>";
    echo "Cod eroare: " . $e->getCode() . "\n";
    echo "Mesaj: " . $e->getMessage() . "\n";
    echo "Fișier: " . $e->getFile() . "\n";
    echo "Linia: " . $e->getLine() . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>EROARE:</strong></p>";
    echo "<pre style='background: #ffeeee; padding: 10px; border: 1px solid red;'>";
    echo $e->getMessage();
    echo "</pre>";
}

echo "</body></html>";
