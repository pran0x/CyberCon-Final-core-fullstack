<?php
/**
 * Login Security Test Script
 * Tests the security features of the login system
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

echo "=== CyberCon Login Security Test ===\n\n";

// Initialize
$db = new Database();
$conn = $db->getConnection();
$security = new SecurityLogger($conn);

// Test 1: Get Client IP
echo "Test 1: IP Detection\n";
echo "---------------------\n";
$testIP = $security->getClientIP();
echo "Detected IP: " . $testIP . "\n\n";

// Test 2: Check if IP is blocked
echo "Test 2: IP Block Status\n";
echo "-----------------------\n";
$isBlocked = $security->isIPBlocked($testIP);
echo "Is IP blocked? " . ($isBlocked ? "YES" : "NO") . "\n\n";

// Test 3: Get failed attempt count
echo "Test 3: Failed Attempt Count\n";
echo "----------------------------\n";
$attemptCount = $security->getFailedAttemptCount($testIP);
echo "Failed attempts in last hour: " . $attemptCount . "\n\n";

// Test 4: Simulate failed login attempts
echo "Test 4: Simulating Failed Login Attempts\n";
echo "----------------------------------------\n";

$testUsername = "test_user";
$testPassword = "wrong_password";

for ($i = 1; $i <= 4; $i++) {
    echo "Attempt {$i}: ";
    
    if ($security->isIPBlocked($testIP)) {
        echo "BLOCKED - Cannot attempt login\n";
        break;
    }
    
    $wasBlocked = $security->logFailedAttempt($testIP, $testUsername . $i, $testPassword . $i);
    
    if ($wasBlocked) {
        echo "BLOCKED after this attempt\n";
        break;
    } else {
        $remaining = 3 - $security->getFailedAttemptCount($testIP);
        echo "Failed - {$remaining} attempts remaining\n";
    }
}

echo "\n";

// Test 5: Check security stats
echo "Test 5: Security Statistics\n";
echo "---------------------------\n";
$stats = $security->getSecurityStats();
echo "Total failed attempts: " . $stats['total_attempts'] . "\n";
echo "Currently blocked IPs: " . $stats['blocked_ips'] . "\n";
echo "Attempts today: " . $stats['attempts_today'] . "\n";
echo "Attempts this week: " . $stats['attempts_this_week'] . "\n\n";

// Test 6: Get recent failed attempts
echo "Test 6: Recent Failed Attempts (Last 5)\n";
echo "---------------------------------------\n";
$attempts = $security->getRecentFailedAttempts(5, 0);
foreach ($attempts as $attempt) {
    echo sprintf(
        "%s | IP: %s | User: %s | Location: %s, %s | %s\n",
        $attempt['attempt_time'],
        $attempt['ip_address'],
        $attempt['username_attempted'],
        $attempt['city'],
        $attempt['country'],
        ($attempt['is_ip_blocked'] ? 'BLOCKED' : 'Not Blocked')
    );
}

echo "\n";

// Test 7: Get blocked IPs
echo "Test 7: Currently Blocked IPs\n";
echo "-----------------------------\n";
$blockedIPs = $security->getBlockedIPs();
if (count($blockedIPs) > 0) {
    foreach ($blockedIPs as $blocked) {
        echo sprintf(
            "IP: %s | Blocked: %s | Reason: %s\n",
            $blocked['ip_address'],
            $blocked['blocked_at'],
            $blocked['reason']
        );
    }
} else {
    echo "No IPs currently blocked\n";
}

echo "\n";

// Test 8: Test SQL Injection Protection
echo "Test 8: SQL Injection Protection Test\n";
echo "-------------------------------------\n";
$maliciousInputs = [
    "admin' OR '1'='1",
    "admin'--",
    "admin' #",
    "' OR 1=1--",
    "admin'; DROP TABLE admins--"
];

echo "Testing SQL injection attempts (these should all fail safely):\n";
foreach ($maliciousInputs as $input) {
    try {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = :username");
        $stmt->bindParam(':username', $input, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch();
        echo "✓ Input: " . substr($input, 0, 30) . "... -> " . ($result ? "FOUND (WARNING!)" : "Not found (Safe)") . "\n";
    } catch (Exception $e) {
        echo "✓ Input: " . substr($input, 0, 30) . "... -> Error caught (Safe)\n";
    }
}

echo "\n";

// Test 9: Cleanup test - Unblock test IP
echo "Test 9: Cleanup - Unblock Test IP\n";
echo "---------------------------------\n";
if ($security->unblockIP($testIP)) {
    echo "✓ Test IP unblocked successfully\n";
} else {
    echo "✗ Failed to unblock (may not have been blocked)\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test in browser:\n";
echo "1. Navigate to: http://localhost/admin/login.php\n";
echo "2. Try logging in with wrong credentials 3 times\n";
echo "3. Check if IP gets blocked\n";
echo "4. View security logs at: http://localhost/admin/security_logs.php\n";
