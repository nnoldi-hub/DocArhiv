<?php
/**
 * Admin Edit Document Entry Point
 * public/admin-edit-document.php
 */

// Activez debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once '../config/config.php';
    
    // Verifică autentificare și rol Admin/Manager
    if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
        redirect('/login.php');
    }
    
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