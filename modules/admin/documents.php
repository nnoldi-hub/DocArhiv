<?php
/**
 * DEPRECATED: Use public/admin-documents.php instead
 * This file provides backward compatibility
 */
require_once __DIR__ . '/../../config/config.php';

// Păstrează parametrii de query dacă există
$queryString = '';
if (!empty($_SERVER['QUERY_STRING'])) {
    $queryString = '?' . $_SERVER['QUERY_STRING'];
}

header('Location: ' . APP_URL . '/admin-documents.php' . $queryString, true, 301);
exit;
