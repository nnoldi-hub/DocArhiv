<?php
$company_id = (int)($_SESSION['company_id'] ?? 0);

// Acțiuni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verify_csrf')) verify_csrf();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $password = $_POST['password'] ?? '';
        $department_id = (int)($_POST['department_id'] ?? 0) ?: null;
        
        $errors = [];
        
        // Validări
        if (empty($full_name)) $errors[] = 'Numele complet este obligatoriu';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalid';
        if (strlen($password) < 8) $errors[] = 'Parola trebuie să aibă minim 8 caractere';
        if (!in_array($role, ['user', 'manager', 'admin'])) $errors[] = 'Rol invalid';
        
        if (empty($errors)) {
            try {
                $db = getDBConnection();
                
                // Verifică dacă email-ul există deja în companie
                $stmt = $db->prepare("SELECT id FROM users WHERE company_id = ? AND email = ?");
                $stmt->execute([$company_id, $email]);
                
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'Un utilizator cu acest email există deja în companie!';
                } else {
                    // Generează username din nume
                    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '.', $full_name));
                    $username = preg_replace('/\.+/', '.', $username);
                    $username = trim($username, '.');
                    
                    // Verifică unicitatea username-ului
                    $counter = 1;
                    $original_username = $username;
                    while (true) {
                        $stmt = $db->prepare("SELECT id FROM users WHERE company_id = ? AND username = ?");
                        $stmt->execute([$company_id, $username]);
                        if (!$stmt->fetch()) break;
                        $username = $original_username . $counter;
                        $counter++;
                    }
                    
                    // Adaugă utilizatorul
                    $stmt = $db->prepare("
                        INSERT INTO users 
                        (company_id, username, email, password, full_name, role, department_id, created_at, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'active')
                    ");
                    
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt->execute([
                        $company_id, 
                        $username, 
                        $email, 
                        $hashed_password, 
                        $full_name, 
                        $role, 
                        $department_id
                    ]);
                    
                    $_SESSION['success'] = 'Utilizator adăugat cu succes!';
                    
                    // Log activitate
                    if (function_exists('logActivity')) {
                        logActivity('create', 'Utilizator nou adăugat: ' . $full_name, 'user', $db->lastInsertId());
                    }
                }
            } catch(Exception $e) {
                $_SESSION['error'] = 'Eroare la adăugarea utilizatorului: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = implode(' | ', $errors);
        }
    }
    
    if ($action === 'edit') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $department_id = (int)($_POST['department_id'] ?? 0) ?: null;
        $new_password = trim($_POST['new_password'] ?? '');
        
        $errors = [];
        
        // Validări
        if (empty($full_name)) $errors[] = 'Numele complet este obligatoriu';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalid';
        if (!in_array($role, ['user', 'manager', 'admin'])) $errors[] = 'Rol invalid';
        if (!empty($new_password) && strlen($new_password) < 8) $errors[] = 'Parola nouă trebuie să aibă minim 8 caractere';
        
        if (empty($errors) && $user_id) {
            try {
                $db = getDBConnection();
                
                // Verifică dacă email-ul există deja pentru alt utilizator
                $stmt = $db->prepare("SELECT id FROM users WHERE company_id = ? AND email = ? AND id != ?");
                $stmt->execute([$company_id, $email, $user_id]);
                
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'Un alt utilizator cu acest email există deja în companie!';
                } else {
                    // Dacă utilizatorul editează propriul profil, nu poate schimba rolul
                    if ($user_id == $_SESSION['user_id']) {
                        // Obține rolul actual pentru a-l păstra
                        $stmt = $db->prepare("SELECT role FROM users WHERE id = ? AND company_id = ?");
                        $stmt->execute([$user_id, $company_id]);
                        $current_data = $stmt->fetch();
                        $role = $current_data['role']; // Păstrează rolul existent
                    }
                    
                    // Construiește query-ul de update
                    $fields = "full_name = ?, email = ?, role = ?, department_id = ?";
                    $params = [$full_name, $email, $role, $department_id];
                    
                    // Dacă se schimbă parola, o adaugă la update
                    if (!empty($new_password)) {
                        $fields .= ", password = ?";
                        $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                    }
                    
                    $params[] = $user_id;
                    $params[] = $company_id;
                    
                    $stmt = $db->prepare("UPDATE users SET $fields WHERE id = ? AND company_id = ?");
                    
                    if ($stmt->execute($params)) {
                        if ($user_id == $_SESSION['user_id']) {
                            $_SESSION['success'] = 'Profilul tău a fost actualizat cu succes!';
                        } else {
                            $_SESSION['success'] = 'Utilizator actualizat cu succes!';
                        }
                        
                        // Log activitate
                        if (function_exists('logActivity')) {
                            $action_desc = $user_id == $_SESSION['user_id'] ? 'Profil propriu actualizat' : 'Utilizator actualizat: ' . $full_name;
                            logActivity('update', $action_desc, 'user', $user_id);
                        }
                    } else {
                        $_SESSION['error'] = 'Eroare la actualizarea utilizatorului!';
                    }
                }
            } catch(Exception $e) {
                $_SESSION['error'] = 'Eroare la actualizarea utilizatorului: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = implode(' | ', $errors);
        }
    }
    
    if ($action === 'delete') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($user_id && $user_id != $_SESSION['user_id']) {
            try {
                $db = getDBConnection();
                
                // Verifică dacă utilizatorul are documente
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM documents WHERE created_by = ?");
                $stmt->execute([$user_id]);
                $doc_count = $stmt->fetch()['count'];
                
                if ($doc_count > 0) {
                    $_SESSION['error'] = 'Nu se poate șterge utilizatorul! Are ' . $doc_count . ' documente încărcate.';
                } else {
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND company_id = ?");
                    $stmt->execute([$user_id, $company_id]);
                    $_SESSION['success'] = 'Utilizator șters cu succes!';
                }
            } catch(Exception $e) {
                $_SESSION['error'] = 'Eroare la ștergerea utilizatorului: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = 'Nu te poți șterge pe tine însuți!';
        }
    }
    
    if ($action === 'toggle_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
        
        if ($user_id && $user_id != $_SESSION['user_id']) {
            try {
                $db = getDBConnection();
                $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$new_status, $user_id, $company_id]);
                $_SESSION['success'] = 'Status utilizator actualizat!';
            } catch(Exception $e) {
                $_SESSION['error'] = 'Eroare la actualizarea statusului: ' . $e->getMessage();
            }
        }
    }
    
    redirect(APP_URL . '/admin-users.php');
    exit;
}

// Obține lista departamentelor pentru dropdown
$departments = [];
$users = [];

try {
    $db = getDBConnection();
    
    // Obține departamentele
    $stmt = $db->prepare("SELECT id, name FROM departments WHERE company_id = ? ORDER BY name");
    $stmt->execute([$company_id]);
    $departments = $stmt->fetchAll();
    
    // Obține utilizatorii cu informații despre departament
    $stmt = $db->prepare("
        SELECT u.*, 
               d.name as department_name,
               (SELECT COUNT(*) FROM documents WHERE created_by = u.id) as document_count
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.company_id = ?
        ORDER BY u.created_at DESC
    ");
    $stmt->execute([$company_id]);
    $users = $stmt->fetchAll();
    
} catch(Exception $e) {
    $_SESSION['error'] = 'Eroare la încărcarea utilizatorilor: ' . $e->getMessage();
}
?>

<!-- Header și statistici -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Gestiune Utilizatori</h4>
        <p class="text-muted mb-0">Administrează utilizatorii companiei</p>
    </div>
    <span class="badge bg-primary fs-6">Total: <?php echo count($users); ?></span>
</div>

<!-- Formular adăugare utilizator -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-person-plus me-2"></i>Adaugă Utilizator Nou
        </h5>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            
            <div class="col-md-4">
                <label class="form-label">Nume Complet <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" placeholder="Ex: Ion Popescu" required>
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" placeholder="ion.popescu@companie.ro" required>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Rol <span class="text-danger">*</span></label>
                <select name="role" class="form-select" required>
                    <option value="user">Utilizator</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Parolă <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" name="password" class="form-control" id="userPassword" required minlength="8">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('userPassword')">
                        <i class="bi bi-eye" id="userPassword-icon"></i>
                    </button>
                </div>
                <small class="text-muted">Minim 8 caractere</small>
            </div>
            
            <div class="col-md-8">
                <label class="form-label">Departament</label>
                <select name="department_id" class="form-select">
                    <option value="">-- Fără departament --</option>
                    <?php foreach($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>">
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-person-plus me-1"></i>Adaugă Utilizator
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lista utilizatori -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-people me-2"></i>Utilizatori Existenți
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th width="80">#</th>
                    <th>Nume Complet</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Departament</th>
                    <th width="120">Documente</th>
                    <th width="100">Status</th>
                    <th width="150">Data Creării</th>
                    <th width="120">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if($users): ?>
                    <?php foreach($users as $index => $user): ?>
                        <tr <?php echo $user['id'] == $_SESSION['user_id'] ? 'class="table-warning"' : ''; ?>>
                            <td>
                                <span class="badge bg-secondary"><?php echo $index + 1; ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <small class="text-muted d-block">(Tu)</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php
                                $role_colors = [
                                    'admin' => 'danger',
                                    'manager' => 'warning',
                                    'user' => 'primary'
                                ];
                                $role_names = [
                                    'admin' => 'Administrator',
                                    'manager' => 'Manager', 
                                    'user' => 'Utilizator'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $role_colors[$user['role']]; ?>">
                                    <i class="bi bi-shield-check me-1"></i>
                                    <?php echo $role_names[$user['role']]; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($user['department_name']): ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-building me-1"></i>
                                        <?php echo htmlspecialchars($user['department_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">
                                        <i class="bi bi-dash"></i> Fără departament
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-success">
                                    <i class="bi bi-file-text me-1"></i>
                                    <?php echo (int)$user['document_count']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if($user['status'] === 'active'): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Activ
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-pause-circle me-1"></i>Inactiv
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                    <div class="btn-group" role="group">
                                        <!-- Edit Button -->
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                title="Editează utilizator">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        <!-- Toggle Status -->
                                        <form method="post" style="display: inline;">
                                            <?php if(function_exists('csrf_field')) echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="btn btn-outline-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?> btn-sm" 
                                                    title="<?php echo $user['status'] === 'active' ? 'Dezactivează' : 'Activează'; ?>">
                                                <i class="bi bi-<?php echo $user['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <!-- Delete -->
                                        <?php if((int)$user['document_count'] === 0): ?>
                                            <form method="post" style="display: inline;" 
                                                  onsubmit="return confirm('Sigur ștergi utilizatorul <?php echo htmlspecialchars($user['full_name']); ?>?')">
                                                <?php if(function_exists('csrf_field')) echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Șterge utilizator">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary btn-sm" disabled title="Nu se poate șterge - are documente">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-outline-success btn-sm" 
                                                onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                title="Editează-ți profilul">
                                            <i class="bi bi-person-gear"></i>
                                        </button>
                                        <span class="badge bg-warning align-self-center">Utilizator curent</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-people display-4 d-block mb-3"></i>
                                <h5>Niciun utilizator găsit</h5>
                                <p>Adaugă primul utilizator folosind formularul de mai sus.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Statistici -->
<?php if(count($users) > 0): ?>
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-people text-primary display-6"></i>
                <h5 class="mt-2">Total Utilizatori</h5>
                <h3 class="text-primary"><?php echo count($users); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-shield-check text-danger display-6"></i>
                <h5 class="mt-2">Administratori</h5>
                <h3 class="text-danger">
                    <?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-person-badge text-warning display-6"></i>
                <h5 class="mt-2">Manageri</h5>
                <h3 class="text-warning">
                    <?php echo count(array_filter($users, fn($u) => $u['role'] === 'manager')); ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-check-circle text-success display-6"></i>
                <h5 class="mt-2">Activi</h5>
                <h3 class="text-success">
                    <?php echo count(array_filter($users, fn($u) => $u['status'] === 'active')); ?>
                </h3>
            </div>
        </div>
    </div>
</div>
<!-- Modal Editare Utilizator -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="bi bi-person-gear me-2"></i>Editează Utilizator
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editUserForm">
                <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nume Complet <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Rol <span class="text-danger">*</span></label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="user">Utilizator</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Departament</label>
                            <select name="department_id" id="edit_department_id" class="form-select">
                                <option value="">-- Fără departament --</option>
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Parolă Nouă</label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="edit_password" class="form-control" 
                                       placeholder="Lasă gol pentru a păstra parola actuală" minlength="8">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('edit_password')">
                                    <i class="bi bi-eye" id="edit_password-icon"></i>
                                </button>
                            </div>
                            <small class="text-muted">Lasă gol dacă nu vrei să schimbi parola. Minim 8 caractere dacă completezi.</small>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Anulează
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Actualizează Utilizator
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
    font-weight: bold;
}
</style>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function editUser(userData) {
    // Populează modalul cu datele utilizatorului
    document.getElementById('edit_user_id').value = userData.id;
    document.getElementById('edit_full_name').value = userData.full_name;
    document.getElementById('edit_email').value = userData.email;
    document.getElementById('edit_role').value = userData.role;
    document.getElementById('edit_department_id').value = userData.department_id || '';
    document.getElementById('edit_password').value = '';
    
    // Verifică dacă este utilizatorul curent și dezactivează câmpurile care nu pot fi editate
    const isCurrentUser = userData.id == <?php echo $_SESSION['user_id']; ?>;
    const roleField = document.getElementById('edit_role');
    
    if (isCurrentUser) {
        // Utilizatorul curent nu poate să își schimbe rolul
        roleField.disabled = true;
        roleField.title = 'Nu îți poți schimba propriul rol';
        
        // Actualizează titlul modalului
        document.getElementById('editUserModalLabel').innerHTML = 
            '<i class="bi bi-person-gear me-2"></i>Editează-ți Profilul';
    } else {
        // Pentru alți utilizatori, rolul poate fi editat
        roleField.disabled = false;
        roleField.title = '';
        
        // Actualizează titlul modalului
        document.getElementById('editUserModalLabel').innerHTML = 
            '<i class="bi bi-person-gear me-2"></i>Editează Utilizator: ' + userData.full_name;
    }
    
    // Afișează modalul
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}
</script>