<?php
/**
 * SuperAdmin Dashboard Content
 * modules/superadmin/dashboard_content.php
 * ATENȚIE: Config-ul și autentificarea sunt deja verificate de layout.php
 */

// Inițializez conexiunea la baza de date pentru statistici
try {
    $db = new Database();
} catch (Exception $e) {
    $db = null;
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Bun venit, <?php echo sanitizeInput($_SESSION['full_name']); ?>!</h1>
        <p class="text-muted">Panou de control SuperAdmin</p>
    </div>
    <div class="text-end">
        <small class="text-muted">
            Ultima conectare: <?php echo date('d.m.Y H:i'); ?>
        </small>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-primary me-3">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">
                            <?php
                            try {
                                if ($db) {
                                    $count = $db->query("SELECT COUNT(*) as count FROM companies")->fetch();
                                    echo $count['count'];
                                } else {
                                    echo '0';
                                }
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </h3>
                        <small class="text-muted">Companii</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-success me-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">
                            <?php
                            try {
                                if ($db) {
                                    $count = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
                                    echo $count['count'];
                                } else {
                                    echo '0';
                                }
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </h3>
                        <small class="text-muted">Utilizatori</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-info me-3">
                        <i class="bi bi-file-earmark"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">
                            <?php
                            try {
                                if ($db) {
                                    $count = $db->query("SELECT COUNT(*) as count FROM documents")->fetch();
                                    echo $count['count'];
                                } else {
                                    echo '0';
                                }
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </h3>
                        <small class="text-muted">Documente</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-warning me-3">
                        <i class="bi bi-hdd"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">
                            <?php
                            try {
                                if ($db) {
                                    $storage = $db->query("SELECT ROUND(SUM(file_size)/1024/1024, 2) as storage_mb FROM documents")->fetch();
                                    echo ($storage['storage_mb'] ?? 0) . ' MB';
                                } else {
                                    echo '0 MB';
                                }
                            } catch (Exception $e) {
                                echo '0 MB';
                            }
                            ?>
                        </h3>
                        <small class="text-muted">Stocare</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Activitate Recentă</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    if ($db) {
                        $activities = $db->query("
                            SELECT a.*, u.full_name, c.name as company_name 
                            FROM activity_logs a
                            LEFT JOIN users u ON a.user_id = u.id
                            LEFT JOIN companies c ON a.company_id = c.id
                            ORDER BY a.created_at DESC 
                            LIMIT 10
                        ")->fetchAll();
                        
                        if ($activities) {
                            foreach ($activities as $activity) {
                                echo '<div class="d-flex justify-content-between align-items-center py-2 border-bottom">';
                                echo '<div>';
                                echo '<strong>' . sanitizeInput($activity['full_name'] ?? 'System') . '</strong>';
                                echo '<small class="text-muted ms-2">' . sanitizeInput($activity['company_name'] ?? '') . '</small>';
                                echo '<br><small>' . sanitizeInput($activity['description']) . '</small>';
                                echo '</div>';
                                echo '<small class="text-muted">' . date('d.m.Y H:i', strtotime($activity['created_at'])) . '</small>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="text-muted">Nu există activitate recentă.</p>';
                        }
                    } else {
                        echo '<p class="text-danger">Nu s-a putut conecta la baza de date pentru activitate.</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="text-danger">Eroare la încărcarea activității.</p>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Status Sistem</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>PHP Version</span>
                    <span class="badge bg-success"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Database</span>
                    <span class="badge bg-<?php echo $db ? 'success' : 'danger'; ?>">
                        <?php echo $db ? 'Online' : 'Offline'; ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Storage</span>
                    <span class="badge bg-info">OK</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Debug Mode</span>
                    <span class="badge bg-<?php echo (defined('DEBUG_MODE') && DEBUG_MODE) ? 'warning' : 'success'; ?>">
                        <?php echo (defined('DEBUG_MODE') && DEBUG_MODE) ? 'ON' : 'OFF'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>