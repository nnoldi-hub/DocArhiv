<?php
// Previne includerea multiplă
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

/**
 * Configurația Principală a Sistemului
 * config/config.php
 * 
 * Acest fișier conține toate configurațiile pentru:
 * - Baza de date
 * - Căi și URL-# IMPORTANT: Setează FALSE în producție!
define('DEBUG_MODE', true);
define('SHOW_ERRORS', true);
define('QUERY_DEBUG', false);
define('PROFILER_ENABLED', false);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}ări securitate
 * - Setări upload
 * - Email și SMTP
 * - Debugging
 * - Constante sistem
 */

// Setări PHP de bază - vor fi suprascrise mai jos în funcție de DEBUG_MODE
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// Setări sesiune (trebuie setate ÎNAINTE de session_start)
ini_set('session.name', 'ARHIVA_SESSION');
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 7200);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);  // 1 în producție cu HTTPS
ini_set('session.cookie_samesite', 'Lax');

// Start session pentru aplicație
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================================================
// CONFIGURAȚII BAZĂ DE DATE
// =============================================================================

// Detectare automată production vs development
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'gusturidelatara.ro') !== false) {
    // Production - Hostico
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'rbcjgzba_DocArhiv');
    define('DB_USER', 'rbcjgzba_nnoldi');
    define('DB_PASS', 'PetreIonel205!');
} else {
    // Development - Local
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'arhiva_documente');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Connection options pentru PDO
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
]);

// =============================================================================
// CONFIGURAȚII URL și CĂZI
// =============================================================================

// URL de bază al aplicației (fără slash la final)
// Se va detecta automat dacă rulează pe production
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'gusturidelatara.ro') !== false) {
    define('APP_URL', 'https://gusturidelatara.ro');
} else {
    define('APP_URL', 'http://localhost/document-archive/public');
}
define('BASE_PATH', dirname(__DIR__));

// Căi importante
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('UPLOAD_PATH', STORAGE_PATH . '/documents');
define('TEMP_PATH', STORAGE_PATH . '/temp');
define('BACKUP_PATH', STORAGE_PATH . '/backups');
define('LOGS_PATH', STORAGE_PATH . '/logs');
define('CACHE_PATH', STORAGE_PATH . '/cache');

// URL-uri publice
define('ASSETS_URL', APP_URL . '/assets');
define('IMAGES_URL', ASSETS_URL . '/images');

// =============================================================================
// SETĂRI APLICAȚIE
// =============================================================================

define('APP_NAME', 'Arhiva Documente');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Sistem Electronic de Arhivare Documente');
define('APP_KEYWORDS', 'arhiva, documente, management, electronic');

// Timezone
define('APP_TIMEZONE', 'Europe/Bucharest');
date_default_timezone_set(APP_TIMEZONE);

// Limba aplicației
define('APP_LOCALE', 'ro_RO');
define('APP_LANGUAGE', 'ro');

// =============================================================================
// SETĂRI SECURITATE
// =============================================================================

// Chei securitate (SCHIMBĂ în producție!)
define('SECRET_KEY', 'your-secret-key-here-change-in-production');
define('CSRF_TOKEN_NAME', '_token');

// Setări sesiune
define('SESSION_LIFETIME', 7200); // 2 ore în secunde
define('SESSION_NAME', 'ARHIVA_SESSION');
define('SESSION_SECURE', false); // TRUE în producție cu HTTPS
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');

// Setări parole
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SYMBOLS', false);

// Rate limiting
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 300); // 5 minute în secunde

// =============================================================================
// SETĂRI UPLOAD și FIȘIERE
// =============================================================================

// Dimensiuni maxime
define('MAX_FILE_SIZE', 52428800); // 50MB în bytes
define('MAX_FILES_PER_UPLOAD', 5);
define('MAX_STORAGE_PER_COMPANY_GB', 10); // GB per companie (default)

// Extensii permise
define('ALLOWED_EXTENSIONS', [
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'rtf', 'odt', 'ods', 'odp',
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg',
    'zip', 'rar', '7z', 'tar', 'gz'
]);

// MIME types permise
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain',
    'text/rtf',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/svg+xml',
    'application/zip',
    'application/x-rar-compressed'
]);

// =============================================================================
// ROLURI și PERMISIUNI
// =============================================================================

// Status utilizatori
define('USER_STATUS_ACTIVE', 'active');
define('USER_STATUS_INACTIVE', 'inactive');
define('USER_STATUS_PENDING', 'pending');

// Status documente
define('DOC_STATUS_ACTIVE', 'active');
define('DOC_STATUS_ARCHIVED', 'archived');
define('DOC_STATUS_DELETED', 'deleted');

// Roluri utilizatori
define('ROLE_SUPERADMIN', 'superadmin');
define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_USER', 'user');

// Permisiuni
define('PERM_VIEW_DOCUMENTS', 'view_documents');
define('PERM_UPLOAD_DOCUMENTS', 'upload_documents');
define('PERM_EDIT_DOCUMENTS', 'edit_documents');
define('PERM_DELETE_DOCUMENTS', 'delete_documents');
define('PERM_MANAGE_USERS', 'manage_users');
define('PERM_MANAGE_DEPARTMENTS', 'manage_departments');
define('PERM_MANAGE_COMPANY', 'manage_company');
define('PERM_VIEW_REPORTS', 'view_reports');
define('PERM_SYSTEM_ADMIN', 'system_admin');

// Permisiuni implicite per rol
define('ROLE_PERMISSIONS', [
    ROLE_SUPERADMIN => [
        PERM_SYSTEM_ADMIN, PERM_MANAGE_COMPANY, PERM_MANAGE_USERS,
        PERM_MANAGE_DEPARTMENTS, PERM_VIEW_DOCUMENTS, PERM_UPLOAD_DOCUMENTS,
        PERM_EDIT_DOCUMENTS, PERM_DELETE_DOCUMENTS, PERM_VIEW_REPORTS
    ],
    ROLE_ADMIN => [
        PERM_MANAGE_COMPANY, PERM_MANAGE_USERS, PERM_MANAGE_DEPARTMENTS,
        PERM_VIEW_DOCUMENTS, PERM_UPLOAD_DOCUMENTS, PERM_EDIT_DOCUMENTS,
        PERM_DELETE_DOCUMENTS, PERM_VIEW_REPORTS
    ],
    ROLE_MANAGER => [
        PERM_VIEW_DOCUMENTS, PERM_UPLOAD_DOCUMENTS, PERM_EDIT_DOCUMENTS,
        PERM_VIEW_REPORTS
    ],
    ROLE_USER => [
        PERM_VIEW_DOCUMENTS, PERM_UPLOAD_DOCUMENTS
    ]
]);

// =============================================================================
// SETĂRI EMAIL și SMTP
// =============================================================================

define('SMTP_ENABLED', false); // Activează când configurezi SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' sau 'ssl'
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM', 'noreply@arhiva.ro');
define('SMTP_FROM_NAME', 'Arhiva Documente');

// Template-uri email
define('EMAIL_TEMPLATES', [
    'welcome' => 'Bun venit la Arhiva Documente!',
    'reset_password' => 'Resetare parolă',
    'document_shared' => 'Document partajat cu tine',
    'new_user' => 'Utilizator nou în companie'
]);

// =============================================================================
// SETĂRI CACHE și PERFORMANCE
// =============================================================================

define('CACHE_ENABLED', true);
define('CACHE_DEFAULT_TTL', 3600); // 1 oră
define('CACHE_DRIVER', 'file'); // 'file', 'redis', 'memcached'

// Cache pentru diferite tipuri de date
define('CACHE_SETTINGS', [
    'user_permissions' => 1800,    // 30 minute
    'company_settings' => 3600,    // 1 oră
    'document_stats' => 900,       // 15 minute
    'search_results' => 300        // 5 minute
]);

// =============================================================================
// SETĂRI LOGGING
// =============================================================================

define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_MAX_FILE_SIZE', 10485760); // 10MB
define('LOG_MAX_FILES', 10);

// Tipuri de log-uri
define('LOG_TYPES', [
    'error' => 'error',
    'activity' => 'activity',
    'security' => 'security',
    'performance' => 'performance',
    'debug' => 'debug'
]);

// =============================================================================
// SETĂRI DEBUGGING
// =============================================================================

// IMPORTANT: Setează FALSE în producție!
define('DEBUG_MODE', false);
define('SHOW_ERRORS', false);
define('QUERY_DEBUG', false);
define('PROFILER_ENABLED', false);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// =============================================================================
// SETĂRI BACKUP
// =============================================================================

define('BACKUP_ENABLED', true);
define('BACKUP_FREQUENCY', 'daily'); // 'daily', 'weekly', 'monthly'
define('BACKUP_RETENTION_DAYS', 30);
define('BACKUP_COMPRESSION', true);
define('BACKUP_TABLES', [
    'companies', 'users', 'departments', 'folders', 'documents',
    'document_tags', 'tags', 'activity_logs', 'notifications'
]);

// =============================================================================
// SETĂRI NOTIFICĂRI
// =============================================================================

define('NOTIFICATIONS_ENABLED', true);
define('NOTIFICATION_CHANNELS', ['database', 'email']);
define('NOTIFICATION_BATCH_SIZE', 100);

// =============================================================================
// SETĂRI SEARCH
// =============================================================================

define('SEARCH_ENABLED', true);
define('SEARCH_MIN_CHARS', 3);
define('SEARCH_MAX_RESULTS', 100);
define('SEARCH_HIGHLIGHT_ENABLED', true);
define('FULLTEXT_SEARCH_ENABLED', true);

// =============================================================================
// SETĂRI PAGINARE
// =============================================================================

define('ITEMS_PER_PAGE', 20);
define('MAX_PAGE_LINKS', 10);

// =============================================================================
// SETĂRI API (pentru versiuni viitoare)
// =============================================================================

define('API_ENABLED', false);
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // requests per minute
define('API_KEY_LENGTH', 32);

// =============================================================================
// FUNCȚII HELPER GLOBALE
// =============================================================================

/**
 * Obține conexiunea la baza de date
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            } else {
                throw new Exception("Database connection failed. Please contact administrator.");
            }
        }
    }
    
    return $pdo;
}

/**
 * Verifică dacă utilizatorul este autentificat
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifică dacă utilizatorul are un anumit rol
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Verifică dacă utilizatorul are o anumită permisiune
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // SuperAdmin are toate permisiunile
    if (hasRole(ROLE_SUPERADMIN)) {
        return true;
    }
    
    $userRole = $_SESSION['role'] ?? ROLE_USER;
    $rolePermissions = ROLE_PERMISSIONS[$userRole] ?? [];
    
    return in_array($permission, $rolePermissions);
}

/**
 * Redirect cu header
 */
function redirect($url, $statusCode = 302) {
    if (!headers_sent()) {
        if (strpos($url, 'http') !== 0) {
            $url = APP_URL . $url;
        }
        header("Location: $url", true, $statusCode);
        exit();
    }
}

/**
 * Log eroare
 */
function logError($message, $context = []) {
    if (!LOG_ENABLED) return;
    
    $logFile = LOGS_PATH . '/error_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logEntry = "[$timestamp] ERROR: $message$contextStr" . PHP_EOL;
    
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Log activitate
 */
function logActivity($action, $description, $entityType = null, $entityId = null) {
    if (!LOG_ENABLED || !isLoggedIn()) return;
    
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("
            INSERT INTO activity_logs 
            (company_id, user_id, action_type, description, entity_type, entity_id, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $_SESSION['company_id'] ?? null,
            $_SESSION['user_id'],
            $action,
            $description,
            $entityType,
            $entityId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        logError("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Generează token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifică token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitizează input pentru XSS
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// =============================================================================
// VERIFICĂRI INIȚIALE
// =============================================================================

// Verifică dacă directoarele necesare există
$requiredDirs = [
    STORAGE_PATH,
    UPLOAD_PATH,
    TEMP_PATH,
    BACKUP_PATH,
    LOGS_PATH,
    CACHE_PATH
];

foreach ($requiredDirs as $dir) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            if (DEBUG_MODE) {
                die("Cannot create required directory: $dir");
            }
        }
    }
}

// Verifică permisiuni directoare
$writableDirs = [STORAGE_PATH, UPLOAD_PATH, TEMP_PATH, LOGS_PATH, CACHE_PATH];
foreach ($writableDirs as $dir) {
    if (!is_writable($dir)) {
        if (DEBUG_MODE) {
            die("Directory not writable: $dir. Please check permissions.");
        }
    }
}

// =============================================================================
// AUTO-LOAD CLASSES IMPORTANTE
// =============================================================================

// Include funcțiile helper
require_once __DIR__ . '/../includes/functions/helpers.php';

// Auto-load classes simple
spl_autoload_register(function ($className) {
    $classFile = __DIR__ . '/../includes/classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// =============================================================================
// SETĂRI FINALE
// =============================================================================

// Setări PHP pentru upload
ini_set('upload_max_filesize', (MAX_FILE_SIZE / 1024 / 1024) . 'M');
ini_set('post_max_size', ((MAX_FILE_SIZE * MAX_FILES_PER_UPLOAD) / 1024 / 1024) . 'M');
ini_set('max_execution_time', 300); // 5 minute pentru upload-uri mari

// Setări memorie
ini_set('memory_limit', '256M');

// =============================================================================
// DEBUG INFO (doar în development)
// =============================================================================

if (DEBUG_MODE && isset($_GET['debug']) && $_GET['debug'] === 'config') {
    echo "<h2>Configuration Debug Info</h2>";
    echo "<pre>";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "App Version: " . APP_VERSION . "\n";
    echo "Base Path: " . BASE_PATH . "\n";
    echo "Storage Path: " . STORAGE_PATH . "\n";
    echo "Max File Size: " . formatFileSize(MAX_FILE_SIZE) . "\n";
    echo "Session Lifetime: " . SESSION_LIFETIME . " seconds\n";
    echo "Cache Enabled: " . (CACHE_ENABLED ? 'Yes' : 'No') . "\n";
    echo "Debug Mode: " . (DEBUG_MODE ? 'Yes' : 'No') . "\n";
    echo "</pre>";
    exit;
}

// =============================================================================
// END OF CONFIG
// =============================================================================

// Mesaj de succes pentru includere
if (DEBUG_MODE) {
    // Adaugă în log că configurația a fost încărcată cu succes
    error_log("Arhiva Documente Config loaded successfully - " . date('Y-m-d H:i:s'));
}