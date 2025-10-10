<?php
/**
 * Test Database Connection and Document
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing database connection...<br>";

try {
    require_once '../config/config.php';
    echo "Config loaded<br>";
    
    $pdo = getDBConnection();
    echo "Database connected<br>";
    
    // Check if document exists
    $stmt = $pdo->prepare("SELECT id, title, company_id FROM documents WHERE id = ? LIMIT 1");
    $stmt->execute([4]);
    $document = $stmt->fetch();
    
    if ($document) {
        echo "Document found:<br>";
        echo "ID: " . $document['id'] . "<br>";
        echo "Title: " . htmlspecialchars($document['title']) . "<br>";
        echo "Company ID: " . $document['company_id'] . "<br>";
    } else {
        echo "Document with ID=4 NOT found<br>";
        
        // Show available documents
        $stmt = $pdo->prepare("SELECT id, title, company_id FROM documents LIMIT 5");
        $stmt->execute();
        $docs = $stmt->fetchAll();
        
        echo "Available documents:<br>";
        foreach ($docs as $doc) {
            echo "- ID: " . $doc['id'] . ", Title: " . htmlspecialchars($doc['title']) . ", Company: " . $doc['company_id'] . "<br>";
        }
    }
    
    // Test companies
    $stmt = $pdo->prepare("SELECT id, name FROM companies LIMIT 3");
    $stmt->execute();
    $companies = $stmt->fetchAll();
    
    echo "<br>Available companies:<br>";
    foreach ($companies as $company) {
        echo "- ID: " . $company['id'] . ", Name: " . htmlspecialchars($company['name']) . "<br>";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>