<?php
/**
 * Main Application File - Document Archive System
 * Core application logic and routing
 */

// Start session
session_start();

// Basic security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Include configuration
require_once '../config/config.php';

// Include necessary classes and functions
require_once '../includes/functions/common.php';
require_once '../includes/classes/Database.php';
require_once '../includes/classes/User.php';
require_once '../includes/classes/Document.php';

// Simple routing based on module and action
$module = $_GET['module'] ?? 'user';
$action = $_GET['action'] ?? 'dashboard';

// Validate module
$allowedModules = ['superadmin', 'admin', 'user'];
if (!in_array($module, $allowedModules)) {
    $module = 'user';
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && $action !== 'login') {
    header('Location: app.php?module=user&action=login');
    exit;
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Archive - <?php echo ucfirst($module); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/<?php echo $module; ?>.css">
</head>
<body class="app-body">
    <div class="app-container">
        <?php
        // Include navigation based on user role
        if (isset($_SESSION['user_id'])) {
            include '../includes/navigation.php';
        }
        ?>
        
        <main class="main-content">
            <?php
            // Load appropriate module
            $modulePath = "../modules/{$module}/{$action}.php";
            
            if (file_exists($modulePath)) {
                include $modulePath;
            } else {
                // Default fallback
                echo '<div class="error">Pagina solicitată nu a fost găsită.</div>';
            }
            ?>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/<?php echo $module; ?>.js"></script>
</body>
</html>