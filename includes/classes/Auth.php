<?php
/**
 * Clasa Auth - Gestionarea Autentificării și Autorizării
 * includes/classes/Auth.php
 */

class Auth {
    private $db;
    private $user;
    private $company;
    
    public function __construct() {
        $this->db = new Database();
        $this->user = new User();
        $this->company = new Company();
    }
    
    /**
     * Autentificare utilizator
     */
    public function login($usernameOrEmail, $password, $remember = false) {
        try {
            // Rate limiting - verifică numărul de încercări
            if ($this->isRateLimited($usernameOrEmail)) {
                throw new Exception('Prea multe încercări de autentificare. Încercați din nou în ' . (LOGIN_LOCKOUT_TIME / 60) . ' minute.');
            }
            
            // Verifică SuperAdmin
            $superadmin = $this->checkSuperAdmin($usernameOrEmail, $password);
            if ($superadmin) {
                return $this->createSession($superadmin, 'superadmin', $remember);
            }
            
            // Verifică utilizatori normali
            $user = $this->user->findByCredentials($usernameOrEmail);
            if (!$user) {
                $this->recordFailedLogin($usernameOrEmail);
                throw new Exception('Credențiale incorecte!');
            }
            
            // Verifică parola
            if (!password_verify($password, $user['password'])) {
                $this->recordFailedLogin($usernameOrEmail);
                throw new Exception('Credențiale incorecte!');
            }
            
            // Verifică status utilizator
            if ($user['status'] !== USER_STATUS_ACTIVE) {
                throw new Exception('Contul este dezactivat. Contactați administratorul!');
            }
            
            // Verifică status companie
            if (!in_array($user['subscription_status'], [COMPANY_STATUS_ACTIVE, COMPANY_STATUS_TRIAL])) {
                throw new Exception('Contul companiei este suspendat sau expirat. Contactați administratorul!');
            }
            
            // Curăță încercările eșuate
            $this->clearFailedLogins($usernameOrEmail);
            
            // Actualizează ultima autentificare (înlocuit updateLastLogin absent)
            $this->db->update('users',
                ['last_login' => date('Y-m-d H:i:s')],
                'id = :id',
                [':id' => $user['id']]
            );
            
            // Creează sesiunea
            return $this->createSession($user, 'user', $remember);
            
        } catch (Exception $e) {
            logError("Login failed: " . $e->getMessage(), ['username' => $usernameOrEmail]);
            throw $e;
        }
    }
    
    /**
     * Verifică SuperAdmin
     */
    private function checkSuperAdmin($usernameOrEmail, $password) {
        try {
            $superadmin = $this->db->query("
                SELECT * FROM superadmin_users 
                WHERE (username = :username OR email = :username) 
                AND status = 'active'
            ")
            ->bind(':username', $usernameOrEmail)
            ->fetch();
            
            if ($superadmin && password_verify($password, $superadmin['password'])) {
                // Actualizează ultima autentificare
                $this->db->update('superadmin_users', 
                    ['last_login' => date('Y-m-d H:i:s')],
                    'id = :id',
                    [':id' => $superadmin['id']]
                );
                
                return $superadmin;
            }
            
            return false;
        } catch (Exception $e) {
            logError("SuperAdmin check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Creează sesiunea utilizatorului
     */
    private function createSession($userData, $type, $remember = false) {
        try {
            // Regenerează ID-ul sesiunii pentru securitate
            session_regenerate_id(true);
            
            if ($type === 'superadmin') {
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['username'] = $userData['username'];
                $_SESSION['full_name'] = $userData['full_name'];
                $_SESSION['email'] = $userData['email'];
                $_SESSION['role'] = ROLE_SUPERADMIN;
                $_SESSION['is_superadmin'] = true;
                $_SESSION['permissions'] = ROLE_PERMISSIONS[ROLE_SUPERADMIN];
                
                // Log activitate SuperAdmin
                logActivity('login', 'SuperAdmin autentificat', 'superadmin', $userData['id']);
                
            } else {
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['company_id'] = $userData['company_id'];
                $_SESSION['company_name'] = $userData['company_name'];
                $_SESSION['username'] = $userData['username'];
                $_SESSION['full_name'] = $userData['full_name'];
                $_SESSION['email'] = $userData['email'];
                $_SESSION['role'] = $userData['role'];
                $_SESSION['department_id'] = $userData['department_id'];
                $_SESSION['avatar_path'] = $userData['avatar_path'];
                $_SESSION['permissions'] = $this->user->getPermissions($userData['id']);
                
                // Log activitate în companie
                logActivity('login', 'Utilizator autentificat', 'user', $userData['id']);
            }
            
            // Setează timestamp-ul autentificării
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['ip_address'] = $this->getClientIP();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Remember me functionality
            if ($remember) {
                $this->setRememberToken($userData['id'], $type);
            }
            
            return true;
        } catch (Exception $e) {
            logError("Failed to create session: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Logout utilizator
     */
    public function logout() {
        try {
            // Log activitate
            if (isLoggedIn()) {
                $userId = $_SESSION['user_id'];
                $userType = $_SESSION['is_superadmin'] ?? false ? 'superadmin' : 'user';
                
                logActivity('logout', 'Utilizator deconectat', $userType, $userId);
                
                // Șterge remember token dacă există
                $this->clearRememberToken($userId, $userType);
            }
            
            // Distruge sesiunea
            $_SESSION = [];
            
            // Șterge cookie-ul de sesiune
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Distruge sesiunea
            session_destroy();
            
            return true;
        } catch (Exception $e) {
            logError("Logout failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Autorizează accesul pe baza rolurilor permise.
     * Se folosește static în module: Auth::authorize(['admin','manager']);
     * Dacă nu e autentificat sau nu are rolul necesar -> redirect la index public.
     */
    public static function authorize(array $allowedRoles) {
        if (!isset($_SESSION)) {
            @session_start();
        }
        // Superadmin are acces implicit
        if (!empty($_SESSION['role']) && $_SESSION['role'] === ROLE_SUPERADMIN) {
            return true;
        }
        $currentRole = $_SESSION['role'] ?? null;
        if (!$currentRole) {
            header('Location: /document-archive/public/login.php');
            exit;
        }
        if (!in_array($currentRole, $allowedRoles)) {
            // Fallback: dacă e user normal și încearcă admin -> trimite la un dashboard user simplu sau la logout.
            header('Location: /document-archive/public/index.php');
            exit;
        }
        return true;
    }
    
    /**
     * Verifică dacă sesiunea este validă
     */
    public function validateSession() {
        try {
            if (!isLoggedIn()) {
                return false;
            }
            
            // Verifică timeout-ul sesiunii
            $loginTime = $_SESSION['login_time'] ?? 0;
            $lastActivity = $_SESSION['last_activity'] ?? 0;
            
            if ((time() - $loginTime) > SESSION_LIFETIME) {
                $this->logout();
                return false;
            }
            
            // Verifică activitatea recentă (auto-logout după inactivitate)
            if ((time() - $lastActivity) > (SESSION_LIFETIME / 2)) {
                $this->logout();
                return false;
            }
            
            // Verifică schimbarea IP-ului (securitate)
            $currentIP = $this->getClientIP();
            $sessionIP = $_SESSION['ip_address'] ?? '';
            
            if ($sessionIP && $currentIP !== $sessionIP) {
                logError("IP address changed during session", [
                    'user_id' => $_SESSION['user_id'],
                    'old_ip' => $sessionIP,
                    'new_ip' => $currentIP
                ]);
                
                $this->logout();
                return false;
            }
            
            // Actualizează ultima activitate
            $_SESSION['last_activity'] = time();
            
            return true;
        } catch (Exception $e) {
            logError("Session validation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Rate limiting pentru încercări de login
     */
    private function isRateLimited($identifier) {
        try {
            $cacheKey = 'login_attempts_' . md5($identifier . $this->getClientIP());
            $cacheFile = CACHE_PATH . '/' . $cacheKey . '.json';
            
            if (!file_exists($cacheFile)) {
                return false;
            }
            
            $data = json_decode(file_get_contents($cacheFile), true);
            if (!$data) {
                return false;
            }
            
            // Verifică dacă lockout-ul a expirat
            if (time() > $data['lockout_until']) {
                unlink($cacheFile);
                return false;
            }
            
            return $data['attempts'] >= LOGIN_MAX_ATTEMPTS;
        } catch (Exception $e) {
            logError("Rate limiting check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Înregistrează încercare eșuată
     */
    private function recordFailedLogin($identifier) {
        try {
            $cacheKey = 'login_attempts_' . md5($identifier . $this->getClientIP());
            $cacheFile = CACHE_PATH . '/' . $cacheKey . '.json';
            
            $data = ['attempts' => 0, 'lockout_until' => 0];
            
            if (file_exists($cacheFile)) {
                $existingData = json_decode(file_get_contents($cacheFile), true);
                if ($existingData) {
                    $data = $existingData;
                }
            }
            
            $data['attempts']++;
            
            if ($data['attempts'] >= LOGIN_MAX_ATTEMPTS) {
                $data['lockout_until'] = time() + LOGIN_LOCKOUT_TIME;
                
                // Log încercare suspectă
                logError("Multiple failed login attempts", [
                    'identifier' => $identifier,
                    'ip' => $this->getClientIP(),
                    'attempts' => $data['attempts']
                ]);
            }
            
            if (!file_exists(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0755, true);
            }
            
            file_put_contents($cacheFile, json_encode($data));
        } catch (Exception $e) {
            logError("Failed to record login attempt: " . $e->getMessage());
        }
    }
    
    /**
     * Curăță încercările eșuate după login reușit
     */
    private function clearFailedLogins($identifier) {
        try {
            $cacheKey = 'login_attempts_' . md5($identifier . $this->getClientIP());
            $cacheFile = CACHE_PATH . '/' . $cacheKey . '.json';
            
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
        } catch (Exception $e) {
            logError("Failed to clear login attempts: " . $e->getMessage());
        }
    }
    
    /**
     * Setează token pentru "Remember Me"
     */
    private function setRememberToken($userId, $type) {
        try {
            $token = bin2hex(random_bytes(32));
            $hashedToken = password_hash($token, PASSWORD_BCRYPT);
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Salvează în baza de date
            $table = $type === 'superadmin' ? 'superadmin_remember_tokens' : 'user_remember_tokens';
            
            // Creează tabelul dacă nu există (pentru remember tokens)
            $this->createRememberTokensTable($table);
            
            $this->db->insert($table, [
                'user_id' => $userId,
                'token_hash' => $hashedToken,
                'expires_at' => $expires,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Setează cookie
            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', false, true);
            setcookie('remember_type', $type, strtotime('+30 days'), '/', '', false, true);
            
        } catch (Exception $e) {
            logError("Failed to set remember token: " . $e->getMessage());
        }
    }
    
    /**
     * Verifică remember token
     */
    public function checkRememberToken() {
        try {
            if (!isset($_COOKIE['remember_token']) || !isset($_COOKIE['remember_type'])) {
                return false;
            }
            
            $token = $_COOKIE['remember_token'];
            $type = $_COOKIE['remember_type'];
            $table = $type === 'superadmin' ? 'superadmin_remember_tokens' : 'user_remember_tokens';
            
            // Găsește token-ul valid
            $tokenData = $this->db->query("
                SELECT * FROM {$table} 
                WHERE expires_at > NOW() 
                ORDER BY created_at DESC
            ")->fetchAll();
            
            foreach ($tokenData as $storedToken) {
                if (password_verify($token, $storedToken['token_hash'])) {
                    // Token valid, autentifică utilizatorul
                    if ($type === 'superadmin') {
                        $userData = $this->db->query("SELECT * FROM superadmin_users WHERE id = :id")
                                           ->bind(':id', $storedToken['user_id'])
                                           ->fetch();
                    } else {
                        $userData = $this->db->query("
                            SELECT 
                                u.id,
                                u.company_id,
                                c.name AS company_name,
                                u.username,
                                u.full_name,
                                u.email,
                                u.role,
                                u.department_id,
                                u.avatar_path,
                                u.status
                            FROM users u
                            LEFT JOIN companies c ON c.id = u.company_id
                            WHERE u.id = :id
                            LIMIT 1
                        ")
                        ->bind(':id', $storedToken['user_id'])
                        ->fetch();
                    }
                    
                    if ($userData && ($userData['status'] === 'active' || $type === 'superadmin')) {
                        return $this->createSession($userData, $type);
                    }
                }
            }
            
            // Token invalid, șterge cookie-urile
            $this->clearRememberCookies();
            return false;
            
        } catch (Exception $e) {
            logError("Remember token check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Curăță remember token
     */
    private function clearRememberToken($userId, $type) {
        try {
            $table = $type === 'superadmin' ? 'superadmin_remember_tokens' : 'user_remember_tokens';
            
            $this->db->delete($table, 'user_id = :user_id', [':user_id' => $userId]);
            $this->clearRememberCookies();
        } catch (Exception $e) {
            logError("Failed to clear remember token: " . $e->getMessage());
        }
    }
    
    /**
     * Curăță cookie-urile remember
     */
    private function clearRememberCookies() {
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_type', '', time() - 3600, '/');
    }
    
    /**
     * Verifică permisiunea utilizatorului
     */
    public function hasPermission($permission) {
        if (!isLoggedIn()) {
            return false;
        }
        
        // SuperAdmin are toate permisiunile
        if (hasRole(ROLE_SUPERADMIN)) {
            return true;
        }
        
        $permissions = $_SESSION['permissions'] ?? [];
        return in_array($permission, $permissions);
    }
    
    /**
     * Verifică dacă utilizatorul poate accesa resursa
     */
    public function canAccess($resource, $resourceId = null, $action = 'view') {
        if (!isLoggedIn()) {
            return false;
        }
        
        // SuperAdmin poate accesa tot
        if (hasRole(ROLE_SUPERADMIN)) {
            return true;
        }
        
        // Verifică dacă resursa aparține companiei utilizatorului
        if ($resourceId && isset($_SESSION['company_id'])) {
            switch ($resource) {
                case 'document':
                    return $this->canAccessDocument($resourceId, $action);
                case 'department':
                    return $this->canAccessDepartment($resourceId, $action);
                case 'user':
                    return $this->canAccessUser($resourceId, $action);
                default:
                    return false;
            }
        }
        
        return false;
    }
    
    /**
     * Verifică accesul la document
     */
    private function canAccessDocument($documentId, $action) {
        try {
            $document = $this->db->query("
                SELECT company_id, created_by, department_id 
                FROM documents 
                WHERE id = :id AND status = :status
            ")
            ->bind(':id', $documentId)
            ->bind(':status', DOC_STATUS_ACTIVE)
            ->fetch();
            
            if (!$document) {
                return false;
            }
            
            // Verifică compania
            if ($document['company_id'] != $_SESSION['company_id']) {
                return false;
            }
            
            // Admin poate tot
            if (hasRole(ROLE_ADMIN)) {
                return true;
            }
            
            // Proprietarul documentului poate tot
            if ($document['created_by'] == $_SESSION['user_id']) {
                return true;
            }
            
            // Manager poate accesa documentele din departamentul său
            if (hasRole(ROLE_MANAGER) && 
                isset($_SESSION['department_id']) && 
                $document['department_id'] == $_SESSION['department_id']) {
                return true;
            }
            
            // User poate doar să vadă documentele din departamentul său
            if ($action === 'view' && 
                isset($_SESSION['department_id']) && 
                $document['department_id'] == $_SESSION['department_id']) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            logError("Document access check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifică accesul la departament
     */
    private function canAccessDepartment($departmentId, $action) {
        try {
            $department = $this->db->query("
                SELECT company_id, manager_id 
                FROM departments 
                WHERE id = :id AND status = :status
            ")
            ->bind(':id', $departmentId)
            ->bind(':status', 'active')
            ->fetch();
            
            if (!$department || $department['company_id'] != $_SESSION['company_id']) {
                return false;
            }
            
            // Admin poate tot
            if (hasRole(ROLE_ADMIN)) {
                return true;
            }
            
            // Manager poate accesa propriul departament
            if (hasRole(ROLE_MANAGER) && $department['manager_id'] == $_SESSION['user_id']) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            logError("Department access check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifică accesul la utilizator
     */
    private function canAccessUser($userId, $action) {
        try {
            $user = $this->db->query("
                SELECT company_id, department_id 
                FROM users 
                WHERE id = :id AND status = :status
            ")
            ->bind(':id', $userId)
            ->bind(':status', USER_STATUS_ACTIVE)
            ->fetch();
            
            if (!$user || $user['company_id'] != $_SESSION['company_id']) {
                return false;
            }
            
            // Admin poate tot
            if (hasRole(ROLE_ADMIN)) {
                return true;
            }
            
            // Utilizatorul poate accesa propriul profil
            if ($userId == $_SESSION['user_id']) {
                return $action === 'view' || $action === 'edit_profile';
            }
            
            // Manager poate vedea utilizatorii din departamentul său
            if (hasRole(ROLE_MANAGER) && 
                isset($_SESSION['department_id']) && 
                $user['department_id'] == $_SESSION['department_id']) {
                return $action === 'view';
            }
            
            return false;
        } catch (Exception $e) {
            logError("User access check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obține IP-ul clientului
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
    
    /**
     * Creează tabelul pentru remember tokens dacă nu există
     */
    private function createRememberTokensTable($tableName) {
        try {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS {$tableName} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token_hash VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_expires (expires_at)
                ) ENGINE=InnoDB
            ")->execute();
        } catch (Exception $e) {
            logError("Failed to create remember tokens table: " . $e->getMessage());
        }
    }
    
    /**
     * Curăță token-urile expirate
     */
    public function cleanupExpiredTokens() {
        try {
            $tables = ['superadmin_remember_tokens', 'user_remember_tokens'];
            
            foreach ($tables as $table) {
                $this->db->query("DELETE FROM {$table} WHERE expires_at < NOW()")->execute();
            }
        } catch (Exception $e) {
            logError("Failed to cleanup expired tokens: " . $e->getMessage());
        }
    }
}