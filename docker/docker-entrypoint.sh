#!/bin/bash
set -e

echo "=========================================="
echo "CyberCon Application - Docker Initialization"
echo "=========================================="

# Create necessary directories
echo "Creating required directories..."
mkdir -p /var/www/html/database
mkdir -p /var/www/html/uploads/avatars
mkdir -p /var/www/html/admin/sent_emails
mkdir -p /var/www/html/logs

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 777 /var/www/html/database
chmod -R 777 /var/www/html/uploads
chmod -R 777 /var/www/html/admin/sent_emails
chmod -R 777 /var/www/html/logs

# Check if SQLite database exists
DB_FILE="/var/www/html/database/cybercon.db"
if [ -f "$DB_FILE" ]; then
    echo "SQLite database found: $DB_FILE"
    chmod 666 "$DB_FILE"
else
    echo "WARNING: SQLite database not found at $DB_FILE"
    echo "The application will create it on first access."
fi

# Display PHP version
echo "PHP Version:"
php -v

# Display configuration info
echo "=========================================="
echo "Application is ready!"
echo "Access the application at: http://localhost:6565"
echo "Admin panel: http://localhost:6565/admin/"
echo "=========================================="

# Execute the main command (Apache)
exec "$@"
