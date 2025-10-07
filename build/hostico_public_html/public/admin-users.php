<?php
/**
 * Admin Users Entry Point
 * public/admin-users.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Configurează variabilele pentru layout
$page_title = 'Gestiune Utilizatori - Admin';
$current_page = 'users';
$content_file = '../modules/admin/users_content.php';

// Include layout-ul unificat
require_once '../modules/admin/layout.php';
