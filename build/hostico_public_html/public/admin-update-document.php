<?php
require_once __DIR__ . '/../includes/classes/database.php';
require_once __DIR__ . '/../includes/functions/security.php';
require_once __DIR__ . '/../includes/functions/helpers.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect(APP_URL . '/login.php');
    exit;
}

// Verifică CSRF
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Token de securitate invalid.';
    redirect(APP_URL . '/admin-documents.php');
    exit;
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

if (!empty($document_date) && !DateTime::createFromFormat('Y-m-d', $document_date)) {
    $_SESSION['error'] = 'Data documentului nu este validă.';
    redirect(APP_URL . '/admin-edit-document.php?id=' . $document_id);
    exit;
}

if (!empty($expiry_date) && !DateTime::createFromFormat('Y-m-d', $expiry_date)) {
    $_SESSION['error'] = 'Data expirării nu este validă.';
    redirect(APP_URL . '/admin-edit-document.php?id=' . $document_id);
    exit;
}

try {
    $db = new Database();
    
    // Verifică că documentul aparține companiei curente
    $check_stmt = $db->query("SELECT id FROM documents WHERE id = :doc_id AND company_id = :company_id AND status = 'active'");
    $check_stmt->bind(':doc_id', $document_id);
    $check_stmt->bind(':company_id', $company_id);
    
    if (!$check_stmt->fetch()) {
        $_SESSION['error'] = 'Documentul nu a fost găsit sau nu aveți permisiunea să îl editați.';
        redirect(APP_URL . '/admin-documents.php');
        exit;
    }
    
    // Pregătește datele pentru actualizare
    $dept_to_save = ($department_id > 0) ? $department_id : null;
    $doc_date_to_save = (!empty($document_date)) ? $document_date : null;
    $exp_date_to_save = (!empty($expiry_date)) ? $expiry_date : null;
    $doc_number_to_save = (!empty($document_number)) ? $document_number : null;
    $description_to_save = (!empty($description)) ? $description : null;
    
    // Actualizează documentul
    $update_stmt = $db->query("
        UPDATE documents SET 
            title = :title,
            description = :description,
            department_id = :department_id,
            document_number = :document_number,
            document_date = :document_date,
            expiry_date = :expiry_date,
            is_confidential = :is_confidential,
            updated_by = :updated_by,
            updated_at = NOW()
        WHERE id = :document_id AND company_id = :company_id
    ");
    
    $update_stmt->bind(':title', $title);
    $update_stmt->bind(':description', $description_to_save);
    $update_stmt->bind(':department_id', $dept_to_save);
    $update_stmt->bind(':document_number', $doc_number_to_save);
    $update_stmt->bind(':document_date', $doc_date_to_save);
    $update_stmt->bind(':expiry_date', $exp_date_to_save);
    $update_stmt->bind(':is_confidential', $is_confidential);
    $update_stmt->bind(':updated_by', $user_id);
    $update_stmt->bind(':document_id', $document_id);
    $update_stmt->bind(':company_id', $company_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Eroare la actualizarea documentului.');
    }
    
    // Gestionarea tagurilor
    if (!empty($tags_input)) {
        // Șterge toate asocierile existente pentru acest document
        $delete_tags_stmt = $db->query("DELETE FROM document_tags WHERE document_id = :doc_id");
        $delete_tags_stmt->bind(':doc_id', $document_id);
        $delete_tags_stmt->execute();
        
        // Procesează noile taguri
        $tags_array = array_map('trim', explode(',', $tags_input));
        $tags_array = array_filter($tags_array); // elimină tagurile goale
        $tags_array = array_unique($tags_array); // elimină duplicatele
        
        foreach ($tags_array as $tag_name) {
            if (strlen($tag_name) > 0 && strlen($tag_name) <= 50) {
                // Verifică dacă tag-ul există, dacă nu îl creează
                $tag_stmt = $db->query("SELECT id FROM tags WHERE company_id = :company_id AND name = :name");
                $tag_stmt->bind(':company_id', $company_id);
                $tag_stmt->bind(':name', $tag_name);
                $tag_result = $tag_stmt->fetch();
                
                if ($tag_result) {
                    $tag_id = $tag_result['id'];
                } else {
                    // Creează tag nou
                    $create_tag = $db->query("INSERT INTO tags (company_id, name, created_at) VALUES (:company_id, :name, NOW())");
                    $create_tag->bind(':company_id', $company_id);
                    $create_tag->bind(':name', $tag_name);
                    
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
                $assoc_stmt = $db->query("INSERT IGNORE INTO document_tags (document_id, tag_id, created_at) VALUES (:doc_id, :tag_id, NOW())");
                $assoc_stmt->bind(':doc_id', $document_id);
                $assoc_stmt->bind(':tag_id', $tag_id);
                $assoc_stmt->execute();
                
                // Actualizează usage_count pentru tag
                $update_usage = $db->query("UPDATE tags SET usage_count = (SELECT COUNT(*) FROM document_tags WHERE tag_id = :tag_id) WHERE id = :tag_id");
                $update_usage->bind(':tag_id', $tag_id);
                $update_usage->execute();
            }
        }
    } else {
        // Dacă nu sunt taguri, șterge toate asocierile existente
        $delete_tags_stmt = $db->query("DELETE FROM document_tags WHERE document_id = :doc_id");
        $delete_tags_stmt->bind(':doc_id', $document_id);
        $delete_tags_stmt->execute();
        
        // Actualizează usage_count pentru toate tagurile
        $update_all_usage = $db->query("
            UPDATE tags SET usage_count = (
                SELECT COUNT(*) FROM document_tags WHERE tag_id = tags.id
            ) WHERE company_id = :company_id
        ");
        $update_all_usage->bind(':company_id', $company_id);
        $update_all_usage->execute();
    }
    
    $_SESSION['success'] = 'Documentul "' . htmlspecialchars($title) . '" a fost actualizat cu succes.';
    
    // Log activitate
    if (function_exists('logActivity')) {
        logActivity('update_document', 'Document actualizat: ' . $title, 'document', $document_id);
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Eroare la actualizarea documentului: ' . $e->getMessage();
    
    if (function_exists('logError')) {
        logError('Document update failed: ' . $e->getMessage(), ['document_id' => $document_id, 'user_id' => $user_id]);
    }
    
    redirect(APP_URL . '/admin-edit-document.php?id=' . $document_id);
    exit;
}

redirect(APP_URL . '/admin-documents.php');
?>