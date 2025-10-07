<?php
/**
 * Pagina de înregistrare
 * public/auth/register.php
 */

// Include configurarea și inițializarea
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/Database.php';
require_once BASE_PATH . '/includes/classes/User.php';
require_once BASE_PATH . '/includes/classes/Company.php';
require_once BASE_PATH . '/includes/classes/Auth.php';
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
$formData = [];
$success = false;

// Procesează formularul
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validez CSRF
    if (!validateCSRF()) {
        $errors['general'] = 'Token de securitate invalid. Reîncărcați pagina.';
    } else {
        // Colectez datele
        $formData = [
            'company_name' => sanitizeXSS($_POST['company_name'] ?? ''),
            'admin_first_name' => sanitizeXSS($_POST['admin_first_name'] ?? ''),
            'admin_last_name' => sanitizeXSS($_POST['admin_last_name'] ?? ''),
            'admin_email' => sanitizeXSS($_POST['admin_email'] ?? ''),
            'admin_phone' => sanitizeXSS($_POST['admin_phone'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ];
        
        // Validează formularul
        $validation = validateForm($formData, [
            'company_name' => [
                'required' => true,
                'min_length' => 2,
                'max_length' => 100,
                'custom' => 'validateCompanyName'
            ],
            'admin_first_name' => [
                'required' => true,
                'min_length' => 2,
                'max_length' => 50,
                'custom' => function($value) {
                    return validatePersonName($value, 'Prenumele');
                }
            ],
            'admin_last_name' => [
                'required' => true,
                'min_length' => 2,
                'max_length' => 50,
                'custom' => function($value) {
                    return validatePersonName($value, 'Numele');
                }
            ],
            'admin_email' => [
                'required' => true,
                'email' => true
            ],
            'admin_phone' => [
                'custom' => 'validatePhone'
            ],
            'password' => [
                'required' => true,
                'password' => true
            ]
        ]);
        
        $errors = $validation['errors'];
        
        // Verifică confirmarea parolei
        if (empty($errors['password']) && $formData['password'] !== $formData['password_confirm']) {
            $errors['password_confirm'] = ['Parolele nu se potrivesc'];
        }
        
        // Verifică dacă email-ul există deja
        if (empty($errors['admin_email'])) {
            $userManager = new User();
            if ($userManager->getByEmail($formData['admin_email'])) {
                $errors['admin_email'] = ['Acest email este deja folosit'];
            }
        }
        
        // Verifică dacă numele companiei există deja
        if (empty($errors['company_name'])) {
            $companyManager = new Company();
            if ($companyManager->getByName($formData['company_name'])) {
                $errors['company_name'] = ['Numele companiei este deja folosit'];
            }
        }
        
        // Înregistrează dacă nu sunt erori
        if (empty($errors)) {
            try {
                $db = new Database();
                $db->beginTransaction();
                
                // Creez compania
                $companyManager = new Company();
                $companyData = [
                    'name' => $formData['company_name'],
                    'status' => 'active',
                    'subscription_type' => 'free',
                    'max_users' => 5,
                    'max_storage_gb' => MAX_STORAGE_PER_COMPANY_GB,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $companyId = $companyManager->create($companyData);
                
                if (!$companyId) {
                    throw new Exception('Nu s-a putut crea compania');
                }
                
                // Creez administratorul companiei
                $userManager = new User();
                $userData = [
                    'company_id' => $companyId,
                    'email' => $formData['admin_email'],
                    'password' => password_hash($formData['password'], PASSWORD_DEFAULT),
                    'first_name' => $formData['admin_first_name'],
                    'last_name' => $formData['admin_last_name'],
                    'phone' => $formData['admin_phone'],
                    'role' => ROLE_ADMIN,
                    'status' => 'active',
                    'email_verified' => 1, // Presupunem că e verificat
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $userId = $userManager->create($userData);
                
                if (!$userId) {
                    throw new Exception('Nu s-a putut crea utilizatorul administrator');
                }
                
                // Creez directorul principal pentru companie
                $mainFolder = [
                    'company_id' => $companyId,
                    'name' => 'Documente',
                    'description' => 'Directorul principal pentru documente',
                    'parent_id' => null,
                    'created_by' => $userId,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $folderId = $db->insert('folders', $mainFolder);
                
                // Înregistrez activitatea
                $activityData = [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'action_type' => 'company_created',
                    'description' => 'Compania și contul administrator au fost create',
                    'ip_address' => getClientIP(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $db->insert('activity_logs', $activityData);
                
                $db->commit();
                
                // Log evenimentul
                logSecurityEvent('successful_registration', [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'email' => $formData['admin_email']
                ], 'medium');
                
                $success = true;
                
            } catch (Exception $e) {
                $db->rollback();
                logError("Registration failed: " . $e->getMessage());
                $errors['general'] = 'A apărut o eroare la înregistrare. Vă rugăm să încercați din nou.';
            }
        }
    }
}

$pageTitle = 'Înregistrare Companie';
include BASE_PATH . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        Înregistrare Companie
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle me-2"></i>Înregistrare reușită!</h5>
                            <p class="mb-0">
                                Compania a fost creată cu succes. Puteți să vă 
                                <a href="<?= APP_URL ?>/auth/login.php" class="alert-link">autentificați acum</a>.
                            </p>
                        </div>
                    <?php else: ?>
                        
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($errors['general']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <?= csrfField() ?>
                            
                            <!-- Informații Companie -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-building me-2"></i>
                                    Informații Companie
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">
                                        Numele Companiei <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control <?= isset($errors['company_name']) ? 'is-invalid' : '' ?>" 
                                           id="company_name" 
                                           name="company_name" 
                                           value="<?= htmlspecialchars($formData['company_name'] ?? '') ?>"
                                           required>
                                    <?php if (isset($errors['company_name'])): ?>
                                        <div class="invalid-feedback">
                                            <?= implode('<br>', array_map('htmlspecialchars', $errors['company_name'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Informații Administrator -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-user-tie me-2"></i>
                                    Informații Administrator
                                </h6>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="admin_first_name" class="form-label">
                                            Prenume <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control <?= isset($errors['admin_first_name']) ? 'is-invalid' : '' ?>" 
                                               id="admin_first_name" 
                                               name="admin_first_name" 
                                               value="<?= htmlspecialchars($formData['admin_first_name'] ?? '') ?>"
                                               required>
                                        <?php if (isset($errors['admin_first_name'])): ?>
                                            <div class="invalid-feedback">
                                                <?= implode('<br>', array_map('htmlspecialchars', $errors['admin_first_name'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="admin_last_name" class="form-label">
                                            Nume <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control <?= isset($errors['admin_last_name']) ? 'is-invalid' : '' ?>" 
                                               id="admin_last_name" 
                                               name="admin_last_name" 
                                               value="<?= htmlspecialchars($formData['admin_last_name'] ?? '') ?>"
                                               required>
                                        <?php if (isset($errors['admin_last_name'])): ?>
                                            <div class="invalid-feedback">
                                                <?= implode('<br>', array_map('htmlspecialchars', $errors['admin_last_name'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="admin_email" class="form-label">
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" 
                                           class="form-control <?= isset($errors['admin_email']) ? 'is-invalid' : '' ?>" 
                                           id="admin_email" 
                                           name="admin_email" 
                                           value="<?= htmlspecialchars($formData['admin_email'] ?? '') ?>"
                                           required>
                                    <?php if (isset($errors['admin_email'])): ?>
                                        <div class="invalid-feedback">
                                            <?= implode('<br>', array_map('htmlspecialchars', $errors['admin_email'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="admin_phone" class="form-label">Telefon</label>
                                    <input type="tel" 
                                           class="form-control <?= isset($errors['admin_phone']) ? 'is-invalid' : '' ?>" 
                                           id="admin_phone" 
                                           name="admin_phone" 
                                           value="<?= htmlspecialchars($formData['admin_phone'] ?? '') ?>">
                                    <?php if (isset($errors['admin_phone'])): ?>
                                        <div class="invalid-feedback">
                                            <?= implode('<br>', array_map('htmlspecialchars', $errors['admin_phone'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Parolă -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-lock me-2"></i>
                                    Securitate
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        Parolă <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                               id="password" 
                                               name="password" 
                                               required>
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
                                
                                <div class="mb-3">
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
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    Creează Compania
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">
                                Aveți deja un cont? 
                                <a href="<?= APP_URL ?>/auth/login.php" class="text-decoration-none">
                                    Autentificați-vă aici
                                </a>
                            </p>
                        </div>
                        
                    <?php endif; ?>
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