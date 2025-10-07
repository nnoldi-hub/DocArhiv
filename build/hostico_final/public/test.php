<?php
// Test PHP de bază - fără include-uri
echo "PHP Test: " . date('Y-m-d H:i:s');
echo "<br>Server: " . $_SERVER['HTTP_HOST'] ?? 'unknown';
echo "<br>PHP Version: " . phpversion();

// Test array simple
$test = ['status' => 'OK', 'message' => 'PHP works'];
echo "<br>JSON Test: " . json_encode($test);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Basic</title>
</head>
<body>
    <h1>Test Page</h1>
    <p>Dacă vezi asta, PHP funcționează!</p>
</body>
</html>