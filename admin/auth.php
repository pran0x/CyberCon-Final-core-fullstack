<?php
/**
 * Authentication check for admin pages
 * Include this at the top of every admin page
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Session timeout after 2 hours
$timeout_duration = 7200; // 2 hours in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}

// Update last activity time
$_SESSION['login_time'] = time();

// Make sure admin_id is set
if (!isset($_SESSION['admin_id'])) {
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT id FROM admins WHERE username = :username");
    $stmt->bindParam(':username', $_SESSION['admin_username']);
    $stmt->execute();
    $admin = $stmt->fetch();
    if ($admin) {
        $_SESSION['admin_id'] = $admin['id'];
    }
}
