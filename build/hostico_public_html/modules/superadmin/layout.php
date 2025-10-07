<?php
/**
 * Layout de bază pentru paginile SuperAdmin
 * modules/superadmin/layout.php
 */

// Verifică autentificarea și rolul (config.php ar trebui să fie deja inclus)
if (!isLoggedIn() || !hasRole('superadmin')) {
    redirect(APP_URL . '/login.php');
    exit;
}

// Obține statistici pentru sidebar
try {
    $db = getDBConnection();
    
    // Statistici generale
    $total_companies = $db->query("SELECT COUNT(*) as count FROM companies")->fetch()['count'];
    $total_users = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    $total_documents = $db->query("SELECT COUNT(*) as count FROM documents")->fetch()['count'];
    $total_storage = $db->query("SELECT COALESCE(SUM(file_size), 0) as total FROM documents")->fetch()['total'];
    
    // Statusuri companii
    $active_companies = $db->query("SELECT COUNT(*) as count FROM companies WHERE subscription_status = 'active'")->fetch()['count'];
    $trial_companies = $db->query("SELECT COUNT(*) as count FROM companies WHERE subscription_status = 'trial'")->fetch()['count'];
    
} catch (Exception $e) {
    $total_companies = $total_users = $total_documents = $total_storage = 0;
    $active_companies = $trial_companies = 0;
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'SuperAdmin'; ?> - Document Archive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
        }
        
        .sidebar {
            width: 260px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 0 20px 20px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header h4 {
            color: #4a5568;
            font-weight: 700;
            margin: 10px 0 5px 0;
        }
        
        .sidebar-header p {
            color: #718096;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .nav-link {
            color: #4a5568;
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .nav-link:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .nav-link i {
            margin-right: 12px;
            font-size: 1.1rem;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }
        
        .content-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .content-body {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            min-height: calc(100vh - 200px);
        }

        .table-responsive {
            overflow: visible;
        }

        @media (max-width: 992px) {
            .table-responsive {
                overflow-x: auto;
            }
        }

        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            background: white;
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
        
        .stats-mini {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            backdrop-filter: blur(10px);
        }
        
        .stats-mini h6 {
            margin: 0;
            color: #4a5568;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stats-mini .h4 {
            margin: 5px 0 0 0;
            color: #2d3748;
            font-weight: 700;
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
        }
        
        .btn-group .btn:last-child {
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }
        
        .actions-dropdown .dropdown-toggle::after {
            display: none;
        }
        
        .actions-dropdown-menu {
            width: 240px;
            max-height: 320px;
            overflow-y: auto;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        
        .actions-dropdown-menu .dropdown-item {
            white-space: normal;
            gap: 0.5rem;
        }
        
        .actions-dropdown-menu .dropdown-item i {
            font-size: 1rem;
        }
        
        <?php if (isset($additional_css)) echo $additional_css; ?>
    </style>
    <?php if (isset($additional_head)) echo $additional_head; ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="mb-3">
                <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
            </div>
            <h4>SuperAdmin</h4>
            <p>Panou Control</p>
            <div class="mt-3">
                <small class="text-muted">Ultima conectare:<br><?php echo date('d.m.Y H:i'); ?></small>
            </div>
        </div>
        
        <nav class="nav flex-column mt-3">
                <a class="nav-link <?php echo (isset($current_page) && $current_page === 'dashboard') ? 'active' : ''; ?>" 
                    href="<?php echo APP_URL; ?>/superadmin-dashboard.php">
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>
                <a class="nav-link <?php echo (isset($current_page) && $current_page === 'companies') ? 'active' : ''; ?>" 
                    href="<?php echo APP_URL; ?>/superadmin-companies.php">
                <i class="bi bi-building"></i>
                Companii
            </a>
                <a class="nav-link <?php echo (isset($current_page) && $current_page === 'users') ? 'active' : ''; ?>" 
                    href="<?php echo APP_URL; ?>/superadmin-users.php">
                <i class="bi bi-people"></i>
                Utilizatori
            </a>
            <a class="nav-link <?php echo (isset($current_page) && $current_page === 'system') ? 'active' : ''; ?>" 
               href="<?php echo APP_URL; ?>/superadmin-system.php">
                <i class="bi bi-gear"></i>
                Sistem
            </a>
            <a class="nav-link <?php echo (isset($current_page) && $current_page === 'reports') ? 'active' : ''; ?>" 
               href="<?php echo APP_URL; ?>/superadmin-reports.php">
                <i class="bi bi-bar-chart"></i>
                Rapoarte
            </a>
            <a class="nav-link <?php echo (isset($current_page) && $current_page === 'logs') ? 'active' : ''; ?>" 
               href="<?php echo APP_URL; ?>/superadmin-logs.php">
                <i class="bi bi-journal-text"></i>
                Log-uri
            </a>
        </nav>
        
        <!-- Mini statistici în sidebar -->
        <div class="px-3 mt-4">
            <div class="stats-mini">
                <h6>Companii</h6>
                <div class="h4"><?php echo $total_companies; ?></div>
                <small class="text-success"><?php echo $active_companies; ?> active</small>
            </div>
            <div class="stats-mini">
                <h6>Utilizatori</h6>
                <div class="h4"><?php echo $total_users; ?></div>
            </div>
            <div class="stats-mini">
                <h6>Documente</h6>
                <div class="h4"><?php echo $total_documents; ?></div>
            </div>
            <div class="stats-mini">
                <h6>Stocare</h6>
                <div class="h4"><?php echo round($total_storage / 1024 / 1024, 1); ?> MB</div>
            </div>
        </div>
        
        <div class="mt-auto p-3">
            <a href="<?php echo APP_URL; ?>/auth/logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Content Header -->
        <div class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0"><?php echo isset($page_title) ? $page_title : 'SuperAdmin'; ?></h1>
                    <p class="text-muted mb-0"><?php echo isset($page_description) ? $page_description : ''; ?></p>
                </div>
                <div>
                    <?php if (isset($header_actions)) echo $header_actions; ?>
                </div>
            </div>
        </div>
        
        <!-- Content Body -->
        <div class="content-body">
            <?php 
            // Afișează mesajele de succes/eroare
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>' . $_SESSION['success'] . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>' . $_SESSION['error'] . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
                unset($_SESSION['error']);
            }
            ?>
            
            <!-- Conținutul paginii va fi inclus aici -->
            <?php 
            if (isset($content_file) && $content_file && file_exists($content_file)) {
                include $content_file; 
            } elseif (isset($content_html)) {
                echo $content_html; 
            }
            ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($additional_scripts)) echo $additional_scripts; ?>
    
    <script>
        // Auto-hide alerts după 5 secunde
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('show')) {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                }
            });
        }, 5000);
    </script>
</body>
</html>