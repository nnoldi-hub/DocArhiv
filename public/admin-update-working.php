<?php
/**
 * Working Update Document Handler
 * public/admin-update-working.php
 */

// Config and includes
$devConfig = __DIR__ . '/../config/config.php';
$prodConfig = __DIR__ . '/../document-archive/config/config.php';

if (file_exists($devConfig)) {
    require_once $devConfig;
} elseif (file_exists($prodConfig)) {
    require_once $prodConfig;
} else {
    die('Config not found');
}

require_once __DIR__ . '/../includes/classes/Database.php';
require_once __DIR__ . '/../includes/functions/helpers.php';

// Force display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Update Results</title>";
echo "<style>body{font-family:Arial;margin:20px;} .box{border:1px solid #ccc;padding:10px;margin:10px 0;} .success{background:#d4edda;color:#155724;} .error{background:#f8d7da;color:#721c24;}</style>";
echo "</head><body>";

echo "<h1>🔧 Document Update Process</h1>";

try {
    // Verify authentication
    if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
        throw new Exception('Not authenticated or insufficient permissions');
    }
    
    echo "<div class='box success'>✅ Authentication: OK</div>";
    
    // Verify CSRF
    $token = $_POST['_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        throw new Exception('CSRF token invalid');
    }
    
    echo "<div class='box success'>✅ CSRF Token: Valid</div>";
    
    // Get data
    $company_id = (int)($_SESSION['company_id'] ?? 0);
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $document_id = (int)($_POST['document_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    echo "<div class='box'>";
    echo "<strong>Extracted Data:</strong><br>";
    echo "Company ID: $company_id<br>";
    echo "User ID: $user_id<br>";
    echo "Document ID: $document_id<br>";
    echo "Title: " . htmlspecialchars($title) . "<br>";
    echo "Department ID: $department_id<br>";
    echo "Description: " . htmlspecialchars(substr($description, 0, 100)) . "<br>";
    echo "</div>";
    
    // Validate
    if ($document_id <= 0) {
        throw new Exception('Invalid document ID');
    }
    if (empty($title)) {
        throw new Exception('Title is required');
    }
    
    echo "<div class='box success'>✅ Data Validation: OK</div>";
    
    // Database operations
    $db = new Database();
    
    // Verify document exists and belongs to company
    $check_stmt = $db->query("SELECT id, title FROM documents WHERE id = :doc_id AND company_id = :company_id AND status = 'active'");
    $check_stmt->bind(':doc_id', $document_id);
    $check_stmt->bind(':company_id', $company_id);
    $existing = $check_stmt->fetch();
    
    if (!$existing) {
        throw new Exception('Document not found or access denied');
    }
    
    echo "<div class='box success'>✅ Document Access: OK (Current title: " . htmlspecialchars($existing['title']) . ")</div>";
    
    // Prepare update data
    $dept_to_save = ($department_id > 0) ? $department_id : null;
    $description_to_save = (!empty($description)) ? $description : null;
    
    // Update document
    $update_stmt = $db->query("
        UPDATE documents SET 
            title = :title,
            description = :description,
            department_id = :department_id,
            updated_at = NOW()
        WHERE id = :document_id AND company_id = :company_id
    ");
    
    $update_stmt->bind(':title', $title);
    $update_stmt->bind(':description', $description_to_save);
    $update_stmt->bind(':department_id', $dept_to_save);
    $update_stmt->bind(':document_id', $document_id);
    $update_stmt->bind(':company_id', $company_id);
    
    if ($update_stmt->execute()) {
        echo "<div class='box success'>✅ Database Update: SUCCESS!</div>";
        
        // Verify the update worked
        $verify_stmt = $db->query("SELECT title, department_id FROM documents WHERE id = :doc_id");
        $verify_stmt->bind(':doc_id', $document_id);
        $updated_doc = $verify_stmt->fetch();
        
        echo "<div class='box success'>";
        echo "<strong>✅ Update Verified:</strong><br>";
        echo "New Title: " . htmlspecialchars($updated_doc['title']) . "<br>";
        echo "New Department ID: " . ($updated_doc['department_id'] ?? 'NULL') . "<br>";
        echo "</div>";
        
        echo "<div class='box success'>";
        echo "<h2>🎉 SUCCESS! Document updated successfully!</h2>";
        echo "<a href='admin-documents.php'>← Back to Documents List</a>";
        echo "</div>";
        
    } else {
        throw new Exception('Database update failed');
    }
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<strong>❌ ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<a href='javascript:history.back()'>← Go Back and Try Again</a>";
    echo "</div>";
}

echo "</body></html>";
?>