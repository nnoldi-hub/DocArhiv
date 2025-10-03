<?php
/**
 * SuperAdmin System Entry Point
 * public/superadmin-system.php
 */

require_once '../config/config.php';

// Verifică autentificare SuperAdmin
if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    redirect('/login.php');
}

// Configurează variabilele pentru layout
$page_title = 'Sistem - SuperAdmin';
$page_description = 'Configurări globale și întreținere sistem';
$current_page = 'system';
$content_file = '../modules/superadmin/system_content.php';

// Include layout-ul unificat
require_once '../modules/superadmin/layout.php';
