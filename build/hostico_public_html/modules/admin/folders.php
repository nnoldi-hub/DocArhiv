<?php
require_once __DIR__ . '/../../config/config.php';
Auth::authorize(['admin','manager']);
$pdo = Database::getInstance()->pdo();
$company_id = Auth::companyId();
if (is_post()) { verify_csrf(); $name = trim($_POST['name'] ?? ''); $parent = $_POST['parent_id'] ?? null;
  if ($name) { $stmt=$pdo->prepare("INSERT INTO folders (company_id,parent_id,name) VALUES (?,?,?)"); $stmt->execute([$company_id,$parent?:null,$name]); }
  redirect('modules/admin/folders.php');
}
$rows = $pdo->prepare("SELECT * FROM folders WHERE company_id=? ORDER BY created_at DESC");
$rows->execute([$company_id]); $rows=$rows->fetchAll();
view_header('Dosare');
?>
<div class="container py-4">
  <h2>Dosare</h2>
  <form method="post" class="row g-2 mb-3">
    <?= csrf_field(); ?>
    <div class="col-md-5"><input class="form-control" name="name" placeholder="Nume dosar" required></div>
    <div class="col-md-5">
      <select class="form-select" name="parent_id">
        <option value="">(rădăcină)</option>
        <?php foreach ($rows as $f): ?><option value="<?= e($f['id']) ?>"><?= e($f['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Adaugă</button></div>
  </form>
  <ul class="list-group">
    <?php foreach ($rows as $f): ?>
      <li class="list-group-item bg-dark text-light d-flex justify-content-between">
        <span>#<?= e($f['id']) ?> — <?= e($f['name']) ?></span>
        <span><?= e($f['created_at']) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php view_footer(); ?>
