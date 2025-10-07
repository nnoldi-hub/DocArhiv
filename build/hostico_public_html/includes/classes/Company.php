<?php
/**
 * Clasa Company - Gestionarea Companiilor (Multi-tenant)
 * includes/classes/Company.php
 */

// Define company status constants if not already defined
if (!defined('COMPANY_STATUS_TRIAL')) {
    define('COMPANY_STATUS_TRIAL', 'trial');
}
if (!defined('COMPANY_STATUS_ACTIVE')) {
    define('COMPANY_STATUS_ACTIVE', 'active');
}
if (!defined('COMPANY_STATUS_SUSPENDED')) {
    define('COMPANY_STATUS_SUSPENDED', 'suspended');
}
if (!defined('COMPANY_STATUS_EXPIRED')) {
    define('COMPANY_STATUS_EXPIRED', 'expired');
}

class Company {
    private $db;
    private $table = 'companies';
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Găsește companie după nume
     */
    public function getByName($name) {
        try {
            $query = $this->db->query("SELECT * FROM {$this->table} WHERE name = :name LIMIT 1");
            $query->bind(':name', $name);
            return $query->fetch();
        } catch (Exception $e) {
            logError("Failed to get company by name: " . $e->getMessage(), ['name' => $name]);
            return false;
        }
    }
    
    /**
     * Găsește companie după ID
     */
    public function getById($id) {
        try {
            $query = $this->db->query("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
            $query->bind(':id', $id);
            return $query->fetch();
        } catch (Exception $e) {
            logError("Failed to get company by ID: " . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }
    
    /**
     * Creează o companie nouă
     */
    public function create($data) {
        // Validări
        if (empty($data['company_name']) || empty($data['email'])) {
            throw new Exception('Numele companiei și emailul sunt obligatorii');
        }
        
        // Generează cod companie unic
        $data['company_code'] = $this->generateCompanyCode($data['company_name']);
        
        // Verifică unicitatea
        if ($this->existsByCode($data['company_code']) || $this->existsByEmail($data['email'])) {
            throw new Exception('Compania sau emailul există deja');
        }
        
        // Setări default
        $data['max_storage_gb'] = $data['max_storage_gb'] ?? MAX_STORAGE_PER_COMPANY_GB;
        $data['max_users'] = $data['max_users'] ?? 5;
        $data['subscription_status'] = $data['subscription_status'] ?? COMPANY_STATUS_TRIAL;
        $data['subscription_start'] = $data['subscription_start'] ?? date('Y-m-d');
        $data['subscription_end'] = $data['subscription_end'] ?? date('Y-m-d', strtotime('+14 days')); // Trial 14 zile
        $data['created_at'] = date('Y-m-d H:i:s');
        
        try {
            $this->db->beginTransaction();
            
            // Creează compania
            $companyId = $this->db->insert($this->table, $data);
            
            if ($companyId) {
                // Creează directorul de stocare pentru companie
                $storagePath = UPLOAD_PATH . '/' . $companyId;
                if (!file_exists($storagePath)) {
                    mkdir($storagePath, 0755, true);
                }
                
                // Creează setări inițiale
                $this->createInitialSettings($companyId);
                
                $this->db->commit();
                logActivity('create', "Companie creată: {$data['company_name']}", 'company', $companyId);
                
                return $companyId;
            }
            
            $this->db->rollBack();
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Failed to create company: " . $e->getMessage(), $data);
            throw new Exception('Eroare la crearea companiei');
        }
    }
    
    /**
     * Actualizează o companie
     */
    public function update($id, $data) {
        // Nu permite actualizarea anumitor câmpuri
        unset($data['id'], $data['company_code'], $data['created_at']);
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        try {
            $result = $this->db->update($this->table, $data, 'id = :id', [':id' => $id]);
            
            if ($result) {
                logActivity('update', "Companie actualizată: ID $id", 'company', $id);
            }
            
            return $result;
        } catch (Exception $e) {
            logError("Failed to update company: " . $e->getMessage(), ['id' => $id, 'data' => $data]);
            throw new Exception('Eroare la actualizarea companiei');
        }
    }
    
    /**
     * Suspendă/activează compania
     */
    public function toggleStatus($id) {
        try {
            $company = $this->findById($id);
            if (!$company) {
                return false;
            }
            
            $newStatus = $company['subscription_status'] === COMPANY_STATUS_ACTIVE 
                ? COMPANY_STATUS_SUSPENDED 
                : COMPANY_STATUS_ACTIVE;
            
            $result = $this->db->update($this->table, 
                ['subscription_status' => $newStatus], 
                'id = :id', 
                [':id' => $id]
            );
            
            if ($result) {
                logActivity('status_change', "Status companie schimbat la: $newStatus", 'company', $id);
            }
            
            return $result;
        } catch (Exception $e) {
            logError("Failed to toggle company status: " . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }
    
    /**
     * Găsește companie după ID
     */
    public function findById($id) {
        try {
            return $this->db->query("SELECT * FROM {$this->table} WHERE id = :id")
                           ->bind(':id', $id)
                           ->fetch();
        } catch (Exception $e) {
            logError("Failed to find company by ID: " . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }
    
    /**
     * Găsește companie după cod
     */
    public function findByCode($code) {
        try {
            return $this->db->query("SELECT * FROM {$this->table} WHERE company_code = :code")
                           ->bind(':code', $code)
                           ->fetch();
        } catch (Exception $e) {
            logError("Failed to find company by code: " . $e->getMessage(), ['code' => $code]);
            return false;
        }
    }
    
    /**
     * Obține toate companiile (pentru SuperAdmin)
     */
    public function getAll($filters = []) {
        try {
            $sql = "SELECT *, 
                    (SELECT COUNT(*) FROM users WHERE company_id = c.id AND status = 'active') as active_users,
                    (SELECT COUNT(*) FROM documents WHERE company_id = c.id AND status = 'active') as total_documents,
                    (SELECT SUM(file_size) FROM documents WHERE company_id = c.id AND status = 'active') as used_storage
                    FROM {$this->table} c WHERE 1=1";
            
            $params = [];
            
            // Filtre opționale
            if (!empty($filters['status'])) {
                $sql .= " AND subscription_status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (company_name LIKE :search OR email LIKE :search OR company_code LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $query = $this->db->query($sql);
            foreach ($params as $key => $value) {
                $query->bind($key, $value);
            }
            
            return $query->fetchAll();
        } catch (Exception $e) {
            logError("Failed to get all companies: " . $e->getMessage(), $filters);
            return [];
        }
    }
    
    /**
     * Verifică dacă compania există după cod
     */
    public function existsByCode($code, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE company_code = :code";
            $params = [':code' => $code];
            
            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = $excludeId;
            }
            
            $query = $this->db->query($sql);
            foreach ($params as $key => $value) {
                $query->bind($key, $value);
            }
            
            $result = $query->fetch();
            return $result && $result['count'] > 0;
        } catch (Exception $e) {
            logError("Failed to check if company exists by code: " . $e->getMessage());
            return true; // Return true pentru siguranță
        }
    }
    
    /**
     * Verifică dacă compania există după email
     */
    public function existsByEmail($email, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";
            $params = [':email' => $email];
            
            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = $excludeId;
            }
            
            $query = $this->db->query($sql);
            foreach ($params as $key => $value) {
                $query->bind($key, $value);
            }
            
            $result = $query->fetch();
            return $result && $result['count'] > 0;
        } catch (Exception $e) {
            logError("Failed to check if company exists by email: " . $e->getMessage());
            return true; // Return true pentru siguranță
        }
    }
    
    /**
     * Generează cod unic pentru companie
     */
    private function generateCompanyCode($companyName) {
        // Curăță numele companiei
        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', $companyName);
        $cleanName = strtoupper(substr($cleanName, 0, 6));
        
        // Adaugă 4 cifre random
        $randomSuffix = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        $code = $cleanName . $randomSuffix;
        
        // Verifică unicitatea
        $counter = 1;
        $originalCode = $code;
        while ($this->existsByCode($code)) {
            $code = $originalCode . $counter;
            $counter++;
        }
        
        return $code;
    }
    
    /**
     * Creează setări inițiale pentru companie
     */
    private function createInitialSettings($companyId) {
        try {
            // Departamente default
            $defaultDepartments = [
                ['name' => 'Administrație', 'icon' => 'building', 'color' => '#3b82f6'],
                ['name' => 'Resurse Umane', 'icon' => 'people', 'color' => '#10b981'],
                ['name' => 'Financiar', 'icon' => 'calculator', 'color' => '#f59e0b'],
                ['name' => 'Legal', 'icon' => 'shield-check', 'color' => '#ef4444'],
                ['name' => 'IT', 'icon' => 'laptop', 'color' => '#8b5cf6']
            ];
            
            foreach ($defaultDepartments as $dept) {
                $this->db->insert('departments', [
                    'company_id' => $companyId,
                    'name' => $dept['name'],
                    'icon' => $dept['icon'],
                    'color' => $dept['color'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Taguri default
            $defaultTags = [
                ['name' => 'Contracte', 'color' => '#3b82f6'],
                ['name' => 'Facturi', 'color' => '#10b981'],
                ['name' => 'Important', 'color' => '#ef4444'],
                ['name' => 'Arhivă', 'color' => '#6b7280'],
                ['name' => 'Urgent', 'color' => '#f59e0b']
            ];
            
            foreach ($defaultTags as $tag) {
                $this->db->insert('tags', [
                    'company_id' => $companyId,
                    'name' => $tag['name'],
                    'color' => $tag['color'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            
        } catch (Exception $e) {
            logError("Failed to create initial settings: " . $e->getMessage(), ['company_id' => $companyId]);
        }
    }
    
    /**
     * Obține statistici generale despre companie
     */
    public function getStats($id) {
        try {
            $stats = [];
            
            // Informații de bază
            $company = $this->findById($id);
            if (!$company) {
                return [];
            }
            
            $stats['company'] = $company;
            
            // Utilizatori
            $stats['users'] = [
                'total' => $this->db->count('users', 'company_id = :cid', [':cid' => $id]),
                'active' => $this->db->count('users', 'company_id = :cid AND status = :status', 
                    [':cid' => $id, ':status' => USER_STATUS_ACTIVE])
            ];
            
            // Documente
            $stats['documents'] = [
                'total' => $this->db->count('documents', 'company_id = :cid AND status = :status',
                    [':cid' => $id, ':status' => DOC_STATUS_ACTIVE]),
                'this_month' => $this->db->count('documents', 
                    'company_id = :cid AND status = :status AND created_at >= :date',
                    [':cid' => $id, ':status' => DOC_STATUS_ACTIVE, ':date' => date('Y-m-01')])
            ];
            
            // Spațiu folosit
            $storageResult = $this->db->query("
                SELECT SUM(file_size) as used_bytes 
                FROM documents 
                WHERE company_id = :company_id AND status = :status
            ")
            ->bind(':company_id', $id)
            ->bind(':status', DOC_STATUS_ACTIVE)
            ->fetch();
            
            $stats['storage'] = [
                'used_bytes' => $storageResult['used_bytes'] ?? 0,
                'used_gb' => round(($storageResult['used_bytes'] ?? 0) / (1024 * 1024 * 1024), 2),
                'limit_gb' => $company['max_storage_gb'],
                'usage_percent' => getStorageUsagePercent($storageResult['used_bytes'] ?? 0, $company['max_storage_gb'])
            ];
            
            // Departamente
            $stats['departments'] = $this->db->count('departments', 'company_id = :cid AND status = :status',
                [':cid' => $id, ':status' => 'active']);
            
            // Activitate recentă
            $stats['recent_activity'] = $this->db->query("
                SELECT COUNT(*) as count 
                FROM activity_logs 
                WHERE company_id = :company_id AND created_at >= :date
            ")
            ->bind(':company_id', $id)
            ->bind(':date', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->fetch()['count'] ?? 0;
            
            return $stats;
        } catch (Exception $e) {
            logError("Failed to get company stats: " . $e->getMessage(), ['id' => $id]);
            return [];
        }
    }
    
    /**
     * Verifică dacă compania poate adăuga utilizatori
     */
    public function canAddUsers($id) {
        try {
            $company = $this->findById($id);
            if (!$company) {
                return false;
            }
            
            $currentUsers = $this->db->count('users', 'company_id = :cid AND status = :status',
                [':cid' => $id, ':status' => USER_STATUS_ACTIVE]);
            
            return $currentUsers < $company['max_users'];
        } catch (Exception $e) {
            logError("Failed to check if company can add users: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifică dacă compania poate încărca documente (spațiu)
     */
    public function canUploadDocument($id, $fileSize) {
        try {
            $stats = $this->getStats($id);
            if (empty($stats)) {
                return false;
            }
            
            $limitBytes = $stats['company']['max_storage_gb'] * 1024 * 1024 * 1024;
            $usedBytes = $stats['storage']['used_bytes'];
            
            return ($usedBytes + $fileSize) <= $limitBytes;
        } catch (Exception $e) {
            logError("Failed to check if company can upload: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Prelungește abonamentul companiei
     */
    public function extendSubscription($id, $months, $newStatus = COMPANY_STATUS_ACTIVE) {
        try {
            $company = $this->findById($id);
            if (!$company) {
                return false;
            }
            
            $currentEnd = $company['subscription_end'] ?: date('Y-m-d');
            $newEnd = date('Y-m-d', strtotime($currentEnd . " +{$months} months"));
            
            $result = $this->db->update($this->table, [
                'subscription_status' => $newStatus,
                'subscription_end' => $newEnd
            ], 'id = :id', [':id' => $id]);
            
            if ($result) {
                logActivity('subscription_extend', "Abonament prelungit cu $months luni", 'company', $id);
            }
            
            return $result;
        } catch (Exception $e) {
            logError("Failed to extend subscription: " . $e->getMessage(), ['id' => $id, 'months' => $months]);
            return false;
        }
    }
    
    /**
     * Obține companiile care expiră în următoarele X zile
     */
    public function getExpiringCompanies($days = 7) {
        try {
            return $this->db->query("
                SELECT * FROM {$this->table} 
                WHERE subscription_status = :status 
                AND subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                ORDER BY subscription_end ASC
            ")
            ->bind(':status', COMPANY_STATUS_ACTIVE)
            ->bind(':days', $days)
            ->fetchAll();
        } catch (Exception $e) {
            logError("Failed to get expiring companies: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marchează companiile expirate
     */
    public function markExpiredCompanies() {
        try {
            $result = $this->db->query("
                UPDATE {$this->table} 
                SET subscription_status = :expired_status 
                WHERE subscription_status = :active_status 
                AND subscription_end < CURDATE()
            ")
            ->bind(':expired_status', COMPANY_STATUS_EXPIRED)
            ->bind(':active_status', COMPANY_STATUS_ACTIVE)
            ->execute();
            
            if ($result) {
                $affectedRows = $this->db->rowCount();
                logActivity('system', "Companiile expirate marcate: $affectedRows", 'company', null);
            }
            
            return $result;
        } catch (Exception $e) {
            logError("Failed to mark expired companies: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Șterge compania și toate datele asociate (GDPR)
     */
    public function deleteCompany($id) {
        try {
            $this->db->beginTransaction();
            
            // Șterge documentele fizice
            $documents = $this->db->query("SELECT file_path FROM documents WHERE company_id = :id")
                                 ->bind(':id', $id)
                                 ->fetchAll();
            
            foreach ($documents as $doc) {
                $filePath = STORAGE_PATH . $doc['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // Șterge directorul companiei
            $companyDir = UPLOAD_PATH . '/' . $id;
            if (file_exists($companyDir)) {
                deleteDirectory($companyDir);
            }
            
            // Șterge datele din baza de date (cascade delete va șterge automat)
            $result = $this->db->delete($this->table, 'id = :id', [':id' => $id]);
            
            if ($result) {
                $this->db->commit();
                logActivity('delete', "Companie ștearsă complet: ID $id", 'company', $id);
                return true;
            }
            
            $this->db->rollBack();
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Failed to delete company: " . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }
}