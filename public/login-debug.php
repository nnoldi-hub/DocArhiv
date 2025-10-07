<?php
/**
 * Pagină Login Simplificată cu Error Handling
 * public/login-debug.php
 */

// Activează error reporting pentru debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- Debug: Start loading config -->\n";

try {
    require_once '../config/config.php';
    echo "<!-- Debug: Config loaded successfully -->\n";
} catch (Exception $e) {
    die("Error loading config: " . $e->getMessage());
}

echo "<!-- Debug: Checking if logged in -->\n";

// Verificare simplă dacă este autentificat
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    echo "<!-- Debug: User is logged in, redirecting -->\n";
    if ($_SESSION['role'] === ROLE_SUPERADMIN || (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'])) {
        header('Location: superadmin-dashboard.php');
        exit;
    } else {
        header('Location: admin-dashboard.php');
        exit;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<!-- Debug: Processing POST request -->\n";
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Vă rugăm completați toate câmpurile!';
    } else {
        try {
            $db = new Database();
            echo "<!-- Debug: Database connected -->\n";
            
            // Verificare SuperAdmin
            $stmt = $db->query("
                SELECT * FROM superadmin_users 
                WHERE (username = ? OR email = ?) 
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->bind(1, $username);
            $stmt->bind(2, $username);
            $superadmin = $stmt->fetch();
            
            echo "<!-- Debug: SuperAdmin query executed -->\n";
            
            if ($superadmin) {
                echo "<!-- Debug: SuperAdmin found: " . htmlspecialchars($superadmin['username']) . " -->\n";
                
                if (password_verify($password, $superadmin['password'])) {
                    echo "<!-- Debug: Password verified successfully -->\n";
                    
                    // Login SuperAdmin
                    $_SESSION['user_id'] = $superadmin['id'];
                    $_SESSION['username'] = $superadmin['username'];
                    $_SESSION['full_name'] = $superadmin['full_name'];
                    $_SESSION['role'] = ROLE_SUPERADMIN;
                    $_SESSION['is_superadmin'] = true;
                    
                    // Update last login
                    try {
                        $updateStmt = $db->query("UPDATE superadmin_users SET last_login = NOW() WHERE id = ?");
                        $updateStmt->bind(1, $superadmin['id']);
                        $updateStmt->execute();
                    } catch (Exception $e) {
                        // Ignorăm eroarea de update, e non-critical
                    }
                    
                    $success = 'Login successful! Redirecting...';
                    header('Location: superadmin-dashboard.php');
                    exit;
                } else {
                    echo "<!-- Debug: Password verification failed -->\n";
                    $error = 'Parola incorectă!';
                }
            } else {
                echo "<!-- Debug: SuperAdmin not found, checking regular users -->\n";
                
                // Verificare utilizator normal
                $stmt = $db->query("
                    SELECT u.*, c.id as company_id, c.name as company_name 
                    FROM users u
                    INNER JOIN companies c ON u.company_id = c.id
                    WHERE u.username = ? AND u.status = 'active' AND c.status = 'active'
                    LIMIT 1
                ");
                $stmt->bind(1, $username);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    echo "<!-- Debug: Regular user logged in -->\n";
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['company_id'] = $user['company_id'];
                    $_SESSION['company_name'] = $user['company_name'];
                    $_SESSION['is_superadmin'] = false;
                    
                    $success = 'Login successful! Redirecting...';
                    header('Location: admin-dashboard.php');
                    exit;
                } else {
                    $error = 'Credențiale incorecte!';
                }
            }
            
        } catch (Exception $e) {
            $error = 'Eroare la autentificare: ' . $e->getMessage();
            echo "<!-- Debug Error: " . htmlspecialchars($e->getMessage()) . " -->\n";
            echo "<!-- Debug Trace: " . htmlspecialchars($e->getTraceAsString()) . " -->\n";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare - Arhiva Documente</title>
    <?php require_once '../includes/functions/assets.php'; renderBootstrapAssets(); ?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo i {
            font-size: 60px;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <i class="bi bi-archive-fill"></i>
            <h3 class="mt-2">Arhiva Documente</h3>
            <p class="text-muted">Autentificare</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Utilizator / Email</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Parolă</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Ține-mă minte</label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Autentificare
            </button>
        </form>
        
        <div class="text-center mt-3">
            <a href="/" class="text-muted"><i class="bi bi-arrow-left me-1"></i>Înapoi la pagina principală</a>
        </div>
        
        <hr class="my-3">
        
        <div class="text-center">
            <small class="text-muted">Test Credentials:<br>
            <strong>superadmin</strong> / <strong>admin123</strong></small>
        </div>
    </div>
    
    <?php renderBootstrapJS(); ?>
</body>
</html>