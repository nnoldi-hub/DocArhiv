<?php
/**
 * Test Debug pentru Admin Edit Document
 */

echo "DEBUG: Start test<br>";

// Test include config
try {
    require_once '../config/config.php';
    echo "DEBUG: Config loaded successfully<br>";
} catch (Exception $e) {
    echo "ERROR loading config: " . $e->getMessage() . "<br>";
    die();
}

// Test sesiune
echo "DEBUG: Session status: " . session_status() . "<br>";
echo "DEBUG: Session ID: " . session_id() . "<br>";

// Test functii de baza
if (function_exists('isLoggedIn')) {
    echo "DEBUG: isLoggedIn function exists<br>";
    if (isLoggedIn()) {
        echo "DEBUG: User is logged in<br>";
        echo "DEBUG: User ID: " . ($_SESSION['user_id'] ?? 'N/A') . "<br>";
        echo "DEBUG: Company ID: " . ($_SESSION['company_id'] ?? 'N/A') . "<br>";
        echo "DEBUG: Role: " . ($_SESSION['role'] ?? 'N/A') . "<br>";
    } else {
        echo "DEBUG: User is NOT logged in<br>";
    }
} else {
    echo "ERROR: isLoggedIn function does not exist<br>";
}

// Test functii CSRF
if (function_exists('csrfField')) {
    echo "DEBUG: csrfField function exists<br>";
} else {
    echo "ERROR: csrfField function does not exist<br>";
}

if (function_exists('verify_csrf')) {
    echo "DEBUG: verify_csrf function exists<br>";
} else {
    echo "ERROR: verify_csrf function does not exist<br>";
}

// Test clasa Database
try {
    $db = new Database();
    echo "DEBUG: Database class instantiated successfully<br>";
} catch (Exception $e) {
    echo "ERROR creating Database: " . $e->getMessage() . "<br>";
}

// Test conexiune DB
try {
    $pdo = getDBConnection();
    echo "DEBUG: Database connection successful<br>";
} catch (Exception $e) {
    echo "ERROR connecting to database: " . $e->getMessage() . "<br>";
}

echo "DEBUG: Test completed<br>";
?>