<?php
require_once 'auth.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die('Database connection failed');
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=cybercon_registrations_' . date('Y-m-d_His') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, [
    'ID',
    'Ticket ID',
    'Full Name',
    'Student ID',
    'Email',
    'Phone',
    'University',
    'Ticket Type',
    'Payment Method',
    'Payment Number',
    'Transaction ID',
    'Status',
    'Registration Date',
    'Created At',
    'Updated At'
]);

// Fetch all registrations
$query = "SELECT * FROM registrations ORDER BY registration_date DESC";
$stmt = $conn->query($query);
$registrations = $stmt->fetchAll();

// Add data rows
foreach ($registrations as $reg) {
    fputcsv($output, [
        $reg['id'],
        $reg['ticket_id'],
        $reg['full_name'],
        $reg['student_id'],
        $reg['email'],
        $reg['phone'],
        $reg['university'],
        $reg['ticket_type'],
        $reg['payment_method'],
        $reg['payment_number'],
        $reg['transaction_id'],
        $reg['status'],
        $reg['registration_date'],
        $reg['created_at'],
        $reg['updated_at']
    ]);
}

fclose($output);
exit();
