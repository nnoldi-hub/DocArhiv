<?php
// SuperAdmin Archival Export
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions/archival.php';

if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    redirect(APP_URL . '/login.php');
}

$db = getDBConnection();
$success = '';
$error = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
    $company_id = (int)($_POST['company_id'] ?? 0);
        $from = trim($_POST['from'] ?? '');
        $to = trim($_POST['to'] ?? '');
        $include_xml = !empty($_POST['include_xml']);
        $convert_pdfa = !empty($_POST['convert_pdfa']);
    $per_doc_metadata = !empty($_POST['per_doc_metadata']);
    $validate_pdfa = !empty($_POST['validate_pdfa']);
        $opts = [
            'company_id' => $company_id ?: null,
            'from' => $from ?: null,
            'to' => $to ?: null,
            'include_xml' => $include_xml,
      'convert_pdfa' => $convert_pdfa,
      'per_doc_metadata' => $per_doc_metadata,
      'validate_pdfa' => $validate_pdfa,
        ];
  $result = buildArchivalPackage($db, $opts);
  $zipName = basename($result['zip_path']);
    $size = isset($result['zip_size']) ? formatFileSize((int)$result['zip_size']) : '';
    $dur = isset($result['duration']) ? $result['duration'] . 's' : '';
    $success = 'Pachet creat (' . (int)$result['count'] . ' documente' . ($size?", $size":"") . ($dur?", $dur":"") . '). ';
    $success .= '<a href="' . APP_URL . '/superadmin-export-download.php?name=' . urlencode($zipName) . '" class="alert-link">Descarcă arhiva</a>';
    if (!empty($result['manifest_export_path'])) {
      $manifestName = basename($result['manifest_export_path']);
      $success .= ' | <a href="' . APP_URL . '/superadmin-export-download.php?name=' . urlencode($manifestName) . '" class="alert-link">Descarcă manifest</a>';
    }
    if (!empty($result['validation_report_export_path'])) {
      $vr = basename($result['validation_report_export_path']);
      $success .= ' | <a href="' . APP_URL . '/superadmin-export-download.php?name=' . urlencode($vr) . '" class="alert-link">Raport PDF/A</a>';
    }
    // log event
    if (function_exists('logActivity')) {
      $desc = 'Export generat: ' . (int)$result['count'] . ' doc' . ($size?", $size":"") . ($dur?", $dur":"");
      if ($company_id) { $desc .= ', company_id=' . $company_id; }
      if ($from || $to) { $desc .= ', range=' . ($from?:'start') . '..' . ($to?:'end'); }
      logActivity('export_generated', $desc, 'export', null);
    }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Companies list
try { $companies = $db->query('SELECT id, company_name FROM companies ORDER BY company_name')->fetchAll(); } catch (Exception $e) { $companies = []; }

// Layout
$page_title = 'Export Arhivistic (SuperAdmin)';
$page_description = 'Generează pachete de export conforme (manifest + fișiere)';
$current_page = 'system';
ob_start();
?>
<div class="container py-3">
  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i><?= $success ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      <strong><i class="bi bi-archive me-1"></i> Export Arhivistic</strong>
    </div>
    <div class="card-body">
      <form method="POST" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-4">
          <label class="form-label">Companie</label>
          <select name="company_id" class="form-select">
            <option value="0">Toate</option>
            <?php foreach ($companies as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['company_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">De la</label>
          <input type="date" name="from" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Până la</label>
          <input type="date" name="to" class="form-control">
        </div>
        <div class="col-12">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="include_xml" name="include_xml" checked>
            <label class="form-check-label" for="include_xml">Include manifest XML (metadate)</label>
          </div>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="convert_pdfa" name="convert_pdfa">
            <label class="form-check-label" for="convert_pdfa">Conversie PDF în PDF/A (dacă este disponibil)</label>
          </div>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="per_doc_metadata" name="per_doc_metadata">
            <label class="form-check-label" for="per_doc_metadata">Generează și metadata.xml per document</label>
          </div>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" id="validate_pdfa" name="validate_pdfa">
            <label class="form-check-label" for="validate_pdfa">Rulează validare PDF/A (veraPDF) și furnizează raport</label>
          </div>
        </div>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary"><i class="bi bi-cpu me-1"></i> Generează Pachet</button>
          <a href="<?= APP_URL ?>/superadmin-system.php" class="btn btn-outline-secondary">Înapoi</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php
$content_html = ob_get_clean();

// Render through superadmin layout
$content_file = null; // We'll render $content_html in layout
require_once __DIR__ . '/../modules/superadmin/layout.php';
