<?php
/**
 * Database Configuration
 * 
 * This file contains the database connection settings
 * and provides a Database class for managing connections
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'cybercon_db';
    private $username = 'root';
    private $password = '';
    private $conn;
    private $useSQLite = true; // Set to false to use MySQL

    /**
     * Get database connection
     * 
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        try {
            if ($this->useSQLite) {
                // SQLite connection (easier setup, no MySQL required)
                $dbFile = __DIR__ . '/../database/cybercon.db';
                $this->conn = new PDO("sqlite:$dbFile");
            } else {
                // MySQL connection
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password
                );
            }
            
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            return null;
        }

        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
