<?php
// Procesarea formularelor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = sanitizeInput($_POST['action']);
        $db = new Database();
        
        switch ($action) {
            case 'toggle_status':
                $user_id = intval($_POST['user_id']);
                $new_status = sanitizeInput($_POST['new_status']);
                
                $db->query("UPDATE users SET status = :status WHERE id = :id")
                   ->bind(':status', $new_status)
                   ->bind(':id', $user_id)
                   ->execute();
                   
                $_SESSION['success'] = 'Status-ul utilizatorului a fost actualizat!';
                break;
                
            case 'change_role':
                $user_id = intval($_POST['user_id']);
                $new_role = sanitizeInput($_POST['new_role']);
                
                $db->query("UPDATE users SET role = :role WHERE id = :id")
                   ->bind(':role', $new_role)
                   ->bind(':id', $user_id)
                   ->execute();
                   
                $_SESSION['success'] = 'Rolul utilizatorului a fost schimbat!';
                break;
                
            case 'reset_password':
                $user_id = intval($_POST['user_id']);
                $provided_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
                $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

                if (empty($provided_password) || empty($confirm_password)) {
                    throw new Exception('Introduceți și confirmați noua parolă.');
                }

                if ($provided_password !== $confirm_password) {
                    throw new Exception('Parolele nu coincid.');
                }

                // Validare complexitate folosind validatePassword dacă e disponibilă
                if (function_exists('validatePassword')) {
                    $validation = validatePassword($provided_password);
                    if (!$validation['valid']) {
                        throw new Exception('Parola nu este validă: ' . implode(' | ', $validation['errors']));
                    }
                } else {
                    if (strlen($provided_password) < 8) {
                        throw new Exception('Parola trebuie să aibă minim 8 caractere.');
                    }
                }

                $hashed = password_hash($provided_password, PASSWORD_DEFAULT);

                $db->query("UPDATE users SET password = :password WHERE id = :id")
                   ->bind(':password', $hashed)
                   ->bind(':id', $user_id)
                   ->execute();

                $_SESSION['success'] = 'Parola a fost schimbată cu succes.';
                break;
                
            case 'delete_user':
                $user_id = intval($_POST['user_id']);
                
                // Verifică dacă utilizatorul are documente
                $documents = $db->query("SELECT COUNT(*) as count FROM documents WHERE uploaded_by = :id")
                               ->bind(':id', $user_id)
                               ->fetch();
                
                if ($documents['count'] > 0) {
                    throw new Exception('Nu poți șterge un utilizator care are documente asociate!');
                }
                
                $db->query("DELETE FROM users WHERE id = :id")
                   ->bind(':id', $user_id)
                   ->execute();
                   
                $_SESSION['success'] = 'Utilizatorul a fost șters cu succes!';
                break;
        }
        
        redirect($_SERVER['REQUEST_URI']);
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        redirect($_SERVER['REQUEST_URI']);
    }
}

// Obține lista utilizatorilor cu detalii companie
try {
    $db = new Database();
    
    $users = $db->query("
        SELECT u.*, c.company_name,
               (SELECT COUNT(*) FROM documents WHERE uploaded_by = u.id) as document_count
        FROM users u
        LEFT JOIN companies c ON u.company_id = c.id
        ORDER BY u.created_at DESC
    ")->fetchAll();
    
    // Obține companiile pentru filtrare
    $companies = $db->query("
        SELECT id, company_name 
        FROM companies 
        ORDER BY company_name
    ")->fetchAll();
    
} catch (Exception $e) {
    $users = [];
    $companies = [];
    $_SESSION['error'] = 'Eroare la încărcarea utilizatorilor: ' . $e->getMessage();
}
?>

<!-- Statistici rapide -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bi bi-people fs-2"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="small opacity-75">Total Utilizatori</div>
                        <div class="fs-4 fw-bold">
                            <?php echo count($users); ?>
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
                        <div class="small opacity-75">Activi</div>
                        <div class="fs-4 fw-bold">
                            <?php echo count(array_filter($users, function($u) { return $u['status'] === 'active'; })); ?>
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
                        <i class="bi bi-shield fs-2"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="small opacity-75">Administratori</div>
                        <div class="fs-4 fw-bold">
                            <?php echo count(array_filter($users, function($u) { return $u['role'] === 'admin'; })); ?>
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
                        <i class="bi bi-pause-circle fs-2"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="small opacity-75">Inactivi</div>
                        <div class="fs-4 fw-bold">
                            <?php echo count(array_filter($users, function($u) { return $u['status'] === 'inactive'; })); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtre și căutare -->
<div class="row mb-4">
    <div class="col-md-4">
        <select class="form-select" id="companyFilter" onchange="filterUsers()">
            <option value="">Toate companiile</option>
            <?php foreach ($companies as $company): ?>
            <option value="<?php echo $company['id']; ?>"><?php echo sanitizeInput($company['company_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="roleFilter" onchange="filterUsers()">
            <option value="">Toate rolurile</option>
            <option value="admin">Administrator</option>
            <option value="user">Utilizator</option>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="statusFilter" onchange="filterUsers()">
            <option value="">Toate statusurile</option>
            <option value="active">Activ</option>
            <option value="inactive">Inactiv</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="text" class="form-control" id="searchInput" placeholder="Caută utilizator..." onkeyup="filterUsers()">
    </div>
</div>

<!-- Lista utilizatorilor -->
<?php if (empty($users)): ?>
<div class="text-center py-5">
    <i class="bi bi-people text-muted" style="font-size: 4rem;"></i>
    <h4 class="text-muted mt-3">Nu există utilizatori înregistrați</h4>
    <p class="text-muted">Utilizatorii vor apărea aici când vor fi creați de administratorii companiilor.</p>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover mb-0" id="usersTable">
        <thead class="table-light">
            <tr>
                <th>Utilizator</th>
                <th>Companie</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Status</th>
                <th>Documente</th>
                <th>Înregistrat</th>
                <th width="150">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr data-company="<?php echo $user['company_id']; ?>" 
                data-role="<?php echo $user['role']; ?>" 
                data-status="<?php echo $user['status']; ?>"
                data-name="<?php echo strtolower($user['full_name'] . ' ' . $user['username']); ?>">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                        </div>
                        <div>
                            <div class="fw-semibold"><?php echo sanitizeInput($user['full_name']); ?></div>
                            <small class="text-muted">@<?php echo sanitizeInput($user['username']); ?></small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-light text-dark"><?php echo sanitizeInput($user['company_name'] ?? 'N/A'); ?></span>
                </td>
                <td>
                    <div><?php echo sanitizeInput($user['email']); ?></div>
                </td>
                <td>
                    <?php
                    $role_class = $user['role'] === 'admin' ? 'bg-danger' : 'bg-info';
                    $role_text = $user['role'] === 'admin' ? 'Administrator' : 'Utilizator';
                    ?>
                    <span class="badge <?php echo $role_class; ?>"><?php echo $role_text; ?></span>
                </td>
                <td>
                    <?php
                    $status_class = $user['status'] === 'active' ? 'bg-success' : 'bg-warning text-dark';
                    $status_text = $user['status'] === 'active' ? 'Activ' : 'Inactiv';
                    ?>
                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </td>
                <td>
                    <span class="badge bg-secondary"><?php echo $user['document_count']; ?></span>
                </td>
                <td>
                    <small><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></small>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <!-- Buton pentru resetare parolă -->
                        <button class="btn btn-warning btn-sm" 
                                data-user-id="<?php echo $user['id']; ?>" 
                                data-user-name="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>"
                                data-bs-toggle="modal" 
                                data-bs-target="#resetPasswordModal"
                                onclick="prepareResetPassword(this)" 
                                title="Resetează Parola">
                            <i class="bi bi-key"></i>
                        </button>
                        
                        <!-- Dropdown pentru alte acțiuni -->
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="viewUser(<?php echo $user['id']; ?>)">
                                    <i class="bi bi-eye me-2"></i>Vizualizează
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>')">
                                    <i class="bi bi-<?php echo $user['status'] === 'active' ? 'pause' : 'play'; ?>-circle me-2"></i>
                                    <?php echo $user['status'] === 'active' ? 'Dezactivează' : 'Activează'; ?>
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="changeRole(<?php echo $user['id']; ?>, '<?php echo $user['role'] === 'admin' ? 'user' : 'admin'; ?>')">
                                    <i class="bi bi-arrow-up-right-circle me-2"></i>
                                    Schimbă în <?php echo $user['role'] === 'admin' ? 'Utilizator' : 'Administrator'; ?>
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['full_name']); ?>')">
                                    <i class="bi bi-trash me-2"></i>Șterge
                                </a></li>
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

<!-- Include modalurile -->
<?php include 'modals/user_modals.php'; ?>

<script>
// Funcții JavaScript pentru gestionarea utilizatorilor
function prepareResetPassword(btn) {
    const userId = btn.getAttribute('data-user-id');
    const userName = btn.getAttribute('data-user-name');
    const uid = document.getElementById('reset_user_id');
    const uname = document.getElementById('reset_user_name');
    if (uid) uid.value = userId;
    if (uname) uname.textContent = userName;
    ['new_password','confirm_password'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const feedback = document.getElementById('password_feedback');
    if (feedback) feedback.textContent = '';
}

function toggleStatus(userId, newStatus) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="user_id" value="${userId}">
        <input type="hidden" name="new_status" value="${newStatus}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function changeRole(userId, newRole) {
    if (confirm(`Schimbi rolul utilizatorului în ${newRole === 'admin' ? 'Administrator' : 'Utilizator'}?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="change_role">
            <input type="hidden" name="user_id" value="${userId}">
            <input type="hidden" name="new_role" value="${newRole}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteUser(userId, userName) {
    if (confirm(`Ești sigur că vrei să ștergi utilizatorul ${userName}?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewUser(userId) {
    alert('Funcția de vizualizare va fi implementată în curând!');
}

function filterUsers() {
    const companyFilter = document.getElementById('companyFilter').value;
    const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#usersTable tbody tr');

    rows.forEach(row => {
        const company = row.getAttribute('data-company');
        const role = row.getAttribute('data-role');
        const status = row.getAttribute('data-status');
        const name = row.getAttribute('data-name');
        
        const companyMatch = !companyFilter || company === companyFilter;
        const roleMatch = !roleFilter || role === roleFilter;
        const statusMatch = !statusFilter || status === statusFilter;
        const nameMatch = !searchTerm || name.includes(searchTerm);
        
        if (companyMatch && roleMatch && statusMatch && nameMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>