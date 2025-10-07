<?php
/**
 * Pagina pentru gestionarea utilizatorilor - SuperAdmin
 * modules/superadmin/users.php
 */

// Include config-ul necesar
require_once '../../config/config.php';

// Setări pentru layout
$page_title = 'Gestionare Utilizatori';
$page_description = 'Administrează utilizatorii din toate companiile';
$current_page = 'users';
$content_file = __DIR__ . '/users_content.php';

// Include layout-ul principal
include 'layout.php';
?>
