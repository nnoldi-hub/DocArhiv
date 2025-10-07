<?php
/**
 * Asset proxy pentru servire CSS/JS când hosting-ul blochează accesul direct
 * Folosește acest script doar ca fallback dacă .htaccess nu rezolvă 403
 */

// Validare tip asset permis
$allowedTypes = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'eot' => 'application/vnd.ms-fontobject',
    'svg' => 'image/svg+xml'
];

// Preia calea din query string
$file = $_GET['file'] ?? '';
$file = str_replace(['..', '\\'], ['', '/'], $file); // Securitate basic

// Determină tipul fișierului
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!isset($allowedTypes[$ext])) {
    http_response_code(400);
    exit('Invalid file type');
}

// Construiește calea completă
$filePath = __DIR__ . '/' . ltrim($file, '/');

// Verifică existența
if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    exit('File not found');
}

// Setează headere corecte
header('Content-Type: ' . $allowedTypes[$ext]);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=31536000'); // Cache 1 an

// Servește fișierul
readfile($filePath);
exit;
?>