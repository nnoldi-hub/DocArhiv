<?php
/**
 * Admin Departments Entry Point
 * public/admin-departments.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Configurează variabilele pentru layout
$page_title = 'Gestiune Departamente - Admin';
$current_page = 'departments';
$content_file = '../modules/admin/departments_content.php';

// Include layout-ul unificat
require_once '../modules/admin/layout.php';
