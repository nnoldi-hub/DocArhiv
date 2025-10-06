<?php
/**
 * Hostico Configuration Override
 * Rename this file to config.local.php after upload to activate
 */

// Database Settings pentru Hostico
define('DB_HOST', 'localhost');
define('DB_NAME', 'rbcjgzba_DocArhiv');
define('DB_USER', 'rbcjgzba_nnoldi');
define('DB_PASS', 'PetreIonel205!');

// URL Settings - înlocuiește cu domeniul tău
define('APP_URL', 'https://domeniul-tau.ro/document-archive');
// Sau dacă folosești subdomeniu:
// define('APP_URL', 'https://arhiva.domeniul-tau.ro');

// Storage Path - înlocuiește cu calea absolută de pe Hostico
// Găsești calea în cPanel → File Manager (arată sus calea completă)
define('STORAGE_PATH', '/home/rbcjgzba/public_html/document-archive/storage');

// Production Settings
define('DEBUG_MODE', false);
define('SHOW_ERRORS', false);

// Optional: Email Settings pentru notificări
// define('SMTP_HOST', 'mail.domeniul-tau.ro');
// define('SMTP_PORT', 587);
// define('SMTP_USER', 'noreply@domeniul-tau.ro');
// define('SMTP_PASS', 'parola-email');