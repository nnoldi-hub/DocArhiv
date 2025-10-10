<?php
/**
 * Test Environment Detection
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Environment Detection Test<br><br>";

echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "<br>";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "<br>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "<br>";

// Check if we're on production or development
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'gusturidelatara.ro') !== false) {
    echo "<br>DETECTED: Production environment (Hostico)<br>";
    echo "DB_HOST should be: localhost<br>";
    echo "DB_NAME should be: rbcjgzba_DocArhiv<br>";
    echo "DB_USER should be: rbcjgzba_nnoldi<br>";
} else {
    echo "<br>DETECTED: Development environment (Local)<br>";
    echo "DB_HOST should be: localhost<br>";
    echo "DB_NAME should be: arhiva_documente<br>";
    echo "DB_USER should be: root<br>";
}

echo "<br>Testing config loading...<br>";

try {
    require_once '../config/config.php';
    echo "Config loaded successfully<br>";
    
    echo "<br>Current configuration:<br>";
    echo "DB_HOST: " . DB_HOST . "<br>";
    echo "DB_NAME: " . DB_NAME . "<br>";
    echo "DB_USER: " . DB_USER . "<br>";
    echo "APP_URL: " . APP_URL . "<br>";
    echo "DEBUG_MODE: " . (DEBUG_MODE ? 'true' : 'false') . "<br>";
    
} catch (Exception $e) {
    echo "ERROR loading config: " . $e->getMessage() . "<br>";
}
?>