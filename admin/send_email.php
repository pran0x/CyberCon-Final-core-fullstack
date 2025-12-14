<?php
require_once 'auth.php';
require_once '../config/database.php';
require_once '../config/email.php';

// Only super admin can send emails
if (!isSuperAdmin() && !function_exists('isSuperAdmin')) {
    header('Location: dashboard.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$emailHandler = new EmailHandler();

$successMessage = '';
$errorMessage = '';
$infoMessage = '';

// Check email configuration status
$emailConfigStatus = $emailHandler->getConfigStatus();
if ($emailConfigStatus['mode'] === 'file') {
    $infoMessage = '<strong>Testing Mode:</strong> Emails will be saved to files in <code>/admin/sent_emails/</code> folder. 
                    <a href="../config/EMAIL_SETUP_GUIDE.md" target="_blank" class="alert-link">View Configuration Guide</a>';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $recipients = $_POST['recipients'] ?? [];
    $subject = trim($_POST['subject'] ?? '');
    $body = $_POST['body'] ?? '';
    $sendType = $_POST['send_type'] ?? 'selected';
    
    if (empty($subject) || empty($body)) {
        $errorMessage = 'Subject and message body are required!';
    } else {
        $recipientList = [];
        
        if ($sendType === 'all') {
            // Get all registrations
            $stmt = $conn->query("SELECT id, full_name, email, ticket_id, student_id, payment_method, transaction_id FROM registrations");
            $recipientList = $stmt->fetchAll();
        } elseif ($sendType === 'status') {
            $status = $_POST['filter_status'] ?? 'confirmed';
            $stmt = $conn->prepare("SELECT id, full_name, email, ticket_id, student_id, payment_method, transaction_id FROM registrations WHERE status = :status");
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            $recipientList = $stmt->fetchAll();
        } elseif ($sendType === 'batch') {
            $batch = $_POST['filter_batch'] ?? '';
            $stmt = $conn->prepare("SELECT id, full_name, email, ticket_id, student_id, payment_method, transaction_id FROM registrations WHERE batch = :batch");
            $stmt->bindParam(':batch', $batch);
            $stmt->execute();
            $recipientList = $stmt->fetchAll();
        } elseif ($sendType === 'department') {
            $dept = $_POST['filter_dept'] ?? '';
            $stmt = $conn->prepare("SELECT id, full_name, email, ticket_id, student_id, payment_method, transaction_id FROM registrations WHERE department = :dept");
            $stmt->bindParam(':dept', $dept);
            $stmt->execute();
            $recipientList = $stmt->fetchAll();
        } else {
            // Selected recipients
            if (!empty($recipients)) {
                $placeholders = str_repeat('?,', count($recipients) - 1) . '?';
                $stmt = $conn->prepare("SELECT id, full_name, email, ticket_id, student_id, payment_method, transaction_id FROM registrations WHERE id IN ($placeholders)");
                $stmt->execute($recipients);
                $recipientList = $stmt->fetchAll();
            }
        }
        
        if (empty($recipientList)) {
            $errorMessage = 'No recipients selected or found!';
        } else {
            // Prepare recipient data
            $emailRecipients = array_map(function($reg) {
                return [
                    'email' => $reg['email'],
                    'name' => $reg['full_name'],
                    'full_name' => $reg['full_name'],
                    'ticket_id' => $reg['ticket_id'],
                    'student_id' => $reg['student_id'],
                    'payment_method' => $reg['payment_method'] ?? 'N/A',
                    'transaction_id' => $reg['transaction_id'] ?? 'N/A'
                ];
            }, $recipientList);
            
            // Send emails
            $results = $emailHandler->sendBulkEmail($emailRecipients, $subject, $body, true);
            
            if ($results['success'] > 0) {
                // Log to database
                try {
                    $logStmt = $conn->prepare("INSERT INTO email_logs (recipients_count, subject, body, sent_by, recipients, send_type) 
                                               VALUES (:count, :subject, :body, :sent_by, :recipients, :send_type)");
                    $logStmt->execute([
                        ':count' => $results['success'],
                        ':subject' => $subject,
                        ':body' => $body,
                        ':sent_by' => $_SESSION['admin_username'],
                        ':recipients' => json_encode(array_column($emailRecipients, 'email')),
                        ':send_type' => $sendType
                    ]);
                } catch (PDOException $e) {
                    // Table might not exist yet, ignore error
                }
                
                $successMessage = "Successfully sent {$results['success']} email(s)!";
                if ($results['failed'] > 0) {
                    $successMessage .= " {$results['failed']} failed.";
                }
            } else {
                $errorMessage = "Failed to send emails. Please check your email configuration.";
            }
        }
    }
}

// Get all registrations for selection
$registrations = $conn->query("SELECT id, ticket_id, full_name, email, status, batch, department FROM registrations ORDER BY created_at DESC")->fetchAll();

// Get filter options
$batches = $conn->query("SELECT DISTINCT batch FROM registrations WHERE batch IS NOT NULL AND batch != '' ORDER BY batch")->fetchAll(PDO::FETCH_COLUMN);
$departments = $conn->query("SELECT DISTINCT department FROM registrations WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

// Get templates
$templates = $emailHandler->getTemplates();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Email - CyberCon Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .template-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .template-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .recipient-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .recipient-item:hover {
            background-color: #f8f9fa;
        }
        .preview-box {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            background-color: #f8f9fa;
            max-height: 400px;
            overflow-y: auto;
        }
        .variable-tag {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.9em;
            cursor: pointer;
            margin: 2px;
        }
        .variable-tag:hover {
            background: #bbdefb;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h3><i class="fas fa-envelope"></i> Send Email to Participants</h3>
        </div>
        
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
                <?php if ($emailConfigStatus['mode'] === 'file'): ?>
                    <hr>
                    <small><i class="fas fa-info-circle"></i> Emails saved to: <code>/admin/sent_emails/</code></small>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($infoMessage): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <i class="fas fa-info-circle"></i> <?php echo $infoMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Email Templates -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-file-alt"></i> Email Templates</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($templates as $key => $template): ?>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card template-card h-100" onclick="loadTemplate('<?php echo $key; ?>')">
                                <div class="card-body text-center">
                                    <i class="fas fa-envelope-open-text fa-3x text-primary mb-3"></i>
                                    <h6><?php echo htmlspecialchars($template['name']); ?></h6>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Email Composer -->
        <form method="POST" id="emailForm">
            <input type="hidden" name="send_email" value="1">
            
            <div class="row">
                <!-- Left Column - Recipients -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Recipients</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><strong>Send To:</strong></label>
                                <select class="form-select" name="send_type" id="sendType" onchange="toggleRecipientOptions()">
                                    <option value="selected">Selected Recipients</option>
                                    <option value="all">All Registrations</option>
                                    <option value="status">By Status</option>
                                    <option value="batch">By Batch</option>
                                    <option value="department">By Department</option>
                                </select>
                            </div>
                            
                            <!-- Filter by Status -->
                            <div class="mb-3" id="statusFilter" style="display: none;">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="filter_status">
                                    <option value="confirmed">Confirmed</option>
                                    <option value="pending">Pending</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            
                            <!-- Filter by Batch -->
                            <div class="mb-3" id="batchFilter" style="display: none;">
                                <label class="form-label">Batch</label>
                                <select class="form-select" name="filter_batch">
                                    <?php foreach ($batches as $batch): ?>
                                        <option value="<?php echo htmlspecialchars($batch); ?>"><?php echo htmlspecialchars($batch); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Filter by Department -->
                            <div class="mb-3" id="deptFilter" style="display: none;">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="filter_dept">
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Individual Selection -->
                            <div id="recipientList">
                                <div class="mb-2">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    <label for="selectAll"><strong>Select All</strong></label>
                                </div>
                                <div class="mb-3">
                                    <input type="text" class="form-control form-control-sm" id="searchRecipients" 
                                           placeholder="🔍 Search by name, email, ticket ID..." 
                                           onkeyup="filterRecipients()">
                                </div>
                                <div id="recipientContainer" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; padding: 10px;">
                                    <?php foreach ($registrations as $reg): ?>
                                        <div class="recipient-item" data-name="<?php echo strtolower(htmlspecialchars($reg['full_name'])); ?>" 
                                             data-email="<?php echo strtolower(htmlspecialchars($reg['email'])); ?>" 
                                             data-ticket="<?php echo strtolower(htmlspecialchars($reg['ticket_id'])); ?>"
                                             data-batch="<?php echo strtolower(htmlspecialchars($reg['batch'] ?? '')); ?>"
                                             data-dept="<?php echo strtolower(htmlspecialchars($reg['department'] ?? '')); ?>">
                                            <input type="checkbox" class="recipient-checkbox" name="recipients[]" value="<?php echo $reg['id']; ?>" id="rec_<?php echo $reg['id']; ?>">
                                            <label for="rec_<?php echo $reg['id']; ?>" style="cursor: pointer; margin-left: 5px;">
                                                <strong><?php echo htmlspecialchars($reg['full_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($reg['email']); ?></small><br>
                                                <small>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($reg['ticket_id']); ?></span>
                                                    <span class="badge bg-<?php echo $reg['status'] === 'confirmed' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($reg['status']); ?>
                                                    </span>
                                                    <?php if ($reg['batch']): ?>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($reg['batch']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($reg['department']): ?>
                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($reg['department']); ?></span>
                                                    <?php endif; ?>
                                                </small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted" id="recipientCount">Showing <?php echo count($registrations); ?> recipients</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Email Content -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-edit"></i> Compose Email</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><strong>Subject *</strong></label>
                                <input type="text" class="form-control" name="subject" id="emailSubject" required placeholder="Email subject...">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Available Variables:</strong></label>
                                <div>
                                    <span class="variable-tag" onclick="insertVariable('{name}')">{name}</span>
                                    <span class="variable-tag" onclick="insertVariable('{full_name}')">{full_name}</span>
                                    <span class="variable-tag" onclick="insertVariable('{email}')">{email}</span>
                                    <span class="variable-tag" onclick="insertVariable('{ticket_id}')">{ticket_id}</span>
                                    <span class="variable-tag" onclick="insertVariable('{student_id}')">{student_id}</span>
                                    <span class="variable-tag" onclick="insertVariable('{payment_method}')">{payment_method}</span>
                                    <span class="variable-tag" onclick="insertVariable('{transaction_id}')">{transaction_id}</span>
                                </div>
                                <small class="text-muted">Click to insert variable into message</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Message Body *</strong></label>
                                <textarea class="form-control" name="body" id="emailBody" rows="15" required placeholder="Email message body (HTML supported)..."></textarea>
                                <small class="text-muted">HTML tags are supported. Use variables for personalization.</small>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary" onclick="previewEmail()">
                                    <i class="fas fa-eye"></i> Preview Email
                                </button>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Are you sure you want to send this email?')">
                                    <i class="fas fa-paper-plane"></i> Send Email
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye"></i> Email Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="preview-box" id="previewContent"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const templates = <?php echo json_encode($templates); ?>;
        
        function loadTemplate(templateKey) {
            const template = templates[templateKey];
            document.getElementById('emailSubject').value = template.subject;
            document.getElementById('emailBody').value = template.body;
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.recipient-checkbox');
            checkboxes.forEach(cb => {
                if (cb.closest('.recipient-item').style.display !== 'none') {
                    cb.checked = selectAll.checked;
                }
            });
        }
        
        function filterRecipients() {
            const searchTerm = document.getElementById('searchRecipients').value.toLowerCase();
            const items = document.querySelectorAll('.recipient-item');
            let visibleCount = 0;
            
            items.forEach(item => {
                const name = item.getAttribute('data-name') || '';
                const email = item.getAttribute('data-email') || '';
                const ticket = item.getAttribute('data-ticket') || '';
                const batch = item.getAttribute('data-batch') || '';
                const dept = item.getAttribute('data-dept') || '';
                
                const matches = name.includes(searchTerm) || 
                               email.includes(searchTerm) || 
                               ticket.includes(searchTerm) ||
                               batch.includes(searchTerm) ||
                               dept.includes(searchTerm);
                
                if (matches) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    // Uncheck hidden items
                    const checkbox = item.querySelector('.recipient-checkbox');
                    if (checkbox) checkbox.checked = false;
                }
            });
            
            // Update count
            document.getElementById('recipientCount').textContent = 
                visibleCount === <?php echo count($registrations); ?> 
                ? 'Showing ' + visibleCount + ' recipients' 
                : 'Showing ' + visibleCount + ' of <?php echo count($registrations); ?> recipients';
            
            // Update select all checkbox
            document.getElementById('selectAll').checked = false;
        }
        
        function toggleRecipientOptions() {
            const sendType = document.getElementById('sendType').value;
            const recipientList = document.getElementById('recipientList');
            const statusFilter = document.getElementById('statusFilter');
            const batchFilter = document.getElementById('batchFilter');
            const deptFilter = document.getElementById('deptFilter');
            
            recipientList.style.display = sendType === 'selected' ? 'block' : 'none';
            statusFilter.style.display = sendType === 'status' ? 'block' : 'none';
            batchFilter.style.display = sendType === 'batch' ? 'block' : 'none';
            deptFilter.style.display = sendType === 'department' ? 'block' : 'none';
        }
        
        function insertVariable(variable) {
            const textarea = document.getElementById('emailBody');
            const cursorPos = textarea.selectionStart;
            const textBefore = textarea.value.substring(0, cursorPos);
            const textAfter = textarea.value.substring(cursorPos);
            textarea.value = textBefore + variable + textAfter;
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = cursorPos + variable.length;
        }
        
        function previewEmail() {
            const subject = document.getElementById('emailSubject').value;
            const body = document.getElementById('emailBody').value;
            
            // Replace variables with sample data
            let preview = body;
            preview = preview.replace(/{name}/g, 'John Doe');
            preview = preview.replace(/{full_name}/g, 'John Doe');
            preview = preview.replace(/{email}/g, 'john.doe@uttarauniversity.edu.bd');
            preview = preview.replace(/{ticket_id}/g, '1234');
            preview = preview.replace(/{student_id}/g, '22330801234');
            preview = preview.replace(/{payment_method}/g, 'Bkash');
            preview = preview.replace(/{transaction_id}/g, 'TXN123456');
            
            document.getElementById('previewContent').innerHTML = preview;
            
            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
        }
    </script>
</body>
</html>
