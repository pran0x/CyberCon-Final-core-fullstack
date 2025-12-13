-- CyberCon Database Setup Instructions

-- Step 1: Create the database and tables
-- Run this command in your terminal:
mysql -u root -p < database/schema.sql

-- OR use phpMyAdmin:
-- 1. Open phpMyAdmin (usually at http://localhost/phpmyadmin)
-- 2. Click on "SQL" tab
-- 3. Copy and paste the contents of schema.sql
-- 4. Click "Go"

-- Step 2: Configure database connection
-- Edit config/database.php if needed:
-- - host: default is 'localhost'
-- - db_name: 'cybercon_db'
-- - username: default is 'root'
-- - password: default is '' (empty for XAMPP/WAMP)

-- Step 3: Test the database connection
-- Create a test file: test-db.php

<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

if ($conn) {
    echo "✓ Database connection successful!\n";
    
    // Test if tables exist
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Found " . count($tables) . " tables\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
} else {
    echo "✗ Database connection failed!\n";
}
?>

-- Step 4: API Endpoints Usage

-- POST: Create new registration
-- URL: http://localhost:8000/api/registrations.php
-- Method: POST
-- Headers: Content-Type: application/json
-- Body Example:
{
    "fullName": "John Doe",
    "studentId": "C211001",
    "email": "john@cuet.ac.bd",
    "phone": "+8801712345678",
    "university": "CUET",
    "ticketType": "Early Bird",
    "paymentMethod": "Bkash",
    "paymentNumber": "01712345678",
    "transactionId": "ABC123XYZ"
}

-- GET: Fetch all registrations
-- URL: http://localhost:8000/api/registrations.php
-- Method: GET

-- GET: Fetch specific registration
-- URL: http://localhost:8000/api/registrations.php?id=CC-2025-1A2B
-- Method: GET

-- GET: Fetch with filters
-- URL: http://localhost:8000/api/registrations.php?email=john@cuet.ac.bd
-- URL: http://localhost:8000/api/registrations.php?university=CUET
-- URL: http://localhost:8000/api/registrations.php?status=pending
-- URL: http://localhost:8000/api/registrations.php?page=1&limit=10

-- Step 5: Test with cURL commands

-- Test POST (Create registration):
curl -X POST http://localhost:8000/api/registrations.php \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "Test User",
    "studentId": "C211001",
    "email": "test@cuet.ac.bd",
    "phone": "+8801712345678",
    "university": "CUET",
    "ticketType": "Early Bird",
    "paymentMethod": "Bkash",
    "paymentNumber": "01712345678",
    "transactionId": "TEST123"
  }'

-- Test GET (Fetch all):
curl http://localhost:8000/api/registrations.php

-- Test GET (Fetch by ID):
curl http://localhost:8000/api/registrations.php?id=CC-2025-1A2B

-- Test GET (Fetch by email):
curl http://localhost:8000/api/registrations.php?email=test@cuet.ac.bd
