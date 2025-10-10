<?php
/**
 * Ultra Simple Test Handler - UPDATED
 * public/test-form.php
 */

// Force display
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>";
echo "<html><head><title>TEST RESULTS</title>";
echo "<style>body{font-family:Arial;margin:20px;} .box{border:1px solid #ccc;padding:10px;margin:10px 0;background:#f9f9f9;}</style>";
echo "</head><body>";

echo "<h1 style='color:green'>✅ TEST HANDLER ACCESSED!</h1>";
echo "<div class='box'>";
echo "<strong>Time:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>Method:</strong> " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "<br>";
echo "<strong>URL:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "<br>";
echo "</div>";

echo "<h2>POST Data Analysis:</h2>";
echo "<div class='box'>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<strong style='color:green'>✅ POST Request Received</strong><br>";
    echo "<strong>POST Count:</strong> " . count($_POST) . "<br><br>";
    
    if (!empty($_POST)) {
        echo "<strong style='color:green'>✅ POST Data Found:</strong><br>";
        echo "<table border='1' style='border-collapse:collapse;width:100%'>";
        echo "<tr style='background:#eee'><th>Field</th><th>Value</th></tr>";
        
        foreach ($_POST as $key => $value) {
            $displayValue = is_string($value) ? htmlspecialchars(substr($value, 0, 200)) : print_r($value, true);
            echo "<tr><td><strong>$key</strong></td><td>$displayValue</td></tr>";
        }
        echo "</table>";
        
        // Check specific fields
        echo "<br><h3>Key Fields Check:</h3>";
        echo "<ul>";
        echo "<li><strong>document_id:</strong> " . (isset($_POST['document_id']) ? $_POST['document_id'] : '❌ Missing') . "</li>";
        echo "<li><strong>title:</strong> " . (isset($_POST['title']) ? $_POST['title'] : '❌ Missing') . "</li>";
        echo "<li><strong>department_id:</strong> " . (isset($_POST['department_id']) ? $_POST['department_id'] : '❌ Missing') . "</li>";
        echo "<li><strong>CSRF token:</strong> " . (isset($_POST['_token']) ? 'Present' : '❌ Missing') . "</li>";
        echo "</ul>";
        
    } else {
        echo "<strong style='color:red'>❌ No POST Data Found</strong>";
    }
} else {
    echo "<strong style='color:red'>❌ Not a POST Request</strong>";
}

echo "</div>";

echo "<div class='box'>";
echo "<a href='javascript:history.back()' style='color:blue;text-decoration:underline'>← Go Back to Edit Page</a>";
echo "</div>";

echo "</body></html>";

// Also log to file
$log_file = __DIR__ . '/../storage/logs/test_access_' . date('Y-m-d_H-i-s') . '.txt';
$log_dir = dirname($log_file);
if (!file_exists($log_dir)) {
    @mkdir($log_dir, 0755, true);
}

$log_content = "TEST ACCESS: " . date('Y-m-d H:i:s') . "\n";
$log_content .= "Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n";
$log_content .= "POST Count: " . count($_POST) . "\n";
if (!empty($_POST)) {
    $log_content .= "POST Data: " . print_r($_POST, true) . "\n";
}
$log_content .= "========================\n";

@file_put_contents($log_file, $log_content, FILE_APPEND);
?>