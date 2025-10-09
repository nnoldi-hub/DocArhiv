<?php
/**
 * Admin Edit Document Entry Point
 * public/admin-edit-document.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Include modulul de editare
require_once '../modules/admin/edit_document.php';
?>