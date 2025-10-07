<?php
/**
 * Pagina de resetare parolă cu token
 * public/auth/reset-password.php
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
$validToken = false;
$token = $_GET['token'] ?? '';
$resetData = null;

// Verifică token-ul
if (!empty($token)) {
    try {
        $db = new Database();
        $hashedToken = hash('sha256', $token);
        
        $query = $db->query("
            SELECT pr.*, u.email, u.id as user_id, u.first_name, u.last_name 
            FROM password_resets pr 
            JOIN users u ON pr.user_id = u.id 
            WHERE pr.token = :token 
            AND pr.expires_at > NOW() 
            AND pr.used_at IS NULL
            LIMIT 1
        ");
        $query->bind(':token', $hashedToken);
        $resetData = $query->fetch();
        
        if ($resetData) {
            $validToken = true;
        } else {
            $errors['token'] = 'Token-ul de resetare este invalid sau a expirat.';
        }
        
    } catch (Exception $e) {
        logError("Token validation failed: " . $e->getMessage());
        $errors['token'] = 'Eroare la validarea token-ului.';
    }
} else {
    $errors['token'] = 'Token-ul de resetare lipsește.';
}

// Procesează formularul de resetare
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // Validez CSRF
    if (!validateCSRF()) {
        $errors['general'] = 'Token de securitate invalid. Reîncărcați pagina.';
    } else {
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        // Validez parola
        $passwordValidation = validatePassword($password);
        if (!$passwordValidation['valid']) {
            $errors['password'] = $passwordValidation['errors'];
        }
        
        // Verifică confirmarea parolei
        if (empty($errors['password']) && $password !== $passwordConfirm) {
            $errors['password_confirm'] = ['Parolele nu se potrivesc'];
        }
        
        // Rate limiting pentru încercări de resetare
        $clientIP = getClientIP();
        if (isRateLimited('password_reset_attempt', $clientIP, 5, 900)) { // 5 încercări în 15 min
            $errors['general'] = 'Prea multe încercări. Încercați din nou în 15 minute.';
        }
        
        if (empty($errors)) {
            recordAttempt('password_reset_attempt', $clientIP, 900);
            
            try {
                $db->beginTransaction();
                
                // Actualizează parola utilizatorului
                $userManager = new User();
                $updateResult = $userManager->update($resetData['user_id'], [
                    'password' => $password // va fi hash-uită în metodă
                ]);
                
                if ($updateResult) {
                    // Marchează token-ul ca folosit
                    $db->update('password_resets', 
                        ['used_at' => date('Y-m-d H:i:s')],
                        'id = :id',
                        [':id' => $resetData['id']]
                    );
                    
                    // Șterge toate token-urile remember me pentru acest utilizator
                    $db->delete('remember_tokens', 'user_id = :user_id', [':user_id' => $resetData['user_id']]);
                    
                    // Înregistrează activitatea
                    $db->insert('activity_logs', [
                        'company_id' => null, // poate fi setat dacă avem compania
                        'user_id' => $resetData['user_id'],
                        'action_type' => 'password_reset_completed',
                        'description' => 'Parola a fost resetată cu succes',
                        'ip_address' => $clientIP,
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Log evenimentul de securitate
                    logSecurityEvent('password_reset_completed', [
                        'user_id' => $resetData['user_id'],
                        'email' => $resetData['email']
                    ], 'medium');
                    
                    $db->commit();
                    $success = true;
                    
                } else {
                    throw new Exception('Nu s-a putut actualiza parola');
                }
                
            } catch (Exception $e) {
                $db->rollback();
                logError("Password reset completion failed: " . $e->getMessage());
                $errors['general'] = 'A apărut o eroare la resetarea parolei. Vă rugăm să încercați din nou.';
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
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-lock me-2"></i>
                        Setare Parolă Nouă
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (isset($errors['token'])): ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Token Invalid</h5>
                            <p class="mb-3"><?= htmlspecialchars($errors['token']) ?></p>
                            <div class="d-grid gap-2">
                                <a href="<?= APP_URL ?>/auth/forgot-password.php" class="btn btn-warning">
                                    <i class="fas fa-redo me-2"></i>
                                    Solicitați un nou token
                                </a>
                                <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Înapoi la Login
                                </a>
                            </div>
                        </div>
                        
                    <?php elseif ($success): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle me-2"></i>Parola a fost resetată!</h5>
                            <p class="mb-0">
                                Parola dvs. a fost actualizată cu succes. 
                                Acum vă puteți autentifica cu noua parolă.
                            </p>
                        </div>
                        
                        <div class="text-center">
                            <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Conectați-vă Acum
                            </a>
                        </div>
                        
                    <?php else: ?>
                        
                        <?php if ($resetData): ?>
                            <div class="alert alert-info">
                                <strong>Resetare parolă pentru:</strong><br>
                                <?= htmlspecialchars($resetData['first_name'] . ' ' . $resetData['last_name']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($resetData['email']) ?></small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($errors['general']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <?= csrfField() ?>
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Parolă Nouă <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                           id="password" 
                                           name="password" 
                                           required
                                           autofocus>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback">
                                            <?= implode('<br>', array_map('htmlspecialchars', $errors['password'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">
                                    Parola trebuie să aibă cel puțin <?= PASSWORD_MIN_LENGTH ?> caractere și să conțină 
                                    <?php if (PASSWORD_REQUIRE_UPPERCASE): ?>litere mari, <?php endif; ?>
                                    <?php if (PASSWORD_REQUIRE_LOWERCASE): ?>litere mici, <?php endif; ?>
                                    <?php if (PASSWORD_REQUIRE_NUMBERS): ?>cifre<?php endif; ?>
                                    <?php if (PASSWORD_REQUIRE_SYMBOLS): ?> și caractere speciale<?php endif; ?>.
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password_confirm" class="form-label">
                                    Confirmă Parola <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" 
                                           id="password_confirm" 
                                           name="password_confirm" 
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirm')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (isset($errors['password_confirm'])): ?>
                                        <div class="invalid-feedback">
                                            <?= implode('<br>', array_map('htmlspecialchars', $errors['password_confirm'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>
                                    Actualizează Parola
                                </button>
                            </div>
                        </form>
                        
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informații securitate -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-shield-alt me-2 text-success"></i>
                        Securitate
                    </h6>
                    <ul class="mb-0 small text-muted">
                        <li>Parola va fi criptată în baza de date</li>
                        <li>Toate sesiunile active vor fi invalidate</li>
                        <li>Cookie-urile "Remember me" vor fi șterse</li>
                        <li>Schimbarea parolei va fi înregistrată în jurnal</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>