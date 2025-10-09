<?php
$company_id = (int)($_SESSION['company_id'] ?? 0);
$q = trim($_GET['q'] ?? '');

// Parametri de sortare (simpli și siguri)
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'desc';

// Validare sortare
$valid_sorts = ['title', 'file_size', 'created_at'];
if (!in_array($sort, $valid_sorts)) {
    $sort = 'created_at';
}
if (!in_array($order, ['asc', 'desc'])) {
    $order = 'desc';
}

$docs = [];
try {
    $db = new Database();
    $order_sql = strtoupper($order);
    
    if ($q) {
        $stmt = $db->query("SELECT d.id, d.title, d.file_size as size, d.created_at, u.full_name as uploader FROM documents d LEFT JOIN users u ON u.id = d.created_by WHERE d.company_id = :cid AND d.status = 'active' AND d.title LIKE :q ORDER BY d.{$sort} {$order_sql} LIMIT 50")
               ->bind(':cid', $company_id)
               ->bind(':q', "%{$q}%");
    } else {
        $stmt = $db->query("SELECT d.id, d.title, d.file_size as size, d.created_at, u.full_name as uploader FROM documents d LEFT JOIN users u ON u.id = d.created_by WHERE d.company_id = :cid AND d.status = 'active' ORDER BY d.{$sort} {$order_sql} LIMIT 50")
               ->bind(':cid', $company_id);
    }
    $docs = $stmt->fetchAll();
} catch(Exception $e) { 
    $docs = [];
    error_log("Docs query error: " . $e->getMessage());
}

// Helper pentru URL-uri de sortare
function getSortUrl($column) {
    global $sort, $order, $q;
    $new_order = ($sort === $column && $order === 'asc') ? 'desc' : 'asc';
    $params = ['sort' => $column, 'order' => $new_order];
    if ($q) $params['q'] = $q;
    return 'admin-documents.php?' . http_build_query($params);
}

// Helper pentru iconița de sortare
function getSortIcon($column) {
    global $sort, $order;
    if ($sort === $column) {
        return $order === 'asc' ? ' ↑' : ' ↓';
    }
    return '';
}
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
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>
            <a href="<?php echo getSortUrl('title'); ?>" class="text-decoration-none text-dark">
              Titlu<?php echo getSortIcon('title'); ?>
            </a>
          </th>
          <th>Uploader</th>
          <th>
            <a href="<?php echo getSortUrl('file_size'); ?>" class="text-decoration-none text-dark">
              Mărime<?php echo getSortIcon('file_size'); ?>
            </a>
          </th>
          <th>
            <a href="<?php echo getSortUrl('created_at'); ?>" class="text-decoration-none text-dark">
              Creat<?php echo getSortIcon('created_at'); ?>
            </a>
          </th>
          <th style="width: 120px;">Acțiuni</th>
        </tr>
      </thead>
      <tbody>
      <?php if($docs): ?>
        <?php foreach ($docs as $index => $d): ?>
        <tr>
          <td><?php echo $index + 1; ?></td>
          <td><?php echo htmlspecialchars($d['title']); ?></td>
          <td><?php echo htmlspecialchars($d['uploader'] ?? '-'); ?></td>
          <td><?php echo number_format((int)($d['size'] ?? 0)/1024, 2); ?> KB</td>
          <td><small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($d['created_at'])); ?></small></td>
          <td>
            <div class="btn-group btn-group-sm">
              <a class="btn btn-outline-secondary" href="view_document.php?id=<?php echo (int)$d['id']; ?>" title="Vezi">
                <i class="bi bi-eye"></i>
              </a>
              <a class="btn btn-outline-success" href="download.php?id=<?php echo (int)$d['id']; ?>" title="Descarcă">
                <i class="bi bi-download"></i>
              </a>
              <a class="btn btn-outline-info" href="print.php?id=<?php echo (int)$d['id']; ?>" target="_blank" title="Print">
                <i class="bi bi-printer"></i>
              </a>
              <a class="btn btn-outline-danger" href="delete_document.php?id=<?php echo (int)$d['id']; ?>" 
                 onclick="return confirm('Ștergi documentul?')" title="Șterge">
                <i class="bi bi-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" class="text-center text-muted py-4">
            <i class="bi bi-inbox display-6"></i><br>
            Nu există documente<?php if($q): ?> care să conțină "<?php echo htmlspecialchars($q); ?>"<?php endif; ?>.
          </td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>