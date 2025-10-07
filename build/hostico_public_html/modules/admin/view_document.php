<?php
// Handler de vizualizare inclus din public/admin-view-document.php

// DEBUG: Verifică calea fișierului actual
if (isset($_GET['debug'])) {
    echo "Current file: " . __FILE__ . "<br>";
    echo "Document ID: " . ($_GET['id'] ?? 'none') . "<br>";
    echo "APP_URL: " . (defined('APP_URL') ? APP_URL : 'not defined') . "<br>";
    echo "Session company_id: " . ($_SESSION['company_id'] ?? 'none') . "<br>";
    exit;
}

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
        SELECT id, title, file_name, file_path, file_type, file_size, created_at, uploaded_by
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
    
    // Incrementează contorul de vizualizare
    $db->prepare("UPDATE documents SET view_count = view_count + 1 WHERE id = ?")
       ->execute([$document_id]);
    
    // Log activitate
    if (function_exists('logActivity')) {
        logActivity('view', 'Document vizualizat: ' . $document['title'], 'document', $document_id);
    }
    
    $file_path = UPLOAD_PATH . '/' . $document['file_path'];
    $file_extension = strtolower(pathinfo($document['file_name'], PATHINFO_EXTENSION));
    
} catch (Exception $e) {
    if (function_exists('logError')) {
        logError('Document view failed: ' . $e->getMessage(), ['document_id' => $document_id]);
    }
    
    $_SESSION['error'] = 'Eroare la încărcarea documentului.';
    redirect(APP_URL . '/admin-documents.php');
    exit;
}

// Nu folosim layout-ul admin pentru pagina de vizualizare
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vizualizare Document - <?php echo htmlspecialchars($document['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .document-viewer { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="document-viewer">
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                    <div>
                        <h4 class="mb-1"><?php echo htmlspecialchars($document['title']); ?></h4>
                        <small class="text-muted">
                            <?php echo htmlspecialchars($document['file_name']); ?> 
                            (<?php echo formatFileSize($document['file_size']); ?>)
                            - <?php echo formatDateTime($document['created_at']); ?>
                        </small>
                    </div>
                    <div class="btn-group">
                        <a href="<?php echo APP_URL; ?>/admin-download.php?id=<?php echo $document['id']; ?>" class="btn btn-success">
                            <i class="bi bi-download me-1"></i>Descarcă
                        </a>
                        <a href="<?php echo APP_URL; ?>/admin-print.php?id=<?php echo $document['id']; ?>" target="_blank" class="btn btn-warning">
                            <i class="bi bi-printer me-1"></i>Print
                        </a>
                        <a href="<?php echo APP_URL; ?>/admin-documents.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Înapoi
                        </a>
                    </div>
                </div>
                
                <div class="p-0">
                    <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                        <!-- Afișare imagine -->
                        <div class="text-center p-3">
                            <img src="<?php echo APP_URL; ?>/admin-stream.php?id=<?php echo $document['id']; ?>" 
                                 class="img-fluid" style="max-height: 80vh;" 
                                 alt="<?php echo htmlspecialchars($document['title']); ?>">
                        </div>
                    
                    <?php elseif ($file_extension === 'pdf'): ?>
                        <!-- Afișare PDF -->
                        <iframe src="<?php echo APP_URL; ?>/admin-stream.php?id=<?php echo $document['id']; ?>" 
                                style="width:100%;height:80vh;border:none;" 
                                title="<?php echo htmlspecialchars($document['title']); ?>">
                        </iframe>
                    
                    <?php else: ?>
                        <!-- Fișiere care nu se pot afișa direct -->
                        <div class="text-center p-5">
                            <i class="bi bi-file-earmark display-1 text-muted"></i>
                            <h5 class="mt-3">Previzualizare indisponibilă</h5>
                            <p class="text-muted">Acest tip de fișier nu poate fi afișat în browser.</p>
                            <a href="<?php echo APP_URL; ?>/admin-download.php?id=<?php echo $document['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-download me-1"></i>Descarcă pentru vizualizare
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
