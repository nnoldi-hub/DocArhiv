<?php
// Handler de ștergere inclus din public/admin-delete.php

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
        SELECT id, title, file_path
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
    
    // Marchează documentul ca șters (soft delete)
    $stmt = $db->prepare("UPDATE documents SET status = 'deleted', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$document_id]);
    
    // Log activitate
    if (function_exists('logActivity')) {
        logActivity('delete', 'Document șters: ' . $document['title'], 'document', $document_id);
    }
    
    $_SESSION['success'] = 'Documentul "' . htmlspecialchars($document['title']) . '" a fost șters.';
    
    // Opțional: șterge și fișierul fizic (dacă vrei ștergere completă)
    /*
    $file_path = UPLOAD_PATH . '/' . $document['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    */
    
} catch (Exception $e) {
    if (function_exists('logError')) {
        logError('Document delete failed: ' . $e->getMessage(), ['document_id' => $document_id]);
    }
    
    $_SESSION['error'] = 'Eroare la ștergerea documentului.';
}

redirect(APP_URL . '/admin-documents.php');
