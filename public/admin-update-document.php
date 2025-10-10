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

try {
    // Verify authentication (exact same)
    if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
        throw new Exception('Not authenticated or insufficient permissions');
    }
    
    // Verify CSRF (exact same)
    $token = $_POST['_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        throw new Exception('CSRF token invalid');
    }
    
    // Get data
    $company_id = (int)($_SESSION['company_id'] ?? 0);
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $document_id = (int)($_POST['document_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    // Validate (exact same)
    if ($document_id <= 0) {
        throw new Exception('Invalid document ID');
    }
    if (empty($title)) {
        throw new Exception('Title is required');
    }
    
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
        $_SESSION['success'] = 'Documentul "' . htmlspecialchars($title) . '" a fost actualizat cu succes.';
        redirect(APP_URL . '/admin-documents.php');
        exit;
        
    } else {
        throw new Exception('Database update failed');
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Eroare la actualizarea documentului: ' . $e->getMessage();
    if (isset($document_id) && $document_id > 0) {
        redirect(APP_URL . '/admin-edit-document.php?id=' . $document_id);
    } else {
        redirect(APP_URL . '/admin-documents.php');
    }
    exit;
}
?>