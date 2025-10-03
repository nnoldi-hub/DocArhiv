<?php
/**
 * Admin Settings Entry Point
 * public/admin-settings.php
 */

require_once '../config/config.php';

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    redirect('/login.php');
}

// Configurează variabilele pentru layout
$page_title = 'Setări Companie - Admin';
$current_page = 'settings';
// Folosește cale absolută pentru conținut ca să evităm problemele de CWD
$content_file = realpath(__DIR__ . '/../modules/admin/settings_content.php');
if ($content_file === false) {
    // Fallback relativ dacă realpath eșuează (ex. pe unele medii restricționate)
    $content_file = __DIR__ . '/../modules/admin/settings_content.php';
}

// Include layout-ul unificat
require_once '../modules/admin/layout.php';

