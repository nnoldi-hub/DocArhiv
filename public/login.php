<?php
/**
 * Pagină Login
 * public/login.php
 */

require_once '../config/config.php';

// Dacă este deja autentificat, redirect
if (isLoggedIn()) {
    if (hasRole(ROLE_SUPERADMIN)) {
        redirect('/superadmin-dashboard.php');
    } else {
        redirect('/admin-dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Vă rugăm completați toate câmpurile!';
    } else {
        $db = new Database();
        
        // Verificare SuperAdmin
        $superadmin = $db->query("
            SELECT * FROM superadmin_users 
            WHERE (username = :username OR email = :email) 
            AND status = 'active'
        ")
        ->bind(':username', $username)
        ->bind(':email', $username)
        ->fetch();
        
        if ($superadmin && password_verify($password, $superadmin['password'])) {
            // Login SuperAdmin
            $_SESSION['user_id'] = $superadmin['id'];
            $_SESSION['username'] = $superadmin['username'];
            $_SESSION['full_name'] = $superadmin['full_name'];
            $_SESSION['role'] = ROLE_SUPERADMIN;
            $_SESSION['is_superadmin'] = true;
            
            // Update last login
            $db->update('superadmin_users', 
                ['last_login' => date('Y-m-d H:i:s')],
                'id = :id',
                [':id' => $superadmin['id']]
            );
            
            redirect('/superadmin-dashboard.php');
        }
        
        // Verificare User Normal
        $user = $db->query("
            SELECT u.*, c.name as company_name, c.status as subscription_status 
            FROM users u
            INNER JOIN companies c ON u.company_id = c.id
            WHERE (u.username = :username OR u.email = :email) 
            AND u.status = 'active'
        ")
        ->bind(':username', $username)
        ->bind(':email', $username)
        ->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Verificare status subscripție
            if ($user['subscription_status'] === 'suspended' || $user['subscription_status'] === 'inactive') {
                $error = 'Contul companiei este suspendat sau inactiv. Contactați administratorul!';
            } else {
                // Login User
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['company_id'] = $user['company_id'];
                $_SESSION['company_name'] = $user['company_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];
                $_SESSION['permissions'] = json_decode($user['permissions'] ?? '[]', true);
                
                // Update last login
                $db->update('users', 
                    ['last_login' => date('Y-m-d H:i:s')],
                    'id = :id',
                    [':id' => $user['id']]
                );
                
                // Log activitate
                $db->insert('activity_logs', [
                    'company_id' => $user['company_id'],
                    'user_id' => $user['id'],
                    'action_type' => 'login',
                    'description' => 'Utilizator autentificat',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
                
                redirect('/admin-dashboard.php');
            }
        } else {
            $error = 'Credențiale incorecte!';
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
    <?php 
    // Include helper pentru assets local
    require_once '../includes/functions/assets.php';
    renderBootstrapAssets();
    ?>
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
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        
        .login-left {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 60px 40px;
        }
        
        .login-right {
            padding: 60px 40px;
        }
        
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37,99,235,0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37,99,235,0.4);
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="row g-0">
                <div class="col-md-5 login-left">
                    <div class="text-center mb-4">
                        <i class="bi bi-archive-fill" style="font-size: 4rem;"></i>
                        <h2 class="mt-3">Arhiva Documente</h2>
                        <p class="mb-0">Sistem profesional de arhivare electronică</p>
                    </div>
                    
                    <div class="mt-5">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <strong>Securitate Maximă</strong>
                                <p class="mb-0 small">Documentele tale sunt protejate</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-cloud"></i>
                            </div>
                            <div>
                                <strong>Cloud Storage</strong>
                                <p class="mb-0 small">Accesează de oriunde</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-search"></i>
                            </div>
                            <div>
                                <strong>Căutare Avansată</strong>
                                <p class="mb-0 small">Găsește orice document instant</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-7 login-right">
                    <h3 class="mb-4">Bine ai revenit!</h3>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nume utilizator sau Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control" 
                                       placeholder="Introduceți username sau email" required autofocus>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Parolă</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="Introduceți parola" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                Ține-mă minte
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Autentificare
                        </button>
                        
                        <div class="text-center">
                            <a href="forgot-password.php" class="text-muted text-decoration-none">
                                Ai uitat parola?
                            </a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="text-muted mb-2">Nu ai cont încă?</p>
                            <a href="register.php" class="btn btn-outline-primary">
                                <i class="bi bi-person-plus me-2"></i>Creează Cont Nou
                            </a>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <a href="/" class="text-decoration-none">← Înapoi la pagina principală</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>