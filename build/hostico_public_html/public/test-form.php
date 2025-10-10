<?php
/**
 * Ultra Simple Test Handler
 * public/test-form.php
 */

// Log any access
$log_file = __DIR__ . '/../storage/logs/test_access_' . date('Y-m-d_H-i-s') . '.txt';
$log_dir = dirname($log_file);
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

file_put_contents($log_file, "ACCESSED: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

echo "<h1>TEST HANDLER ACCESSED!</h1>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "</p>";

if (!empty($_POST)) {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
} else {
    echo "<p>No POST data received</p>";
}

echo "<a href='javascript:history.back()'>Go Back</a>";
?>