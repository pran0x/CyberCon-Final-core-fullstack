#!/bin/bash

echo "======================================"
echo "CyberCon Email Configuration Helper"
echo "======================================"
echo ""

# Create sent_emails directory
echo "Creating /admin/sent_emails directory..."
mkdir -p /home/pran0x/Projects/CyberCon-Final-core-fullstack/admin/sent_emails
chmod 755 /home/pran0x/Projects/CyberCon-Final-core-fullstack/admin/sent_emails
echo "✓ Directory created"
echo ""

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "⚠️  Composer not found"
    echo ""
    echo "To enable SMTP support, install Composer:"
    echo "  curl -sS https://getcomposer.org/installer | php"
    echo "  sudo mv composer.phar /usr/local/bin/composer"
    echo ""
else
    echo "✓ Composer is installed"
    echo ""
    
    # Ask if user wants to install PHPMailer
    read -p "Install PHPMailer for SMTP support? (y/n): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        cd /home/pran0x/Projects/CyberCon-Final-core-fullstack
        composer require phpmailer/phpmailer
        echo "✓ PHPMailer installed"
    fi
fi

echo ""
echo "======================================"
echo "Current Configuration:"
echo "======================================"
echo "Mode: File Fallback (Testing Mode)"
echo "Emails will be saved to: /admin/sent_emails/"
echo ""
echo "To configure email for production:"
echo "1. Edit: /config/email.php"
echo "2. Choose SMTP provider (Gmail/Outlook/Custom)"
echo "3. Add credentials (use App Passwords for Gmail)"
echo "4. Set use_file_fallback = false"
echo ""
echo "Full guide: /config/EMAIL_SETUP_GUIDE.md"
echo "======================================"
