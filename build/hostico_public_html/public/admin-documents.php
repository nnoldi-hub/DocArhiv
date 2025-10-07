<?php
/**
 * Admin Documents Entry Point
 * public/admin-documents.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Configurează variabilele pentru layout
$page_title = 'Gestiune Documente - Admin';
$current_page = 'documents';
$content_file = '../modules/admin/documents_content.php';

// Include layout-ul unificat
require_once '../modules/admin/layout.php';
