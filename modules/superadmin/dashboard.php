<?php
/**
 * SuperAdmin Dashboard
 * modules/superadmin/dashboard.php
 * ATENȚIE: Config-ul este deja încărcat de superadmin-dashboard.php
 */

// Verifică din nou autentificare SuperAdmin (safety check)
if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    redirect('/login.php');
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperAdmin Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            transition: all 0.3s;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        
        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-check text-white fs-1"></i>
                        <h5 class="text-white mt-2">SuperAdmin</h5>
                        <small class="text-white-50">Panou Control</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active mb-2" href="<?php echo APP_URL; ?>/superadmin-dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a class="nav-link mb-2" href="<?php echo APP_URL; ?>/superadmin-companies.php">
                            <i class="bi bi-building me-2"></i> Companii
                        </a>
                        <a class="nav-link mb-2" href="<?php echo APP_URL; ?>/superadmin-users.php">
                            <i class="bi bi-people me-2"></i> Utilizatori
                        </a>
                        <a class="nav-link mb-2" href="<?php echo APP_URL; ?>/superadmin-system.php">
                            <i class="bi bi-gear me-2"></i> Sistem
                        </a>
                        <a class="nav-link mb-2" href="<?php echo APP_URL; ?>/superadmin-reports.php">
                            <i class="bi bi-bar-chart me-2"></i> Rapoarte
                        </a>
                        <a class="nav-link mb-2" href="<?php echo APP_URL; ?>/superadmin-logs.php">
                            <i class="bi bi-file-text me-2"></i> Log-uri
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
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
                                                    $db = new Database();
                                                    $count = $db->query("SELECT COUNT(*) as count FROM companies")->fetch();
                                                    echo $count['count'];
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
                                                    $count = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
                                                    echo $count['count'];
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
                                                    $count = $db->query("SELECT COUNT(*) as count FROM documents")->fetch();
                                                    echo $count['count'];
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
                                                    $storage = $db->query("SELECT ROUND(SUM(file_size)/1024/1024, 2) as storage_mb FROM documents")->fetch();
                                                    echo ($storage['storage_mb'] ?? 0) . ' MB';
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
                                        <span class="badge bg-success">Online</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Storage</span>
                                        <span class="badge bg-info">OK</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Debug Mode</span>
                                        <span class="badge bg-<?php echo DEBUG_MODE ? 'warning' : 'success'; ?>">
                                            <?php echo DEBUG_MODE ? 'ON' : 'OFF'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
