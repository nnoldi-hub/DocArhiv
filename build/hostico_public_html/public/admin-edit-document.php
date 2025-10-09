<?php
<?php
/**
 * Admin Edit Document Entry Point
 * build/hostico_public_html/public/admin-edit-document.php
 */

// Resolve config path whether this runs from public_gusturidelatara or local public
$devConfig = __DIR__ . '/../config/config.php';
$prodConfig = __DIR__ . '/../document-archive/config/config.php';

if (file_exists($devConfig)) {
    require_once $devConfig;
} elseif (file_exists($prodConfig)) {
    require_once $prodConfig;
} else {
    http_response_code(500);
    echo 'Config not found';
    exit;
}

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Resolve module path for both layouts
$devModule = __DIR__ . '/../modules/admin/edit_document.php';
$prodModule = __DIR__ . '/../document-archive/modules/admin/edit_document.php';

if (file_exists($devModule)) {
    require_once $devModule;
} elseif (file_exists($prodModule)) {
    require_once $prodModule;
} else {
    http_response_code(500);
    echo 'Edit module not found';
    exit;
}
?>
    
    // Include modulul de editare
    require_once '../modules/admin/edit_document.php';
    
} catch (Exception $e) {
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
    echo "<h1>Eroare la încărcarea paginii de editare</h1>";
    echo "<p><strong>Eroare:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Fișier:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Linia:</strong> " . $e->getLine() . "</p>";
    echo "<p><a href='admin-documents.php'>← Înapoi la documente</a></p>";
    echo "</body></html>";
} catch (Error $e) {
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
    echo "<h1>Eroare fatală la încărcarea paginii de editare</h1>";
    echo "<p><strong>Eroare:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Fișier:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Linia:</strong> " . $e->getLine() . "</p>";
    echo "<p><a href='admin-documents.php'>← Înapoi la documente</a></p>";
    echo "</body></html>";
}
?>