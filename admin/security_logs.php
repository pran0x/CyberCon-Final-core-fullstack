<?php
require_once 'auth.php';
require_once '../config/database.php';
require_once '../config/security.php';

$db = new Database();
$conn = $db->getConnection();
$security = new SecurityLogger($conn);

// Only super admins can view security logs
if (!isSuperAdmin()) {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

$success = $error = '';

// Handle IP unblock
if (isset($_GET['unblock'])) {
    $ipToUnblock = $_GET['unblock'];
    if ($security->unblockIP($ipToUnblock)) {
        $success = "IP address {$ipToUnblock} has been unblocked successfully.";
    } else {
        $error = "Failed to unblock IP address.";
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Get security stats
$stats = $security->getSecurityStats();

// Get failed attempts
$attempts = $security->getRecentFailedAttempts($perPage, $offset);

// Get blocked IPs
$blockedIPs = $security->getBlockedIPs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Logs - CyberCon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .security-stat {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .security-stat h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        .security-stat p {
            margin: 0;
            opacity: 0.9;
        }
        .blocked-badge {
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
        }
        .attempt-row:hover {
            background-color: #f8f9fa;
        }
        .password-cell {
            font-family: monospace;
            font-size: 0.9rem;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar mb-4">
            <h3><i class="fas fa-shield-alt"></i> Security Logs</h3>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Security Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="security-stat">
                    <h3><?php echo $stats['total_attempts']; ?></h3>
                    <p><i class="fas fa-exclamation-triangle"></i> Total Failed Attempts</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="security-stat" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h3><?php echo $stats['blocked_ips']; ?></h3>
                    <p><i class="fas fa-ban"></i> Blocked IPs</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="security-stat" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <h3><?php echo $stats['attempts_today']; ?></h3>
                    <p><i class="fas fa-calendar-day"></i> Attempts Today</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="security-stat" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <h3><?php echo $stats['attempts_this_week']; ?></h3>
                    <p><i class="fas fa-calendar-week"></i> Attempts This Week</p>
                </div>
            </div>
        </div>
        
        <!-- Blocked IPs Section -->
        <?php if (count($blockedIPs) > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5><i class="fas fa-ban"></i> Currently Blocked IP Addresses</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Blocked At</th>
                                <th>Unblock At</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blockedIPs as $blocked): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($blocked['ip_address']); ?></code></td>
                                    <td><?php echo date('M j, Y H:i:s', strtotime($blocked['blocked_at'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($blocked['unblock_at']) {
                                            echo date('M j, Y H:i:s', strtotime($blocked['unblock_at']));
                                        } else {
                                            echo '<span class="badge bg-warning">Permanent</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($blocked['reason']); ?></td>
                                    <td>
                                        <a href="?unblock=<?php echo urlencode($blocked['ip_address']); ?>" 
                                           class="btn btn-sm btn-success" 
                                           onclick="return confirm('Are you sure you want to unblock this IP?')">
                                            <i class="fas fa-unlock"></i> Unblock
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Failed Login Attempts -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Failed Login Attempts Log</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>IP Address</th>
                                <th>Username</th>
                                <th>Password Attempted</th>
                                <th>Location</th>
                                <th>User Agent</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($attempts) > 0): ?>
                                <?php foreach ($attempts as $attempt): ?>
                                    <tr class="attempt-row">
                                        <td>
                                            <small><?php echo date('M j, Y H:i:s', strtotime($attempt['attempt_time'])); ?></small>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($attempt['ip_address']); ?></code>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($attempt['username_attempted']); ?></strong>
                                        </td>
                                        <td class="password-cell" title="<?php echo htmlspecialchars($attempt['password_attempted']); ?>">
                                            <code><?php echo htmlspecialchars(substr($attempt['password_attempted'], 0, 20)) . (strlen($attempt['password_attempted']) > 20 ? '...' : ''); ?></code>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($attempt['city'] . ', ' . $attempt['country']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small style="max-width: 200px; display: block; overflow: hidden; text-overflow: ellipsis;" 
                                                   title="<?php echo htmlspecialchars($attempt['user_agent']); ?>">
                                                <?php echo htmlspecialchars(substr($attempt['user_agent'], 0, 50)); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($attempt['is_ip_blocked']): ?>
                                                <span class="blocked-badge">
                                                    <i class="fas fa-ban"></i> BLOCKED
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                                        <p class="mt-2 mb-0">No failed login attempts recorded.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if (count($attempts) >= $perPage): ?>
        <div class="mt-3">
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="page-item active">
                        <span class="page-link">Page <?php echo $page; ?></span>
                    </li>
                    
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Footer -->
    <footer class="text-center py-3 mt-4" style="background: #f8f9fa; border-top: 1px solid #dee2e6;">
        <div class="container">
            <p class="mb-0 text-muted small">
                Developed by <a href="https://linkedin.com/in/pran0x" target="_blank" class="text-decoration-none"><strong>pran0x</strong></a> | 
                All rights reserved by <strong>Cyber Security Club, Uttara University</strong>
            </p>
        </div>
    </footer>
</body>
</html>
