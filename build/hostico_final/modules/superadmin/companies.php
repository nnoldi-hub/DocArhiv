<?php
/**
 * Pagina pentru gestionarea companiilor - SuperAdmin
 * modules/superadmin/companies.php
 */

// Include config-ul necesar
require_once '../../config/config.php';

// Setări pentru layout
$page_title = 'Gestionare Companii';
$page_description = 'Administrează companiile înregistrate în sistem';
$current_page = 'companies';
$content_file = __DIR__ . '/companies_content.php';

// Include layout-ul principal
include 'layout.php';
?>
