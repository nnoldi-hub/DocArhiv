<?php
// Handler de printare inclus din public/admin-print.php

$document_id = (int)($_GET['id'] ?? 0);

if (!$document_id) {
    die('Document invalid');
}

try {
    $db = getDBConnection();
    $company_id = (int)($_SESSION['company_id'] ?? 0);
    
    // Găsește documentul în compania curentă
    $stmt = $db->prepare("
        SELECT id, title, original_filename as file_name, stored_filename, file_path
        FROM documents 
        WHERE id = ? AND company_id = ? AND status = 'active'
    ");
    $stmt->execute([$document_id, $company_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        die('Documentul nu a fost găsit');
    }
    
    // Log activitate print
    if (function_exists('logActivity')) {
        logActivity('print', 'Document printat: ' . $document['title'], 'document', $document_id);
    }
    
} catch (Exception $e) {
    die('Eroare la încărcarea documentului');
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print - <?php echo htmlspecialchars($document['title']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .document-info { margin-bottom: 20px; }
        .print-instructions { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        @media print {
            .print-instructions { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($document['title']); ?></h1>
        <p><strong>Fișier:</strong> <?php echo htmlspecialchars($document['file_name']); ?></p>
        <p><strong>Data printării:</strong> <?php echo date('d.m.Y H:i'); ?></p>
    </div>
    
    <div class="print-instructions">
        <h3>Instrucțiuni de printare:</h3>
        <p>1. Apăsați Ctrl+P sau CMD+P pentru a deschide dialogul de printare</p>
        <p>2. Pentru documente PDF/imagine, acestea vor fi descărcate automat pentru printare</p>
        <p>3. Această pagină poate fi printată ca foaie de parcurs pentru document</p>
    </div>
    
    <div class="document-info">
        <h3>Informații document:</h3>
        <p><strong>Titlu:</strong> <?php echo htmlspecialchars($document['title']); ?></p>
        <p><strong>Nume fișier:</strong> <?php echo htmlspecialchars($document['file_name']); ?></p>
        <p><strong>Data printării:</strong> <?php echo date('d.m.Y H:i'); ?></p>
        <p><strong>Utilizator:</strong> <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></p>
        <p><strong>Companie:</strong> <?php echo htmlspecialchars($_SESSION['company_name'] ?? ''); ?></p>
    </div>
    
    <script>
        // Auto-deschide download pentru fișier și apoi printare
        setTimeout(function() {
            // Deschide download-ul documentului
            window.open('<?php echo APP_URL; ?>/admin-download.php?id=<?php echo $document_id; ?>', '_blank');
            
            // Deschide dialogul de printare pentru această pagină
            setTimeout(function() {
                window.print();
            }, 1000);
        }, 500);
        
        // Închide fereastra după printare
        window.addEventListener('afterprint', function() {
            setTimeout(function() {
                window.close();
            }, 1000);
        });
    </script>
</body>
</html>
