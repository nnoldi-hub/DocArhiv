<?php
/**
 * SuperAdmin Reports Entry Point
 * public/superadmin-reports.php
 */

require_once '../config/config.php';

// Verifică autentificare SuperAdmin
if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    redirect('/login.php');
}

// Configurează layout
$page_title = 'Rapoarte și Statistici';
$page_description = 'Analize detaliate și export date';
$current_page = 'reports';
$content_file = '../modules/superadmin/reports_content.php';

require_once '../modules/superadmin/layout.php';