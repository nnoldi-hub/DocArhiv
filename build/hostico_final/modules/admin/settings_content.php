<?php
/**
 * Settings Content - Setări Companie
 */

// Hint de debug non-intruziv pentru a confirma randarea (se vede doar în sursa paginii)
echo "<!-- settings_content loaded -->";

$company_id = (int)($_SESSION['company_id'] ?? 0);
$db = getDBConnection();

// Mesaje pentru utilizator
$success_message = '';
$error_message = '';

// Preluat setările companiei
$stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch();

if (!$company) {
    $error_message = "Compania nu a fost găsită.";
}

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $company) {
    if (!verify_csrf()) {
        $error_message = "Token CSRF invalid.";
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            switch ($action) {
                case 'update_branding':
                    $company_name = trim($_POST['company_name'] ?? '');
                    $primary_color = trim($_POST['primary_color'] ?? '#007bff');
                    $secondary_color = trim($_POST['secondary_color'] ?? '#6c757d');
                    
                    if (empty($company_name)) {
                        throw new Exception("Numele companiei este obligatoriu.");
                    }
                    
                    // Validare culori hex
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $primary_color) || 
                        !preg_match('/^#[0-9A-Fa-f]{6}$/', $secondary_color)) {
                        throw new Exception("Culorile trebuie să fie în format hex valid.");
                    }
                    
                        $stmt = $db->prepare("
                            UPDATE companies 
                            SET company_name = ?, primary_color = ?, secondary_color = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$company_name, $primary_color, $secondary_color, $company_id]);
                    
                    $success_message = "Setările de branding au fost actualizate cu succes.";
                    
                    // Reîncarcă datele companiei
                    $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
                    $stmt->execute([$company_id]);
                    $company = $stmt->fetch();
                    if (!empty($company['company_name'])) {
                        $_SESSION['company_name'] = $company['company_name'];
                    }
                    break;
                    
                case 'update_storage':
                    $max_file_size = (int)($_POST['max_file_size'] ?? 10);
                    $max_storage = (int)($_POST['max_storage'] ?? 1000);
                    $allowed_extensions = trim($_POST['allowed_extensions'] ?? '');
                    
                    if ($max_file_size < 1 || $max_file_size > 100) {
                        throw new Exception("Mărimea maximă a fișierului trebuie să fie între 1-100 MB.");
                    }
                    
                    if ($max_storage < 100 || $max_storage > 10000) {
                        throw new Exception("Spațiul de stocare trebuie să fie între 100-10000 MB.");
                    }
                    
                    $stmt = $db->prepare("
                        UPDATE companies 
                        SET max_file_size = ?, max_storage = ?, allowed_extensions = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$max_file_size, $max_storage, $allowed_extensions, $company_id]);
                    
                    $success_message = "Setările de stocare au fost actualizate cu succes.";
                    
                    // Reîncarcă datele companiei
                    $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
                    $stmt->execute([$company_id]);
                    $company = $stmt->fetch();
                    break;
                    
                case 'update_security':
                    $session_timeout = (int)($_POST['session_timeout'] ?? 3600);
                    $password_min_length = (int)($_POST['password_min_length'] ?? 8);
                    $require_uppercase = isset($_POST['require_uppercase']) ? 1 : 0;
                    $require_numbers = isset($_POST['require_numbers']) ? 1 : 0;
                    $require_symbols = isset($_POST['require_symbols']) ? 1 : 0;
                    $login_attempts = (int)($_POST['login_attempts'] ?? 5);
                    
                    if ($session_timeout < 300 || $session_timeout > 86400) {
                        throw new Exception("Timpul de expirare sesiune trebuie să fie între 5 minute și 24 ore.");
                    }
                    
                    if ($password_min_length < 6 || $password_min_length > 50) {
                        throw new Exception("Lungimea minimă a parolei trebuie să fie între 6-50 caractere.");
                    }
                    
                    if ($login_attempts < 3 || $login_attempts > 20) {
                        throw new Exception("Numărul de încercări trebuie să fie între 3-20.");
                    }
                    
                    $stmt = $db->prepare("
                        UPDATE companies 
                        SET session_timeout = ?, password_min_length = ?, require_uppercase = ?, 
                            require_numbers = ?, require_symbols = ?, login_attempts = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $session_timeout, $password_min_length, $require_uppercase, 
                        $require_numbers, $require_symbols, $login_attempts, $company_id
                    ]);
                    
                    $success_message = "Setările de securitate au fost actualizate cu succes.";
                    
                    // Reîncarcă datele companiei
                    $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
                    $stmt->execute([$company_id]);
                    $company = $stmt->fetch();
                    break;
                    
                case 'update_notifications':
                    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
                    $notification_email = trim($_POST['notification_email'] ?? '');
                    $notify_new_users = isset($_POST['notify_new_users']) ? 1 : 0;
                    $notify_new_documents = isset($_POST['notify_new_documents']) ? 1 : 0;
                    $notify_document_shared = isset($_POST['notify_document_shared']) ? 1 : 0;
                    
                    if ($email_notifications && empty($notification_email)) {
                        throw new Exception("Email-ul pentru notificări este obligatoriu când sunt activate notificările.");
                    }
                    
                    if (!empty($notification_email) && !filter_var($notification_email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Email-ul pentru notificări nu este valid.");
                    }
                    
                    $stmt = $db->prepare("
                        UPDATE companies 
                        SET email_notifications = ?, notification_email = ?, notify_new_users = ?, 
                            notify_new_documents = ?, notify_document_shared = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $email_notifications, $notification_email, $notify_new_users, 
                        $notify_new_documents, $notify_document_shared, $company_id
                    ]);
                    
                    $success_message = "Setările de notificări au fost actualizate cu succes.";
                    
                    // Reîncarcă datele companiei
                    $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
                    $stmt->execute([$company_id]);
                    $company = $stmt->fetch();
                    break;
                    
                default:
                    throw new Exception("Acțiune invalidă.");
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Calculează spațiul folosit (în bytes) din tabela documents: încearcă file_size, apoi fallback pe size
try {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(file_size), 0) AS used_storage
        FROM documents
        WHERE company_id = ?
    ");
    $stmt->execute([$company_id]);
    $storage_info = $stmt->fetch();
    $used_storage = (int)($storage_info['used_storage'] ?? 0);
    if ($used_storage === 0) {
        try {
            $stmt2 = $db->prepare("
                SELECT COALESCE(SUM(size), 0) AS used_storage
                FROM documents
                WHERE company_id = ?
            ");
            $stmt2->execute([$company_id]);
            $storage_info2 = $stmt2->fetch();
            $used_storage = (int)($storage_info2['used_storage'] ?? 0);
        } catch (Exception $e2) {
            $used_storage = 0;
        }
    }
} catch (Exception $e) {
    try {
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(size), 0) AS used_storage
            FROM documents
            WHERE company_id = ?
        ");
        $stmt->execute([$company_id]);
        $storage_info = $stmt->fetch();
        $used_storage = (int)($storage_info['used_storage'] ?? 0);
    } catch (Exception $e2) {
        $used_storage = 0;
    }
}
$used_storage_mb = round($used_storage / (1024 * 1024), 2);
$max_storage_mb = (int)($company['max_storage'] ?? 1000);
$storage_percentage = $max_storage_mb > 0 ? round(($used_storage_mb / $max_storage_mb) * 100, 1) : 0;

                    // Culorile predefinite pentru branding
$color_palette = [
    '#007bff', '#6610f2', '#6f42c1', '#e83e8c', '#dc3545', '#fd7e14',
    '#ffc107', '#28a745', '#20c997', '#17a2b8', '#6c757d', '#343a40',
    '#0056b3', '#004085', '#495057', '#e74c3c', '#f39c12', '#27ae60',
    '#2980b9', '#8e44ad', '#34495e', '#16a085', '#f1c40f', '#e67e22'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="bi bi-gear-fill me-2"></i>Setări Companie
    </h4>
    <div class="d-flex gap-2">
        <span class="badge bg-info">
            <i class="bi bi-hdd-stack me-1"></i>
            Stocare: <?= $used_storage_mb ?> / <?= $max_storage_mb ?> MB (<?= $storage_percentage ?>%)
        </span>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($company): ?>
<!-- Tabs pentru setări -->
<ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="branding-tab" data-bs-toggle="tab" data-bs-target="#branding" type="button" role="tab">
            <i class="bi bi-palette-fill me-1"></i>Branding
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="storage-tab" data-bs-toggle="tab" data-bs-target="#storage" type="button" role="tab">
            <i class="bi bi-hdd me-1"></i>Stocare & Fișiere
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
            <i class="bi bi-shield-check me-1"></i>Securitate
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
            <i class="bi bi-bell me-1"></i>Notificări
        </button>
    </li>
</ul>

<div class="tab-content" id="settingsTabsContent">
    
    <!-- Tab Branding -->
    <div class="tab-pane fade show active" id="branding" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-palette-fill me-2"></i>Branding & Identitate Vizuală
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_branding">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Numele Companiei</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                           value="<?= htmlspecialchars($company['company_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="primary_color" class="form-label">Culoare Primară</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" id="primary_color" 
                                           name="primary_color" value="<?= htmlspecialchars($company['primary_color'] ?? '#007bff') ?>">
                                    <input type="text" class="form-control" 
                                           value="<?= htmlspecialchars($company['primary_color'] ?? '#007bff') ?>" 
                                           id="primary_color_text" readonly>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="secondary_color" class="form-label">Culoare Secundară</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" id="secondary_color" 
                                           name="secondary_color" value="<?= htmlspecialchars($company['secondary_color'] ?? '#6c757d') ?>">
                                    <input type="text" class="form-control" 
                                           value="<?= htmlspecialchars($company['secondary_color'] ?? '#6c757d') ?>" 
                                           id="secondary_color_text" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Culori Predefinite</label>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <?php foreach ($color_palette as $color): ?>
                                        <button type="button" class="btn p-0 border" 
                                                style="width: 30px; height: 30px; background-color: <?= $color ?>;"
                                                onclick="setColor('primary_color', '<?= $color ?>')"
                                                title="<?= $color ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Preview</label>
                                <div class="border rounded p-3" id="color_preview">
                                    <div class="d-flex gap-2">
                                        <span class="badge" id="primary_preview" 
                                              style="background-color: <?= htmlspecialchars($company['primary_color'] ?? '#007bff') ?>;">
                                            Culoare Primară
                                        </span>
                                        <span class="badge" id="secondary_preview" 
                                              style="background-color: <?= htmlspecialchars($company['secondary_color'] ?? '#6c757d') ?>;">
                                            Culoare Secundară
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Salvează Setările Branding
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Tab Stocare & Fișiere -->
    <div class="tab-pane fade" id="storage" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-hdd me-2"></i>Stocare & Limite Fișiere
                </h5>
            </div>
            <div class="card-body">
                <!-- Info stocare actuală -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Utilizare Stocare</h6>
                                <div class="progress mb-2">
                                    <div class="progress-bar <?= $storage_percentage > 80 ? 'bg-danger' : ($storage_percentage > 60 ? 'bg-warning' : 'bg-success') ?>" 
                                         style="width: <?= min($storage_percentage, 100) ?>%"></div>
                                </div>
                                <small class="text-muted"><?= $used_storage_mb ?> MB din <?= $max_storage_mb ?> MB (<?= $storage_percentage ?>%)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Statistici Fișiere</h6>
                                <?php
                                $stmt = $db->prepare("SELECT COUNT(*) as total_files FROM documents WHERE company_id = ?");
                                $stmt->execute([$company_id]);
                                $file_stats = $stmt->fetch();
                                ?>
                                <p class="card-text mb-0">
                                    <i class="bi bi-file-earmark me-1"></i>
                                    Total fișiere: <strong><?= number_format($file_stats['total_files'] ?? 0) ?></strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_storage">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_file_size" class="form-label">Mărime Maximă Fișier (MB)</label>
                                <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                       value="<?= (int)($company['max_file_size'] ?? 10) ?>" min="1" max="100" required>
                                <div class="form-text">Mărimea maximă permisă pentru un fișier încărcat (1-100 MB)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="max_storage" class="form-label">Spațiu Total Stocare (MB)</label>
                                <input type="number" class="form-control" id="max_storage" name="max_storage" 
                                       value="<?= (int)($company['max_storage'] ?? 1000) ?>" min="100" max="10000" required>
                                <div class="form-text">Spațiul total de stocare pentru companie (100-10000 MB)</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="allowed_extensions" class="form-label">Extensii Permise</label>
                                <textarea class="form-control" id="allowed_extensions" name="allowed_extensions" rows="4"
                                          placeholder="pdf,doc,docx,txt,jpg,png..."><?= htmlspecialchars($company['allowed_extensions'] ?? 'pdf,doc,docx,txt,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif') ?></textarea>
                                <div class="form-text">Extensiile de fișiere permise, separate prin virgulă</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Salvează Setările Stocare
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Tab Securitate -->
    <div class="tab-pane fade" id="security" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-shield-check me-2"></i>Politici Securitate
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_security">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Sesiuni</h6>
                            <div class="mb-3">
                                <label for="session_timeout" class="form-label">Expirare Sesiune (secunde)</label>
                                <select class="form-select" id="session_timeout" name="session_timeout">
                                    <option value="300" <?= (int)($company['session_timeout'] ?? 3600) === 300 ? 'selected' : '' ?>>5 minute</option>
                                    <option value="900" <?= (int)($company['session_timeout'] ?? 3600) === 900 ? 'selected' : '' ?>>15 minute</option>
                                    <option value="1800" <?= (int)($company['session_timeout'] ?? 3600) === 1800 ? 'selected' : '' ?>>30 minute</option>
                                    <option value="3600" <?= (int)($company['session_timeout'] ?? 3600) === 3600 ? 'selected' : '' ?>>1 oră</option>
                                    <option value="7200" <?= (int)($company['session_timeout'] ?? 3600) === 7200 ? 'selected' : '' ?>>2 ore</option>
                                    <option value="14400" <?= (int)($company['session_timeout'] ?? 3600) === 14400 ? 'selected' : '' ?>>4 ore</option>
                                    <option value="28800" <?= (int)($company['session_timeout'] ?? 3600) === 28800 ? 'selected' : '' ?>>8 ore</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="login_attempts" class="form-label">Încercări Login Maxime</label>
                                <input type="number" class="form-control" id="login_attempts" name="login_attempts" 
                                       value="<?= (int)($company['login_attempts'] ?? 5) ?>" min="3" max="20" required>
                                <div class="form-text">Numărul maxim de încercări de autentificare eșuate</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Politici Parole</h6>
                            <div class="mb-3">
                                <label for="password_min_length" class="form-label">Lungime Minimă Parolă</label>
                                <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                       value="<?= (int)($company['password_min_length'] ?? 8) ?>" min="6" max="50" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Cerințe Parole</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="require_uppercase" name="require_uppercase" 
                                           <?= (int)($company['require_uppercase'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="require_uppercase">
                                        Cel puțin o literă mare
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="require_numbers" name="require_numbers" 
                                           <?= (int)($company['require_numbers'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="require_numbers">
                                        Cel puțin o cifră
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="require_symbols" name="require_symbols" 
                                           <?= (int)($company['require_symbols'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="require_symbols">
                                        Cel puțin un simbol special
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Salvează Setările Securitate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Tab Notificări -->
    <div class="tab-pane fade" id="notifications" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bell me-2"></i>Notificări Email
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_notifications">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" 
                                           name="email_notifications" <?= (int)($company['email_notifications'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="email_notifications">
                                        <strong>Activează Notificările Email</strong>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notification_email" class="form-label">Email pentru Notificări</label>
                                <input type="email" class="form-control" id="notification_email" name="notification_email" 
                                       value="<?= htmlspecialchars($company['notification_email'] ?? '') ?>"
                                       placeholder="admin@compania.ro">
                                <div class="form-text">Email-ul la care se vor trimite notificările</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tipuri de Notificări</label>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notify_new_users" 
                                       name="notify_new_users" <?= (int)($company['notify_new_users'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notify_new_users">
                                    Utilizatori noi înregistrați
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notify_new_documents" 
                                       name="notify_new_documents" <?= (int)($company['notify_new_documents'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notify_new_documents">
                                    Documente noi încărcate
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notify_document_shared" 
                                       name="notify_document_shared" <?= (int)($company['notify_document_shared'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notify_document_shared">
                                    Documente partajate
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Salvează Setările Notificări
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
</div>

<script>
// Funcții pentru color picker și preview
function setColor(inputId, color) {
    const colorInput = document.getElementById(inputId);
    const textInput = document.getElementById(inputId + '_text');
    
    if (colorInput && textInput) {
        colorInput.value = color;
        textInput.value = color;
        updateColorPreview();
    }
}

function updateColorPreview() {
    const primaryColor = document.getElementById('primary_color').value;
    const secondaryColor = document.getElementById('secondary_color').value;
    
    const primaryPreview = document.getElementById('primary_preview');
    const secondaryPreview = document.getElementById('secondary_preview');
    
    if (primaryPreview) {
        primaryPreview.style.backgroundColor = primaryColor;
    }
    if (secondaryPreview) {
        secondaryPreview.style.backgroundColor = secondaryColor;
    }
}

// Event listeners pentru color inputs
document.addEventListener('DOMContentLoaded', function() {
    const primaryColorInput = document.getElementById('primary_color');
    const secondaryColorInput = document.getElementById('secondary_color');
    const primaryTextInput = document.getElementById('primary_color_text');
    const secondaryTextInput = document.getElementById('secondary_color_text');
    
    if (primaryColorInput && primaryTextInput) {
        primaryColorInput.addEventListener('input', function() {
            primaryTextInput.value = this.value;
            updateColorPreview();
        });
    }
    
    if (secondaryColorInput && secondaryTextInput) {
        secondaryColorInput.addEventListener('input', function() {
            secondaryTextInput.value = this.value;
            updateColorPreview();
        });
    }
    
    // Activare/dezactivare câmpuri notificări
    const emailNotificationsCheckbox = document.getElementById('email_notifications');
    const notificationEmailInput = document.getElementById('notification_email');
    const notificationCheckboxes = document.querySelectorAll('input[name^="notify_"]');
    
    if (emailNotificationsCheckbox && notificationEmailInput) {
        function toggleNotificationFields() {
            const isEnabled = emailNotificationsCheckbox.checked;
            notificationEmailInput.disabled = !isEnabled;
            notificationCheckboxes.forEach(checkbox => {
                checkbox.disabled = !isEnabled;
            });
        }
        
        emailNotificationsCheckbox.addEventListener('change', toggleNotificationFields);
        toggleNotificationFields(); // Set initial state
    }
});
</script>

<?php else: ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Compania nu a fost găsită sau nu aveți permisiuni pentru a accesa această pagină.
    </div>
<?php endif; ?>
