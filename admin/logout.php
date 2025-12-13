<?php
session_start();

// Log logout before destroying session
require_once '../config/database.php';
require_once '../config/logger.php';

$db = new Database();
$conn = $db->getConnection();
$logger = new ActivityLogger($conn);

if (isset($_SESSION['admin_username'])) {
    $logger->log('Admin Logout', 'Admin logged out', 'admin', $_SESSION['admin_id'] ?? null);
}

session_unset();
session_destroy();
header('Location: login.php');
exit();
