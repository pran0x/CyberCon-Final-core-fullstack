<?php
/**
 * Security Logger Class
 * Handles failed login attempts, IP blocking, and security logging
 */

class SecurityLogger {
    private $conn;
    private $maxAttempts = 3;
    private $blockDuration = 3600; // 1 hour in seconds
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get client IP address (handles proxy/CDN scenarios)
     */
    public function getClientIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
    
    /**
     * Get user agent
     */
    public function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
    
    /**
     * Get geolocation data from IP (using free API)
     */
    public function getLocationFromIP($ip) {
        // Skip for local IPs
        if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'UNKNOWN') {
            return ['country' => 'Local', 'city' => 'Localhost'];
        }
        
        try {
            // Using ip-api.com (free, no API key needed, 45 req/min limit)
            $url = "http://ip-api.com/json/{$ip}?fields=country,city,status";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                if ($data && $data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? 'Unknown',
                        'city' => $data['city'] ?? 'Unknown'
                    ];
                }
            }
        } catch (Exception $e) {
            // Silently fail
        }
        
        return ['country' => 'Unknown', 'city' => 'Unknown'];
    }
    
    /**
     * Check if IP is currently blocked
     */
    public function isIPBlocked($ip) {
        try {
            // Compatible with both MySQL and SQLite
            $query = "SELECT id, unblock_at FROM blocked_ips 
                     WHERE ip_address = :ip 
                     AND (unblock_at IS NULL OR unblock_at > datetime('now'))";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get number of failed attempts in the last hour
     */
    public function getFailedAttemptCount($ip) {
        try {
            // Compatible with both MySQL and SQLite
            $query = "SELECT COUNT(*) as count FROM failed_login_attempts 
                     WHERE ip_address = :ip 
                     AND attempt_time > datetime('now', '-1 hour')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
            $result = $stmt->fetch();
            
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            // Fallback for MySQL
            try {
                $query = "SELECT COUNT(*) as count FROM failed_login_attempts 
                         WHERE ip_address = :ip 
                         AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':ip', $ip);
                $stmt->execute();
                $result = $stmt->fetch();
                return (int)($result['count'] ?? 0);
            } catch (PDOException $e2) {
                return 0;
            }
        }
    }
    
    /**
     * Log failed login attempt
     */
    public function logFailedAttempt($ip, $username, $password) {
        try {
            // Get location data
            $location = $this->getLocationFromIP($ip);
            $userAgent = $this->getUserAgent();
            
            // Insert failed attempt
            $query = "INSERT INTO failed_login_attempts 
                     (ip_address, username_attempted, password_attempted, user_agent, country, city) 
                     VALUES (:ip, :username, :password, :user_agent, :country, :city)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':user_agent', $userAgent);
            $stmt->bindParam(':country', $location['country']);
            $stmt->bindParam(':city', $location['city']);
            $stmt->execute();
            
            // Check if should block IP
            $attemptCount = $this->getFailedAttemptCount($ip);
            
            if ($attemptCount >= $this->maxAttempts) {
                $this->blockIP($ip);
                return true; // IP was blocked
            }
            
            return false; // IP not blocked yet
        } catch (PDOException $e) {
            error_log("Security Logger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Block an IP address
     */
    public function blockIP($ip) {
        try {
            $unblockTime = date('Y-m-d H:i:s', time() + $this->blockDuration);
            
            // SQLite-compatible insert or replace
            $query = "INSERT OR REPLACE INTO blocked_ips (ip_address, blocked_at, unblock_at, reason) 
                     VALUES (:ip, datetime('now'), :unblock_at, 'Too many failed login attempts')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':unblock_at', $unblockTime);
            $stmt->execute();
            
            // Mark attempts as blocked
            $updateQuery = "UPDATE failed_login_attempts 
                           SET is_blocked = 1 
                           WHERE ip_address = :ip 
                           AND attempt_time > datetime('now', '-1 hour')";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':ip', $ip);
            $updateStmt->execute();
            
            return true;
        } catch (PDOException $e) {
            error_log("Block IP Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Unblock an IP address (admin action)
     */
    public function unblockIP($ip) {
        try {
            $query = "DELETE FROM blocked_ips WHERE ip_address = :ip";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':ip', $ip);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get recent failed attempts
     */
    public function getRecentFailedAttempts($limit = 100, $offset = 0) {
        try {
            $query = "SELECT f.*, b.id as is_ip_blocked 
                     FROM failed_login_attempts f
                     LEFT JOIN blocked_ips b ON f.ip_address = b.ip_address
                     ORDER BY f.attempt_time DESC 
                     LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get all blocked IPs
     */
    public function getBlockedIPs() {
        try {
            $query = "SELECT * FROM blocked_ips 
                     WHERE unblock_at IS NULL OR unblock_at > datetime('now')
                     ORDER BY blocked_at DESC";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get total counts for dashboard
     */
    public function getSecurityStats() {
        try {
            $stats = [
                'total_attempts' => 0,
                'blocked_ips' => 0,
                'attempts_today' => 0,
                'attempts_this_week' => 0
            ];
            
            // Total attempts
            $query = "SELECT COUNT(*) as count FROM failed_login_attempts";
            $result = $this->conn->query($query)->fetch();
            $stats['total_attempts'] = $result['count'];
            
            // Blocked IPs
            $query = "SELECT COUNT(*) as count FROM blocked_ips 
                     WHERE unblock_at IS NULL OR unblock_at > datetime('now')";
            $result = $this->conn->query($query)->fetch();
            $stats['blocked_ips'] = $result['count'];
            
            // Today's attempts (SQLite compatible)
            $query = "SELECT COUNT(*) as count FROM failed_login_attempts 
                     WHERE DATE(attempt_time) = DATE('now')";
            $result = $this->conn->query($query)->fetch();
            $stats['attempts_today'] = $result['count'];
            
            // This week's attempts (SQLite compatible)
            $query = "SELECT COUNT(*) as count FROM failed_login_attempts 
                     WHERE attempt_time >= datetime('now', '-7 days')";
            $result = $this->conn->query($query)->fetch();
            $stats['attempts_this_week'] = $result['count'];
            
            return $stats;
        } catch (PDOException $e) {
            return [
                'total_attempts' => 0,
                'blocked_ips' => 0,
                'attempts_today' => 0,
                'attempts_this_week' => 0
            ];
        }
    }
    
    /**
     * Clean old records (optional maintenance function)
     */
    public function cleanOldRecords($daysToKeep = 30) {
        try {
            // Delete old failed attempts (SQLite compatible)
            $query = "DELETE FROM failed_login_attempts 
                     WHERE attempt_time < datetime('now', '-' || :days || ' days')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':days', $daysToKeep, PDO::PARAM_INT);
            $stmt->execute();
            
            // Delete expired blocks
            $query = "DELETE FROM blocked_ips 
                     WHERE unblock_at IS NOT NULL AND unblock_at < datetime('now')";
            $this->conn->query($query);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
