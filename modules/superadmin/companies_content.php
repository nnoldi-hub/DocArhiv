<?php
// Procesarea formularelor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== POST REQUEST RECEIVED ===");
    error_log("POST Data: " . print_r($_POST, true));
    
    try {
        $action = sanitizeInput($_POST['action']);
        error_log("Action received: " . $action);
        $db = new Database();

        if (!function_exists('generateSecurePassword')) {
            function generateSecurePassword($length = 12) {
                $sets = [
                    'upper' => 'ABCDEFGHJKMNPQRSTUVWXYZ',
                    'lower' => 'abcdefghjkmnpqrstuvwxyz',
                    'digits' => '23456789',
                    'symbols' => '!@#$%^&*()_+-=' // simboluri sigure
                ];
                $all = $sets['upper'] . $sets['lower'] . $sets['digits'] . $sets['symbols'];
                $password = '';
                // Asigură cel puțin un caracter din fiecare set
                foreach ($sets as $set) {
                    $password .= $set[random_int(0, strlen($set) - 1)];
                }
                for ($i = strlen($password); $i < $length; $i++) {
                    $password .= $all[random_int(0, strlen($all) - 1)];
                }
                return str_shuffle($password);
            }
        }
        
        switch ($action) {
            case 'add_company':
                error_log("=== ADD COMPANY DEBUG START ===");
                error_log("POST data: " . print_r($_POST, true));
                
                $company_name = sanitizeInput($_POST['company_name']);
                $email = sanitizeInput($_POST['email']);
                $status = sanitizeInput($_POST['subscription_status']); // Mapăm la coloana status
                $create_admin = isset($_POST['create_admin']) ? true : false;
                $admin_name = sanitizeInput($_POST['admin_name'] ?? '');
                
                error_log("Company name: $company_name");
                error_log("Email: $email");
                error_log("Create admin: " . ($create_admin ? 'Yes' : 'No'));
                $admin_email = sanitizeInput($_POST['admin_email'] ?? '');
                $admin_username = sanitizeInput($_POST['admin_username'] ?? '');
                
                // Validări
                if (empty($company_name) || empty($email)) {
                    throw new Exception('Numele companiei și emailul sunt obligatorii!');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Adresa de email nu este validă!');
                }
                
                // Verifică dacă emailul există deja
                $existing = $db->query("SELECT id FROM companies WHERE email = :email")
                              ->bind(':email', $email)
                              ->fetch();
                
                if ($existing) {
                    throw new Exception('O companie cu acest email există deja!');
                }
                
                // Start transaction
                $db->beginTransaction();
                
                try {
                    // Inserează compania
                    $result = $db->query("
                        INSERT INTO companies (name, email, status, created_at) 
                        VALUES (:name, :email, :status, NOW())
                    ")
                    ->bind(':name', $company_name)
                    ->bind(':email', $email)
                    ->bind(':status', $status)
                    ->execute();
                    
                    if (!$result) {
                        throw new Exception('Eroare la salvarea companiei!');
                    }
                    
                    $company_id = $db->lastInsertId();
                    
                    // Creează admin-ul dacă este solicitat
                    if ($create_admin && !empty($admin_name) && !empty($admin_email) && !empty($admin_username)) {
                        // Verifică dacă username-ul admin există în aceeași companie
                        $existing_username = $db->query("
                            SELECT id FROM users 
                            WHERE company_id = :company_id AND username = :username
                        ")
                        ->bind(':company_id', $company_id)
                        ->bind(':username', $admin_username)
                        ->fetch();
                        
                        if ($existing_username) {
                            throw new Exception('Un utilizator cu acest username există deja în companie!');
                        }
                        
                        // Verifică dacă email-ul admin există în alte companii (nu în aceeași companie)
                        $existing_email = $db->query("
                            SELECT id FROM users 
                            WHERE company_id != :company_id AND email = :email
                        ")
                        ->bind(':company_id', $company_id)
                        ->bind(':email', $admin_email)
                        ->fetch();
                        
                        if ($existing_email) {
                            throw new Exception('Un utilizator cu acest email există deja în altă companie!');
                        }
                        
                        // Parolă furnizată sau generată
                        $raw_password = trim($_POST['admin_password'] ?? '');
                        if ($raw_password === '') {
                            $raw_password = generateSecurePassword();
                            error_log("Generated password: " . $raw_password);
                        } else {
                            error_log("Using provided password: " . $raw_password);
                        }
                        $temp_password = $raw_password; // pentru afișare
                        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                        
                        error_log("Admin credentials - Username: " . $admin_username . ", Email: " . $admin_email . ", Password: " . $temp_password);
                        
                        // Inserează admin-ul
                        $admin_result = $db->query("
                            INSERT INTO users (company_id, username, email, password, full_name, role, status, created_at) 
                            VALUES (:company_id, :username, :email, :password, :full_name, 'admin', 'active', NOW())
                        ")
                        ->bind(':company_id', $company_id)
                        ->bind(':username', $admin_username)
                        ->bind(':email', $admin_email)
                        ->bind(':password', $hashed_password)
                        ->bind(':full_name', $admin_name)
                        ->execute();
                        
                        if (!$admin_result) {
                            throw new Exception('Eroare la crearea admin-ului!');
                        }
                        
                        $admin_id = $db->lastInsertId();
                        
                        // Marchează adminul ca principal pentru companie
                        $db->query("UPDATE companies SET primary_admin_id = :admin_id WHERE id = :company_id")
                           ->bind(':admin_id', $admin_id)
                           ->bind(':company_id', $company_id)
                           ->execute();
                        
                        $db->commit();
                        $_SESSION['success'] = 'Compania și admin-ul au fost create cu succes!<br>
                        <strong>Date de conectare pentru administrator:</strong><br>
                        Username: <code>' . $admin_username . '</code><br>
                        Email: <code>' . $admin_email . '</code><br>
                        Parolă temporară: <code>' . $temp_password . '</code><br>
                        <small>Conectează-te la: <a href="/login.php" target="_blank">/login.php</a></small>';
                    } else {
                        $db->commit();
                        $_SESSION['success'] = 'Compania a fost adăugată cu succes!';
                    }
                    
                    error_log("=== ADD COMPANY SUCCESS ===");
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log("=== ADD COMPANY ERROR: " . $e->getMessage() . " ===");
                    error_log("Stack trace: " . $e->getTraceAsString());
                    throw $e;
                }
                break;
                
            case 'update_status':
                $company_id = intval($_POST['company_id']);
                $new_status = sanitizeInput($_POST['new_status']);
                
                $db->query("UPDATE companies SET subscription_status = :status WHERE id = :id")
                   ->bind(':status', $new_status)
                   ->bind(':id', $company_id)
                   ->execute();
                   
                $_SESSION['success'] = 'Status-ul companiei a fost actualizat!';
                break;
                
            case 'create_admin':
                $company_id = intval($_POST['company_id']);
                $admin_name = sanitizeInput($_POST['admin_full_name']);
                $admin_email = sanitizeInput($_POST['admin_email']);
                $admin_username = sanitizeInput($_POST['admin_username']);
                
                // Validări
                if (empty($admin_name) || empty($admin_email) || empty($admin_username)) {
                    throw new Exception('Toate câmpurile sunt obligatorii!');
                }
                
                if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Adresa de email nu este validă!');
                }
                
                // Verifică dacă compania există
                $company = $db->query("SELECT name as company_name FROM companies WHERE id = :id")
                             ->bind(':id', $company_id)
                             ->fetch();
                
                if (!$company) {
                    throw new Exception('Compania nu a fost găsită!');
                }
                
                // Verifică dacă username-ul sau email-ul există deja în aceeași companie
                $existing_user = $db->query("
                    SELECT id FROM users 
                    WHERE company_id = :company_id 
                    AND (username = :username OR email = :email)
                ")
                ->bind(':company_id', $company_id)
                ->bind(':username', $admin_username)
                ->bind(':email', $admin_email)
                ->fetch();
                
                if ($existing_user) {
                    throw new Exception('Un utilizator cu acest username sau email există deja în această companie!');
                }
                
                // Verifică dacă există deja un admin în companie
                $existing_admin = $db->query("
                    SELECT id FROM users 
                    WHERE company_id = :company_id AND role = 'admin'
                ")
                ->bind(':company_id', $company_id)
                ->fetch();
                
                if ($existing_admin) {
                    throw new Exception('Această companie are deja un administrator!');
                }
                
                // Generează parolă temporară
                $provided = trim($_POST['admin_password'] ?? '');
                $temp_password = $provided !== '' ? $provided : generateSecurePassword();
                $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                
                // Creează admin-ul
                $result = $db->query("
                    INSERT INTO users (company_id, username, email, password, full_name, role, status, created_at) 
                    VALUES (:company_id, :username, :email, :password, :full_name, 'admin', 'active', NOW())
                ")
                ->bind(':company_id', $company_id)
                ->bind(':username', $admin_username)
                ->bind(':email', $admin_email)
                ->bind(':password', $hashed_password)
                ->bind(':full_name', $admin_name)
                ->execute();
                
                if ($result) {
                    $new_admin_id = $db->lastInsertId();
                    
                    $db->query("UPDATE companies SET primary_admin_id = :admin_id WHERE id = :company_id")
                       ->bind(':admin_id', $new_admin_id)
                       ->bind(':company_id', $company_id)
                       ->execute();
                    
                    $_SESSION['success'] = "Admin-ul {$admin_name} a fost creat cu succes pentru compania {$company['company_name']}!<br>
                                           <strong>Parola temporară:</strong> <code>{$temp_password}</code><br>
                                           <small class='text-muted'>Admin-ul trebuie să își schimbe parola la primul login.</small>";
                } else {
                    throw new Exception('Eroare la crearea admin-ului!');
                }
                break;
            
            case 'assign_admin':
                $company_id = intval($_POST['company_id']);
                $user_id = intval($_POST['user_id']);
                
                if ($company_id <= 0 || $user_id <= 0) {
                    throw new Exception('Date invalide pentru asocierea administratorului.');
                }
                
                // Verifică dacă compania și utilizatorul există și aparțin împreună
                $company = $db->query("SELECT id, name as company_name FROM companies WHERE id = :id")
                              ->bind(':id', $company_id)
                              ->fetch();
                
                if (!$company) {
                    throw new Exception('Compania selectată nu există!');
                }
                
                $user = $db->query("SELECT id, full_name, role FROM users WHERE id = :user_id AND company_id = :company_id")
                            ->bind(':user_id', $user_id)
                            ->bind(':company_id', $company_id)
                            ->fetch();
                
                if (!$user) {
                    throw new Exception('Utilizatorul selectat nu aparține acestei companii!');
                }
                
                $db->beginTransaction();
                
                try {
                    // Retrogradează alți administratori existenți
                    $db->query("UPDATE users SET role = 'manager' WHERE company_id = :company_id AND role = 'admin' AND id != :user_id")
                       ->bind(':company_id', $company_id)
                       ->bind(':user_id', $user_id)
                       ->execute();
                    
                    // Promovează utilizatorul selectat ca admin dacă nu este deja
                    $db->query("UPDATE users SET role = 'admin' WHERE id = :user_id")
                       ->bind(':user_id', $user_id)
                       ->execute();
                    
                    // Actualizează compania cu adminul principal
                    $db->query("UPDATE companies SET primary_admin_id = :user_id WHERE id = :company_id")
                       ->bind(':user_id', $user_id)
                       ->bind(':company_id', $company_id)
                       ->execute();
                    
                    $db->commit();
                    $_SESSION['success'] = "Utilizatorul {$user['full_name']} a fost setat ca administrator principal pentru {$company['company_name']}.";
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                break;
                
            case 'delete_company':
                $company_id = intval($_POST['company_id']);
                
                // Verifică dacă compania are utilizatori
                $users = $db->query("SELECT COUNT(*) as count FROM users WHERE company_id = :id")
                           ->bind(':id', $company_id)
                           ->fetch();
                
                if ($users['count'] > 0) {
                    throw new Exception('Nu poți șterge o companie care are utilizatori asociați!');
                }
                
                $db->query("DELETE FROM companies WHERE id = :id")
                   ->bind(':id', $company_id)
                   ->execute();
                   
                $_SESSION['success'] = 'Compania a fost ștearsă cu succes!';
                break;
        }
        
        redirect($_SERVER['REQUEST_URI']);
        
    } catch (Exception $e) {
        error_log("=== GENERAL ERROR: " . $e->getMessage() . " ===");
        error_log("Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = $e->getMessage();
        redirect($_SERVER['REQUEST_URI']);
    }
}

// Obține lista companiilor cu statistici
try {
    $db = new Database();
    
    $companies = $db->query("
        SELECT c.*,
               COALESCE(u.user_count, 0) as user_count,
               COALESCE(d.document_count, 0) as document_count,
               COALESCE(d.storage_used, 0) as storage_used,
               admin.full_name AS primary_admin_name,
               admin.email AS primary_admin_email,
               admin.id AS primary_admin_id
        FROM companies c
        LEFT JOIN (
            SELECT company_id, COUNT(*) as user_count 
            FROM users 
            GROUP BY company_id
        ) u ON c.id = u.company_id
        LEFT JOIN (
            SELECT company_id, COUNT(*) as document_count, SUM(file_size) as storage_used 
            FROM documents 
            GROUP BY company_id
        ) d ON c.id = d.company_id
        LEFT JOIN users admin ON admin.id = c.primary_admin_id
        ORDER BY c.created_at DESC
    ")->fetchAll();

    // Obține lista utilizatorilor per companie pentru asocieri rapide
    $company_users_map = [];
    $users_rows = $db->query("
        SELECT id, company_id, full_name, email, role
        FROM users
        ORDER BY full_name ASC
    ")->fetchAll();
    
    foreach ($users_rows as $user_row) {
        $company_id = $user_row['company_id'];
        if (!isset($company_users_map[$company_id])) {
            $company_users_map[$company_id] = [];
        }
        $company_users_map[$company_id][] = [
            'id' => (int) $user_row['id'],
            'full_name' => $user_row['full_name'],
            'email' => $user_row['email'],
            'role' => $user_row['role']
        ];
    }
    
} catch (Exception $e) {
    $companies = [];
    $company_users_map = [];
    $_SESSION['error'] = 'Eroare la încărcarea companiilor: ' . $e->getMessage();
}
?>

<!-- Statistici rapide -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bi bi-building fs-2"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="small opacity-75">Total Companii</div>
                        <div class="fs-4 fw-bold">
                            <?php echo count($companies); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bi bi-check-circle fs-2"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="small opacity-75">Active</div>
                        <div class="fs-4 fw-bold">
                            <?php echo count(array_filter($companies, function($c) { return $c['subscription_status'] === 'active'; })); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-warning text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bi bi-clock fs-2"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="small opacity-75">Trial</div>
                        <div class="fs-4 fw-bold">
                            <?php echo count(array_filter($companies, function($c) { return $c['subscription_status'] === 'trial'; })); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bi bi-people fs-2"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="small opacity-75">Total Utilizatori</div>
                        <div class="fs-4 fw-bold">
                            <?php echo array_sum(array_column($companies, 'user_count')); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista companiilor -->
<?php if (empty($companies)): ?>
<div class="text-center py-5">
    <i class="bi bi-building text-muted" style="font-size: 4rem;"></i>
    <h4 class="text-muted mt-3">Nu există companii înregistrate</h4>
    <p class="text-muted">Adaugă prima companie pentru a începe.</p>
    <button class="btn btn-primary mt-3" onclick="openAddModal()">
        <i class="bi bi-plus-lg me-2"></i>Adaugă Prima Companie
    </button>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover mb-0" id="companiesTable">
        <thead class="table-light">
            <tr>
                <th>Companie</th>
                <th>Contact</th>
                <th>Administrator</th>
                <th>Utilizatori</th>
                <th>Documente</th>
                <th>Stocare</th>
                <th>Status</th>
                <th>Creat</th>
                <th width="130">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($companies as $company): ?>
            <tr data-status="<?php echo $company['subscription_status']; ?>" data-name="<?php echo strtolower($company['name']); ?>" data-company-id="<?php echo $company['id']; ?>" data-company-name="<?php echo htmlspecialchars($company['name'], ENT_QUOTES); ?>">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                            <?php echo strtoupper(substr($company['name'], 0, 2)); ?>
                        </div>
                        <div>
                            <div class="fw-semibold"><?php echo sanitizeInput($company['name']); ?></div>
                            <small class="text-muted">ID: <?php echo $company['id']; ?></small>
                        </div>
                    </div>
                </td>
                <td>
                    <div><?php echo sanitizeInput($company['email']); ?></div>
                </td>
                <td>
                    <?php if (!empty($company['primary_admin_name'])): ?>
                        <div class="fw-semibold mb-1">
                            <i class="bi bi-person-badge me-2 text-primary"></i>
                            <?php echo sanitizeInput($company['primary_admin_name']); ?>
                        </div>
                        <small class="text-muted d-block"><?php echo sanitizeInput($company['primary_admin_email']); ?></small>
                    <?php else: ?>
                        <span class="badge bg-light text-muted border">Nesetat</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-info"><?php echo $company['user_count']; ?></span>
                </td>
                <td>
                    <span class="badge bg-secondary"><?php echo $company['document_count']; ?></span>
                </td>
                <td>
                    <?php
                    $storage_mb = round($company['storage_used'] / 1024 / 1024, 2);
                    $storage_class = $storage_mb > 100 ? 'text-danger' : ($storage_mb > 50 ? 'text-warning' : 'text-success');
                    ?>
                    <span class="<?php echo $storage_class; ?>"><?php echo $storage_mb; ?> MB</span>
                </td>
                <td>
                    <?php
                    $status = $company['subscription_status'];
                    $badge_class = [
                        'active' => 'bg-success',
                        'trial' => 'bg-warning text-dark',
                        'suspended' => 'bg-danger',
                        'expired' => 'bg-secondary'
                    ][$status] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span>
                </td>
                <td>
                    <small><?php echo date('d.m.Y', strtotime($company['created_at'])); ?></small>
                </td>
                <td>
                    <div class="btn-group actions-dropdown" role="group">
                        <!-- Buton principal pentru admin -->
                        <button class="btn btn-primary btn-sm" onclick="createAdmin(<?php echo $company['id']; ?>, '<?php echo addslashes($company['name']); ?>')" title="Creează Admin">
                            <i class="bi bi-person-plus"></i>
                        </button>
                        
                        <!-- Dropdown pentru alte acțiuni -->
                        <div class="dropdown position-static">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-display="static">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow actions-dropdown-menu">
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center" onclick="viewCompany(<?php echo $company['id']; ?>)">
                                        <i class="bi bi-eye me-2"></i>
                                        <span>Vizualizează</span>
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center" onclick="editCompany(<?php echo $company['id']; ?>)">
                                        <i class="bi bi-pencil me-2"></i>
                                        <span>Editează</span>
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center" onclick="assignAdmin(<?php echo $company['id']; ?>)">
                                        <i class="bi bi-person-badge me-2"></i>
                                        <span>Setează Administrator</span>
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li class="px-3 py-1">
                                    <div class="text-muted text-uppercase fw-semibold small">Status abonament</div>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center" onclick="changeStatus(<?php echo $company['id']; ?>, 'active')">
                                        <i class="bi bi-check-circle me-2 text-success"></i>
                                        <span>Activează</span>
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center" onclick="changeStatus(<?php echo $company['id']; ?>, 'trial')">
                                        <i class="bi bi-clock me-2 text-warning"></i>
                                        <span>Trial</span>
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center" onclick="changeStatus(<?php echo $company['id']; ?>, 'suspended')">
                                        <i class="bi bi-pause-circle me-2 text-warning"></i>
                                        <span>Suspendă</span>
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center" onclick="changeStatus(<?php echo $company['id']; ?>, 'expired')">
                                        <i class="bi bi-x-circle me-2 text-secondary"></i>
                                        <span>Expirat</span>
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center text-danger" onclick="deleteCompany(<?php echo $company['id']; ?>, '<?php echo addslashes($company['name']); ?>')">
                                        <i class="bi bi-trash me-2"></i>
                                        <span>Șterge</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Include toate modalurile existente -->
<?php include 'modals/company_modals_debug.php'; ?>

<script>
const companyUsersMap = <?php echo json_encode($company_users_map ?? [], JSON_UNESCAPED_UNICODE); ?>;

// Funcții JavaScript pentru gestionarea companiilor
function deleteCompany(companyId, companyName) {
    document.getElementById('delete_company_id').value = companyId;
    document.getElementById('delete_company_name').textContent = companyName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function changeStatus(companyId, newStatus) {
    document.getElementById('status_company_id').value = companyId;
    document.getElementById('status_new_status').value = newStatus;
    
    const statusTexts = {
        'active': 'Activ',
        'trial': 'Trial',
        'suspended': 'Suspendat',
        'expired': 'Expirat'
    };
    document.getElementById('status_text').textContent = statusTexts[newStatus];
    
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

function createAdmin(companyId, companyName) {
    document.getElementById('admin_company_id').value = companyId;
    document.getElementById('admin_company_name').textContent = companyName;
    
    const form = document.querySelector('#createAdminModal form');
    if (form) {
        form.reset();
    }
    
    const adminFullName = document.getElementById('admin_full_name');
    const adminUsername = document.getElementById('admin_username');
    const adminEmail = document.getElementById('admin_email');
    
    [adminFullName, adminUsername, adminEmail].forEach(input => {
        if (!input) {
            return;
        }
        input.removeAttribute('disabled');
        input.readOnly = false;
        input.classList.remove('disabled');
    });
    
    if (adminFullName) {
        setTimeout(() => adminFullName.focus(), 150);
    }
    
    const modal = new bootstrap.Modal(document.getElementById('createAdminModal'));
    modal.show();
}

// Asigurare fallback: când modalul devine vizibil, forțează activarea câmpurilor
document.addEventListener('shown.bs.modal', function(e) {
    if (e.target && e.target.id === 'createAdminModal') {
        ['admin_full_name','admin_username','admin_email'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.disabled = false;
                el.readOnly = false;
                el.classList.remove('disabled');
                // Curăță valori vechi fantomă dacă vin din autocomplete
                if (!el.value) el.value = '';
            }
        });
        const first = document.getElementById('admin_full_name');
        if (first) first.focus();
    }
});

function assignAdmin(companyId) {
    const modalElement = document.getElementById('assignAdminModal');
    const modal = new bootstrap.Modal(modalElement);
    const select = document.getElementById('assign_admin_user_id');
    const companyNameHolder = document.getElementById('assign_admin_company_name');
    const submitButton = document.getElementById('assign_admin_submit');
    const companyIdInput = document.getElementById('assign_admin_company_id');
    const helperText = document.getElementById('assign_admin_helper');

    companyIdInput.value = companyId;

    const row = document.querySelector(`#companiesTable tbody tr[data-company-id='${companyId}']`);
    if (row && companyNameHolder) {
        companyNameHolder.textContent = row.getAttribute('data-company-name') || '';
    }

    select.innerHTML = '';
    const users = companyUsersMap[String(companyId)] || [];

    if (!users.length) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Nu există utilizatori înregistrați în această companie';
        select.appendChild(option);
        select.disabled = true;
        submitButton.disabled = true;
        if (helperText) {
            helperText.textContent = 'Adaugă mai întâi utilizatori în companie pentru a putea alege un administrator.';
            helperText.classList.remove('text-muted');
            helperText.classList.add('text-danger');
        }
    } else {
        select.disabled = false;
        submitButton.disabled = false;
        if (helperText) {
            helperText.textContent = 'Selectează un utilizator existent pentru rolul de administrator principal. Acțiunea va promova utilizatorul la rolul de "admin" dacă este necesar.';
            helperText.classList.remove('text-danger');
            helperText.classList.add('text-muted');
        }

        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = `${user.full_name} (${user.email})`;
            if (user.role === 'admin') {
                option.textContent += ' - Admin curent';
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    modal.show();
}

function viewCompany(companyId) {
    // TODO: Implementează vizualizarea companiei
    alert('Funcția de vizualizare va fi implementată în curând!');
}

function editCompany(companyId) {
    // TODO: Implementează editarea companiei
    alert('Funcția de editare va fi implementată în curând!');
}

function filterCompanies() {
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#companiesTable tbody tr');

    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        const name = row.getAttribute('data-name');
        
        const statusMatch = !statusFilter || status === statusFilter;
        const nameMatch = !searchTerm || name.includes(searchTerm);
        
        if (statusMatch && nameMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function generateAdminPassword() {
    const input = document.getElementById('admin_password');
    if (!input) return;
    const password = generateClientPassword();
    input.value = password;
    input.dispatchEvent(new Event('input'));
    showTransientBadge(input, 'Generat');
}

function generateClientPassword() {
    const upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
    const lower = 'abcdefghjkmnpqrstuvwxyz';
    const digits = '23456789';
    const symbols = '!@#$%^&*()_+-=';
    const all = upper + lower + digits + symbols;
    let pwd = [
        upper[Math.floor(Math.random()*upper.length)],
        lower[Math.floor(Math.random()*lower.length)],
        digits[Math.floor(Math.random()*digits.length)],
        symbols[Math.floor(Math.random()*symbols.length)]
    ];
    for (let i = pwd.length; i < 12; i++) {
        pwd.push(all[Math.floor(Math.random()*all.length)]);
    }
    return pwd.sort(()=>0.5-Math.random()).join('');
}

function togglePasswordVisibility(id, btn) {
    const input = document.getElementById(id);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password' || input.type === 'text') {
        input.type = input.type === 'password' ? 'text' : 'password';
        if (icon) icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    }
}

function copyToClipboard(id, btn) {
    const input = document.getElementById(id);
    if (!input || !input.value) return;
    navigator.clipboard.writeText(input.value).then(()=>{
        showTransientBadge(btn, 'Copiat');
    });
}

function showTransientBadge(target, text) {
    const badge = document.createElement('span');
    badge.className = 'badge bg-success position-absolute translate-middle';
    badge.style.zIndex = '1080';
    badge.textContent = text;
    const rect = target.getBoundingClientRect();
    badge.style.top = (rect.top + window.scrollY - 8) + 'px';
    badge.style.left = (rect.left + window.scrollX + rect.width) + 'px';
    document.body.appendChild(badge);
    setTimeout(()=>badge.remove(), 1600);
}
</script>

<?php
// Modalul este deja inclus mai sus
// include __DIR__ . '/modals/company_modals_debug.php';
?>