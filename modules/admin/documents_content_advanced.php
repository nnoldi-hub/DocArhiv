<?php
/**
 * Advanced Documents Management with Filtering, Sorting & Pagination
 * modules/admin/documents_content_advanced.php
 */

$company_id = (int)($_SESSION['company_id'] ?? 0);

// Parametri de căutare și filtrare
$search = trim($_GET['q'] ?? '');
$department_id = (int)($_GET['dept'] ?? 0);
$tag_id = (int)($_GET['tag'] ?? 0);
$file_type = trim($_GET['type'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');

// Parametri de sortare
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Parametri de paginare
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20; // 20 documente per pagină
$offset = ($page - 1) * $per_page;

// Validare câmpuri de sortare
$valid_sort_fields = [
    'title' => 'd.title',
    'created_at' => 'd.created_at', 
    'file_size' => 'd.file_size',
    'original_filename' => 'd.original_filename',
    'mime_type' => 'd.mime_type'
];

$sort_field = $valid_sort_fields[$sort_by] ?? 'd.created_at';

try {
    $db = new Database();
    
    // Construiește query-ul cu JOINs și WHERE conditions
    $where_conditions = ["d.company_id = :company_id", "d.status = 'active'"];
    $params = [':company_id' => $company_id];
    
    // Adaugă condiții de filtrare
    if ($search) {
        $where_conditions[] = "(d.title LIKE :search OR d.description LIKE :search OR d.document_number LIKE :search)";
        $params[':search'] = "%{$search}%";
    }
    
    if ($department_id > 0) {
        $where_conditions[] = "d.department_id = :dept_id";
        $params[':dept_id'] = $department_id;
    }
    
    if ($file_type) {
        $where_conditions[] = "d.mime_type LIKE :file_type";
        $params[':file_type'] = "%{$file_type}%";
    }
    
    if ($date_from) {
        $where_conditions[] = "DATE(d.created_at) >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    if ($date_to) {
        $where_conditions[] = "DATE(d.created_at) <= :date_to";
        $params[':date_to'] = $date_to;
    }
    
    // JOIN pentru taguri (dacă este filtru de tag)
    $tag_join = '';
    if ($tag_id > 0) {
        $tag_join = "INNER JOIN document_tags dt ON dt.document_id = d.id";
        $where_conditions[] = "dt.tag_id = :tag_id";
        $params[':tag_id'] = $tag_id;
    }
    
    // Query principal pentru documente
    $base_query = "
        FROM documents d 
        LEFT JOIN users u ON u.id = d.created_by 
        LEFT JOIN departments dept ON dept.id = d.department_id
        {$tag_join}
        WHERE " . implode(' AND ', $where_conditions);
    
    // Count total pentru paginare
    $count_query = "SELECT COUNT(DISTINCT d.id) as total {$base_query}";
    $stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_documents = $stmt->fetchColumn();
    $total_pages = ceil($total_documents / $per_page);
    
    // Query pentru documente cu paginare
    $documents_query = "
        SELECT DISTINCT 
            d.id, d.title, d.original_filename, d.stored_filename, 
            d.file_size, d.mime_type, d.created_at, d.download_count, d.view_count,
            d.department_id, d.is_confidential,
            u.full_name as uploader_name,
            dept.name as department_name
        {$base_query}
        ORDER BY {$sort_field} {$sort_order}
        LIMIT {$per_page} OFFSET {$offset}";
    
    $stmt = $db->prepare($documents_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $documents = $stmt->fetchAll();
    
    // Obține departamentele pentru dropdown
    $departments_query = "SELECT id, name FROM departments WHERE company_id = :company_id AND status = 'active' ORDER BY name";
    $stmt = $db->prepare($departments_query);
    $stmt->bindValue(':company_id', $company_id);
    $stmt->execute();
    $departments = $stmt->fetchAll();
    
    // Obține tagurile pentru dropdown
    $tags_query = "SELECT id, name, color FROM tags WHERE company_id = :company_id ORDER BY name";
    $stmt = $db->prepare($tags_query);
    $stmt->bindValue(':company_id', $company_id);
    $stmt->execute();
    $tags = $stmt->fetchAll();
    
    // Obține tipurile de fișiere existente
    $types_query = "SELECT DISTINCT mime_type FROM documents WHERE company_id = :company_id AND status = 'active' AND mime_type IS NOT NULL ORDER BY mime_type";
    $stmt = $db->prepare($types_query);
    $stmt->bindValue(':company_id', $company_id);
    $stmt->execute();
    $file_types = $stmt->fetchAll();
    
} catch (Exception $e) {
    $documents = [];
    $total_documents = 0;
    $total_pages = 0;
    $departments = [];
    $tags = [];
    $file_types = [];
    error_log("Documents advanced query error: " . $e->getMessage());
}

// Helper functions pentru UI
function buildQueryString($overrides = []) {
    $params = array_merge($_GET, $overrides);
    unset($params['page']); // Remove page from overrides unless explicitly set
    return http_build_query(array_filter($params));
}

function getSortIcon($field) {
    global $sort_by, $sort_order;
    if ($sort_by === $field) {
        return $sort_order === 'asc' ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>';
    }
    return '<i class="bi bi-arrow-down-up text-muted"></i>';
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

function getFileTypeIcon($mime_type) {
    $icons = [
        'application/pdf' => 'file-earmark-pdf text-danger',
        'application/msword' => 'file-earmark-word text-primary', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'file-earmark-word text-primary',
        'application/vnd.ms-excel' => 'file-earmark-excel text-success',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'file-earmark-excel text-success',
        'image/jpeg' => 'file-earmark-image text-info',
        'image/png' => 'file-earmark-image text-info',
        'image/gif' => 'file-earmark-image text-info',
        'text/plain' => 'file-earmark-text text-secondary',
        'application/zip' => 'file-earmark-zip text-warning',
    ];
    
    return $icons[$mime_type] ?? 'file-earmark text-muted';
}
?>

<!-- Filters & Search Bar -->
<div class="card mb-4">
    <div class="card-header">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <h5 class="mb-0"><i class="bi bi-files me-2"></i>Gestiune Documente Avansată</h5>
            </div>
            <div class="col-md-9">
                <form method="get" action="admin-documents.php" class="row g-2">
                    <!-- Căutare text -->
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" class="form-control" placeholder="Căutare..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    
                    <!-- Filtru departament -->
                    <div class="col-md-2">
                        <select name="dept" class="form-select">
                            <option value="">Toate dept.</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= $department_id == $dept['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Filtru tag -->
                    <div class="col-md-2">
                        <select name="tag" class="form-select">
                            <option value="">Toate tagurile</option>
                            <?php foreach ($tags as $tag): ?>
                                <option value="<?= $tag['id'] ?>" <?= $tag_id == $tag['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tag['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Filtru tip fișier -->
                    <div class="col-md-2">
                        <select name="type" class="form-select">
                            <option value="">Toate tipurile</option>
                            <option value="pdf" <?= $file_type === 'pdf' ? 'selected' : '' ?>>PDF</option>
                            <option value="image" <?= $file_type === 'image' ? 'selected' : '' ?>>Imagini</option>
                            <option value="document" <?= $file_type === 'document' ? 'selected' : '' ?>>Documente</option>
                            <option value="spreadsheet" <?= $file_type === 'spreadsheet' ? 'selected' : '' ?>>Excel</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-funnel me-1"></i>Filtrează
                            </button>
                            <a href="admin-documents.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Advanced filters (collapsible) -->
<div class="card mb-4">
    <div class="card-body">
        <button class="btn btn-link p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
            <i class="bi bi-sliders me-2"></i>Filtre Avansate <i class="bi bi-chevron-down"></i>
        </button>
        
        <div class="collapse mt-3" id="advancedFilters">
            <form method="get" action="admin-documents.php" class="row g-3">
                <!-- Keep existing filters -->
                <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="dept" value="<?= $department_id ?>">
                <input type="hidden" name="tag" value="<?= $tag_id ?>">
                <input type="hidden" name="type" value="<?= htmlspecialchars($file_type) ?>">
                
                <div class="col-md-3">
                    <label class="form-label">Data de la:</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data până la:</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sortare după:</label>
                    <select name="sort" class="form-select">
                        <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Data creării</option>
                        <option value="title" <?= $sort_by === 'title' ? 'selected' : '' ?>>Nume (A-Z)</option>
                        <option value="file_size" <?= $sort_by === 'file_size' ? 'selected' : '' ?>>Mărime fișier</option>
                        <option value="original_filename" <?= $sort_by === 'original_filename' ? 'selected' : '' ?>>Nume fișier</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ordine:</label>
                    <select name="order" class="form-select">
                        <option value="desc" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Descrescător</option>
                        <option value="asc" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Crescător</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i>Aplică Filtrele
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Results Summary & Upload -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h6 class="mb-0">
            <?= number_format($total_documents) ?> documente găsite
            <?php if ($search || $department_id || $tag_id || $file_type || $date_from || $date_to): ?>
                <span class="badge bg-info">Filtrate</span>
            <?php endif; ?>
        </h6>
        <small class="text-muted">
            Pagina <?= $page ?> din <?= $total_pages ?> 
            (<?= $per_page ?> per pagină)
        </small>
    </div>
    
    <!-- Upload form -->
    <div class="card">
        <div class="card-body p-3">
            <form method="post" enctype="multipart/form-data" action="<?= APP_URL ?>/admin-upload.php" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label for="upload_file" class="form-label small">Fișier</label>
                    <input type="file" id="upload_file" name="document" class="form-control" required>
                </div>
                <div class="col-auto">
                    <label for="upload_title" class="form-label small">Titlu</label>
                    <input type="text" id="upload_title" name="title" class="form-control" placeholder="Opțional">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i>Încarcă
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Documents Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px">#</th>
                    <th style="width: 50px">
                        <a href="?<?= buildQueryString(['sort' => 'mime_type', 'order' => $sort_by === 'mime_type' && $sort_order === 'ASC' ? 'desc' : 'asc']) ?>" class="text-decoration-none">
                            Tip <?= getSortIcon('mime_type') ?>
                        </a>
                    </th>
                    <th>
                        <a href="?<?= buildQueryString(['sort' => 'title', 'order' => $sort_by === 'title' && $sort_order === 'ASC' ? 'desc' : 'asc']) ?>" class="text-decoration-none">
                            Titlu <?= getSortIcon('title') ?>
                        </a>
                    </th>
                    <th>
                        <a href="?<?= buildQueryString(['sort' => 'original_filename', 'order' => $sort_by === 'original_filename' && $sort_order === 'ASC' ? 'desc' : 'asc']) ?>" class="text-decoration-none">
                            Fișier <?= getSortIcon('original_filename') ?>
                        </a>
                    </th>
                    <th>Departament</th>
                    <th>Uploader</th>
                    <th>
                        <a href="?<?= buildQueryString(['sort' => 'file_size', 'order' => $sort_by === 'file_size' && $sort_order === 'ASC' ? 'desc' : 'asc']) ?>" class="text-decoration-none">
                            Mărime <?= getSortIcon('file_size') ?>
                        </a>
                    </th>
                    <th>
                        <a href="?<?= buildQueryString(['sort' => 'created_at', 'order' => $sort_by === 'created_at' && $sort_order === 'ASC' ? 'desc' : 'asc']) ?>" class="text-decoration-none">
                            Data <?= getSortIcon('created_at') ?>
                        </a>
                    </th>
                    <th style="width: 120px">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox display-4 d-block mb-2"></i>
                            Nu s-au găsit documente cu criteriile selectate.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documents as $index => $doc): ?>
                        <tr>
                            <td><?= $offset + $index + 1 ?></td>
                            <td>
                                <i class="bi bi-<?= getFileTypeIcon($doc['mime_type']) ?>"></i>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($doc['title']) ?></strong>
                                <?php if ($doc['is_confidential']): ?>
                                    <span class="badge bg-warning ms-1">Confidențial</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code class="small"><?= htmlspecialchars($doc['original_filename']) ?></code>
                            </td>
                            <td>
                                <?php if ($doc['department_name']): ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($doc['department_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($doc['uploader_name']) ?></td>
                            <td>
                                <span class="badge bg-light text-dark"><?= formatFileSize($doc['file_size']) ?></span>
                            </td>
                            <td>
                                <small><?= date('d.m.Y H:i', strtotime($doc['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_document.php?id=<?= $doc['id'] ?>" class="btn btn-outline-primary" title="Vezi">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="download.php?id=<?= $doc['id'] ?>" class="btn btn-outline-success" title="Descarcă">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <a href="print.php?id=<?= $doc['id'] ?>" class="btn btn-outline-info" title="Print">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <button class="btn btn-outline-danger" onclick="deleteDocument(<?= $doc['id'] ?>)" title="Șterge">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <!-- Previous -->
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= buildQueryString(['page' => $page - 1]) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    ?>
                    
                    <?php if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= buildQueryString(['page' => 1]) ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= buildQueryString(['page' => $total_pages]) ?>"><?= $total_pages ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Next -->
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= buildQueryString(['page' => $page + 1]) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="text-center mt-2">
                <small class="text-muted">
                    Afișez documentele <?= $offset + 1 ?>-<?= min($offset + $per_page, $total_documents) ?> din <?= number_format($total_documents) ?>
                </small>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteDocument(id) {
    if (confirm('Sigur vrei să ștergi acest document? Această acțiune nu poate fi anulată.')) {
        // Implementează ștergerea prin AJAX sau form
        window.location.href = 'delete_document.php?id=' + id;
    }
}

// Auto-submit filters on change (optional)
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('select[name="dept"], select[name="tag"], select[name="type"]');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });
});
</script>