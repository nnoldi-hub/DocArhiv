<?php
/**
 * Clasa Database - Wrapper PDO pentru operații DB
 * includes/classes/Database.php
 */

class Database {
    private $pdo;
    private $stmt;
    private $error;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Pregătește query-ul
     */
    public function query($sql) {
        $this->stmt = $this->pdo->prepare($sql);
        return $this;
    }
    
    /**
     * Bind parametri
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }
    
    /**
     * Execută query-ul
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            logError("Database Error: " . $e->getMessage(), [
                'sql' => $this->stmt->queryString
            ]);
            return false;
        }
    }
    
    /**
     * Returnează toate rezultatele
     */
    public function fetchAll() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    /**
     * Returnează un singur rezultat
     */
    public function fetch() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    /**
     * Returnează numărul de rânduri afectate
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    /**
     * Returnează ID-ul ultimului insert
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Începe tranzacție
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit tranzacție
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback tranzacție
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }
    
    /**
     * Returnează eroarea
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Query direct (pentru query-uri simple)
     */
    public function run($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            logError("Database Error: " . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            return false;
        }
    }
    
    /**
     * Insert cu array asociativ
     */
    public function insert($table, $data) {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = ':' . implode(', :', $keys);
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $this->query($sql);
        
        foreach ($data as $key => $value) {
            $this->bind(':' . $key, $value);
        }
        
        if ($this->execute()) {
            return $this->lastInsertId();
        }
        return false;
    }
    
    /**
     * Update cu array asociativ
     */
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }
        $setString = implode(', ', $set);
        
        $sql = "UPDATE {$table} SET {$setString} WHERE {$where}";
        $this->query($sql);
        
        foreach ($data as $key => $value) {
            $this->bind(':' . $key, $value);
        }
        
        foreach ($whereParams as $key => $value) {
            $this->bind($key, $value);
        }
        
        return $this->execute();
    }
    
    /**
     * Delete
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->query($sql);
        
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
        
        return $this->execute();
    }
    
    /**
     * Verifică dacă există
     */
    public function exists($table, $where, $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $this->query($sql);
        
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
        
        $result = $this->fetch();
        return $result && $result['count'] > 0;
    }
    
    /**
     * Count
     */
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $this->query($sql);
        
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
        
        $result = $this->fetch();
        return $result ? $result['count'] : 0;
    }
}