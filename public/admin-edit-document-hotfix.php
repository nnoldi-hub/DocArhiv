<?php
/**
 * Admin Edit Document Entry Point - HOTFIX
 * public/admin-edit-document.php
 */

// Debug mode
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "DEBUG MODE ACTIVE<br>";

try {
    echo "Loading config...<br>";
    require_once '../config/config.php';
    echo "Config loaded successfully<br>";
    
    // Force admin session for now
    $_SESSION['user_id'] = 1;
    $_SESSION['company_id'] = 1;
    $_SESSION['role'] = 'admin';
    
    echo "Session set<br>";
    
    // Get document ID
    $document_id = (int)($_GET['id'] ?? 0);
    echo "Document ID: $document_id<br>";
    
    if ($document_id <= 0) {
        echo "Invalid document ID<br>";
        exit;
    }
    
    // Simple database check
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        echo "Document not found<br>";
        exit;
    }
    
    echo "Document found: " . htmlspecialchars($document['title']) . "<br>";
    
    // Load the edit module
    echo "Loading edit module...<br>";
    require_once '../modules/admin/edit_document.php';
    echo "Edit module loaded successfully<br>";
    
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