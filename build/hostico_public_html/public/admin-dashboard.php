<?php
/**
 * Admin Dashboard Entry Point
 * public/admin-dashboard.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Configurează variabilele pentru layout
$page_title = 'Dashboard - Admin';
$current_page = 'dashboard';
$content_file = '../modules/admin/dashboard_content.php';

// Include layout-ul unificat
require_once '../modules/admin/layout.php';
