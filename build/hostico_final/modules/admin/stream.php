<?php
// Handler de streaming inclus din public/admin-stream.php

$document_id = (int)($_GET['id'] ?? 0);

if (!$document_id) {
    http_response_code(400);
    exit('Invalid document ID');
}

try {
    $db = getDBConnection();
    $company_id = (int)($_SESSION['company_id'] ?? 0);
    
    // Găsește documentul în compania curentă
    $stmt = $db->prepare("
        SELECT file_name, file_path, file_type, mime_type
        FROM documents 
        WHERE id = ? AND company_id = ? AND status = 'active'
    ");
    $stmt->execute([$document_id, $company_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        http_response_code(404);
        exit('Document not found');
    }
    
    $file_path = UPLOAD_PATH . '/' . $document['file_path'];
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        exit('File not found on server');
    }
    
    // Determină MIME type-ul
    $mime_type = $document['mime_type'] ?: 'application/octet-stream';
    $file_extension = strtolower(pathinfo($document['file_name'], PATHINFO_EXTENSION));
    
    // Override MIME type pentru extensii cunoscute
    $mime_types = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg', 
        'png' => 'image/png',
        'gif' => 'image/gif',
        'txt' => 'text/plain',
        'html' => 'text/html'
    ];
    
    if (isset($mime_types[$file_extension])) {
        $mime_type = $mime_types[$file_extension];
    }
    
    // Setează header-ele pentru streaming
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($file_path));
    
    // Pentru PDF-uri, permite afișarea în browser
    if ($file_extension === 'pdf') {
        header('Content-Disposition: inline; filename="' . $document['file_name'] . '"');
    }
    
    // Disable caching pentru securitate
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Trimite fișierul
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    if (function_exists('logError')) {
        logError('Document stream failed: ' . $e->getMessage(), ['document_id' => $document_id]);
    }
    
    http_response_code(500);
    exit('Server error');
}
