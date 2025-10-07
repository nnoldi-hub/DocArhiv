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
    // Preferă căi relative din /public pentru compatibilitate maximă
    // Vor rezolva către /public/assets/... indiferent de rewrite în root
    echo "\n<!-- Local Assets -->\n";
    echo '<link href="assets/css/bootstrap.min.css" rel="stylesheet">' . "\n";
    echo '<link href="assets/css/bootstrap-icons.css" rel="stylesheet">' . "\n";
}

function renderBootstrapJS() {
    // Cale relativă din /public
    echo "\n<!-- Bootstrap JS Local -->\n";
    echo '<script src="assets/js/bootstrap.bundle.min.js"></script>' . "\n";
}
?>