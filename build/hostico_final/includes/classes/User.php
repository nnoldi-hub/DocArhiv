<?php
/**
 * Clasa User - Gestionarea Utilizatorilor
 * includes/classes/User.php
 */

class User {
    private $db;
    private $table = 'users';
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Găsește utilizator după email
     */
    public function getByEmail($email) {
        try {
            $query = $this->db->query("SELECT * FROM {$this->table} WHERE email = :email LIMIT 1");
            $query->bind(':email', $email);
            return $query->fetch();
        } catch (Exception $e) {
            logError("Failed to get user by email: " . $e->getMessage(), ['email' => $email]);
            return false;
        }
    }
    
    /**
     * Găsește utilizator după credențiale (email sau username)
     */
    public function findByCredentials($usernameOrEmail) {
        try {
            $query = $this->db->query("SELECT * FROM {$this->table} WHERE (email = :credential OR username = :credential) AND status = :status LIMIT 1");
            $query->bind(':credential', $usernameOrEmail);
            $query->bind(':status', USER_STATUS_ACTIVE);
            return $query->fetch();
        } catch (Exception $e) {
            logError("Failed to find user by credentials: " . $e->getMessage(), ['credential' => $usernameOrEmail]);
            return false;
        }
    }
    
    /**
     * Creează un utilizator nou
     */
    public function create($data) {
        // Validări
        if (empty($data['email']) || empty($data['password'])) {
            throw new Exception('Date incomplete pentru utilizator');
        }
        
        // Verifică unicitatea email
        if ($this->getByEmail($data['email'])) {
            throw new Exception('Email-ul există deja');
        }
        
        // Hash parolă
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        
        // Setări default
        $data['role'] = $data['role'] ?? ROLE_USER;
        $data['status'] = $data['status'] ?? USER_STATUS_ACTIVE;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        try {
            $id = $this->db->insert($this->table, $data);
            
            if ($id) {
                logActivity('create', "Utilizator creat: {$data['email']}", 'user', $id);
            }
            
            return $id;
        } catch (Exception $e) {
            logError("Failed to create user: " . $e->getMessage(), $data);
            throw new Exception('Eroare la crearea utilizatorului');
        }
    }
    
    /**
     * Actualizează un utilizator
     */
    public function update($id, $data) {
        // Nu permite actualizarea anumitor câmpuri
        unset($data['id'], $data['created_at']);
        
        // Hash parolă dacă este trimisă
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        } else {
            unset($data['password']);
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        try {
            $result = $this->db->update($this->table, $data, 'id = :id', [':id' => $id]);
            
            if ($result) {
                logActivity('update', "Utilizator actualizat: ID $id", 'user', $id);
            }
            
            return $result;
        } catch (Exception $e) {
            logError("Failed to update user: " . $e->getMessage(), ['id' => $id, 'data' => $data]);
            throw new Exception('Eroare la actualizarea utilizatorului');
        }
    }
    
    /**
     * Găsește utilizator după ID
     */
    public function getById($id) {
        try {
            $query = $this->db->query("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
            $query->bind(':id', $id);
            return $query->fetch();
        } catch (Exception $e) {
            logError("Failed to get user by ID: " . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }
    
    /**
     * Obține permisiunile unui utilizator
     */
    public function getPermissions($id) {
        try {
            $user = $this->getById($id);
            if (!$user) {
                return [];
            }
            
            // Permisiuni bazate pe rol
            $rolePermissions = ROLE_PERMISSIONS[$user['role']] ?? [];
            
            return $rolePermissions;
        } catch (Exception $e) {
            logError("Failed to get user permissions: " . $e->getMessage(), ['user_id' => $id]);
            return [];
        }
    }
    
    /**
     * Verifică dacă utilizatorul are o permisiune
     */
    public function hasPermission($id, $permission) {
        $permissions = $this->getPermissions($id);
        return in_array($permission, $permissions);
    }
}