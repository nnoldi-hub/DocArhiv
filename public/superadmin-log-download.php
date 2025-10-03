<?php
// public/superadmin-log-download.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';
require_once __DIR__ . '/../includes/functions/helpers.php';

if (!isLoggedIn() || !hasRole(ROLE_SUPERADMIN)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$kind = $_GET['kind'] ?? '';
$date = $_GET['date'] ?? '';
$base = realpath(LOGS_PATH);
$path = '';

switch ($kind) {
    case 'php_errors':
        $path = $base . DIRECTORY_SEPARATOR . 'php_errors.log';
        break;
    case 'app_error':
        $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
        $path = $base . DIRECTORY_SEPARATOR . 'error_' . $d . '.log';
        break;
    case 'security':
        $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
        $path = $base . DIRECTORY_SEPARATOR . 'security_' . $d . '.log';
        break;
    default:
        http_response_code(400);
        echo 'Unknown kind';
        exit;
}

if (!$path || !file_exists($path)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

// Ensure path stays within logs directory
$real = realpath($path);
if (strpos($real, $base) !== 0) {
    http_response_code(400);
    echo 'Invalid path';
    exit;
}

$filename = basename($real);
header('Content-Description: File Transfer');
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($real));
header('Cache-Control: no-store');

readfile($real);
exit;
