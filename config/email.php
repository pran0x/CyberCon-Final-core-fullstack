<?php
/**
 * Email Configuration and Handler
 * Handles email sending with templates
 */

class EmailHandler {
    private $from_email;
    private $from_name;
    private $smtp_enabled;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $smtp_secure; // 'tls' or 'ssl'
    private $use_file_fallback; // For testing without email server
    
    public function __construct() {
        // ==========================================
        // EMAIL CONFIGURATION - EDIT THESE SETTINGS
        // ==========================================
        
        $this->from_email = 'cybersecurity@club.uttara.ac.bd';
        $this->from_name = 'CyberCon25 - Uttara University';
        
        // METHOD 1: Use PHP mail() function (requires server configuration)
        // Set to false and enable file fallback for testing
        $this->smtp_enabled = false;
        
        // METHOD 2: SMTP Configuration (recommended for production)
        // Uncomment and configure these for Gmail, Outlook, or custom SMTP
        /*
        $this->smtp_enabled = true;
        $this->smtp_host = 'smtp.gmail.com';  // Gmail SMTP server
        $this->smtp_port = 587;                // TLS port (465 for SSL)
        $this->smtp_username = 'your-email@gmail.com';
        $this->smtp_password = 'your-app-password'; // Use App Password for Gmail
        $this->smtp_secure = 'tls';            // 'tls' or 'ssl'
        */
        
        // For Outlook/Office365:
        // $this->smtp_host = 'smtp.office365.com';
        // $this->smtp_port = 587;
        
        // For Yahoo:
        // $this->smtp_host = 'smtp.mail.yahoo.com';
        // $this->smtp_port = 587;
        
        // METHOD 3: File Fallback (for testing without email server)
        // Emails will be saved to files in /admin/sent_emails/
        $this->use_file_fallback = true; // Set to false when email is configured
    }
    
    /**
     * Send email to single recipient
     */
    public function sendEmail($to, $subject, $body, $isHTML = true) {
        if ($isHTML) {
            $body = $this->wrapHTMLTemplate($body, $subject);
        }
        
        // Use file fallback for testing
        if ($this->use_file_fallback) {
            return $this->saveEmailToFile($to, $subject, $body);
        }
        
        // Try SMTP if enabled
        if ($this->smtp_enabled && $this->canUseSMTP()) {
            return $this->sendViaSMTP($to, $subject, $body);
        }
        
        // Fall back to PHP mail()
        $headers = $this->getHeaders($isHTML);
        $success = @mail($to, $subject, $body, $headers);
        
        // If mail() fails, save to file as backup
        if (!$success && $this->use_file_fallback !== false) {
            return $this->saveEmailToFile($to, $subject, $body);
        }
        
        return $success;
    }
    
    /**
     * Check if we can use SMTP (PHPMailer or similar)
     */
    private function canUseSMTP() {
        // Check if PHPMailer is available
        return class_exists('PHPMailer\PHPMailer\PHPMailer');
    }
    
    /**
     * Send email via SMTP using PHPMailer
     */
    private function sendViaSMTP($to, $subject, $body) {
        if (!$this->canUseSMTP()) {
            return false;
        }
        
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = $this->smtp_secure;
            $mail->Port = $this->smtp_port;
            
            // Recipients
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save email to file for testing (fallback method)
     */
    private function saveEmailToFile($to, $subject, $body) {
        $dir = __DIR__ . '/../admin/sent_emails';
        
        // Create directory if it doesn't exist
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $filename = $dir . '/email_' . date('Y-m-d_H-i-s') . '_' . md5($to . time()) . '.html';
        
        $content = "<!--\n";
        $content .= "To: $to\n";
        $content .= "Subject: $subject\n";
        $content .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $content .= "-->\n\n";
        $content .= $body;
        
        $success = file_put_contents($filename, $content);
        
        return $success !== false;
    }
    
    /**
     * Send email to multiple recipients
     */
    public function sendBulkEmail($recipients, $subject, $body, $isHTML = true) {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $name = is_array($recipient) ? ($recipient['name'] ?? '') : '';
            
            // Personalize message if name is available
            $personalizedBody = $body;
            if (!empty($name)) {
                $personalizedBody = str_replace('{name}', $name, $body);
                $personalizedBody = str_replace('{full_name}', $name, $personalizedBody);
            } else {
                $personalizedBody = str_replace('{name}', 'Participant', $body);
                $personalizedBody = str_replace('{full_name}', 'Participant', $personalizedBody);
            }
            
            // Replace other placeholders
            if (is_array($recipient)) {
                foreach ($recipient as $key => $value) {
                    $personalizedBody = str_replace('{' . $key . '}', $value, $personalizedBody);
                }
            }
            
            if ($this->sendEmail($email, $subject, $personalizedBody, $isHTML)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $email;
            }
            
            // Small delay to prevent being marked as spam
            usleep(100000); // 0.1 second
        }
        
        return $results;
    }
    
    /**
     * Get email headers
     */
    private function getHeaders($isHTML = true) {
        $headers = [];
        $headers[] = "From: {$this->from_name} <{$this->from_email}>";
        $headers[] = "Reply-To: {$this->from_email}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        $headers[] = "MIME-Version: 1.0";
        
        if ($isHTML) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Wrap email body in HTML template
     */
    private function wrapHTMLTemplate($body, $subject) {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($subject) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .email-body {
            padding: 30px;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>CyberCon25</h1>
            <p>Cyber Security Club - Uttara University</p>
        </div>
        <div class="email-body">
            ' . $body . '
        </div>
        <div class="email-footer">
            <p><strong>CyberCon25 - Uttara University</strong></p>
            <p>Uttara, Dhaka, Bangladesh</p>
            <p>Email: cybersecurity@club.uttara.ac.bd | Phone: +880 1919399235</p>
            <p style="margin-top: 10px;">
                <a href="https://facebook.com/csc.uu.bd" style="color: #667eea; text-decoration: none; margin: 0 5px;">Facebook</a> |
                <a href="https://www.linkedin.com/company/cscuu" style="color: #667eea; text-decoration: none; margin: 0 5px;">LinkedIn</a> |
                <a href="https://github.com/Cyber-Security-Club-Uttara-University" style="color: #667eea; text-decoration: none; margin: 0 5px;">GitHub</a>
            </p>
            <p style="color: #999; margin-top: 15px;">© 2025 Cyber Security Club, Uttara University. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Get email templates
     */
    public function getTemplates() {
        return [
            'registration_confirmation' => [
                'name' => 'Registration Confirmation',
                'subject' => 'Welcome to CyberCon25! Registration Confirmed',
                'body' => '<h2>Hello {name}!</h2>
                <p>Thank you for registering for <strong>CyberCon25</strong>!</p>
                <div class="info-box">
                    <strong>Your Registration Details:</strong><br>
                    Ticket ID: <strong>{ticket_id}</strong><br>
                    Name: {full_name}<br>
                    Email: {email}<br>
                    Student ID: {student_id}
                </div>
                <p>Please keep your Ticket ID safe. You will need it for event entry.</p>
                <p style="text-align: center;">
                    <a href="http://192.168.0.100" class="button">View Event Details</a>
                </p>
                <p>We look forward to seeing you at the event!</p>
                <p>Best regards,<br><strong>CyberCon25 Team</strong></p>'
            ],
            'payment_verified' => [
                'name' => 'Payment Verified',
                'subject' => 'Payment Verified - CyberCon25 Registration Complete',
                'body' => '<h2>Hello {name}!</h2>
                <p>Great news! Your payment has been verified and your registration is now <strong>CONFIRMED</strong>.</p>
                <div class="info-box">
                    <strong>Registration Details:</strong><br>
                    Ticket ID: <strong>{ticket_id}</strong><br>
                    Status: <span style="color: green;">✓ Confirmed</span><br>
                    Payment Method: {payment_method}<br>
                    Transaction ID: {transaction_id}
                </div>
                <p>You are all set for CyberCon25! Please bring your Ticket ID on the event day.</p>
                <p><strong>Event Details:</strong></p>
                <ul>
                    <li>Date: [Event Date]</li>
                    <li>Time: [Event Time]</li>
                    <li>Venue: Uttara University</li>
                </ul>
                <p>See you there!</p>
                <p>Best regards,<br><strong>CyberCon25 Team</strong></p>'
            ],
            'event_reminder' => [
                'name' => 'Event Reminder',
                'subject' => 'CyberCon25 is Tomorrow! Important Reminders',
                'body' => '<h2>Hello {name}!</h2>
                <p>This is a friendly reminder that <strong>CyberCon25</strong> is happening tomorrow!</p>
                <div class="warning-box">
                    <strong>⚠️ Important Reminders:</strong><br>
                    • Bring your Ticket ID: <strong>{ticket_id}</strong><br>
                    • Arrive 30 minutes early for registration<br>
                    • Bring your student ID card<br>
                    • Dress code: Smart casual
                </div>
                <p><strong>Event Details:</strong></p>
                <ul>
                    <li><strong>Date:</strong> [Event Date]</li>
                    <li><strong>Time:</strong> [Event Time]</li>
                    <li><strong>Venue:</strong> Uttara University, Dhaka</li>
                </ul>
                <p>We are excited to have you join us for this amazing cybersecurity event!</p>
                <p>Best regards,<br><strong>CyberCon25 Team</strong></p>'
            ],
            'custom' => [
                'name' => 'Custom Message',
                'subject' => '',
                'body' => '<h2>Hello {name}!</h2>
                <p>Your message content here...</p>
                <p>Best regards,<br><strong>CyberCon25 Team</strong></p>'
            ]
        ];
    }
    
    /**
     * Get current email configuration status
     */
    public function getConfigStatus() {
        $status = [
            'mode' => 'unknown',
            'details' => ''
        ];
        
        if ($this->use_file_fallback) {
            $status['mode'] = 'file';
            $status['details'] = 'Emails are being saved to files (Testing Mode)';
        } elseif ($this->smtp_enabled) {
            $status['mode'] = 'smtp';
            $status['details'] = 'Using SMTP: ' . ($this->smtp_host ?? 'Not configured');
        } else {
            $status['mode'] = 'mail';
            $status['details'] = 'Using PHP mail() function';
        }
        
        return $status;
    }
}
