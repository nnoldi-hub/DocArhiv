<?php
// Test simplu cu configurația de bază
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug: Starting...<br>";

// Test simple
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'gusturidelatara.ro') !== false) {
    echo "Production mode detected<br>";
    $app_url = 'https://gusturidelatara.ro';
} else {
    echo "Development mode<br>";
    $app_url = 'http://localhost/document-archive/public';
}

echo "App URL: " . $app_url . "<br>";
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Test Simple</title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Test Page</h1>
        <p>Dacă vezi styling Bootstrap, assets-urile locale funcționează!</p>
        <button class="btn btn-primary">Test Button</button>
    </div>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>