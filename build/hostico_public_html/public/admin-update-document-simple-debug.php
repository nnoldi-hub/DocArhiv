<?php
/**
 * Simple Debug Handler - TEST VERSION
 * public/admin-update-document-simple-debug.php
 */

// Force immediate error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Immediate log write
$debug_file = __DIR__ . '/../storage/logs/simple_debug_' . date('Y-m-d_H-i-s') . '.log';

// Ensure directory exists
$log_dir = dirname($debug_file);
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Log immediately that we were accessed
$log_content = "=== SIMPLE DEBUG ACCESSED ===\n";
$log_content .= "Time: " . date('Y-m-d H:i:s') . "\n";
$log_content .= "Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n";
$log_content .= "POST data exists: " . (empty($_POST) ? 'NO' : 'YES') . "\n";
$log_content .= "POST count: " . count($_POST) . "\n";

if (!empty($_POST)) {
    $log_content .= "POST keys: " . implode(', ', array_keys($_POST)) . "\n";
    foreach ($_POST as $key => $value) {
        if (is_string($value)) {
            $log_content .= "$key: " . substr($value, 0, 100) . "\n";
        }
    }
}

$log_content .= "=== END LOG ===\n\n";

file_put_contents($debug_file, $log_content, FILE_APPEND | LOCK_EX);

// Redirect back with message
echo "<h1>Debug Log Created</h1>";
echo "<p>Log file: " . basename($debug_file) . "</p>";
echo "<p>POST data logged: " . (empty($_POST) ? 'No' : 'Yes') . "</p>";
echo "<a href='../admin-documents.php'>Back to Documents</a>";
?>