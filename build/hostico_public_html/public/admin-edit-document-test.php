<?php
/**
 * Test Edit Document Entry Point
 */

require_once '../config/config.php';

// Verifică autentificare
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Include modulul de test
require_once '../modules/admin/edit_document_test.php';
?>