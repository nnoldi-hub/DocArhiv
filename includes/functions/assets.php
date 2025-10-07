<?php
/**
 * Funcție helper pentru încărcarea assets-urilor locale
 * Înlocuiește CDN-urile cu fișiere locale pentru compatibilitate Hostico
 */

function loadLocalAssets() {
    $appUrl = defined('APP_URL') ? APP_URL : '';
    $baseUrl = rtrim($appUrl, '/');
    
    echo "\n<!-- Local Assets pentru Hostico -->\n";
    echo '<link href="' . $baseUrl . '/assets/css/bootstrap.min.css" rel="stylesheet">' . "\n";
    echo '<link href="' . $baseUrl . '/assets/css/bootstrap-icons.css" rel="stylesheet">' . "\n";
    echo "\n";
}

function loadLocalBootstrapJS() {
    $appUrl = defined('APP_URL') ? APP_URL : '';
    $baseUrl = rtrim($appUrl, '/');
    
    echo "\n<!-- Bootstrap JS Local -->\n";
    echo '<script src="' . $baseUrl . '/assets/js/bootstrap.bundle.min.js"></script>' . "\n";
}

// Actualizează funcția din helpers.php
function renderBootstrapAssets() {
    // Folosim assets-uri locale pentru production
    if (defined('APP_URL') && strpos(APP_URL, 'gusturidelatara.ro') !== false) {
        echo "\n<!-- Local Assets pentru Hostico -->\n";
        echo '<link href="/assets/css/bootstrap.min.css" rel="stylesheet">' . "\n";
        echo '<link href="/assets/css/bootstrap-icons.css" rel="stylesheet">' . "\n";
        return;
    }
    
    // Fallback la CDN pentru development local
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">';
}

function renderBootstrapJS() {
    // Folosim assets-uri locale pentru production
    if (defined('APP_URL') && strpos(APP_URL, 'gusturidelatara.ro') !== false) {
        echo "\n<!-- Bootstrap JS Local -->\n";
        echo '<script src="/assets/js/bootstrap.bundle.min.js"></script>' . "\n";
        return;
    }
    
    // Fallback la CDN pentru development local
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>';
}
?>