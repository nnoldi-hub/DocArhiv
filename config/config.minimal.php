<?php
// Configurație minimală pentru test
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Detectare environment
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'gusturidelatara.ro') !== false) {
    // Production - Hostico
    define('APP_URL', 'https://gusturidelatara.ro');
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'rbcjgzba_DocArhiv');
    define('DB_USER', 'rbcjgzba_nnoldi');
    define('DB_PASS', 'PetreIonel205!');
} else {
    // Development
    define('APP_URL', 'http://localhost/document-archive/public');
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'arhiva_documente');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

define('DB_CHARSET', 'utf8mb4');
define('BASE_PATH', dirname(__DIR__));

echo "Config loaded successfully!";
?>