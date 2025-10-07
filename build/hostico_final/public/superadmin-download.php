<?php
/**
 * SuperAdmin secure download for backups
 * public/superadmin-download.php
 */
require_once '../config/config.php';

if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    http_response_code(403);
    exit('Forbidden');
}

$name = $_GET['name'] ?? '';
if (!$name || !preg_match('/^[a-zA-Z0-9_.-]+$/', $name)) {
    http_response_code(400);
    exit('Parametru invalid');
}

$path = BACKUP_PATH . DIRECTORY_SEPARATOR . $name;
if (!file_exists($path) || !is_file($path)) {
    http_response_code(404);
    exit('Fișierul nu există');
}

$mime = 'application/octet-stream';
if (str_ends_with($name, '.zip')) $mime = 'application/zip';
if (str_ends_with($name, '.sql')) $mime = 'application/sql';

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: must-revalidate');
header('Pragma: public');
readfile($path);
exit;
