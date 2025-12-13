<?php
/**
 * Activity Logger Class
 * Tracks admin actions and system activities
 */

class ActivityLogger {
    private $db;
    
    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }
    
    /**
     * Log an activity
     */
    public function log($action, $description = '', $entityType = null, $entityId = null) {
        if (!$this->db) return false;
        
        // Get admin info from session
        $adminId = $_SESSION['admin_id'] ?? null;
        $adminUsername = $_SESSION['admin_username'] ?? 'System';
        
        // Get client info
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        try {
            $query = "INSERT INTO activity_logs 
                (admin_id, admin_username, action, entity_type, entity_id, description, ip_address, user_agent) 
                VALUES 
                (:admin_id, :admin_username, :action, :entity_type, :entity_id, :description, :ip_address, :user_agent)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':admin_id', $adminId);
            $stmt->bindParam(':admin_username', $adminUsername);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':entity_type', $entityType);
            $stmt->bindParam(':entity_id', $entityId);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':user_agent', $userAgent);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent logs
     */
    public function getRecentLogs($limit = 100, $offset = 0, $action = '', $admin = '') {
        if (!$this->db) return [];
        
        $query = "SELECT * FROM activity_logs WHERE 1=1";
        $params = [];
        
        if ($action) {
            $query .= " AND action LIKE :action";
            $params[':action'] = '%' . $action . '%';
        }
        
        if ($admin) {
            $query .= " AND admin_username LIKE :admin";
            $params[':admin'] = '%' . $admin . '%';
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        try {
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Failed to fetch logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total log count
     */
    public function getTotalCount($action = '', $admin = '') {
        if (!$this->db) return 0;
        
        $query = "SELECT COUNT(*) as total FROM activity_logs WHERE 1=1";
        $params = [];
        
        if ($action) {
            $query .= " AND action LIKE :action";
            $params[':action'] = '%' . $action . '%';
        }
        
        if ($admin) {
            $query .= " AND admin_username LIKE :admin";
            $params[':admin'] = '%' . $admin . '%';
        }
        
        try {
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}
