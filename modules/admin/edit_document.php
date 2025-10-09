<?php
require_once __DIR__ . '/../../includes/classes/database.php';
require_once __DIR__ . '/../../includes/functions/security.php';
require_once __DIR__ . '/../../includes/functions/helpers.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect(APP_URL . '/login.php');
    exit;
}

$company_id = (int)($_SESSION['company_id'] ?? 0);
$document_id = (int)($_GET['id'] ?? 0);

if ($document_id <= 0) {
    $_SESSION['error'] = 'Document invalid.';
    redirect(APP_URL . '/admin-documents.php');
    exit;
}

try {
    $db = new Database();
    
    // Obține datele documentului
    $stmt = $db->query("
        SELECT d.*, dept.name as department_name,
               GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ') as tag_names,
               GROUP_CONCAT(t.id ORDER BY t.name SEPARATOR ',') as tag_ids
        FROM documents d 
        LEFT JOIN departments dept ON d.department_id = dept.id
        LEFT JOIN document_tags dt ON d.id = dt.document_id
        LEFT JOIN tags t ON dt.tag_id = t.id
        WHERE d.id = :doc_id AND d.company_id = :company_id AND d.status = 'active'
        GROUP BY d.id
    ");
    
    $stmt->bind(':doc_id', $document_id);
    $stmt->bind(':company_id', $company_id);
    $document = $stmt->fetch();
    
    if (!$document) {
        $_SESSION['error'] = 'Documentul nu a fost găsit.';
        redirect(APP_URL . '/admin-documents.php');
        exit;
    }
    
    // Obține toate departamentele pentru dropdown
    $dept_stmt = $db->query("SELECT id, name FROM departments WHERE company_id = :cid AND status = 'active' ORDER BY name");
    $dept_stmt->bind(':cid', $company_id);
    $departments = $dept_stmt->fetchAll();
    
    // Obține toate tagurile pentru sugestii
    $tags_stmt = $db->query("SELECT name FROM tags WHERE company_id = :cid ORDER BY usage_count DESC, name");
    $tags_stmt->bind(':cid', $company_id);
    $all_tags = $tags_stmt->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Eroare la încărcarea documentului: ' . $e->getMessage();
    redirect(APP_URL . '/admin-documents.php');
    exit;
}

$page_title = 'Editare Document - ' . htmlspecialchars($document['title']);
require_once __DIR__ . '/../../public/admin-header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../../public/admin-sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-pencil-square me-2"></i>Editare Document
                </h1>
                <div class="btn-toolbar">
                    <a href="<?= APP_URL ?>/admin-documents.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Înapoi la documente
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-text me-2"></i>
                        <?= htmlspecialchars($document['title']) ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= APP_URL ?>/admin-update-document.php">
                        <input type="hidden" name="document_id" value="<?= $document['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="row g-3">
                            <!-- Informații fișier (readonly) -->
                            <div class="col-md-6">
                                <label class="form-label">Nume fișier original</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($document['original_filename']) ?>" readonly>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Mărime</label>
                                <input type="text" class="form-control" value="<?= formatFileSize($document['file_size']) ?>" readonly>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Data creării</label>
                                <input type="text" class="form-control" value="<?= date('d.m.Y H:i', strtotime($document['created_at'])) ?>" readonly>
                            </div>
                            
                            <!-- Câmpuri editabile -->
                            <div class="col-md-8">
                                <label for="title" class="form-label">Titlu document *</label>
                                <input type="text" id="title" name="title" class="form-control" 
                                       value="<?= htmlspecialchars($document['title']) ?>" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="department_id" class="form-label">Departament</label>
                                <select id="department_id" name="department_id" class="form-select">
                                    <option value="">Fără departament</option>
                                    <?php foreach($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" 
                                                <?= ($dept['id'] == $document['department_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label for="description" class="form-label">Descriere</label>
                                <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($document['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label for="tags" class="form-label">Taguri</label>
                                <input type="text" id="tags" name="tags" class="form-control" 
                                       value="<?= htmlspecialchars($document['tag_names'] ?? '') ?>"
                                       placeholder="Introdu taguri separate prin virgulă (ex: contract, legal, 2025)">
                                <div class="form-text">
                                    Taguri existente: 
                                    <?php if (!empty($all_tags)): ?>
                                        <?php foreach(array_slice($all_tags, 0, 10) as $tag): ?>
                                            <span class="badge bg-light text-dark me-1 cursor-pointer tag-suggestion" 
                                                  onclick="addTag('<?= htmlspecialchars($tag['name']) ?>')"><?= htmlspecialchars($tag['name']) ?></span>
                                        <?php endforeach; ?>
                                        <?php if(count($all_tags) > 10): ?>
                                            <small class="text-muted">... și alte <?= count($all_tags) - 10 ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small class="text-muted">Nu există taguri create încă</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Informații suplimentare opționale -->
                            <div class="col-md-6">
                                <label for="document_number" class="form-label">Număr document</label>
                                <input type="text" id="document_number" name="document_number" class="form-control" 
                                       value="<?= htmlspecialchars($document['document_number'] ?? '') ?>"
                                       placeholder="Ex: DOC-2025-001">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="document_date" class="form-label">Data document</label>
                                <input type="date" id="document_date" name="document_date" class="form-control" 
                                       value="<?= $document['document_date'] ?? '' ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="expiry_date" class="form-label">Data expirare</label>
                                <input type="date" id="expiry_date" name="expiry_date" class="form-control" 
                                       value="<?= $document['expiry_date'] ?? '' ?>">
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_confidential" name="is_confidential" value="1"
                                           <?= ($document['is_confidential'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_confidential">
                                        Document confidențial
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-check-lg me-1"></i>Salvează modificările
                                </button>
                                <a href="<?= APP_URL ?>/admin-documents.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg me-1"></i>Anulează
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Funcție pentru adăugarea rapidă de taguri
function addTag(tagName) {
    const tagsInput = document.getElementById('tags');
    const currentTags = tagsInput.value.trim();
    
    // Verifică dacă tag-ul nu există deja
    const tagsArray = currentTags ? currentTags.split(',').map(t => t.trim().toLowerCase()) : [];
    if (tagsArray.includes(tagName.toLowerCase())) {
        return; // Tag-ul există deja
    }
    
    // Adaugă tag-ul
    if (currentTags) {
        tagsInput.value = currentTags + ', ' + tagName;
    } else {
        tagsInput.value = tagName;
    }
}

// Style pentru tag suggestions
document.addEventListener('DOMContentLoaded', function() {
    const tagSuggestions = document.querySelectorAll('.tag-suggestion');
    tagSuggestions.forEach(tag => {
        tag.style.cursor = 'pointer';
        tag.addEventListener('mouseover', function() {
            this.classList.remove('bg-light', 'text-dark');
            this.classList.add('bg-primary', 'text-white');
        });
        tag.addEventListener('mouseout', function() {
            this.classList.remove('bg-primary', 'text-white');
            this.classList.add('bg-light', 'text-dark');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../public/admin-footer.php'; ?>