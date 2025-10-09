<?php
/**
 * Clasa DocumentManager - Gestionare documente
 * includes/classes/DocumentManager.php
 */

class DocumentManager {
    private $db;
    private $uploadPath;
    
    public function __construct() {
        $this->db = new Database();
        $this->uploadPath = UPLOAD_PATH;
    }
    
    /**
     * Upload document
     */
    public function uploadDocument($file, $data) {
        try {
            // Validare fișier
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }
            
            // Generare hash unic pentru fișier
            $fileHash = hash_file('sha256', $file['tmp_name']);
            
            // Verificare dacă fișierul există deja (deduplicare)
            $existing = $this->db->query("
                SELECT id FROM documents 
                WHERE company_id = :company_id AND file_hash = :hash AND status = 'active'
            ")
            ->bind(':company_id', $data['company_id'])
            ->bind(':hash', $fileHash)
            ->fetch();
            
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Acest document există deja în arhivă!'
                ];
            }
            
            // Creare structură directoare: company_id/year/month
            $year = date('Y');
            $month = date('m');
            $companyDir = $this->uploadPath . '/' . $data['company_id'];
            $yearDir = $companyDir . '/' . $year;
            $monthDir = $yearDir . '/' . $month;
            
            if (!file_exists($monthDir)) {
                mkdir($monthDir, 0755, true);
            }
            
            // Generare nume fișier unic
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $uniqueName = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $monthDir . '/' . $uniqueName;
            $relativeFilePath = $data['company_id'] . '/' . $year . '/' . $month . '/' . $uniqueName;
            
            // Mutare fișier
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return [
                    'success' => false,
                    'message' => 'Eroare la salvarea fișierului!'
                ];
            }
            
            // Extragere text pentru indexare (pentru PDF-uri)
            $indexedContent = $this->extractText($filePath, $extension);
            
            // Salvare în bază de date
            $this->db->beginTransaction();
            
            try {
                $documentData = [
                    'company_id' => $data['company_id'],
                    'department_id' => $data['department_id'] ?: null,
                    'folder_id' => $data['folder_id'] ?: null,
                    'title' => $data['title'],
                    'description' => $data['description'] ?: null,
                    'file_name' => $file['name'],
                    'file_path' => $relativeFilePath,
                    'file_hash' => $fileHash,
                    'file_size' => $file['size'],
                    'file_type' => $extension,
                    'mime_type' => $file['type'],
                    'created_by' => $data['user_id'],
                    'document_date' => $data['document_date'] ?: date('Y-m-d'),
                    'document_number' => $data['document_number'] ?: null,
                    'indexed_content' => $indexedContent
                ];
                
                $documentId = $this->db->insert('documents', $documentData);
                
                if (!$documentId) {
                    throw new Exception('Eroare la salvarea în baza de date');
                }
                
                // Procesare taguri
                if (!empty($data['tags'])) {
                    $this->processTags($documentId, $data['company_id'], $data['tags']);
                }
                
                // Log activitate
                $this->logActivity($data['company_id'], $data['user_id'], 'upload', 'document', $documentId, 
                    'Document încărcat: ' . $data['title']);
                
                $this->db->commit();
                
                return [
                    'success' => true,
                    'message' => 'Document încărcat cu succes!',
                    'document_id' => $documentId
                ];
                
            } catch (Exception $e) {
                $this->db->rollBack();
                // Șterge fișierul dacă salvarea în DB a eșuat
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                throw $e;
            }
            
        } catch (Exception $e) {
            logError('Document upload error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Eroare la încărcarea documentului: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validare fișier
     */
    private function validateFile($file) {
        // Verificare erori upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Eroare la încărcarea fișierului!'
            ];
        }
        
        // Verificare dimensiune
        if ($file['size'] > MAX_FILE_SIZE) {
            return [
                'success' => false,
                'message' => 'Fișierul este prea mare! Maxim: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'
            ];
        }
        
        // Verificare extensie
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            return [
                'success' => false,
                'message' => 'Tip de fișier nepermis! Extensii permise: ' . implode(', ', ALLOWED_EXTENSIONS)
            ];
        }
        
        // Verificare MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
            return [
                'success' => false,
                'message' => 'Tip MIME nepermis!'
            ];
        }
        
        return ['success' => true];
    }
    
    /**
     * Extragere text din document pentru indexare
     */
    private function extractText($filePath, $extension) {
        try {
            if ($extension === 'txt') {
                return file_get_contents($filePath);
            }
            
            // Pentru PDF ar trebui folosit o librărie precum Smalot\PdfParser
            // Pentru simplificare, returnăm null
            // TODO: Implementare OCR/text extraction pentru PDF, DOC, etc.
            
            return null;
            
        } catch (Exception $e) {
            logError('Text extraction error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Procesare taguri
     */
    private function processTags($documentId, $companyId, $tags) {
        if (is_string($tags)) {
            $tags = array_map('trim', explode(',', $tags));
        }
        
        foreach ($tags as $tagName) {
            if (empty($tagName)) continue;
            
            // Verificare dacă tag-ul există
            $tag = $this->db->query("
                SELECT id FROM tags 
                WHERE company_id = :company_id AND name = :name
            ")
            ->bind(':company_id', $companyId)
            ->bind(':name', $tagName)
            ->fetch();
            
            if (!$tag) {
                // Creare tag nou
                $tagId = $this->db->insert('tags', [
                    'company_id' => $companyId,
                    'name' => $tagName,
                    'color' => '#' . substr(md5($tagName), 0, 6)
                ]);
            } else {
                $tagId = $tag['id'];
                // Incrementare usage_count
                $this->db->query("
                    UPDATE tags SET usage_count = usage_count + 1 
                    WHERE id = :id
                ")->bind(':id', $tagId)->execute();
            }
            
            // Asociere tag cu document
            $this->db->insert('document_tags', [
                'document_id' => $documentId,
                'tag_id' => $tagId
            ]);
        }
    }
    
    /**
     * Obține document după ID
     */
    public function getDocument($documentId, $companyId) {
        return $this->db->query("
            SELECT d.*,
                   dept.name as department_name,
                   f.name as folder_name,
                   u.full_name as uploaded_by_name,
                   GROUP_CONCAT(t.name) as tags
            FROM documents d
            LEFT JOIN departments dept ON d.department_id = dept.id
            LEFT JOIN folders f ON d.folder_id = f.id
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN document_tags dt ON d.id = dt.document_id
            LEFT JOIN tags t ON dt.tag_id = t.id
            WHERE d.id = :id AND d.company_id = :company_id AND d.status = 'active'
            GROUP BY d.id
        ")
        ->bind(':id', $documentId)
        ->bind(':company_id', $companyId)
        ->fetch();
    }
    
    /**
     * Descarcă document
     */
    public function downloadDocument($documentId, $companyId, $userId) {
        $doc = $this->getDocument($documentId, $companyId);
        
        if (!$doc) {
            return ['success' => false, 'message' => 'Document negăsit!'];
        }
        
        $filePath = $this->uploadPath . '/' . $doc['file_path'];
        
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Fișierul nu există pe server!'];
        }
        
        // Incrementare counter download
        $this->db->query("
            UPDATE documents SET download_count = download_count + 1 
            WHERE id = :id
        ")->bind(':id', $documentId)->execute();
        
        // Log activitate
        $this->logActivity($companyId, $userId, 'download', 'document', $documentId, 
            'Document descărcat: ' . $doc['title']);
        
        return [
            'success' => true,
            'file_path' => $filePath,
            'file_name' => $doc['file_name'],
            'mime_type' => $doc['mime_type']
        ];
    }
    
    /**
     * Șterge document (soft delete)
     */
    public function deleteDocument($documentId, $companyId, $userId) {
        $doc = $this->getDocument($documentId, $companyId);
        
        if (!$doc) {
            return ['success' => false, 'message' => 'Document negăsit!'];
        }
        
        // Soft delete
        $result = $this->db->update('documents', 
            ['status' => 'deleted'],
            'id = :id AND company_id = :company_id',
            [':id' => $documentId, ':company_id' => $companyId]
        );
        
        if ($result) {
            // Log activitate
            $this->logActivity($companyId, $userId, 'delete', 'document', $documentId, 
                'Document șters: ' . $doc['title']);
            
            return ['success' => true, 'message' => 'Document șters cu succes!'];
        }
        
        return ['success' => false, 'message' => 'Eroare la ștergerea documentului!'];
    }
    
    /**
     * Căutare avansată documente
     */
    public function searchDocuments($companyId, $searchParams) {
        $whereConditions = ["d.company_id = :company_id", "d.status = 'active'"];
        $params = [':company_id' => $companyId];
        
        // Full-text search
        if (!empty($searchParams['query'])) {
            $whereConditions[] = "MATCH(d.title, d.description, d.indexed_content) AGAINST(:query IN BOOLEAN MODE)";
            $params[':query'] = $searchParams['query'];
        }
        
        // Filtre
        if (!empty($searchParams['department_id'])) {
            $whereConditions[] = "d.department_id = :department_id";
            $params[':department_id'] = $searchParams['department_id'];
        }
        
        if (!empty($searchParams['folder_id'])) {
            $whereConditions[] = "d.folder_id = :folder_id";
            $params[':folder_id'] = $searchParams['folder_id'];
        }
        
        if (!empty($searchParams['tags'])) {
            $whereConditions[] = "d.id IN (
                SELECT document_id FROM document_tags dt
                INNER JOIN tags t ON dt.tag_id = t.id
                WHERE t.name IN (" . implode(',', array_fill(0, count($searchParams['tags']), '?')) . ")
            )";
            // Adaugă tagurile în params
        }
        
        if (!empty($searchParams['date_from'])) {
            $whereConditions[] = "d.document_date >= :date_from";
            $params[':date_from'] = $searchParams['date_from'];
        }
        
        if (!empty($searchParams['date_to'])) {
            $whereConditions[] = "d.document_date <= :date_to";
            $params[':date_to'] = $searchParams['date_to'];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $query = "
            SELECT d.*,
                   dept.name as department_name,
                   f.name as folder_name,
                   u.full_name as uploaded_by_name,
                   GROUP_CONCAT(t.name) as tags
            FROM documents d
            LEFT JOIN departments dept ON d.department_id = dept.id
            LEFT JOIN folders f ON d.folder_id = f.id
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN document_tags dt ON d.id = dt.document_id
            LEFT JOIN tags t ON dt.tag_id = t.id
            WHERE " . $whereClause . "
            GROUP BY d.id
            ORDER BY d.created_at DESC
        ";
        
        $stmt = $this->db->query($query);
        foreach ($params as $key => $value) {
            $stmt->bind($key, $value);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obține statistici companie
     */
    public function getCompanyStats($companyId) {
        $stats = [];
        
        // Total documente
        $stats['total_documents'] = $this->db->count('documents', 
            'company_id = :company_id AND status = :status',
            [':company_id' => $companyId, ':status' => 'active']
        );
        
        // Spațiu utilizat
        $result = $this->db->query("
            SELECT SUM(file_size) as total_size
            FROM documents
            WHERE company_id = :company_id AND status = 'active'
        ")->bind(':company_id', $companyId)->fetch();
        $stats['total_size'] = $result['total_size'] ?? 0;
        
        // Documente pe departament
        $stats['by_department'] = $this->db->query("
            SELECT dept.name, COUNT(d.id) as count
            FROM departments dept
            LEFT JOIN documents d ON dept.id = d.department_id AND d.status = 'active'
            WHERE dept.company_id = :company_id AND dept.status = 'active'
            GROUP BY dept.id
            ORDER BY count DESC
        ")->bind(':company_id', $companyId)->fetchAll();
        
        // Documente recente (ultimele 30 zile)
        $stats['recent_uploads'] = $this->db->query("
            SELECT COUNT(*) as count
            FROM documents
            WHERE company_id = :company_id 
            AND status = 'active'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->bind(':company_id', $companyId)->fetch()['count'] ?? 0;
        
        // Top utilizatori (cei mai activi)
        $stats['top_uploaders'] = $this->db->query("
            SELECT u.full_name, COUNT(d.id) as count
            FROM users u
            LEFT JOIN documents d ON u.id = d.created_by AND d.status = 'active'
            WHERE u.company_id = :company_id AND u.status = 'active'
            GROUP BY u.id
            ORDER BY count DESC
            LIMIT 5
        ")->bind(':company_id', $companyId)->fetchAll();
        
        return $stats;
    }
    
    /**
     * Log activitate
     */
    private function logActivity($companyId, $userId, $action, $entityType, $entityId, $description) {
        $this->db->insert('activity_logs', [
            'company_id' => $companyId,
            'user_id' => $userId,
            'action_type' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}