<?php
/**
 * Test Direct pentru Edit Document Module
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting test...<br>";

try {
    // Include direct config
    require_once '../config/config.php';
    echo "Config loaded<br>";
    
    // Force session values for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['company_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Test Admin';
    
    // Set test document ID
    $_GET['id'] = 4; // Document ID din URL
    
    echo "Session data set<br>";
    
    // Include edit document module directly
    include '../modules/admin/edit_document.php';
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    echo "Stack trace:<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>