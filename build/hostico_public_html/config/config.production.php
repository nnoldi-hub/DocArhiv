<?php
/**
 * Production Configuration for Hostico
 * This file will be automatically copied to config.local.php during deployment
 */

// Database Settings pentru Hostico
define('DB_HOST', 'localhost');
define('DB_NAME', 'rbcjgzba_DocArhiv');
define('DB_USER', 'rbcjgzba_nnoldi');
define('DB_PASS', 'PetreIonel205!');

// URL Settings - Hostico domain
define('APP_URL', 'https://gusturidelatara.ro');

// Storage Path - absolute path pe Hostico
define('STORAGE_PATH', '/home/rbcjgzba/public_html/document-archive/storage');

// Production Settings
define('DEBUG_MODE', false);
define('SHOW_ERRORS', false);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Security Settings
define('SECURE_HEADERS', true);
define('SESSION_SECURE', true);
define('SESSION_HTTPONLY', true);

// File Upload Limits pentru shared hosting
define('MAX_UPLOAD_SIZE', '10M');
define('MAX_FILES_PER_UPLOAD', 5);

// Cache Settings
define('ENABLE_CACHE', true);
define('CACHE_LIFETIME', 3600);

// Logging pentru production
define('LOG_LEVEL', 'ERROR');
define('LOG_FILE', STORAGE_PATH . '/logs/production.log');

// Optional: Email notification settings
// Pentru notificări importante în production
/*
define('ADMIN_EMAIL', 'admin@nnoldi.online');
define('SMTP_HOST', 'mail.nnoldi.online');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@nnoldi.online');
define('SMTP_PASS', 'your-email-password');
*/