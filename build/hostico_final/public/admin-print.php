<?php
/**
 * Admin Print Document Entry Point
 * public/admin-print.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Include handler-ul din modules
require_once '../modules/admin/print.php';