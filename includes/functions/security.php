<?php
/**
 * Funcții de Securitate
 * includes/functions/security.php
 */

/**
 * Verifică și sanitizează input-ul pentru XSS
 */
function sanitizeXSS($input) {
    if (is_array($input)) {
        return array_map('sanitizeXSS', $input);
    }
    
    // Curăță HTML malițios
    $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Înlătură script tags și event handlers
    $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $input);
    $input = preg_replace('/on\w+\s*=\s*["\']?[^"\'>\s]*["\']?/i', '', $input);
    $input = preg_replace('/javascript\s*:/i', '', $input);
    
    return trim($input);
}

/**
 * Verifică token CSRF
 */
function validateCSRF($token = null) {
    if ($token === null) {
        $token = $_POST[CSRF_TOKEN_NAME] ?? $_GET[CSRF_TOKEN_NAME] ?? '';
    }
    
    if (empty($token) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generează și returnează câmp hidden pentru CSRF
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verifică dacă request-ul este HTTPS
 */
function isHTTPS() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
           (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
           (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
}

/**
 * Forțează HTTPS în producție
 */
function enforceHTTPS() {
    if (!DEBUG_MODE && !isHTTPS()) {
        $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: $redirectUrl", true, 301);
        exit();
    }
}

/**
 * Setează headers de securitate
 */
function setSecurityHeaders() {
    // Content Security Policy
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
           "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
           "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " .
           "img-src 'self' data: https:; " .
           "connect-src 'self'; " .
           "frame-ancestors 'none'";
    
    header("Content-Security-Policy: $csp");
    
    // Alte headers de securitate
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // HSTS pentru HTTPS
    if (isHTTPS()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    
    // Previne caching pentru pagini sensibile
    if (isLoggedIn()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }
}

/**
 * Verifică dacă fișierul încărcat este sigur
 */
function validateUploadedFile($file) {
    $errors = [];
    
    // Verifică dacă fișierul a fost încărcat prin HTTP POST
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'Fișierul nu a fost încărcat corect';
        return $errors;
    }
    
    // Verifică dimensiunea
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'Fișierul este prea mare. Dimensiunea maximă: ' . formatFileSize(MAX_FILE_SIZE);
    }
    
    // Verifică extensia
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        $errors[] = 'Extensia fișierului nu este permisă. Extensii permise: ' . implode(', ', ALLOWED_EXTENSIONS);
    }
    
    // Verifică MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        $errors[] = 'Tipul fișierului nu este permis';
    }
    
    // Verifică pentru fișiere PHP ascunse
    if (preg_match('/\.php/i', $file['name']) || strpos($mimeType, 'php') !== false) {
        $errors[] = 'Fișierele PHP nu sunt permise';
    }
    
    // Verifică pentru null bytes
    if (strpos($file['name'], "\0") !== false) {
        $errors[] = 'Numele fișierului conține caractere invalide';
    }
    
    // Scanează pentru malware simplu (doar extensii dubioase)
    $suspiciousExtensions = ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js'];
    if (in_array($extension, $suspiciousExtensions)) {
        $errors[] = 'Tipul fișierului nu este permis din motive de securitate';
    }
    
    return $errors;
}

/**
 * Curăță numele fișierului pentru stocare
 */
function sanitizeFilename($filename) {
    // Înlătură extensii multiple
    $filename = preg_replace('/\.+/', '.', $filename);
    
    // Înlătură caractere speciale
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Înlătură puncte de la început
    $filename = ltrim($filename, '.');
    
    // Limitează lungimea
    if (strlen($filename) > 200) {
        $info = pathinfo($filename);
        $name = substr($info['filename'], 0, 195);
        $filename = $name . '.' . $info['extension'];
    }
    
    return $filename;
}

/**
 * Generează nume unic pentru fișier
 */
function generateUniqueFilename($originalName, $companyId) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $baseName = sanitizeFilename($baseName);
    
    // Generează timestamp și random
    $timestamp = date('YmdHis');
    $random = bin2hex(random_bytes(8));
    
    // Format: companyId_timestamp_random_baseName.ext
    $newName = $companyId . '_' . $timestamp . '_' . $random . '_' . $baseName . '.' . $extension;
    
    return $newName;
}

/**
 * Calculează hash-ul fișierului pentru deduplicare
 */
function calculateFileHash($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    return hash_file('sha256', $filePath);
}

/**
 * Verifică pentru documente duplicate
 */
function checkDuplicateDocument($fileHash, $companyId, $excludeId = null) {
    try {
        $db = new Database();
        
        $sql = "SELECT id, title, file_name FROM documents 
                WHERE company_id = :company_id AND file_hash = :hash AND status = :status";
        $params = [
            ':company_id' => $companyId,
            ':hash' => $fileHash,
            ':status' => DOC_STATUS_ACTIVE
        ];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $query = $db->query($sql);
        foreach ($params as $key => $value) {
            $query->bind($key, $value);
        }
        
        return $query->fetch();
    } catch (Exception $e) {
        logError("Duplicate check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifică permisiunile directorului pentru upload
 */
function validateUploadDirectory($path) {
    // Verifică dacă directorul există
    if (!file_exists($path)) {
        if (!mkdir($path, 0755, true)) {
            return false;
        }
    }
    
    // Verifică permisiunile de scriere
    if (!is_writable($path)) {
        return false;
    }
    
    // Verifică că nu este în afara directorului de upload
    $realPath = realpath($path);
    $uploadPath = realpath(UPLOAD_PATH);
    
    if (strpos($realPath, $uploadPath) !== 0) {
        return false;
    }
    
    return true;
}

/**
 * Rate limiting generic
 */
function isRateLimited($action, $identifier, $maxAttempts = 10, $timeWindow = 3600) {
    try {
        $cacheKey = 'rate_limit_' . $action . '_' . md5($identifier);
        $cacheFile = CACHE_PATH . '/' . $cacheKey . '.json';
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        if (!$data) {
            return false;
        }
        
        // Verifică dacă timpul a expirat
        if (time() > $data['reset_time']) {
            unlink($cacheFile);
            return false;
        }
        
        return $data['attempts'] >= $maxAttempts;
    } catch (Exception $e) {
        logError("Rate limiting check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Înregistrează încercare pentru rate limiting
 */
function recordAttempt($action, $identifier, $timeWindow = 3600) {
    try {
        $cacheKey = 'rate_limit_' . $action . '_' . md5($identifier);
        $cacheFile = CACHE_PATH . '/' . $cacheKey . '.json';
        
        $data = ['attempts' => 0, 'reset_time' => time() + $timeWindow];
        
        if (file_exists($cacheFile)) {
            $existingData = json_decode(file_get_contents($cacheFile), true);
            if ($existingData && time() < $existingData['reset_time']) {
                $data = $existingData;
            }
        }
        
        $data['attempts']++;
        
        if (!file_exists(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        
        file_put_contents($cacheFile, json_encode($data));
    } catch (Exception $e) {
        logError("Failed to record attempt: " . $e->getMessage());
    }
}

/**
 * Verifică user agent pentru boți suspicioși
 */
function isValidUserAgent($userAgent = null) {
    if ($userAgent === null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    if (empty($userAgent)) {
        return false;
    }
    
    // Lista de user agents suspicioși
    $suspiciousAgents = [
        'sqlmap', 'nikto', 'nmap', 'masscan', 'zmap',
        'wget', 'curl', 'python-requests', 'bot', 'crawler',
        'spider', 'scraper', 'harvester'
    ];
    
    $userAgentLower = strtolower($userAgent);
    
    foreach ($suspiciousAgents as $suspicious) {
        if (strpos($userAgentLower, $suspicious) !== false) {
            return false;
        }
    }
    
    return true;
}

/**
 * Verifică IP-ul pentru liste negre
 */
function isBlockedIP($ip = null) {
    if ($ip === null) {
        $ip = getClientIP();
    }
    
    // Lista de IP-uri blocate (în producție ar trebui să fie dintr-o bază de date)
    $blockedIPs = [
        '127.0.0.1', // Exemplu - șterge în producție
    ];
    
    if (in_array($ip, $blockedIPs)) {
        return true;
    }
    
    // Verifică pentru IP-uri private în producție
    if (!DEBUG_MODE) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Log eveniment de securitate
 */
function logSecurityEvent($event, $details = [], $severity = 'medium') {
    try {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'severity' => $severity,
            'ip' => getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'user_id' => $_SESSION['user_id'] ?? null,
            'company_id' => $_SESSION['company_id'] ?? null,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'details' => $details
        ];
        
        $logFile = LOGS_PATH . '/security_' . date('Y-m-d') . '.log';
        $logEntry = json_encode($logData) . PHP_EOL;
        
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Pentru evenimente critice, log și în baza de date
        if ($severity === 'high' || $severity === 'critical') {
            $db = new Database();
            $db->insert('activity_logs', [
                'company_id' => $_SESSION['company_id'] ?? null,
                'user_id' => $_SESSION['user_id'] ?? null,
                'action_type' => 'security_event',
                'description' => $event,
                'ip_address' => getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'metadata' => json_encode($details)
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Failed to log security event: " . $e->getMessage());
    }
}

/**
 * Verifică pentru atacuri SQL injection în parametri
 */
function detectSQLInjection($input) {
    if (is_array($input)) {
        foreach ($input as $value) {
            if (detectSQLInjection($value)) {
                return true;
            }
        }
        return false;
    }
    
    $sqlPatterns = [
        '/(\bunion\b.*\bselect\b)/i',
        '/(\bselect\b.*\bfrom\b)/i',
        '/(\binsert\b.*\binto\b)/i',
        '/(\bdelete\b.*\bfrom\b)/i',
        '/(\bupdate\b.*\bset\b)/i',
        '/(\bdrop\b.*\btable\b)/i',
        '/(\bcreate\b.*\btable\b)/i',
        '/(\balter\b.*\btable\b)/i',
        '/(\btruncate\b.*\btable\b)/i',
        '/(\bexec\b.*\()/i',
        '/(\bexecute\b.*\()/i',
        '/(;.*--)/',
        '/(\/\*.*\*\/)/s',
        '/(\bxp_)/i',
        '/(\bsp_)/i'
    ];
    
    foreach ($sqlPatterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Verifică pentru atacuri XSS în input
 */
function detectXSS($input) {
    if (is_array($input)) {
        foreach ($input as $value) {
            if (detectXSS($value)) {
                return true;
            }
        }
        return false;
    }
    
    // Eliminăm modificatorul 'g' (nu există în PCRE pentru PHP) și simplificăm regex-ul script pentru performanță.
    $xssPatterns = [
        // Script tags
        '/<script\b[^>]*>(.*?)<\/script>/is',
        // URI schemes potențial periculoase
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/data\s*:/i',
        // Atribute de eveniment (onclick, onerror etc.)
        '/on[a-zA-Z]+\s*=/',
        // Elemente potențial abuzabile
        '/<iframe/i',
        '/<object/i',
        '/<embed/i',
        '/<link/i',
        '/<meta/i',
        // CSS expression (legacy IE)
        '/expression\s*\(/i'
    ];
    
    foreach ($xssPatterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Middleware de securitate pentru toate request-urile
 */
function securityMiddleware() {
    // Setează headers de securitate
    setSecurityHeaders();
    
    // Verifică IP blocat
    if (isBlockedIP()) {
        logSecurityEvent('blocked_ip_access', ['ip' => getClientIP()], 'high');
        http_response_code(403);
        die('Access denied');
    }
    
    // Verifică user agent
    if (!isValidUserAgent()) {
        logSecurityEvent('suspicious_user_agent', ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''], 'medium');
    }
    
    // Verifică pentru SQL injection în toate input-urile
    $allInputs = array_merge($_GET, $_POST, $_COOKIE);
    if (detectSQLInjection($allInputs)) {
        logSecurityEvent('sql_injection_attempt', ['inputs' => $allInputs], 'critical');
        http_response_code(400);
        die('Bad request');
    }
    
    // Verifică pentru XSS în toate input-urile
    if (detectXSS($allInputs)) {
        logSecurityEvent('xss_attempt', ['inputs' => $allInputs], 'high');
    }
    
    // Verifică CSRF pentru POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRF()) {
        logSecurityEvent('csrf_token_invalid', [], 'high');
        http_response_code(403);
        die('CSRF token invalid');
    }
    
    // Rate limiting pentru anumite acțiuni
    $action = $_GET['action'] ?? $_POST['action'] ?? 'general';
    $identifier = getClientIP() . '_' . ($_SESSION['user_id'] ?? 'anonymous');
    
    if (isRateLimited($action, $identifier)) {
        logSecurityEvent('rate_limit_exceeded', ['action' => $action], 'medium');
        http_response_code(429);
        die('Too many requests');
    }
    
    recordAttempt($action, $identifier);
}

/**
 * Generează nonce pentru inline scripts
 */
function generateNonce() {
    if (!isset($_SESSION['csp_nonce'])) {
        $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
    }
    return $_SESSION['csp_nonce'];
}

/**
 * Verifică integritatea fișierului
 */
function verifyFileIntegrity($filePath, $expectedHash) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $actualHash = hash_file('sha256', $filePath);
    return hash_equals($expectedHash, $actualHash);
}

/**
 * Curăță cache-ul de securitate
 */
function cleanupSecurityCache() {
    try {
        $cacheFiles = glob(CACHE_PATH . '/rate_limit_*.json');
        $now = time();
        
        foreach ($cacheFiles as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $now > $data['reset_time']) {
                unlink($file);
            }
        }
    } catch (Exception $e) {
        logError("Failed to cleanup security cache: " . $e->getMessage());
    }
}

/**
 * Obține lista cu țări blocate (exemplu)
 */
function getBlockedCountries() {
    // În producție, aceasta ar veni dintr-o bază de date sau API
    return [
        // 'CN', 'RU', 'KP' // Exemple - configurează după necesități
    ];
}

/**
 * Verifică geolocation pentru IP
 */
function checkGeolocation($ip = null) {
    if ($ip === null) {
        $ip = getClientIP();
    }
    
    // Pentru implementare completă, integrează cu servicii GeoIP
    // Momentan returnează true (permis)
    return true;
}

/**
 * Generează raport de securitate
 */
function generateSecurityReport($days = 7) {
    try {
        $report = [];
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        // Citește log-urile de securitate
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime($startDate . " +{$i} days"));
            $logFile = LOGS_PATH . '/security_' . $date . '.log';
            
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $event = json_decode($line, true);
                    if ($event) {
                        $report[] = $event;
                    }
                }
            }
        }
        
        // Grupează pe tip de eveniment
        $summary = [];
        foreach ($report as $event) {
            $type = $event['event'];
            if (!isset($summary[$type])) {
                $summary[$type] = 0;
            }
            $summary[$type]++;
        }
        
        return [
            'period' => $days . ' days',
            'total_events' => count($report),
            'events_by_type' => $summary,
            'detailed_events' => $report
        ];
        
    } catch (Exception $e) {
        logError("Failed to generate security report: " . $e->getMessage());
        return [];
    }
}