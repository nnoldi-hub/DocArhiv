<?php
/**
 * Admin Edit Document Entry Point - Clean Version
 * Generated: <?= date('Y-m-d H:i:s') ?>
 */

// Robust path detection for Hostico layout
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
} elseif (file_exists(__DIR__ . '/../document-archive/config/config.php')) {
    require_once __DIR__ . '/../document-archive/config/config.php';
} else {
    http_response_code(500);
    die('Configuration not found');
}

// Authentication check
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
    exit;
}

// Include edit module
if (file_exists(__DIR__ . '/../modules/admin/edit_document.php')) {
    require_once __DIR__ . '/../modules/admin/edit_document.php';
} elseif (file_exists(__DIR__ . '/../document-archive/modules/admin/edit_document.php')) {
    require_once __DIR__ . '/../document-archive/modules/admin/edit_document.php';
} else {
    http_response_code(500);
    die('Edit module not found');
}

// Explicit termination - nothing should output after this point
exit;