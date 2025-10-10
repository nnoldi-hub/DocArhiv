<?php
/**
 * Test Admin Edit Document - Version Robustă
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing admin edit document functionality...<br><br>";

try {
    // Include config
    require_once '../config/config.php';
    echo "✓ Config loaded<br>";
    
    // Force login session for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['company_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Test Admin';
    echo "✓ Session data set<br>";
    
    // Test database connection
    $db = new Database();
    echo "✓ Database connection established<br>";
    
    // Find any document to test with
    $stmt = $db->query("SELECT id, title, company_id FROM documents WHERE status = 'active' LIMIT 1");
    $testDoc = $stmt->fetch();
    
    if (!$testDoc) {
        echo "✗ No active documents found in database<br>";
        // Create a test document
        echo "Creating test document...<br>";
        
        $insertStmt = $db->query("
            INSERT INTO documents (company_id, title, description, file_name, file_path, file_size, status, created_at) 
            VALUES (1, 'Test Document', 'Test description', 'test.pdf', '/storage/test.pdf', 1024, 'active', NOW())
        ");
        
        if ($insertStmt->execute()) {
            $document_id = $db->lastInsertId();
            echo "✓ Test document created with ID: $document_id<br>";
        } else {
            throw new Exception("Failed to create test document");
        }
    } else {
        $document_id = $testDoc['id'];
        echo "✓ Using existing document ID: $document_id (Title: " . htmlspecialchars($testDoc['title']) . ")<br>";
    }
    
    // Set the document ID in GET
    $_GET['id'] = $document_id;
    
    echo "<br>Loading edit module...<br>";
    
    // Start output buffering to catch any errors
    ob_start();
    
    // Include the edit module
    include '../modules/admin/edit_document.php';
    
    $output = ob_get_clean();
    
    echo "✓ Edit module loaded successfully<br>";
    echo "<hr>";
    echo $output;
    
} catch (Exception $e) {
    ob_end_clean();
    echo "✗ ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . ($e->getFile() ?? 'Unknown') . "<br>";
    echo "Line: " . ($e->getLine() ?? 'Unknown') . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    ob_end_clean();
    echo "✗ FATAL ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>