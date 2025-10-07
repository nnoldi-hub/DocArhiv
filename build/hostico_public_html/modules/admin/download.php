<?php
// Handler de download inclus din public/admin-download.php

$document_id = (int)($_GET['id'] ?? 0);

if (!$document_id) {
    $_SESSION['error'] = 'Document invalid.';
    redirect(APP_URL . '/admin-documents.php');
    exit;
}

try {
    $db = getDBConnection();
    $company_id = (int)($_SESSION['company_id'] ?? 0);
    
    // Găsește documentul în compania curentă
    $stmt = $db->prepare("
        SELECT file_name, file_path, file_type, title 
        FROM documents 
        WHERE id = ? AND company_id = ? AND status = 'active'
    ");
    $stmt->execute([$document_id, $company_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        $_SESSION['error'] = 'Documentul nu a fost găsit.';
        redirect(APP_URL . '/admin-documents.php');
        exit;
    }
    
    $file_path = UPLOAD_PATH . '/' . $document['file_path'];
    
    if (!file_exists($file_path)) {
        $_SESSION['error'] = 'Fișierul nu există pe server.';
        redirect(APP_URL . '/admin-documents.php');
        exit;
    }
    
    // Incrementează contorul de download
    $db->prepare("UPDATE documents SET download_count = download_count + 1 WHERE id = ?")
       ->execute([$document_id]);
    
    // Log activitate
    if (function_exists('logActivity')) {
        logActivity('download', 'Document descărcat: ' . $document['title'], 'document', $document_id);
    }
    
    // Setează header-ele pentru download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $document['file_name'] . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Trimite fișierul
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    if (function_exists('logError')) {
        logError('Document download failed: ' . $e->getMessage(), ['document_id' => $document_id]);
    }
    
    $_SESSION['error'] = 'Eroare la descărcarea documentului.';
    redirect(APP_URL . '/admin-documents.php');
    exit;
}
