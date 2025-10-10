<?php
/**
 * Admin Update Document Handler - DEBUG VERSION
 * public/admin-update-document-debug.php
 * 
 * This debug version logs everything that happens during save
 */

// Enable error reporting and logging for debug
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering to capture any unexpected output
ob_start();

try {
    $debug_log = __DIR__ . '/../storage/logs/update_debug_' . date('Y-m-d') . '.log';
    
    // Create log directory if it doesn't exist
    $log_dir = dirname($debug_log);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    function debug_log($message) {
        global $debug_log;
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($debug_log, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    }
    
    debug_log("=== UPDATE DEBUG SESSION START ===");
    debug_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
    debug_log("POST data: " . json_encode($_POST));
    debug_log("SESSION data: " . json_encode($_SESSION ?? []));
    debug_log("User authenticated: " . (isLoggedIn() ? 'Yes' : 'No'));
    
    require_once '../config/config.php';
    debug_log("Config loaded successfully");
    
    // Verifică autentificare și rol Admin/Manager
    if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
        debug_log("Authentication failed - redirecting to login");
        redirect('/login.php');
    }
    
    debug_log("Authentication passed");
    
    // Include handler-ul de update
    debug_log("Including update module");
    require_once '../modules/admin/update_document.php';
    debug_log("Update module included successfully");
    
} catch (Exception $e) {
    debug_log("ERROR: " . $e->getMessage());
    debug_log("Stack trace: " . $e->getTraceAsString());
    
    // Clean output buffer
    ob_clean();
    
    // Show user-friendly error
    $_SESSION['error'] = 'A apărut o eroare: ' . $e->getMessage();
    redirect(APP_URL . '/admin-documents.php');
}

// Capture any unexpected output
$output = ob_get_clean();
if (!empty($output)) {
    debug_log("Unexpected output captured: " . $output);
}

debug_log("=== UPDATE DEBUG SESSION END ===");
?>