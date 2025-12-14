<?php
require_once 'auth.php';
require_once '../config/database.php';

// Only super admin can access
if (!isSuperAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Check if email_logs table exists, if not create it
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS email_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        recipients_count INTEGER NOT NULL,
        subject TEXT NOT NULL,
        body TEXT NOT NULL,
        sent_by VARCHAR(100) NOT NULL,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        recipients TEXT,
        status VARCHAR(20) DEFAULT 'sent',
        send_type VARCHAR(50)
    )");
} catch (PDOException $e) {
    // Table already exists or error
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get sent emails from database
$countStmt = $conn->query("SELECT COUNT(*) as total FROM email_logs");
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

$stmt = $conn->prepare("SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$emails = $stmt->fetchAll();

// Also check for saved email files
$emailFiles = [];
$emailDir = __DIR__ . '/sent_emails';
if (is_dir($emailDir)) {
    $files = glob($emailDir . '/email_*.html');
    rsort($files); // Most recent first
    $emailFiles = array_slice($files, 0, 50); // Last 50 files
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $deleteStmt = $conn->prepare("DELETE FROM email_logs WHERE id = :id");
    $deleteStmt->bindParam(':id', $deleteId, PDO::PARAM_INT);
    $deleteStmt->execute();
    header('Location: email_history.php?deleted=1');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email History - CyberCon Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .email-preview {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .email-item {
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        .email-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        .file-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        .file-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h3><i class="fas fa-history"></i> Email History</h3>
            <div class="top-bar-actions">
                <a href="send_email.php" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send New Email
                </a>
            </div>
        </div>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> Email record deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Email Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                        <h3><?php echo $totalRecords; ?></h3>
                        <p class="text-muted">Total Emails Sent</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x text-success mb-3"></i>
                        <h3>
                            <?php 
                            $recipientsStmt = $conn->query("SELECT SUM(recipients_count) as total FROM email_logs");
                            echo $recipientsStmt->fetch()['total'] ?? 0;
                            ?>
                        </h3>
                        <p class="text-muted">Total Recipients</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-file fa-3x text-info mb-3"></i>
                        <h3><?php echo count($emailFiles); ?></h3>
                        <p class="text-muted">Saved Files</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#database">
                    <i class="fas fa-database"></i> Sent Emails
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#files">
                    <i class="fas fa-folder"></i> Saved Files (Testing Mode)
                </a>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- Database Records Tab -->
            <div id="database" class="tab-pane fade show active">
                <?php if (empty($emails)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No emails sent yet</h5>
                            <p class="text-muted">Emails will appear here after you send them</p>
                            <a href="send_email.php" class="btn btn-primary mt-3">
                                <i class="fas fa-paper-plane"></i> Send Your First Email
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($emails as $email): ?>
                        <div class="card mb-3 email-item">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-0">
                                            <i class="fas fa-envelope"></i> 
                                            <?php echo htmlspecialchars($email['subject']); ?>
                                        </h5>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button class="btn btn-sm btn-info" onclick="viewEmail(<?php echo $email['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteEmail(<?php echo $email['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> Sent by: <strong><?php echo htmlspecialchars($email['sent_by']); ?></strong>
                                        </small>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($email['sent_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <small>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-users"></i> <?php echo $email['recipients_count']; ?> Recipients
                                            </span>
                                            <?php if ($email['send_type']): ?>
                                                <span class="badge bg-secondary">
                                                    <?php echo ucfirst(str_replace('_', ' ', $email['send_type'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Hidden preview -->
                                <div id="preview-<?php echo $email['id']; ?>" style="display: none;" class="mt-3">
                                    <hr>
                                    <div class="email-preview">
                                        <?php echo $email['body']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Files Tab -->
            <div id="files" class="tab-pane fade">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-folder"></i> Saved Email Files</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($emailFiles)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                                <p class="text-muted">No saved email files found</p>
                                <small class="text-muted">Files appear here when using File Fallback mode</small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                These are emails saved during testing (File Fallback mode). 
                                Click to open in a new tab and preview.
                            </div>
                            <div style="max-height: 600px; overflow-y: auto;">
                                <?php foreach ($emailFiles as $file): ?>
                                    <?php
                                    $filename = basename($file);
                                    $fileSize = filesize($file);
                                    $fileTime = filemtime($file);
                                    
                                    // Extract recipient from file
                                    $content = file_get_contents($file, false, null, 0, 500);
                                    preg_match('/To: (.+)/i', $content, $matches);
                                    $recipient = $matches[1] ?? 'Unknown';
                                    preg_match('/Subject: (.+)/i', $content, $matches);
                                    $subject = $matches[1] ?? 'No Subject';
                                    ?>
                                    <div class="file-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <i class="fas fa-file-code text-info"></i>
                                                <strong><?php echo htmlspecialchars($subject); ?></strong>
                                                <br>
                                                <small class="text-muted">To: <?php echo htmlspecialchars($recipient); ?></small>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('M d, Y h:i A', $fileTime); ?>
                                                </small>
                                                <br>
                                                <small class="text-muted"><?php echo number_format($fileSize / 1024, 2); ?> KB</small>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <a href="sent_emails/<?php echo $filename; ?>" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="fas fa-external-link-alt"></i> Open
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewEmail(id) {
            const preview = document.getElementById('preview-' + id);
            if (preview.style.display === 'none') {
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }
        
        function deleteEmail(id) {
            if (confirm('Are you sure you want to delete this email record?')) {
                window.location.href = '?delete=' + id;
            }
        }
    </script>
</body>
</html>
