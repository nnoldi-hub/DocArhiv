<?php
// Handler de upload inclus din public/admin-upload.php

// Verifică CSRF dacă funcția există
if (function_exists('verify_csrf')) {
    verify_csrf();
}

// Verifică dacă există fișier uploadat
if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'Nu s-a putut încărca fișierul. Verificați dimensiunea și tipul fișierului.';
    redirect(APP_URL . '/admin-documents.php');
    exit;
}

$file = $_FILES['document'];
$title = trim($_POST['title'] ?? '');
$department_id = (int)($_POST['department_id'] ?? 0);
$tags_input = trim($_POST['tags'] ?? '');
$company_id = (int)($_SESSION['company_id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);

// Verifică extensia și dimensiunea
$allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png'];
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    $_SESSION['error'] = 'Tipul de fișier nu este permis. Extensii permise: ' . implode(', ', $allowed_extensions);
    redirect(APP_URL . '/admin-documents.php');
    exit;
}

if ($file['size'] > MAX_FILE_SIZE) {
    $_SESSION['error'] = 'Fișierul este prea mare. Dimensiunea maximă permisă: ' . formatFileSize(MAX_FILE_SIZE);
    redirect(APP_URL . '/admin-documents.php');
    exit;
}

try {
    $db = getDBConnection();
    
    // Generează un nume unic pentru fișier
    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
    $upload_path = UPLOAD_PATH . '/' . $unique_name;
    
    // Asigură-te că directorul există
    if (!file_exists(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    // Mută fișierul în directorul de upload
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Nu s-a putut salva fișierul pe server.');
    }
    
    // Calculează hash SHA-256 pentru integritate
    $sha256 = hash_file('sha256', $upload_path);
    $metadataJson = null; // metadate opționale (ex: conversie PDF/A)
    
    // Detectare MIME type mai robustă
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
    $detectedMime = $finfo ? finfo_file($finfo, $upload_path) : null;
    if ($finfo) { finfo_close($finfo); }
    $mimeToStore = $detectedMime ?: ($file['type'] ?? null);

    // Opțional: conversie PDF -> PDF/A la upload (activare prin system_settings.convert_pdf_to_pdfa_on_upload)
    if ($file_extension === 'pdf') {
        $enablePdfa = false;
        try {
            $st = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'convert_pdf_to_pdfa_on_upload' LIMIT 1");
            $st->execute();
            $val = $st->fetchColumn();
            $enablePdfa = is_string($val) ? (strtolower(trim($val)) === 'true' || trim($val) === '1') : false;
        } catch (Exception $e) { /* ignore */ }
        if ($enablePdfa) {
            require_once __DIR__ . '/../../includes/functions/archival.php';
            $converted_name = preg_replace('/\.pdf$/i', '_pdfa.pdf', $unique_name);
            $converted_path = UPLOAD_PATH . '/' . $converted_name;
            if (to_pdfa($upload_path, $converted_path)) {
                @unlink($upload_path);
                $unique_name = $converted_name;
                $upload_path = $converted_path;
                // Recalculează hash/mime/size
                $sha256 = hash_file('sha256', $upload_path);
                $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
                $detectedMime = $finfo ? finfo_file($finfo, $upload_path) : null;
                if ($finfo) { finfo_close($finfo); }
                $mimeToStore = $detectedMime ?: 'application/pdf';
                $file['size'] = filesize($upload_path);
            }
        }
    }
    
    // Salvează în baza de date
    // Salvează în baza de date (include metadata JSON și department_id dacă există)
    $stmt = $db->prepare("
           INSERT INTO documents (company_id, created_by, department_id, title, original_filename, stored_filename, file_path, file_size, mime_type, file_hash, metadata, created_at, status) 
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'active')
    ");
    
    $document_title = $title ?: pathinfo($file['name'], PATHINFO_FILENAME);
    $dept_to_save = ($department_id > 0) ? $department_id : null;
    
    $stmt->execute([
        $company_id,
        $user_id,
        $dept_to_save,
        $document_title,
        $file['name'],           // original_filename
        $unique_name,           // stored_filename  
        $unique_name,           // file_path
        $file['size'],
        $mimeToStore,
        $sha256,
        $metadataJson
    ]);
    
    $document_id = $db->lastInsertId();
    
    // Procesează tagurile dacă există
    if (!empty($tags_input)) {
        $tags_array = array_map('trim', explode(',', $tags_input));
        $tags_array = array_filter($tags_array); // elimină tagurile goale
        
        foreach ($tags_array as $tag_name) {
            if (strlen($tag_name) > 0 && strlen($tag_name) <= 50) {
                // Verifică dacă tag-ul există, dacă nu îl creează
                $tag_stmt = $db->prepare("SELECT id FROM tags WHERE company_id = ? AND name = ?");
                $tag_stmt->execute([$company_id, $tag_name]);
                $tag_id = $tag_stmt->fetchColumn();
                
                if (!$tag_id) {
                    // Creează tag nou
                    $create_tag = $db->prepare("INSERT INTO tags (company_id, name, created_at) VALUES (?, ?, NOW())");
                    $create_tag->execute([$company_id, $tag_name]);
                    $tag_id = $db->lastInsertId();
                }
                
                // Asociază tag-ul cu documentul
                $assoc_stmt = $db->prepare("INSERT IGNORE INTO document_tags (document_id, tag_id) VALUES (?, ?)");
                $assoc_stmt->execute([$document_id, $tag_id]);
                
                // Incrementează usage_count pentru tag
                $update_usage = $db->prepare("UPDATE tags SET usage_count = usage_count + 1 WHERE id = ?");
                $update_usage->execute([$tag_id]);
            }
        }
    }
    
    $_SESSION['success'] = 'Documentul "' . htmlspecialchars($document_title) . '" a fost încărcat cu succes.';
    
    // Log activitate
    if (function_exists('logActivity')) {
        logActivity('upload_document', 'Document încărcat: ' . $document_title, 'document', $document_id);
    }
    
} catch (Exception $e) {
    // Șterge fișierul dacă s-a încărcat dar nu s-a salvat în DB
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    $_SESSION['error'] = 'Eroare la încărcarea documentului: ' . $e->getMessage();
    
    if (function_exists('logError')) {
        logError('Document upload failed: ' . $e->getMessage(), ['file' => $file['name'], 'user_id' => $user_id]);
    }
}

redirect(APP_URL . '/admin-documents.php');
