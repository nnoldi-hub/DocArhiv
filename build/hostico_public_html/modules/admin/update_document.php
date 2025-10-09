<?php
// Handler pentru actualizarea documentelor
// modules/admin/update_document.php

require_once __DIR__ . '/../../includes/classes/database.php';
require_once __DIR__ . '/../../includes/functions/security.php';
require_once __DIR__ . '/../../includes/functions/helpers.php';

// Verifică autentificarea
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect(APP_URL . '/login.php');
    exit;
}

// Verifică CSRF
if (!validateCSRF()) {
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

// Validări
if (empty($title)) {
    $_SESSION['error'] = 'Titlul documentului este obligatoriu.';
    redirect(APP_URL . '/modules/admin/edit_document.php?id=' . $document_id);
    exit;
}

try {
    $db = new Database();
    
    // Verifică că documentul aparține companiei curente
    $check_stmt = $db->query("SELECT id FROM documents WHERE id = :doc_id AND company_id = :company_id AND status = 'active'");
    $check_stmt->bind(':doc_id', $document_id);
    $check_stmt->bind(':company_id', $company_id);
    
    if (!$check_stmt->fetch()) {
        $_SESSION['error'] = 'Documentul nu a fost găsit.';
        redirect(APP_URL . '/admin-documents.php');
        exit;
    }
    
    // Pregătește datele pentru actualizare
    $dept_to_save = ($department_id > 0) ? $department_id : null;
    $description_to_save = (!empty($description)) ? $description : null;
    
    // Actualizează documentul
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
                        // Obține ID-ul tag-ului nou creat - folosind metoda din clasa Database
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
            }
        }
    } else {
        // Dacă nu sunt taguri, șterge toate asocierile existente
        $delete_tags_stmt = $db->query("DELETE FROM document_tags WHERE document_id = :doc_id");
        $delete_tags_stmt->bind(':doc_id', $document_id);
        $delete_tags_stmt->execute();
    }
    
    $_SESSION['success'] = 'Documentul "' . htmlspecialchars($title) . '" a fost actualizat cu succes.';
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Eroare la actualizarea documentului: ' . $e->getMessage();
    redirect(APP_URL . '/modules/admin/edit_document.php?id=' . $document_id);
    exit;
}

redirect(APP_URL . '/admin-documents.php');
?>