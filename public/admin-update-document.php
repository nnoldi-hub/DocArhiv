<?php
/**
 * Admin Update Document Handler
 * public/admin-update-document.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Include handler-ul de update
require_once '../modules/admin/update_document.php';
?>