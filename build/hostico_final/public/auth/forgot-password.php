<?php
/**
 * Pagina de resetare parolă
 * public/auth/forgot-password.php
 */

// Include configurarea și inițializarea
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/Database.php';
require_once BASE_PATH . '/includes/classes/User.php';
require_once BASE_PATH . '/includes/functions/helpers.php';
require_once BASE_PATH . '/includes/functions/validation.php';
require_once BASE_PATH . '/includes/functions/security.php';

// Aplicăm middleware-ul de securitate
securityMiddleware();

// Redirect dacă utilizatorul este deja logat
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    redirect('/dashboard');
}

$errors = [];
$success = false;
$email = '';

// Procesează formularul
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validez CSRF
    if (!validateCSRF()) {
        $errors['general'] = 'Token de securitate invalid. Reîncărcați pagina.';
    } else {
        $email = sanitizeXSS($_POST['email'] ?? '');
        
        // Validez email-ul
        $emailValidation = validateEmail($email);
        if (!$emailValidation['valid']) {
            $errors['email'] = $emailValidation['message'];
        }
        
        // Rate limiting pentru cereri de resetare
        $clientIP = getClientIP();
        if (isRateLimited('password_reset', $clientIP, 5, 3600)) { // 5 încercări pe oră
            $errors['general'] = 'Prea multe cereri de resetare. Încercați din nou în 1 oră.';
        }
        
        if (empty($errors)) {
            recordAttempt('password_reset', $clientIP, 3600);
            
            try {
                $userManager = new User();
                $user = $userManager->getByEmail($email);
                
                if ($user) {
                    // Generează token de resetare
                    $resetToken = bin2hex(random_bytes(32));
                    $resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Salvează token-ul în baza de date
                    $db = new Database();
                    $db->insert('password_resets', [
                        'user_id' => $user['id'],
                        'email' => $email,
                        'token' => hash('sha256', $resetToken),
                        'expires_at' => $resetExpires,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Șterge token-urile vechi pentru acest utilizator
                    $db->delete('password_resets', 
                        'user_id = :user_id AND created_at < :cutoff',
                        [
                            ':user_id' => $user['id'],
                            ':cutoff' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                        ]
                    );
                    
                    // În aplicația reală, aici ar fi trimiterea email-ului
                    // Pentru demo, afișăm link-ul direct
                    if (DEBUG_MODE) {
                        $resetLink = APP_URL . '/auth/reset-password.php?token=' . $resetToken;
                        $_SESSION['debug_reset_link'] = $resetLink;
                    }
                    
                    // Log evenimentul
                    logSecurityEvent('password_reset_requested', [
                        'user_id' => $user['id'],
                        'email' => $email
                    ], 'medium');
                    
                    // Înregistrează activitatea
                    $db->insert('activity_logs', [
                        'company_id' => $user['company_id'],
                        'user_id' => $user['id'],
                        'action_type' => 'password_reset_request',
                        'description' => 'Cerere de resetare parolă',
                        'ip_address' => $clientIP,
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
                
                // Întotdeauna afișăm mesajul de succes pentru securitate
                // (să nu dezvăluim dacă email-ul există sau nu)
                $success = true;
                
            } catch (Exception $e) {
                logError("Password reset failed: " . $e->getMessage());
                $errors['general'] = 'A apărut o eroare. Vă rugăm să încercați din nou.';
            }
        }
    }
}

$pageTitle = 'Resetare Parolă';
include BASE_PATH . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-key me-2"></i>
                        Resetare Parolă
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle me-2"></i>Cerere trimisă!</h5>
                            <p class="mb-0">
                                Dacă adresa de email există în sistem, veți primi instrucțiuni 
                                pentru resetarea parolei în câteva minute.
                            </p>
                            
                            <?php if (DEBUG_MODE && isset($_SESSION['debug_reset_link'])): ?>
                                <hr>
                                <div class="alert alert-info mt-3">
                                    <strong>Debug Mode:</strong> 
                                    <a href="<?= htmlspecialchars($_SESSION['debug_reset_link']) ?>" 
                                       class="alert-link" target="_blank">
                                        Link resetare parolă
                                    </a>
                                </div>
                                <?php unset($_SESSION['debug_reset_link']); ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-center">
                            <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Înapoi la Login
                            </a>
                        </div>
                        
                    <?php else: ?>
                        
                        <p class="text-muted mb-4">
                            Introduceți adresa de email asociată contului dvs. și vă vom 
                            trimite instrucțiuni pentru resetarea parolei.
                        </p>
                        
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($errors['general']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <?= csrfField() ?>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    Adresa de Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($email) ?>"
                                       placeholder="exemplu@email.com"
                                       required
                                       autofocus>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback">
                                        <?= htmlspecialchars($errors['email']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Trimite Instrucțiuni
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <p class="mb-2">
                                Vă amintiți parola? 
                                <a href="<?= APP_URL ?>/auth/login.php" class="text-decoration-none">
                                    Conectați-vă aici
                                </a>
                            </p>
                            
                            <p class="mb-0">
                                Nu aveți cont? 
                                <a href="<?= APP_URL ?>/auth/register.php" class="text-decoration-none">
                                    Înregistrați-vă aici
                                </a>
                            </p>
                        </div>
                        
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informații securitate -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-shield-alt me-2 text-primary"></i>
                        Informații de Securitate
                    </h6>
                    <ul class="mb-0 small text-muted">
                        <li>Link-ul de resetare este valabil doar 1 oră</li>
                        <li>Puteți solicita maximum 5 resetări pe oră</li>
                        <li>Link-ul poate fi folosit o singură dată</li>
                        <li>Toate încercările de resetare sunt înregistrate</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>