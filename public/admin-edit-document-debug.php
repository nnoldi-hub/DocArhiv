<?php
/**
 * Admin Edit Document Entry Point - Debug Version
 * public/admin-edit-document-debug.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "DEBUG: Starting admin-edit-document<br>";

try {
    echo "DEBUG: Including config...<br>";
    require_once '../config/config.php';
    echo "DEBUG: Config included successfully<br>";
    
    echo "DEBUG: Session status: " . session_status() . "<br>";
    
    // Check if functions exist
    if (!function_exists('isLoggedIn')) {
        throw new Exception("Function isLoggedIn does not exist");
    }
    echo "DEBUG: isLoggedIn function exists<br>";
    
    if (!function_exists('hasRole')) {
        throw new Exception("Function hasRole does not exist");
    }
    echo "DEBUG: hasRole function exists<br>";
    
    // Force session for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['company_id'] = 1;
    $_SESSION['role'] = 'admin';
    
    echo "DEBUG: Checking authentication...<br>";
    
    // Verifică autentificare și rol Admin/Manager
    if (!isLoggedIn()) {
        echo "DEBUG: User not logged in, redirecting...<br>";
        // Don't redirect for testing
        // redirect('/login.php');
    } else {
        echo "DEBUG: User is logged in<br>";
    }
    
    if (!hasRole('admin') && !hasRole('manager')) {
        echo "DEBUG: User doesn't have admin/manager role<br>";
        // Don't redirect for testing
        // redirect('/login.php');
    } else {
        echo "DEBUG: User has correct role<br>";
    }
    
    echo "DEBUG: Including edit module...<br>";
    
    // Include modulul de editare
    require_once '../modules/admin/edit_document.php';
    
    echo "DEBUG: Edit module included successfully<br>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>