# Email Configuration Guide

## Current Status
The email system is currently using **File Fallback Mode** - emails are saved to files instead of being sent. This is perfect for testing!

Sent emails are saved in: `/admin/sent_emails/`

## Configuration Options

### Option 1: File Fallback (Current - For Testing)
**No configuration needed!** Emails are saved to HTML files in `/admin/sent_emails/`

This is perfect for:
- Testing email templates
- Development environment
- When you don't have an SMTP server

To use this mode:
```php
$this->use_file_fallback = true;
```

---

### Option 2: PHP mail() Function
**Requires:** Server with mail functionality configured (sendmail/postfix)

To enable:
```php
$this->use_file_fallback = false;
$this->smtp_enabled = false;
```

**Installation on Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install sendmail
sudo sendmailconfig
```

**Installation on CentOS/RHEL:**
```bash
sudo yum install sendmail
sudo systemctl start sendmail
sudo systemctl enable sendmail
```

---

### Option 3: SMTP (Recommended for Production)

#### Using Gmail SMTP

1. **Enable 2-Step Verification** on your Google account
2. **Generate App Password:**
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and your device
   - Copy the 16-character password

3. **Configure in `/config/email.php`:**
```php
$this->smtp_enabled = true;
$this->smtp_host = 'smtp.gmail.com';
$this->smtp_port = 587;
$this->smtp_username = 'your-email@gmail.com';
$this->smtp_password = 'xxxx xxxx xxxx xxxx'; // Your app password
$this->smtp_secure = 'tls';
$this->use_file_fallback = false;
```

4. **Install PHPMailer:**
```bash
cd /home/pran0x/Projects/CyberCon-Final-core-fullstack
composer require phpmailer/phpmailer
```

#### Using Outlook/Office 365 SMTP

```php
$this->smtp_enabled = true;
$this->smtp_host = 'smtp.office365.com';
$this->smtp_port = 587;
$this->smtp_username = 'your-email@outlook.com';
$this->smtp_password = 'your-password';
$this->smtp_secure = 'tls';
$this->use_file_fallback = false;
```

#### Using Yahoo SMTP

```php
$this->smtp_enabled = true;
$this->smtp_host = 'smtp.mail.yahoo.com';
$this->smtp_port = 587;
$this->smtp_username = 'your-email@yahoo.com';
$this->smtp_password = 'your-app-password';
$this->smtp_secure = 'tls';
$this->use_file_fallback = false;
```

#### Using Custom SMTP Server

```php
$this->smtp_enabled = true;
$this->smtp_host = 'mail.yourdomain.com';
$this->smtp_port = 587; // or 465 for SSL
$this->smtp_username = 'noreply@yourdomain.com';
$this->smtp_password = 'your-password';
$this->smtp_secure = 'tls'; // or 'ssl'
$this->use_file_fallback = false;
```

---

## Testing Email Configuration

### Test with File Fallback (Current Setup)
1. Go to Admin Panel → Send Email
2. Select a recipient
3. Compose and send email
4. Check `/admin/sent_emails/` folder for the HTML file
5. Open the HTML file in a browser to preview

### Test with Real Email
1. Configure SMTP settings (see above)
2. Send a test email to yourself
3. Check spam folder if not received
4. Verify sender email is not blacklisted

---

## Troubleshooting

### "Failed to send emails" Error
**Solution:** The system automatically falls back to file mode. Check `/admin/sent_emails/` for saved emails.

### Gmail "Less secure app" Error
**Solution:** Use App Passwords (see Gmail SMTP section above)

### "Could not instantiate mail function"
**Solution:** 
- Install sendmail: `sudo apt-get install sendmail`
- Or enable SMTP mode with proper credentials

### Port 587 Connection Timeout
**Solution:**
- Try port 465 with SSL instead of TLS
- Check if firewall is blocking outbound SMTP
- Verify SMTP server address is correct

### Authentication Failed
**Solution:**
- Double-check username and password
- For Gmail, use App Password (not regular password)
- Ensure 2FA is enabled for Gmail
- Check if account requires additional security settings

---

## Recommended Setup for Production

1. **Install Composer** (if not installed):
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
```

2. **Install PHPMailer**:
```bash
cd /home/pran0x/Projects/CyberCon-Final-core-fullstack
composer require phpmailer/phpmailer
```

3. **Configure SMTP** in `/config/email.php` (use Gmail/Outlook settings above)

4. **Test thoroughly** before production use

---

## Security Notes

- **Never commit** SMTP credentials to Git
- Use environment variables for production: `$_ENV['SMTP_PASSWORD']`
- Enable rate limiting to prevent spam abuse
- Use App Passwords instead of regular passwords
- Keep PHPMailer updated for security patches

---

## Current Configuration Status

✅ **File Fallback Mode ACTIVE** - Emails saved to `/admin/sent_emails/`
⚠️ **SMTP Disabled** - Configure SMTP for production
📧 **From Email:** cybersecurity@club.uttara.ac.bd
🏢 **From Name:** CyberCon25 - Uttara University

---

## Quick Start (Recommended)

For immediate testing:
1. Leave current settings (file fallback enabled)
2. Send test emails - they'll be saved to files
3. Open HTML files in browser to preview
4. When ready for production, configure SMTP

For production:
1. Install PHPMailer with composer
2. Configure Gmail/Outlook SMTP settings
3. Set `use_file_fallback = false`
4. Test with your own email first
5. Deploy to production

---

## Need Help?

Contact: pran0x
Email: pranto.ks@uttarauniversity.edu.bd
