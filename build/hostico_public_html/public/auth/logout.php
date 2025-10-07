<?php
/**
 * Pagina de logout
 * public/auth/logout.php
 */

// Include configurarea și inițializarea (config.php include deja restul utilitarelor & autoload)
require_once dirname(__DIR__, 2) . '/config/config.php';

// Asigură includerea claselor esențiale folosind căile corecte
require_once BASE_PATH . '/includes/classes/Database.php';
require_once BASE_PATH . '/includes/classes/Auth.php';
require_once BASE_PATH . '/includes/functions/helpers.php';
require_once BASE_PATH . '/includes/functions/security.php';

// Pentru logout aplicăm un subset minimal (evităm rularea detectXSS pe cookie sesiune care provoacă avertismente aici)
setSecurityHeaders();

// Înregistrează activitatea de logout dacă utilizatorul este logat
if (isset($_SESSION['user_id'])) {
    try {
        $db = new Database();
        $db->insert('activity_logs', [
            'company_id' => $_SESSION['company_id'] ?? null,
            'user_id' => $_SESSION['user_id'],
            'action_type' => 'logout',
            'description' => 'Utilizatorul s-a delogat',
            'ip_address' => getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        logSecurityEvent('user_logout', [
            'user_id' => $_SESSION['user_id'],
            'company_id' => $_SESSION['company_id'] ?? null
        ], 'low');
        
    } catch (Exception $e) {
        logError("Failed to log logout activity: " . $e->getMessage());
    }
}

// Șterge cookie-ul remember me dacă există
if (isset($_COOKIE['remember_token'])) {
    try {
        $db = new Database();
        $db->delete('remember_tokens', 'token = :token', [':token' => $_COOKIE['remember_token']]);
    } catch (Exception $e) {
        logError("Failed to clear remember token: " . $e->getMessage());
    }
    
    setcookie('remember_token', '', time() - 3600, '/', '', isHTTPS(), true);
}

// Folosește metoda din Auth dacă există
$auth = new Auth();
$auth->logout();

// Start sesiune nouă doar pentru mesaj (evităm reutilizarea vechiului ID deoarece logout() a distrus-o deja)
session_start();
$_SESSION['logout_message'] = 'V-ați delogat cu succes.';

redirect('/login.php');
?>