<?php
$company_id = (int)($_SESSION['company_id'] ?? 0);

// Acțiuni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verify_csrf')) verify_csrf();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $manager_id = (int)($_POST['manager_id'] ?? 0) ?: null;
        
        if ($name) {
            try {
                $db = getDBConnection();
                
                // Verifică dacă departamentul există deja
                $stmt = $db->prepare("SELECT id FROM departments WHERE company_id = ? AND name = ?");
                $stmt->execute([$company_id, $name]);
                
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'Departamentul există deja!';
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO departments (company_id, name, manager_id, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$company_id, $name, $manager_id]);
                    $_SESSION['success'] = 'Departament adăugat cu succes!';
                }
            } catch(Exception $e) {
                $_SESSION['error'] = 'Eroare la adăugarea departamentului: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = 'Numele departamentului este obligatoriu!';
        }
    }
    
    if ($action === 'delete') {
        $dept_id = (int)($_POST['dept_id'] ?? 0);
        
        if ($dept_id) {
            try {
                $db = getDBConnection();
                
                // Verifică dacă departamentul are utilizatori
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE department_id = ?");
                $stmt->execute([$dept_id]);
                $user_count = $stmt->fetch()['count'];
                
                if ($user_count > 0) {
                    $_SESSION['error'] = 'Nu se poate șterge departamentul! Are ' . $user_count . ' utilizatori asignați.';
                } else {
                    $stmt = $db->prepare("DELETE FROM departments WHERE id = ? AND company_id = ?");
                    $stmt->execute([$dept_id, $company_id]);
                    $_SESSION['success'] = 'Departament șters cu succes!';
                }
            } catch(Exception $e) {
                $_SESSION['error'] = 'Eroare la ștergerea departamentului: ' . $e->getMessage();
            }
        }
    }
    
    redirect(APP_URL . '/admin-departments.php');
    exit;
}

// Listare departamente
$departments = [];
$managers = [];

try {
    $db = getDBConnection();
    
    // Obține managerii disponibili (utilizatori cu rol manager/admin din aceeași companie)
    $stmt = $db->prepare("
        SELECT id, full_name 
        FROM users 
        WHERE company_id = ? AND role IN ('admin', 'manager') AND status = 'active'
        ORDER BY full_name
    ");
    $stmt->execute([$company_id]);
    $managers = $stmt->fetchAll();
    
    // Obține departamentele cu informații despre manager
    $stmt = $db->prepare("
        SELECT d.*, 
               u.full_name as manager_name,
               (SELECT COUNT(*) FROM users WHERE department_id = d.id) as user_count
        FROM departments d
        LEFT JOIN users u ON d.manager_id = u.id
        WHERE d.company_id = ?
        ORDER BY d.name
    ");
    $stmt->execute([$company_id]);
    $departments = $stmt->fetchAll();
    
} catch(Exception $e) {
    $_SESSION['error'] = 'Eroare la încărcarea departamentelor: ' . $e->getMessage();
}
?>

<!-- Header și statistici -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Gestiune Departamente</h4>
        <p class="text-muted mb-0">Administrează departamentele companiei</p>
    </div>
    <span class="badge bg-primary fs-6">Total: <?php echo count($departments); ?></span>
</div>

<!-- Formular adăugare departament -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-plus-circle me-2"></i>Adaugă Departament Nou
        </h5>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?php if(function_exists('csrf_field')) echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            
            <div class="col-md-6">
                <label class="form-label">Nume Departament <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="Ex: Resurse Umane" required>
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Manager Departament</label>
                <select name="manager_id" class="form-select">
                    <option value="">-- Selectează Manager --</option>
                    <?php foreach($managers as $manager): ?>
                        <option value="<?php echo $manager['id']; ?>">
                            <?php echo htmlspecialchars($manager['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-plus-lg me-1"></i>Adaugă
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lista departamente -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-building me-2"></i>Departamente Existente
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th width="80">#</th>
                    <th>Nume Departament</th>
                    <th>Manager</th>
                    <th width="120">Utilizatori</th>
                    <th width="150">Data Creării</th>
                    <th width="100">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if($departments): ?>
                    <?php foreach($departments as $index => $dept): ?>
                        <tr>
                            <td>
                                <span class="badge bg-secondary"><?php echo $index + 1; ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($dept['name']); ?></strong>
                            </td>
                            <td>
                                <?php if($dept['manager_name']): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-person-check me-1"></i>
                                        <?php echo htmlspecialchars($dept['manager_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">
                                        <i class="bi bi-person-dash me-1"></i>Fără manager
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <i class="bi bi-people me-1"></i>
                                    <?php echo (int)$dept['user_count']; ?> utilizatori
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('d.m.Y H:i', strtotime($dept['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php if((int)$dept['user_count'] === 0): ?>
                                    <form method="post" style="display: inline;" 
                                          onsubmit="return confirm('Sigur ștergi departamentul <?php echo htmlspecialchars($dept['name']); ?>?')">
                                        <?php if(function_exists('csrf_field')) echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="dept_id" value="<?php echo $dept['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Șterge departament">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary btn-sm" disabled title="Nu se poate șterge - are utilizatori">
                                        <i class="bi bi-lock"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-building display-4 d-block mb-3"></i>
                                <h5>Niciun departament găsit</h5>
                                <p>Adaugă primul departament folosind formularul de mai sus.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if(count($departments) > 0): ?>
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-building text-primary display-6"></i>
                <h5 class="mt-2">Total Departamente</h5>
                <h3 class="text-primary"><?php echo count($departments); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-person-check text-success display-6"></i>
                <h5 class="mt-2">Cu Manager</h5>
                <h3 class="text-success">
                    <?php echo count(array_filter($departments, fn($d) => !empty($d['manager_name']))); ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body text-center">
                <i class="bi bi-people text-info display-6"></i>
                <h5 class="mt-2">Total Utilizatori</h5>
                <h3 class="text-info">
                    <?php echo array_sum(array_column($departments, 'user_count')); ?>
                </h3>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>