<?php
// Test edit document - versiune simplificată
require_once __DIR__ . '/../../config/config.php';

// Verifică autentificarea
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
    exit;
}

$document_id = (int)($_GET['id'] ?? 0);

if ($document_id <= 0) {
    $_SESSION['error'] = 'Document invalid.';
    redirect(APP_URL . '/admin-documents.php');
    exit;
}

echo "TEST: Document ID = " . $document_id . "<br>";
echo "TEST: User logged in = " . (isLoggedIn() ? 'YES' : 'NO') . "<br>";
echo "TEST: Company ID = " . ($_SESSION['company_id'] ?? 'none') . "<br>";

try {
    require_once __DIR__ . '/../../includes/classes/database.php';
    $db = new Database();
    echo "TEST: Database connection OK<br>";
    
    // Test query simplu
    $stmt = $db->query("SELECT id, title FROM documents WHERE id = :doc_id");
    $stmt->bind(':doc_id', $document_id);
    $document = $stmt->fetch();
    
    if ($document) {
        echo "TEST: Document found: " . htmlspecialchars($document['title']) . "<br>";
    } else {
        echo "TEST: Document NOT found<br>";
    }
    
} catch (Exception $e) {
    echo "TEST ERROR: " . $e->getMessage() . "<br>";
    echo "TEST ERROR FILE: " . $e->getFile() . " LINE: " . $e->getLine() . "<br>";
}

echo "<br><a href='" . APP_URL . "/admin-documents.php'>Înapoi la documente</a>";
?>