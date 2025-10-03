<?php
/**
 * SuperAdmin Users Entry Point
 * public/superadmin-users.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol SuperAdmin
if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    redirect('/login.php');
}

// Configurează variabilele pentru layout
$page_title = 'Gestiune Utilizatori - SuperAdmin';
$current_page = 'users';
$content_file = '../modules/superadmin/users_content.php';

// Include layout-ul unificat
require_once '../modules/superadmin/layout.php';