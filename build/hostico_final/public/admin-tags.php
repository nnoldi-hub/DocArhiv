<?php
/**
 * Admin Tags Entry Point
 * public/admin-tags.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Configurează variabilele pentru layout
$page_title = 'Gestiune Taguri - Admin';
$current_page = 'tags';
$content_file = '../modules/admin/tags_content.php';

// Include layout-ul unificat
require_once '../modules/admin/layout.php';
