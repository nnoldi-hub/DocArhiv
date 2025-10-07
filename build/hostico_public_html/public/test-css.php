<?php
// Test simplu pentru a verifica dacă PHP poate servi CSS
header('Content-Type: text/css');
$cssFile = __DIR__ . '/assets/css/bootstrap.min.css';
if (file_exists($cssFile)) {
    readfile($cssFile);
} else {
    echo "/* File not found: $cssFile */";
}
exit;
?>