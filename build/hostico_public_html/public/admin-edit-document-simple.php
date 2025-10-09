<?php
/**
 * Admin Edit Document - Versiune Standalone
 * Pentru a evita problemele cu include-uri complexe
 */

// Activez debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html lang='ro'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Edit Document</title>
</head>
<body>
    <h1>Test Admin Edit Document</h1>";

try {
    echo "<p>Încărcare config...</p>";
    require_once '../config/config.php';
    echo "<p>✓ Config încărcat cu succes</p>";
    
    echo "<p>Test funcții...</p>";
    if (function_exists('isLoggedIn')) {
        echo "<p>✓ Funcția isLoggedIn există</p>";
    } else {
        echo "<p>✗ Funcția isLoggedIn NU există</p>";
    }
    
    if (function_exists('hasRole')) {
        echo "<p>✓ Funcția hasRole există</p>";
    } else {
        echo "<p>✗ Funcția hasRole NU există</p>";
    }
    
    echo "<p>Test conexiune database...</p>";
    $pdo = getDBConnection();
    echo "<p>✓ Conexiune database OK</p>";
    
    // Simulez sesiune de admin
    $_SESSION['user_id'] = 1;
    $_SESSION['company_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Test Admin';
    
    echo "<p>✓ Sesiune simulată setată</p>";
    
    // Test document ID
    $document_id = (int)($_GET['id'] ?? 0);
    echo "<p>Document ID din URL: $document_id</p>";
    
    if ($document_id > 0) {
        $stmt = $pdo->prepare("SELECT id, title, description, company_id FROM documents WHERE id = ? LIMIT 1");
        $stmt->execute([$document_id]);
        $document = $stmt->fetch();
        
        if ($document) {
            echo "<p>✓ Document găsit:</p>";
            echo "<ul>";
            echo "<li>ID: " . $document['id'] . "</li>";
            echo "<li>Titlu: " . htmlspecialchars($document['title']) . "</li>";
            echo "<li>Company ID: " . $document['company_id'] . "</li>";
            echo "</ul>";
            
            // Formular simplu de editare
            echo "<h2>Formular de editare</h2>";
            echo "<form method='post' action='admin-update-document.php' style='border: 1px solid #ccc; padding: 20px; max-width: 500px;'>";
            echo "<input type='hidden' name='document_id' value='" . $document['id'] . "'>";
            echo "<div style='margin-bottom: 10px;'>";
            echo "<label>Titlu:</label><br>";
            echo "<input type='text' name='title' value='" . htmlspecialchars($document['title']) . "' style='width: 100%; padding: 5px;'>";
            echo "</div>";
            echo "<div style='margin-bottom: 10px;'>";
            echo "<label>Descriere:</label><br>";
            echo "<textarea name='description' style='width: 100%; padding: 5px; height: 80px;'>" . htmlspecialchars($document['description'] ?? '') . "</textarea>";
            echo "</div>";
            echo "<button type='submit' style='background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer;'>Salvează</button>";
            echo " <a href='admin-documents.php' style='padding: 10px 20px; background: #ccc; text-decoration: none; color: black;'>Anulează</a>";
            echo "</form>";
            
        } else {
            echo "<p>✗ Document cu ID $document_id nu a fost găsit</p>";
            
            // Arată documente disponibile
            $stmt = $pdo->prepare("SELECT id, title FROM documents LIMIT 5");
            $stmt->execute();
            $docs = $stmt->fetchAll();
            
            echo "<p>Documente disponibile:</p><ul>";
            foreach ($docs as $doc) {
                echo "<li><a href='?id=" . $doc['id'] . "'>ID " . $doc['id'] . ": " . htmlspecialchars($doc['title']) . "</a></li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p>Nu s-a furnizat un ID valid de document în URL</p>";
        echo "<p>Exemplu: <a href='?id=1'>?id=1</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ EROARE: " . $e->getMessage() . "</p>";
    echo "<p>Fișier: " . $e->getFile() . "</p>";
    echo "<p>Linia: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<p style='color: red;'>✗ EROARE FATALĂ: " . $e->getMessage() . "</p>";
    echo "<p>Fișier: " . $e->getFile() . "</p>";
    echo "<p>Linia: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
?>