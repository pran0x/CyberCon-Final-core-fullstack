<?php
require_once 'auth.php';
require_once '../config/email.php';

// Only super admin can access
if (!isSuperAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$emailHandler = new EmailHandler();
$testResult = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $testEmail = trim($_POST['test_email']);
    
    $subject = 'CyberCon Email Test - ' . date('Y-m-d H:i:s');
    $body = '<h2>Hello!</h2>
             <p>This is a <strong>test email</strong> from the CyberCon email system.</p>
             <div class="info-box">
                 <strong>Test Information:</strong><br>
                 Sent At: ' . date('Y-m-d H:i:s') . '<br>
                 Test Email: ' . htmlspecialchars($testEmail) . '<br>
                 Server: ' . $_SERVER['SERVER_NAME'] . '
             </div>
             <p>If you received this email, your email configuration is working correctly!</p>
             <p>Best regards,<br><strong>CyberCon Email System</strong></p>';
    
    if ($emailHandler->sendEmail($testEmail, $subject, $body, true)) {
        $configStatus = $emailHandler->getConfigStatus();
        if ($configStatus['mode'] === 'file') {
            $testResult = '<div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Test email saved successfully!<br>
                <small>Check <code>/admin/sent_emails/</code> folder for the HTML file.</small>
            </div>';
        } else {
            $testResult = '<div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Test email sent successfully to ' . htmlspecialchars($testEmail) . '!
            </div>';
        }
    } else {
        $testResult = '<div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> Failed to send test email. Please check configuration.
        </div>';
    }
}

$configStatus = $emailHandler->getConfigStatus();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - CyberCon Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar">
            <h3><i class="fas fa-flask"></i> Email Configuration Test</h3>
        </div>
        
        <?php if ($testResult): ?>
            <?php echo $testResult; ?>
        <?php endif; ?>
        
        <!-- Configuration Status -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-cog"></i> Current Configuration</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">Mode</th>
                        <td>
                            <?php if ($configStatus['mode'] === 'file'): ?>
                                <span class="badge bg-warning">File Fallback (Testing)</span>
                            <?php elseif ($configStatus['mode'] === 'smtp'): ?>
                                <span class="badge bg-success">SMTP</span>
                            <?php else: ?>
                                <span class="badge bg-primary">PHP mail()</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Details</th>
                        <td><?php echo htmlspecialchars($configStatus['details']); ?></td>
                    </tr>
                    <tr>
                        <th>Configuration File</th>
                        <td><code>/config/email.php</code></td>
                    </tr>
                    <?php if ($configStatus['mode'] === 'file'): ?>
                    <tr>
                        <th>Sent Emails Location</th>
                        <td><code>/admin/sent_emails/</code></td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Configuration Guide:</strong> 
                    <a href="../config/EMAIL_SETUP_GUIDE.md" target="_blank" class="alert-link">View EMAIL_SETUP_GUIDE.md</a>
                </div>
            </div>
        </div>
        
        <!-- Test Email Form -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-paper-plane"></i> Send Test Email</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><strong>Test Email Address:</strong></label>
                        <input type="email" class="form-control" name="test_email" required 
                               placeholder="your-email@example.com" 
                               value="<?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?>">
                        <small class="form-text text-muted">
                            Enter your email address to receive a test email
                        </small>
                    </div>
                    
                    <?php if ($configStatus['mode'] === 'file'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Testing Mode Active:</strong> Email will be saved to <code>/admin/sent_emails/</code> folder instead of being sent.
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" name="send_test" class="btn btn-success btn-lg">
                        <i class="fas fa-paper-plane"></i> Send Test Email
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Quick Setup Guide -->
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-book"></i> Quick Setup Guide</h5>
            </div>
            <div class="card-body">
                <h6><strong>For Testing (Current Setup):</strong></h6>
                <ol>
                    <li>Leave current configuration (file fallback enabled)</li>
                    <li>Send test email above</li>
                    <li>Open the HTML file from <code>/admin/sent_emails/</code> in your browser</li>
                    <li>Preview how emails will look</li>
                </ol>
                
                <hr>
                
                <h6><strong>For Production (Real Emails):</strong></h6>
                
                <div class="accordion" id="setupAccordion">
                    <!-- Gmail Setup -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#gmail">
                                <i class="fab fa-google"></i>&nbsp; Setup with Gmail
                            </button>
                        </h2>
                        <div id="gmail" class="accordion-collapse collapse" data-bs-parent="#setupAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Install PHPMailer: <code>composer require phpmailer/phpmailer</code></li>
                                    <li>Enable 2-Step Verification on Google Account</li>
                                    <li>Generate App Password: <a href="https://myaccount.google.com/apppasswords" target="_blank">myaccount.google.com/apppasswords</a></li>
                                    <li>Edit <code>/config/email.php</code> and set:
                                        <pre class="bg-light p-2 mt-2"><code>$this->smtp_enabled = true;
$this->smtp_host = 'smtp.gmail.com';
$this->smtp_port = 587;
$this->smtp_username = 'your-email@gmail.com';
$this->smtp_password = 'xxxx xxxx xxxx xxxx'; // App Password
$this->smtp_secure = 'tls';
$this->use_file_fallback = false;</code></pre>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Outlook Setup -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#outlook">
                                <i class="fab fa-microsoft"></i>&nbsp; Setup with Outlook
                            </button>
                        </h2>
                        <div id="outlook" class="accordion-collapse collapse" data-bs-parent="#setupAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Install PHPMailer: <code>composer require phpmailer/phpmailer</code></li>
                                    <li>Edit <code>/config/email.php</code> and set:
                                        <pre class="bg-light p-2 mt-2"><code>$this->smtp_enabled = true;
$this->smtp_host = 'smtp.office365.com';
$this->smtp_port = 587;
$this->smtp_username = 'your-email@outlook.com';
$this->smtp_password = 'your-password';
$this->smtp_secure = 'tls';
$this->use_file_fallback = false;</code></pre>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PHP mail() Setup -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#phpmail">
                                <i class="fas fa-server"></i>&nbsp; Setup with PHP mail()
                            </button>
                        </h2>
                        <div id="phpmail" class="accordion-collapse collapse" data-bs-parent="#setupAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Install sendmail:
                                        <pre class="bg-light p-2 mt-2"><code>sudo apt-get update
sudo apt-get install sendmail
sudo sendmailconfig</code></pre>
                                    </li>
                                    <li>Edit <code>/config/email.php</code> and set:
                                        <pre class="bg-light p-2 mt-2"><code>$this->smtp_enabled = false;
$this->use_file_fallback = false;</code></pre>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
