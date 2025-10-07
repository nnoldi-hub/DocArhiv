<?php
/**
 * Admin View Document Entry Point
 * public/admin-view-document.php
 */

require_once __DIR__ . '/../config/config.php';

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Include handler-ul din modules
require_once __DIR__ . '/../modules/admin/view_document.php';