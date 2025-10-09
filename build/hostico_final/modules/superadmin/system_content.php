<?php
// modules/superadmin/system_content.php - Versiune simplificată funcțională

// Rezultate acțiuni
$sys_success = '';
$sys_error = '';

// Procesează acțiunile simple
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole(ROLE_SUPERADMIN)) {
    try {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'db_optimize':
                $pdo = getDBConnection();
                $tables = ['companies','users','departments','folders','tags','documents','document_tags','activity_logs'];
                $optimized = 0;
                foreach ($tables as $t) {
                    try { 
                        $pdo->query("ANALYZE TABLE `{$t}`"); 
                        $pdo->query("OPTIMIZE TABLE `{$t}`"); 
                        $optimized++;
                    } catch (Exception $e) {}
                }
                $sys_success = "Baza de date optimizată cu succes! ({$optimized} tabele procesate)";
                break;

            case 'cache_clear':
                $cleared = 0;
                if (file_exists(CACHE_PATH)) {
                    $files = glob(CACHE_PATH . '/*');
                    foreach ($files as $file) {
                        if (is_file($file) && strpos($file, 'rate_limit_') === false) {
                            if (@unlink($file)) $cleared++;
                        }
                    }
                }
                $sys_success = "Cache curățat cu succes! ({$cleared} fișiere șterse)";
                break;

            case 'backup_now':
                $timestamp = date('Ymd_His');
                $backupDir = STORAGE_PATH . '/backups';
                if (!file_exists($backupDir)) { @mkdir($backupDir, 0755, true); }
                
                // Backup simplu SQL
                $pdo = getDBConnection();
                $sqlFile = $backupDir . "/backup_db_{$timestamp}.sql";
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                
                $sql = "-- Backup generat la " . date('Y-m-d H:i:s') . "\n\n";
                foreach ($tables as $table) {
                    $sql .= "-- Tabel: {$table}\n";
                    $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
                    $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                    $sql .= $create['Create Table'] . ";\n\n";
                }
                
                if (file_put_contents($sqlFile, $sql)) {
                    $sys_success = "Backup creat cu succes: backup_db_{$timestamp}.sql";
                } else {
                    $sys_error = "Eroare la crearea backup-ului!";
                }
                break;

            default:
                $sys_error = 'Acțiune invalidă.';
        }
    } catch (Exception $e) {
        $sys_error = 'Eroare: ' . $e->getMessage();
    }
}
?>

<?php if (!empty($sys_success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($sys_success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($sys_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($sys_error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center py-4">
                <i class="bi bi-database fs-1 text-primary mb-3"></i>
                <h5 class="fw-bold mb-1">Baza de Date</h5>
                <p class="text-muted">Optimizare și întreținere</p>
                <form method="POST">
                    <input type="hidden" name="action" value="db_optimize">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-wrench me-1"></i> Optimizează
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center py-4">
                <i class="bi bi-shield-check fs-1 text-success mb-3"></i>
                <h5 class="fw-bold mb-1">Backup</h5>
                <p class="text-muted">Backup bază de date</p>
                <form method="POST">
                    <input type="hidden" name="action" value="backup_now">
                    <button class="btn btn-success" type="submit">
                        <i class="bi bi-download me-1"></i> Backup acum
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center py-4">
                <i class="bi bi-speedometer fs-1 text-warning mb-3"></i>
                <h5 class="fw-bold mb-1">Cache</h5>
                <p class="text-muted">Gestionare cache sistem</p>
                <form method="POST">
                    <input type="hidden" name="action" value="cache_clear">
                    <button class="btn btn-warning" type="submit">
                        <i class="bi bi-arrow-clockwise me-1"></i> Curăță cache
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title"><i class="bi bi-archive me-1"></i> Export Arhivistic</h5>
                <p class="text-muted mb-3">Generează pachete pentru depunere la Arhivele Naționale (manifest + fișiere).</p>
                <div class="mt-auto">
                    <button class="btn btn-outline-primary" disabled>
                        <i class="bi bi-arrow-right-circle me-1"></i> În dezvoltare
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title"><i class="bi bi-journal-text me-1"></i> Dosar Tehnic</h5>
                <p class="text-muted mb-3">Rezumat arhitectură, securitate și conformitate arhivistică.</p>
                <div class="mt-auto">
                    <button class="btn btn-outline-secondary" disabled>
                        <i class="bi bi-file-earmark-text me-1"></i> În dezvoltare
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informații Sistem</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Versiune PHP:</strong> <?= PHP_VERSION ?><br>
                        <strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?><br>
                        <strong>Memorie PHP:</strong> <?= ini_get('memory_limit') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Timp server:</strong> <?= date('Y-m-d H:i:s') ?><br>
                        <strong>Zona oră:</strong> <?= date_default_timezone_get() ?><br>
                        <strong>Upload max:</strong> <?= ini_get('upload_max_filesize') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>