-- Database Schema pentru Hostico Shared Hosting
-- Pentru baza de date: rbcjgzba_DocArhiv
-- MySQL 8.0+

-- NU creăm baza de date - există deja pe Hostico
-- USE rbcjgzba_DocArhiv; -- Comentat - selectezi manual în phpMyAdmin

-- Tabel SuperAdmin
CREATE TABLE IF NOT EXISTS superadmin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Tabel Firme (Multi-tenant)
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100) DEFAULT 'România',
    registration_number VARCHAR(50),
    tax_number VARCHAR(50),
    logo_path VARCHAR(255),
    storage_limit BIGINT DEFAULT 10737418240,
    user_limit INT DEFAULT 50,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Tabel Utilizatori
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    department_id INT,
    avatar_path VARCHAR(255),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    permissions JSON,
    UNIQUE KEY unique_company_username (company_id, username),
    UNIQUE KEY unique_company_email (company_id, email),
    INDEX idx_company (company_id),
    INDEX idx_role (role),
    INDEX idx_department (department_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel Departamente
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    manager_id INT,
    parent_id INT,
    color VARCHAR(7) DEFAULT '#007bff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    UNIQUE KEY unique_company_name (company_id, name),
    INDEX idx_company (company_id),
    INDEX idx_parent (parent_id),
    INDEX idx_manager (manager_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel Dosare/Foldere
CREATE TABLE IF NOT EXISTS folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    department_id INT,
    parent_id INT,
    path VARCHAR(500),
    color VARCHAR(7) DEFAULT '#28a745',
    icon VARCHAR(50) DEFAULT 'folder',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'archived') DEFAULT 'active',
    permissions JSON,
    UNIQUE KEY unique_company_path (company_id, path),
    INDEX idx_company (company_id),
    INDEX idx_department (department_id),
    INDEX idx_parent (parent_id),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Tabel Taguri
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#6c757d',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usage_count INT DEFAULT 0,
    UNIQUE KEY unique_company_name (company_id, name),
    INDEX idx_company (company_id),
    INDEX idx_usage (usage_count),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel Documente
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type VARCHAR(100),
    file_hash VARCHAR(64),
    version INT DEFAULT 1,
    parent_document_id INT,
    department_id INT,
    folder_id INT,
    document_number VARCHAR(100),
    document_date DATE,
    expiry_date DATE,
    created_by INT NOT NULL,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'archived', 'deleted') DEFAULT 'active',
    is_confidential BOOLEAN DEFAULT FALSE,
    download_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    metadata JSON,
    INDEX idx_company (company_id),
    INDEX idx_department (department_id),
    INDEX idx_folder (folder_id),
    INDEX idx_created_by (created_by),
    INDEX idx_file_hash (file_hash),
    INDEX idx_document_date (document_date),
    INDEX idx_status (status),
    FULLTEXT KEY idx_fulltext_search (title, description, document_number),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel many-to-many pentru Document-Tag
CREATE TABLE IF NOT EXISTS document_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_document_tag (document_id, tag_id),
    INDEX idx_document (document_id),
    INDEX idx_tag (tag_id),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel Log Activități
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabel Permisiuni Documente
CREATE TABLE IF NOT EXISTS document_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    user_id INT,
    department_id INT,
    permission_type ENUM('view', 'edit', 'delete', 'admin') NOT NULL,
    granted_by INT NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_document (document_id),
    INDEX idx_user (user_id),
    INDEX idx_department (department_id),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Tabel Setări Sistem (per companie)
CREATE TABLE IF NOT EXISTS company_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_company_setting (company_id, setting_key),
    INDEX idx_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabel backup
CREATE TABLE IF NOT EXISTS backup_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    backup_type ENUM('full', 'incremental', 'documents') NOT NULL,
    file_path VARCHAR(500),
    file_size BIGINT,
    status ENUM('running', 'completed', 'failed') DEFAULT 'running',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    error_message TEXT,
    created_by INT,
    INDEX idx_company (company_id),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert SuperAdmin default (doar dacă nu există)
INSERT IGNORE INTO superadmin_users (username, email, password, full_name, status) 
VALUES ('superadmin', 'superadmin@arhiva.ro', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'active');

-- Setări default pentru sistem
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB;

-- Inserează setări default sistem
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('app_name', 'Sistem Arhivare Documente', 'string', 'Numele aplicației'),
('app_version', '1.0.0', 'string', 'Versiunea aplicației'),
('maintenance_mode', '0', 'boolean', 'Modul mentenanță'),
('max_upload_size', '52428800', 'integer', 'Dimensiunea maximă upload (bytes)'),
('allowed_file_types', '["pdf","doc","docx","xls","xlsx","jpg","jpeg","png","txt"]', 'json', 'Tipuri fișiere permise'),
('session_timeout', '7200', 'integer', 'Timeout sesiune (secunde)'),
('backup_retention_days', '30', 'integer', 'Zile păstrare backup-uri'),
('enable_file_versioning', '1', 'boolean', 'Activează versionarea fișierelor'),
('enable_audit_log', '1', 'boolean', 'Activează jurnalul de audit'),
('default_company_storage_limit', '10737418240', 'integer', 'Limită stocare implicită companie (bytes)');