<?php
/**
 * SuperAdmin Logs Entry Point
 * public/superadmin-logs.php
 */

require_once '../config/config.php';

if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    redirect('/login.php');
}

$page_title = 'Log-uri Sistem';
$page_description = 'Monitorizare activitate și erori sistem';
$current_page = 'logs';
$content_file = '../modules/superadmin/logs_content.php';

require_once '../modules/superadmin/layout.php';