<?php
$company_id = (int)($_SESSION['company_id'] ?? 0);

// Paleta de culori predefinite pentru taguri și departamente
$color_palette = [
    '#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1',
    '#e83e8c', '#fd7e14', '#20c997', '#6c757d', '#343a40', '#f8f9fa',
    '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3',
    '#54a0ff', '#5f27cd', '#00d2d3', '#ff9f43', '#c44569', '#40407a'
];

// Acțiuni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verify_csrf')) verify_csrf();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_tag') {
        $name = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? '#007bff';
        $description = trim($_POST['description'] ?? '');
        
        if ($name) {
            try {
                $db = getDBConnection();
                
                // Verifică dacă tag-ul există deja
                $stmt = $db->prepare("SELECT id FROM tags WHERE company_id = ? AND name = ?");
                $stmt->execute([$company_id, $name]);
                
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'Tag-ul există deja!';
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO tags (company_id, name, color, description, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$company_id, $name, $color, $description]);
                    $_SESSION['success'] = 'Tag adăugat cu succes!';
                }
            } catch(Exception $e) {
                $_SESSION['error'] = 'Eroare la adăugarea tag-ului: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = 'Numele tag-ului este obligatoriu!';
        }
    }
    
    if ($action === 'edit_tag') {
        $tag_id = (int)($_POST['tag_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? '#007bff';
        $description = trim($_POST['description'] ?? '');
        
        if ($tag_id && $name) {
            try {
                $db = getDBConnection();
                
                // Verifică dacă alt tag cu același nume există
                $stmt = $db->prepare("SELECT id FROM tags WHERE company_id = ? AND name = ? AND id != ?");
                $stmt->execute([$company_id, $name, $tag_id]);
                
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'Un alt tag cu acest nume există deja!';
                } else {
                    $stmt = $db->prepare("
                        UPDATE tags 
                        SET name = ?, color = ?, description = ? 
                        WHERE id = ? AND company_id = ?
                    ");
                    $stmt->execute([$name, $color, $description, $tag_id, $company_id]);
                    $_SESSION['success'] = 'Tag actualizat cu succes!';
                }
            } catch(Exception $e) {
                $_SESSION['error'] = 'Eroare la actualizarea tag-ului: ' . $e->getMessage();
            }
        }
    }
    
    if ($action === 'delete_tag') {
        $tag_id = (int)($_POST['tag_id'] ?? 0);
        
        if ($tag_id) {
            try {
                $db = getDBConnection();
                
                // Verifică dacă tag-ul este folosit
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM document_tags WHERE tag_id = ?");
                $stmt->execute([$tag_id]);
                $usage_count = $stmt->fetch()['count'];
                
                if ($usage_count > 0) {
                    $_SESSION['error'] = 'Nu se poate șterge tag-ul! Este folosit de ' . $usage_count . ' documente.';
                } else {
                    $stmt = $db->prepare("DELETE FROM tags WHERE id = ? AND company_id = ?");
                    $stmt->execute([$tag_id, $company_id]);
                    $_SESSION['success'] = 'Tag șters cu succes!';
                }
            } catch(Exception $e) {
                $_SESSION['error'] = 'Eroare la ștergerea tag-ului: ' . $e->getMessage();
            }
        }
    }
    
    if ($action === 'update_department_color') {
        $dept_id = (int)($_POST['dept_id'] ?? 0);
        $color = $_POST['color'] ?? '#007bff';
        
        if ($dept_id) {
            try {
                $db = getDBConnection();
                $stmt = $db->prepare("UPDATE departments SET color = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$color, $dept_id, $company_id]);
                $_SESSION['success'] = 'Culoarea departamentului a fost actualizată!';
            } catch(Exception $e) {
                $_SESSION['error'] = 'Eroare la actualizarea culorii: ' . $e->getMessage();
            }
        }
    }
    
    redirect(APP_URL . '/admin-tags.php');
    exit;
}

// Obține tag-urile și departamentele
$tags = [];
$departments = [];

try {
    $db = getDBConnection();
    
    // Obține tag-urile cu numărul de utilizări
    $stmt = $db->prepare("
        SELECT t.*, 
               (SELECT COUNT(*) FROM document_tags WHERE tag_id = t.id) as usage_count
        FROM tags t
        WHERE t.company_id = ?
        ORDER BY t.name
    ");
    $stmt->execute([$company_id]);
    $tags = $stmt->fetchAll();
    
    // Obține departamentele pentru setarea culorilor
    $stmt = $db->prepare("
        SELECT id, name, color, 
               (SELECT COUNT(*) FROM users WHERE department_id = d.id) as user_count
        FROM departments d
        WHERE d.company_id = ?
        ORDER BY d.name
    ");
    $stmt->execute([$company_id]);
    $departments = $stmt->fetchAll();
    
} catch(Exception $e) {
    $_SESSION['error'] = 'Eroare la încărcarea datelor: ' . $e->getMessage();
}
?>

<!-- Header și statistici -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Gestiune Taguri & Culori</h4>
        <p class="text-muted mb-0">Administrează tagurile pentru documente și culorile departamentelor</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-primary fs-6">Taguri: <?php echo count($tags); ?></span>
        <span class="badge bg-secondary fs-6">Departamente: <?php echo count($departments); ?></span>
    </div>
</div>

<!-- Secțiunea Taguri -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-tags me-2"></i>Taguri pentru Documente
        </h5>
    </div>
    <div class="card-body">
        <!-- Formular adăugare tag -->
        <form method="post" class="row g-3 mb-4">
            <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
            <input type="hidden" name="action" value="add_tag">
            
            <div class="col-md-4">
                <label class="form-label">Nume Tag <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="Ex: Urgent" required>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Culoare</label>
                <div class="input-group">
                    <input type="color" name="color" class="form-control form-control-color" value="#007bff">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-palette"></i>
                    </button>
                    <div class="dropdown-menu p-2" style="width: 200px;">
                        <div class="row g-1">
                            <?php foreach(array_chunk($color_palette, 6) as $row): ?>
                                <div class="col-12 d-flex gap-1">
                                    <?php foreach($row as $color): ?>
                                        <div class="color-picker-option" 
                                             style="width: 25px; height: 25px; background-color: <?php echo $color; ?>; border: 1px solid #ccc; cursor: pointer;"
                                             onclick="selectColor('<?php echo $color; ?>', 'color')"></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Descriere</label>
                <input type="text" name="description" class="form-control" placeholder="Opțional">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-plus-lg me-1"></i>Adaugă
                </button>
            </div>
        </form>
        
        <!-- Lista taguri -->
        <div class="row g-2">
            <?php if($tags): ?>
                <?php foreach($tags as $tag): ?>
                    <div class="col-auto">
                        <div class="badge position-relative p-2 d-flex align-items-center gap-2" 
                             style="background-color: <?php echo htmlspecialchars($tag['color']); ?>; color: <?php echo (hexdec(substr($tag['color'], 1, 2)) + hexdec(substr($tag['color'], 3, 2)) + hexdec(substr($tag['color'], 5, 2))) > 380 ? '#000' : '#fff'; ?>;">
                            <i class="bi bi-tag-fill"></i>
                            <span><?php echo htmlspecialchars($tag['name']); ?></span>
                            <?php if($tag['usage_count'] > 0): ?>
                                <span class="badge bg-light text-dark"><?php echo $tag['usage_count']; ?></span>
                            <?php endif; ?>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-sm p-0 border-0" 
                                        style="background: none; color: inherit;"
                                        onclick="editTag(<?php echo htmlspecialchars(json_encode($tag)); ?>)"
                                        title="Editează tag">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if($tag['usage_count'] == 0): ?>
                                    <form method="post" style="display: inline;" 
                                          onsubmit="return confirm('Sigur ștergi tag-ul <?php echo htmlspecialchars($tag['name']); ?>?')">
                                        <?php if(function_exists('csrf_field')) echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete_tag">
                                        <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                        <button type="submit" class="btn btn-sm p-0 border-0" 
                                                style="background: none; color: inherit;"
                                                title="Șterge tag">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if($tag['description']): ?>
                            <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($tag['description']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-tags display-4 d-block mb-3"></i>
                        <h5>Niciun tag găsit</h5>
                        <p>Adaugă primul tag folosind formularul de mai sus.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Secțiunea Culori Departamente -->
<?php if($departments): ?>
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-palette me-2"></i>Culori Departamente
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Setează culori pentru departamente pentru o identificare vizuală mai ușoară.</p>
        
        <div class="row g-3">
            <?php foreach($departments as $dept): ?>
                <div class="col-md-6">
                    <div class="card border">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="width: 24px; height: 24px; background-color: <?php echo htmlspecialchars($dept['color'] ?? '#007bff'); ?>; border-radius: 4px;"></div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($dept['name']); ?></strong>
                                        <br><small class="text-muted"><?php echo $dept['user_count']; ?> utilizatori</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                        onclick="editDepartmentColor(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['name']); ?>', '<?php echo htmlspecialchars($dept['color'] ?? '#007bff'); ?>')">
                                    <i class="bi bi-palette"></i> Culoare
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Editare Tag -->
<div class="modal fade" id="editTagModal" tabindex="-1" aria-labelledby="editTagModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTagModalLabel">
                    <i class="bi bi-tag me-2"></i>Editează Tag
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editTagForm">
                <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
                <input type="hidden" name="action" value="edit_tag">
                <input type="hidden" name="tag_id" id="edit_tag_id">
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nume Tag <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_tag_name" class="form-control" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Culoare</label>
                            <div class="input-group">
                                <input type="color" name="color" id="edit_tag_color" class="form-control form-control-color">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="bi bi-palette"></i>
                                </button>
                                <div class="dropdown-menu p-2" style="width: 200px;">
                                    <div class="row g-1">
                                        <?php foreach(array_chunk($color_palette, 6) as $row): ?>
                                            <div class="col-12 d-flex gap-1">
                                                <?php foreach($row as $color): ?>
                                                    <div class="color-picker-option" 
                                                         style="width: 25px; height: 25px; background-color: <?php echo $color; ?>; border: 1px solid #ccc; cursor: pointer;"
                                                         onclick="selectColor('<?php echo $color; ?>', 'edit_tag_color')"></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Descriere</label>
                            <input type="text" name="description" id="edit_tag_description" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Anulează
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Actualizează Tag
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editare Culoare Departament -->
<div class="modal fade" id="editDepartmentColorModal" tabindex="-1" aria-labelledby="editDepartmentColorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDepartmentColorModalLabel">
                    <i class="bi bi-palette me-2"></i>Editează Culoare Departament
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editDepartmentColorForm">
                <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
                <input type="hidden" name="action" value="update_department_color">
                <input type="hidden" name="dept_id" id="edit_dept_id">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Departament</label>
                        <input type="text" id="edit_dept_name" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Culoare</label>
                        <div class="input-group">
                            <input type="color" name="color" id="edit_dept_color" class="form-control form-control-color">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-palette"></i>
                            </button>
                            <div class="dropdown-menu p-2" style="width: 200px;">
                                <div class="row g-1">
                                    <?php foreach(array_chunk($color_palette, 6) as $row): ?>
                                        <div class="col-12 d-flex gap-1">
                                            <?php foreach($row as $color): ?>
                                                <div class="color-picker-option" 
                                                     style="width: 25px; height: 25px; background-color: <?php echo $color; ?>; border: 1px solid #ccc; cursor: pointer;"
                                                     onclick="selectColor('<?php echo $color; ?>', 'edit_dept_color')"></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Anulează
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Actualizează Culoare
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectColor(color, targetFieldId) {
    document.getElementById(targetFieldId).value = color;
}

function editTag(tagData) {
    document.getElementById('edit_tag_id').value = tagData.id;
    document.getElementById('edit_tag_name').value = tagData.name;
    document.getElementById('edit_tag_color').value = tagData.color;
    document.getElementById('edit_tag_description').value = tagData.description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editTagModal'));
    modal.show();
}

function editDepartmentColor(deptId, deptName, currentColor) {
    document.getElementById('edit_dept_id').value = deptId;
    document.getElementById('edit_dept_name').value = deptName;
    document.getElementById('edit_dept_color').value = currentColor;
    
    const modal = new bootstrap.Modal(document.getElementById('editDepartmentColorModal'));
    modal.show();
}
</script>