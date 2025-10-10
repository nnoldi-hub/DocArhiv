<?php
/**
 * Admin Update Document Handler - FIXED
 * public/admin-update-document.php
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    require_once '../config/config.php';
} catch (Exception $e) {
    die('Config error: ' . $e->getMessage());
}

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
    exit;
}

// Include classes and functions
try {
    require_once '../includes/classes/Database.php';
    require_once '../includes/functions/helpers.php';
} catch (Exception $e) {
    die('Include error: ' . $e->getMessage());
}

try {
    // Verify CSRF
    $token = $_POST['_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        throw new Exception('Token CSRF invalid. Vă rugăm să reîncărcați pagina și să încercați din nou.');
    }
    
    $company_id = (int)($_SESSION['company_id'] ?? 0);
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $document_id = (int)($_POST['document_id'] ?? 0);
    
    // Validare date de intrare
    if ($document_id <= 0) {
        $_SESSION['error'] = 'Document invalid.';
        redirect(APP_URL . '/admin-documents.php');
        exit;
    }
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $tags_input = trim($_POST['tags'] ?? '');
    $document_number = trim($_POST['document_number'] ?? '');
    $document_date = $_POST['document_date'] ?? null;
    $expiry_date = $_POST['expiry_date'] ?? null;
    $is_confidential = isset($_POST['is_confidential']) ? 1 : 0;
    
    // Validări
    if (empty($title)) {
        $_SESSION['error'] = 'Titlul documentului este obligatoriu.';
        redirect(APP_URL . '/admin-edit-document.php?id=' . $document_id);
        exit;
    }
    
    $db = new Database();
    
    // Verifică că documentul aparține companiei curente
    $check_stmt = $db->query("SELECT id FROM documents WHERE id = ? AND company_id = ? AND status = 'active'");
    $check_stmt->bind(1, $document_id);
    $check_stmt->bind(2, $company_id);
    
    if (!$check_stmt->fetch()) {
        $_SESSION['error'] = 'Documentul nu a fost găsit.';
        redirect(APP_URL . '/admin-documents.php');
        exit;
    }
    
    // Pregătește datele pentru actualizare
    $dept_to_save = ($department_id > 0) ? $department_id : null;
    $description_to_save = (!empty($description)) ? $description : null;
    $document_number_to_save = (!empty($document_number)) ? $document_number : null;
    $document_date_to_save = (!empty($document_date)) ? $document_date : null;
    $expiry_date_to_save = (!empty($expiry_date)) ? $expiry_date : null;
    
    // Actualizează documentul
    $update_stmt = $db->query("
        UPDATE documents SET 
            title = ?,
            description = ?,
            department_id = ?,
            document_number = ?,
            document_date = ?,
            expiry_date = ?,
            is_confidential = ?,
            updated_at = NOW()
        WHERE id = ? AND company_id = ?
    ");
    
    $update_stmt->bind(1, $title);
    $update_stmt->bind(2, $description_to_save);
    $update_stmt->bind(3, $dept_to_save);
    $update_stmt->bind(4, $document_number_to_save);
    $update_stmt->bind(5, $document_date_to_save);
    $update_stmt->bind(6, $expiry_date_to_save);
    $update_stmt->bind(7, $is_confidential);
    $update_stmt->bind(8, $document_id);
    $update_stmt->bind(9, $company_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Eroare la actualizarea documentului.');
    }
    
    // Gestionarea tagurilor - simplified
    if (!empty($tags_input)) {
        // Șterge toate asocierile existente pentru acest document
        $delete_tags_stmt = $db->query("DELETE FROM document_tags WHERE document_id = ?");
        $delete_tags_stmt->bind(1, $document_id);
        $delete_tags_stmt->execute();
        
        // Procesează noile taguri
        $tags_array = array_map('trim', explode(',', $tags_input));
        $tags_array = array_filter($tags_array); // elimină tagurile goale
        $tags_array = array_unique($tags_array); // elimină duplicatele
        
        foreach ($tags_array as $tag_name) {
            if (strlen($tag_name) > 0 && strlen($tag_name) <= 50) {
                // Verifică dacă tag-ul există
                $tag_stmt = $db->query("SELECT id FROM tags WHERE company_id = ? AND name = ?");
                $tag_stmt->bind(1, $company_id);
                $tag_stmt->bind(2, $tag_name);
                $tag_result = $tag_stmt->fetch();
                
                if ($tag_result) {
                    $tag_id = $tag_result['id'];
                } else {
                    // Creează tag nou
                    $create_tag = $db->query("INSERT INTO tags (company_id, name, created_at) VALUES (?, ?, NOW())");
                    $create_tag->bind(1, $company_id);
                    $create_tag->bind(2, $tag_name);
                    
                    if ($create_tag->execute()) {
                        // Obține ID-ul tag-ului nou creat
                        $tag_id_stmt = $db->query("SELECT LAST_INSERT_ID() as tag_id");
                        $tag_id_result = $tag_id_stmt->fetch();
                        $tag_id = $tag_id_result['tag_id'];
                    } else {
                        continue; // Sări la următorul tag dacă crearea a eșuat
                    }
                }
                
                // Asociază tag-ul cu documentul
                $assoc_stmt = $db->query("INSERT IGNORE INTO document_tags (document_id, tag_id, created_at) VALUES (?, ?, NOW())");
                $assoc_stmt->bind(1, $document_id);
                $assoc_stmt->bind(2, $tag_id);
                $assoc_stmt->execute();
            }
        }
    } else {
        // Dacă nu sunt taguri, șterge toate asocierile existente
        $delete_tags_stmt = $db->query("DELETE FROM document_tags WHERE document_id = ?");
        $delete_tags_stmt->bind(1, $document_id);
        $delete_tags_stmt->execute();
    }
    
    $_SESSION['success'] = 'Documentul "' . htmlspecialchars($title) . '" a fost actualizat cu succes.';
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Document update error: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
    
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