<?php
require_once 'auth.php';
require_once '../config/database.php';
require_once '../config/logger.php';

$db = new Database();
$conn = $db->getConnection();
$logger = new ActivityLogger($conn);

// Handle filters
$actionFilter = $_GET['action'] ?? '';
$adminFilter = $_GET['admin'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get logs
$logs = $logger->getRecentLogs($limit, $offset, $actionFilter, $adminFilter);
$totalLogs = $logger->getTotalCount($actionFilter, $adminFilter);
$totalPages = ceil($totalLogs / $limit);

// Get action types for filter
$actionsQuery = "SELECT DISTINCT action FROM activity_logs ORDER BY action";
$actionsStmt = $conn->query($actionsQuery);
$actions = $actionsStmt->fetchAll(PDO::FETCH_COLUMN);

// Get admin usernames for filter
$adminsQuery = "SELECT DISTINCT admin_username FROM activity_logs ORDER BY admin_username";
$adminsStmt = $conn->query($adminsQuery);
$admins = $adminsStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - CyberCon Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .log-icon {
            width: 30px;
            text-align: center;
        }
        .log-action-login { color: #0d6efd; }
        .log-action-logout { color: #6c757d; }
        .log-action-create { color: #198754; }
        .log-action-update { color: #ffc107; }
        .log-action-delete { color: #dc3545; }
        .log-action-view { color: #0dcaf0; }
        .log-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h3><i class="fas fa-history"></i> Activity Logs</h3>
            <div class="top-bar-actions">
                <span class="badge bg-info">
                    <?php echo number_format($totalLogs); ?> Total Logs
                </span>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Action Type</label>
                        <select name="action" class="form-select">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo htmlspecialchars($action); ?>" 
                                        <?php echo $actionFilter === $action ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Admin User</label>
                        <select name="admin" class="form-select">
                            <option value="">All Admins</option>
                            <?php foreach ($admins as $admin): ?>
                                <option value="<?php echo htmlspecialchars($admin); ?>" 
                                        <?php echo $adminFilter === $admin ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($admin); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="logs.php" class="btn btn-secondary w-100">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Activity Log Entries</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;"></th>
                                <th>Action</th>
                                <th>Admin</th>
                                <th>Description</th>
                                <th>Entity</th>
                                <th>IP Address</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No activity logs found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php
                                    // Determine icon based on action
                                    $icon = 'fa-circle';
                                    $iconClass = 'text-secondary';
                                    
                                    if (stripos($log['action'], 'login') !== false) {
                                        $icon = 'fa-sign-in-alt';
                                        $iconClass = 'log-action-login';
                                    } elseif (stripos($log['action'], 'logout') !== false) {
                                        $icon = 'fa-sign-out-alt';
                                        $iconClass = 'log-action-logout';
                                    } elseif (stripos($log['action'], 'create') !== false || stripos($log['action'], 'add') !== false) {
                                        $icon = 'fa-plus-circle';
                                        $iconClass = 'log-action-create';
                                    } elseif (stripos($log['action'], 'update') !== false || stripos($log['action'], 'edit') !== false) {
                                        $icon = 'fa-edit';
                                        $iconClass = 'log-action-update';
                                    } elseif (stripos($log['action'], 'delete') !== false) {
                                        $icon = 'fa-trash';
                                        $iconClass = 'log-action-delete';
                                    } elseif (stripos($log['action'], 'view') !== false) {
                                        $icon = 'fa-eye';
                                        $iconClass = 'log-action-view';
                                    } elseif (stripos($log['action'], 'export') !== false) {
                                        $icon = 'fa-download';
                                        $iconClass = 'text-success';
                                    }
                                    ?>
                                    <tr style="cursor: pointer;" onclick="showLogDetails(<?php echo htmlspecialchars(json_encode($log), ENT_QUOTES); ?>)">
                                        <td class="log-icon">
                                            <i class="fas <?php echo $icon; ?> <?php echo $iconClass; ?>"></i>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($log['action']); ?></strong></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($log['admin_username']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['description'] ?: '-'); ?></td>
                                        <td>
                                            <?php if ($log['entity_type']): ?>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($log['entity_type']); ?>
                                                    <?php if ($log['entity_id']): ?>
                                                        #<?php echo $log['entity_id']; ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                                        <td class="log-time">
                                            <?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&action=<?php echo urlencode($actionFilter); ?>&admin=<?php echo urlencode($adminFilter); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || abs($i - $page) <= 2): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&action=<?php echo urlencode($actionFilter); ?>&admin=<?php echo urlencode($adminFilter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&action=<?php echo urlencode($actionFilter); ?>&admin=<?php echo urlencode($adminFilter); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Log Details Modal -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle"></i> Log Entry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Log ID</label>
                            <div id="detail-id" class="fw-bold"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Action Type</label>
                            <div id="detail-action" class="fw-bold"></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Admin User</label>
                            <div id="detail-admin"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Admin ID</label>
                            <div id="detail-admin-id"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small">Description</label>
                        <div id="detail-description" class="alert alert-light"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Entity Type</label>
                            <div id="detail-entity-type"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Entity ID</label>
                            <div id="detail-entity-id"></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">IP Address</label>
                            <div id="detail-ip" class="font-monospace"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Timestamp</label>
                            <div id="detail-timestamp"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small">User Agent</label>
                        <div id="detail-user-agent" class="small text-break" style="max-height: 100px; overflow-y: auto;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showLogDetails(log) {
            // Populate modal with log details
            document.getElementById('detail-id').textContent = '#' + log.id;
            document.getElementById('detail-action').textContent = log.action;
            document.getElementById('detail-admin').innerHTML = '<span class="badge bg-secondary">' + log.admin_username + '</span>';
            document.getElementById('detail-admin-id').textContent = log.admin_id || 'N/A';
            document.getElementById('detail-description').textContent = log.description || 'No description available';
            document.getElementById('detail-entity-type').textContent = log.entity_type || 'N/A';
            document.getElementById('detail-entity-id').textContent = log.entity_id || 'N/A';
            document.getElementById('detail-ip').textContent = log.ip_address;
            document.getElementById('detail-user-agent').textContent = log.user_agent || 'N/A';
            
            // Format timestamp
            const date = new Date(log.created_at);
            document.getElementById('detail-timestamp').textContent = date.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            // Show modal
            var modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
            modal.show();
        }
    </script>
    
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
