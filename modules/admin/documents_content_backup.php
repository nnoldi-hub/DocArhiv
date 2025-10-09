<?php
$company_id = (int)($_SESSION['company_id'] ?? 0);
$q = trim($_GET['q'] ?? '');
$docs = [];
// Fallback simplu dacă nu există DocumentManager::search
try {
  if (class_exists('DocumentManager') && method_exists('DocumentManager','search')) {
    $docs = DocumentManager::search($company_id, $q);
  } else {
    // Căutare minimală directă
    $db = new Database();
    if ($q) {
      $stmt = $db->query("SELECT d.id, d.title, d.file_size as size, d.created_at, u.full_name as uploader FROM documents d LEFT JOIN users u ON u.id = d.created_by WHERE d.company_id = :cid AND d.status = 'active' AND d.title LIKE :q ORDER BY d.created_at DESC LIMIT 100")
             ->bind(':cid', $company_id)
             ->bind(':q', "%{$q}%");
    } else {
      $stmt = $db->query("SELECT d.id, d.title, d.file_size as size, d.created_at, u.full_name as uploader FROM documents d LEFT JOIN users u ON u.id = d.created_by WHERE d.company_id = :cid AND d.status = 'active' ORDER BY d.created_at DESC LIMIT 100")
             ->bind(':cid', $company_id);
    }
    $docs = $stmt->fetchAll();
  }
} catch(Exception $e) { $docs=[]; }
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Documente</h4>
  <form class="d-flex" method="get" action="admin-documents.php">
    <label for="search_input" class="visually-hidden">Căutare documente</label>
    <input type="text" id="search_input" class="form-control me-2" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Căutare...">
    <button class="btn btn-outline-secondary" type="submit">Caută</button>
    <?php if($q): ?><a class="btn btn-link" href="admin-documents.php">Reset</a><?php endif; ?>
  </form>
</div>
<div class="card mb-4">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" action="<?php echo APP_URL; ?>/admin-upload.php">
  <?php // CSRF simplu dacă există o funcție custom
  if (function_exists('csrf_field')) { echo csrf_field(); } ?>
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label for="document_file" class="form-label small text-muted">Fișier</label>
          <input type="file" id="document_file" name="document" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label for="document_title" class="form-label small text-muted">Titlu</label>
          <input type="text" id="document_title" name="title" class="form-control" placeholder="Titlu opțional">
        </div>
        <div class="col-md-4">
          <button class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Încarcă</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light"><tr><th>#</th><th>Titlu</th><th>Uploader</th><th>Mărime</th><th>Creat</th><th></th></tr></thead>
      <tbody>
      <?php if($docs): foreach ($docs as $d): ?>
        <tr>
          <td><?php echo (int)$d['id']; ?></td>
          <td><?php echo htmlspecialchars($d['title']); ?></td>
          <td><?php echo htmlspecialchars($d['uploader'] ?? '-'); ?></td>
          <td><?php echo htmlspecialchars(number_format((int)($d['size'] ?? 0)/1024,2)); ?> KB</td>
          <td><small class="text-muted"><?php echo htmlspecialchars($d['created_at']); ?></small></td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <a class="btn btn-outline-secondary" href="<?php echo APP_URL; ?>/admin-view-document.php?id=<?php echo (int)$d['id']; ?>" title="Vezi"><i class="bi bi-eye"></i></a>
              <a class="btn btn-outline-success" href="<?php echo APP_URL; ?>/admin-download.php?id=<?php echo (int)$d['id']; ?>" title="Descarcă"><i class="bi bi-download"></i></a>
              <a class="btn btn-outline-warning" href="<?php echo APP_URL; ?>/admin-print.php?id=<?php echo (int)$d['id']; ?>" target="_blank" title="Print"><i class="bi bi-printer"></i></a>
              <a class="btn btn-outline-danger" href="<?php echo APP_URL; ?>/admin-delete.php?id=<?php echo (int)$d['id']; ?>" onclick="return confirm('Ștergi documentul?')" title="Șterge"><i class="bi bi-trash"></i></a>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Nu există documente.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>