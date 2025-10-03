<?php
/**
 * Admin Stream Document Entry Point
 * public/admin-stream.php
 */

require_once __DIR__ . '/../config/config.php';

// Verifică autentificare și rol Admin/Manager
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('manager'))) {
    http_response_code(401);
    exit('Unauthorized');
}

// Include handler-ul din modules
require_once __DIR__ . '/../modules/admin/stream.php';