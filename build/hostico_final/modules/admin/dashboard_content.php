<?php
// Content pentru dashboard admin companie
try { $db = new Database(); } catch(Exception $e) { $db=null; }
$cid = (int)($_SESSION['company_id'] ?? 0);
function safeCount($db,$sql){ try { return $db? ($db->query($sql)->fetch()['c'] ?? 0) : 0; } catch(Exception $e){ return 0; } }
$recentDocs = [];
if ($db) {
    try {
        $recentDocs = $db->query("SELECT id,title,created_at,file_size FROM documents WHERE company_id={$cid} ORDER BY created_at DESC LIMIT 5")->fetchAll();
    } catch(Exception $e){ $recentDocs=[]; }
}
?>
<div class="row g-4 mb-4">
  <div class="col-md-3"><div class="card-stat"><h4>Utilizatori</h4><div class="number"><?php echo safeCount($db,"SELECT COUNT(*) c FROM users WHERE company_id={$cid}"); ?></div></div></div>
  <div class="col-md-3"><div class="card-stat"><h4>Documente</h4><div class="number"><?php echo safeCount($db,"SELECT COUNT(*) c FROM documents WHERE company_id={$cid}"); ?></div></div></div>
  <div class="col-md-3"><div class="card-stat"><h4>Departamente</h4><div class="number"><?php echo safeCount($db,"SELECT COUNT(*) c FROM departments WHERE company_id={$cid}"); ?></div></div></div>
  <div class="col-md-3"><div class="card-stat"><h4>Stocare</h4><div class="number"><?php try { $s=$db? $db->query("SELECT SUM(file_size) t FROM documents WHERE company_id={$cid}")->fetch()['t']:0; echo round($s/1024/1024,1).' MB'; } catch(Exception $e){ echo '0 MB'; } ?></div></div></div>
</div>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header bg-white"><strong><i class="bi bi-clock-history me-2"></i>Documente recente</strong></div>
      <div class="card-body p-0">
        <?php if ($recentDocs): ?>
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead class="table-light"><tr><th>Titlu</th><th>Dimensiune</th><th>Data</th></tr></thead>
            <tbody>
            <?php foreach($recentDocs as $d): ?>
              <tr>
                <td><?php echo htmlspecialchars($d['title']); ?></td>
                <td><?php echo formatFileSize($d['file_size']); ?></td>
                <td><small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($d['created_at'])); ?></small></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <p class="p-3 text-muted mb-0">Nu există documente recente.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header bg-white"><strong><i class="bi bi-activity me-2"></i>Status</strong></div>
      <div class="card-body">
        <div class="mb-3 d-flex justify-content-between"><span>PHP</span><span class="badge bg-success"><?php echo PHP_VERSION; ?></span></div>
        <div class="mb-3 d-flex justify-content-between"><span>Bază date</span><span class="badge bg-<?php echo $db?'success':'danger'; ?>"><?php echo $db? 'Online':'Offline'; ?></span></div>
        <div class="mb-3 d-flex justify-content-between"><span>Companie</span><span class="badge bg-info text-dark"><?php echo htmlspecialchars($companyName); ?></span></div>
        <div class="d-flex justify-content-between"><span>Rol</span><span class="badge bg-secondary text-capitalize"><?php echo htmlspecialchars($_SESSION['role']??''); ?></span></div>
      </div>
    </div>
  </div>
</div>