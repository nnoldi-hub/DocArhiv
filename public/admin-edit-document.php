<?php
/**
 * Admin Edit Document Entry Point
 * public/admin-edit-document.php
 *
 * NOTE: In production (Hostico), public files live in /public_gusturidelatara
 * while the application code lives in /document-archive. We resolve both paths.
 */

// Fail-safe error visibility during troubleshooting only (kept quiet by config otherwise)
ini_set('log_errors', 1);

// Resolve config path for both dev (../config) and prod (../document-archive/config)
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

// Optional trace mode to debug production issues without logs
if (isset($_GET['trace'])) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "TRACE: config loaded, auth ok\n";
}

// Resolve module path for both dev and prod layouts
$devModule = __DIR__ . '/../modules/admin/edit_document.php';
$prodModule = __DIR__ . '/../document-archive/modules/admin/edit_document.php';

if (file_exists($devModule)) {
    if (isset($_GET['trace'])) echo "TRACE: including dev module...\n";
    require_once $devModule;
} elseif (file_exists($prodModule)) {
    if (isset($_GET['trace'])) echo "TRACE: including prod module...\n";
    require_once $prodModule;
} else {
    http_response_code(500);
    echo 'Edit module not found';
    exit;
}

// Stop here to avoid any accidental trailing output in deployed files
if (isset($_GET['trace'])) echo "TRACE: module included successfully.\nTerminating script to avoid trailing output.\n";
exit;
?>