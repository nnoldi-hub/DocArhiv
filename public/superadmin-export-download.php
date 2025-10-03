<?php
/**
 * SuperAdmin secure download for archival exports (storage/exports)
 */
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    http_response_code(403);
    exit('Forbidden');
}

$name = $_GET['name'] ?? '';
if (!$name || !preg_match('/^[a-zA-Z0-9_.-]+$/', $name)) {
    http_response_code(400);
    exit('Parametru invalid');
}

$base = STORAGE_PATH . DIRECTORY_SEPARATOR . 'exports';
$path = $base . DIRECTORY_SEPARATOR . $name;
if (!file_exists($path) || !is_file($path)) {
    http_response_code(404);
    exit('Fișierul nu există');
}

$mime = 'application/octet-stream';
if (str_ends_with($name, '.zip')) $mime = 'application/zip';
if (str_ends_with($name, '.xml')) $mime = 'application/xml';
if (str_ends_with($name, '.txt')) $mime = 'text/plain; charset=UTF-8';

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: must-revalidate');
header('Pragma: public');
readfile($path);
exit;
