<?php
/**
 * Admin Update Document Handler - COPIED FROM WORKING VERSION
 * public/admin-update-document.php
 */

// Config and includes (exact same as working version)
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
    
    // Get data (exact same PLUS additional fields)
    $company_id = (int)($_SESSION['company_id'] ?? 0);
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $document_id = (int)($_POST['document_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $tags_input = trim($_POST['tags'] ?? '');
    $document_number = trim($_POST['document_number'] ?? '');
    $document_date = $_POST['document_date'] ?? null;
    $expiry_date = $_POST['expiry_date'] ?? null;
    $is_confidential = isset($_POST['is_confidential']) ? 1 : 0;
    
    // Validate (exact same)
    if ($document_id <= 0) {
        throw new Exception('Invalid document ID');
    }
    if (empty($title)) {
        throw new Exception('Title is required');
    }
    
    // Database operations (exact same)
    $db = new Database();
    
    // Verify document exists and belongs to company (exact same)
    $check_stmt = $db->query("SELECT id, title FROM documents WHERE id = :doc_id AND company_id = :company_id AND status = 'active'");
    $check_stmt->bind(':doc_id', $document_id);
    $check_stmt->bind(':company_id', $company_id);
    $existing = $check_stmt->fetch();
    
    if (!$existing) {
        throw new Exception('Document not found or access denied');
    }
    
    // Prepare update data (exact same PLUS additional fields)
    $dept_to_save = ($department_id > 0) ? $department_id : null;
    $description_to_save = (!empty($description)) ? $description : null;
    $document_number_to_save = (!empty($document_number)) ? $document_number : null;
    $document_date_to_save = (!empty($document_date)) ? $document_date : null;
    $expiry_date_to_save = (!empty($expiry_date)) ? $expiry_date : null;
    
    // Update document (exact same PLUS additional fields)
    $update_stmt = $db->query("
        UPDATE documents SET 
            title = :title,
            description = :description,
            department_id = :department_id,
            document_number = :document_number,
            document_date = :document_date,
            expiry_date = :expiry_date,
            is_confidential = :is_confidential,
            updated_at = NOW()
        WHERE id = :document_id AND company_id = :company_id
    ");
    
    $update_stmt->bind(':title', $title);
    $update_stmt->bind(':description', $description_to_save);
    $update_stmt->bind(':department_id', $dept_to_save);
    $update_stmt->bind(':document_number', $document_number_to_save);
    $update_stmt->bind(':document_date', $document_date_to_save);
    $update_stmt->bind(':expiry_date', $expiry_date_to_save);
    $update_stmt->bind(':is_confidential', $is_confidential);
    $update_stmt->bind(':document_id', $document_id);
    $update_stmt->bind(':company_id', $company_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Database update failed');
    }
    
    // Handle tags (optional - simplified)
    if (!empty($tags_input)) {
        // Delete existing tag associations
        $delete_tags_stmt = $db->query("DELETE FROM document_tags WHERE document_id = :doc_id");
        $delete_tags_stmt->bind(':doc_id', $document_id);
        $delete_tags_stmt->execute();
        
        // Process new tags
        $tags_array = array_map('trim', explode(',', $tags_input));
        $tags_array = array_filter($tags_array);
        $tags_array = array_unique($tags_array);
        
        foreach ($tags_array as $tag_name) {
            if (strlen($tag_name) > 0 && strlen($tag_name) <= 50) {
                // Check if tag exists
                $tag_stmt = $db->query("SELECT id FROM tags WHERE company_id = :company_id AND name = :name");
                $tag_stmt->bind(':company_id', $company_id);
                $tag_stmt->bind(':name', $tag_name);
                $tag_result = $tag_stmt->fetch();
                
                if ($tag_result) {
                    $tag_id = $tag_result['id'];
                } else {
                    // Create new tag
                    $create_tag = $db->query("INSERT INTO tags (company_id, name, created_at) VALUES (:company_id, :name, NOW())");
                    $create_tag->bind(':company_id', $company_id);
                    $create_tag->bind(':name', $tag_name);
                    
                    if ($create_tag->execute()) {
                        $tag_id_stmt = $db->query("SELECT LAST_INSERT_ID() as tag_id");
                        $tag_id_result = $tag_id_stmt->fetch();
                        $tag_id = $tag_id_result['tag_id'];
                    } else {
                        continue;
                    }
                }
                
                // Associate tag with document
                $assoc_stmt = $db->query("INSERT IGNORE INTO document_tags (document_id, tag_id, created_at) VALUES (:doc_id, :tag_id, NOW())");
                $assoc_stmt->bind(':doc_id', $document_id);
                $assoc_stmt->bind(':tag_id', $tag_id);
                $assoc_stmt->execute();
            }
        }
    } else {
        // Delete all tag associations if no tags
        $delete_tags_stmt = $db->query("DELETE FROM document_tags WHERE document_id = :doc_id");
        $delete_tags_stmt->bind(':doc_id', $document_id);
        $delete_tags_stmt->execute();
    }
    
    $_SESSION['success'] = 'Documentul "' . htmlspecialchars($title) . '" a fost actualizat cu succes.';
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Eroare la actualizarea documentului: ' . $e->getMessage();
    if (isset($document_id) && $document_id > 0) {
        redirect(APP_URL . '/admin-edit-document.php?id=' . $document_id);
    } else {
        redirect(APP_URL . '/admin-documents.php');
    }
    exit;
}

redirect(APP_URL . '/admin-documents.php');
?>