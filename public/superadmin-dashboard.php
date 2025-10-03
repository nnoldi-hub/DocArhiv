<?php
/**
 * SuperAdmin Dashboard Entry Point
 * public/superadmin-dashboard.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol SuperAdmin
if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    redirect('/login.php');
}

// Configurează variabilele pentru layout
$page_title = 'Dashboard - SuperAdmin';
$current_page = 'dashboard';
$content_file = '../modules/superadmin/dashboard_content.php'; // Updated to point to the new content file

// Include layout-ul unificat
require_once '../modules/superadmin/layout.php';