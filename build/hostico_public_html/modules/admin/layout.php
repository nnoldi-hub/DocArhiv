<?php
/**
 * Layout Admin Companie
 * modules/admin/layout.php
 */

// Layout-ul este inclus din pagina publică care a verificat deja autentificarea
$companyName = $_SESSION['company_name'] ?? 'Companie';
$fullName = $_SESSION['full_name'] ?? '';

// Statistici companie pentru sidebar
try {
    $db = getDBConnection();
    $cid = (int)($_SESSION['company_id'] ?? 0);
    $stat_users = $db->query("SELECT COUNT(*) c FROM users WHERE company_id={$cid}")->fetch()['c'] ?? 0;
    $stat_docs = $db->query("SELECT COUNT(*) c FROM documents WHERE company_id={$cid}")->fetch()['c'] ?? 0;
    $stat_dept = $db->query("SELECT COUNT(*) c FROM departments WHERE company_id={$cid}")->fetch()['c'] ?? 0;
    $stat_storage = $db->query("SELECT COALESCE(SUM(file_size),0) total FROM documents WHERE company_id={$cid}")->fetch()['total'] ?? 0;
} catch (Exception $e) {
    $stat_users = $stat_docs = $stat_dept = $stat_storage = 0;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($page_title) ? $page_title : 'Admin'; ?> - <?php echo htmlspecialchars($companyName); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {background: #f2f6fa; font-family: 'Segoe UI', sans-serif;}
.sidebar {width:250px;position:fixed;top:0;left:0;height:100vh;background:#0d1b2a;color:#fff;display:flex;flex-direction:column;}
.sidebar a.nav-link{color:#cbd5e1;border-radius:8px;margin:4px 12px;font-weight:500}
.sidebar a.nav-link.active, .sidebar a.nav-link:hover{background:#1b263b;color:#fff}
.main-wrapper{margin-left:250px;min-height:100vh;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:12px 24px;display:flex;align-items:center;justify-content:space-between}
.content{padding:24px;flex:1}
.card-stat{border:none;border-radius:16px;padding:20px;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,.05)}
.card-stat h4{font-size:14px;text-transform:uppercase;color:#64748b;margin-bottom:8px;letter-spacing:.5px}
.card-stat .number{font-size:32px;font-weight:700;color:#0f172a}
.footer-small{text-align:center;padding:12px;color:#64748b;font-size:12px}
.badge-soft{background:#1b263b;color:#fff}
</style>
<?php if (isset($additional_head)) echo $additional_head; ?>
</head>
<body>
<div class="sidebar">
  <div class="p-4 border-bottom border-secondary">
    <h5 class="mb-0"><i class="bi bi-building me-2"></i><?php echo htmlspecialchars($companyName); ?></h5>
    <small class="text-muted">Panou Admin</small>
  </div>
  <nav class="flex-grow-1 mt-3">
    <a class="nav-link <?php echo ($current_page??'')==='dashboard'?'active':''; ?>" href="<?php echo APP_URL; ?>/admin-dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
  <a class="nav-link <?php echo ($current_page??'')==='documents'?'active':''; ?>" href="<?php echo APP_URL; ?>/admin-documents.php"><i class="bi bi-file-earmark me-2"></i>Documente</a>
  <a class="nav-link <?php echo ($current_page??'')==='departments'?'active':''; ?>" href="<?php echo APP_URL; ?>/admin-departments.php"><i class="bi bi-diagram-3 me-2"></i>Departamente</a>
  <a class="nav-link <?php echo ($current_page??'')==='users'?'active':''; ?>" href="<?php echo APP_URL; ?>/admin-users.php"><i class="bi bi-people me-2"></i>Utilizatori</a>
  <a class="nav-link <?php echo ($current_page??'')==='tags'?'active':''; ?>" href="<?php echo APP_URL; ?>/admin-tags.php"><i class="bi bi-tags me-2"></i>Taguri</a>
  <a class="nav-link <?php echo ($current_page??'')==='settings'?'active':''; ?>" href="<?php echo APP_URL; ?>/admin-settings.php"><i class="bi bi-gear me-2"></i>Setări</a>
  </nav>
  <div class="p-3 border-top border-secondary">
    <div class="d-flex align-items-center mb-2">
      <i class="bi bi-person-circle fs-4 me-2"></i>
      <div><strong><?php echo htmlspecialchars($fullName); ?></strong><br><small class="text-muted text-capitalize"><?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?></small></div>
    </div>
    <div class="d-grid gap-1">
      <a href="<?php echo APP_URL; ?>/admin-users.php" class="btn btn-outline-info btn-sm">
        <i class="bi bi-person-gear me-1"></i>Editează Profil
      </a>
      <a href="<?php echo APP_URL; ?>/auth/logout.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-box-arrow-right me-1"></i>Logout
      </a>
    </div>
  </div>
</div>
<div class="main-wrapper">
  <div class="topbar">
    <div>
      <h6 class="mb-0 fw-semibold"><?php echo isset($page_title)?$page_title:'Dashboard'; ?></h6>
      <?php if (!empty($page_description)) echo '<small class="text-muted">'. $page_description .'</small>'; ?>
    </div>
    <div>
      <span class="badge bg-primary">Utilizatori: <?php echo $stat_users; ?></span>
      <span class="badge bg-success">Doc: <?php echo $stat_docs; ?></span>
      <span class="badge bg-info text-dark">Dept: <?php echo $stat_dept; ?></span>
      <span class="badge bg-warning text-dark">Stocare: <?php echo round($stat_storage/1024/1024,1); ?> MB</span>
    </div>
  </div>
  <div class="content">
    <?php if (isset($_SESSION['success'])) { echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>'; unset($_SESSION['success']); } ?>
    <?php if (isset($_SESSION['error'])) { echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>'; unset($_SESSION['error']); } ?>
    <?php if (isset($content_file) && file_exists($content_file)) include $content_file; ?>
  </div>
  <div class="footer-small">&copy; <?php echo date('Y'); ?> Arhiva Documente</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($additional_scripts)) echo $additional_scripts; ?>
</body>
</html>