<?php
/**
 * Admin Edit Document Entry Point (build)
 * Robust path resolution for Hostico layout
 */

// Locate config in dev or production folder structure
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

// Auth check
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Include module from dev or production layout
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
// Ensure no stray content from this entrypoint is rendered after the module
exit;
?>