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

// Make sure admin_id and role are set
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role'])) {
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT id, role FROM admins WHERE username = :username");
    $stmt->bindParam(':username', $_SESSION['admin_username']);
    $stmt->execute();
    $admin = $stmt->fetch();
    if ($admin) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_role'] = $admin['role'];
    }
}

/**
 * Check if the current user is a super admin
 */
function isSuperAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';
}

/**
 * Check if the current user is a viewer
 */
function isViewer() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'viewer';
}

/**
 * Check if the current user can edit a specific admin profile
 * Super admins can edit anyone, normal admins can only edit their own, viewers can only edit their own
 */
function canEditAdmin($targetAdminId) {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    // Super admin can edit anyone
    if (isSuperAdmin()) {
        return true;
    }
    
    // Normal admin and viewer can only edit their own profile
    return $_SESSION['admin_id'] == $targetAdminId;
}

/**
 * Check if current user can view a specific admin profile
 * Super admins can view anyone, normal admins can only view their own, viewers can only view their own
 */
function canViewAdmin($targetAdminId) {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    // Super admin can view anyone
    if (isSuperAdmin()) {
        return true;
    }
    
    // Normal admin and viewer can only view their own profile
    return $_SESSION['admin_id'] == $targetAdminId;
}

/**
 * Check if current user can edit registration data
 * Super admins and normal admins can edit, viewers cannot
 */
function canEditRegistrations() {
    return !isViewer();
}

/**
 * Check if current user can delete data
 * Only super admins and normal admins can delete, viewers cannot
 */
function canDelete() {
    return !isViewer();
}

/**
 * Check if current user can manage admins (create/delete)
 * Only super admins can manage other admins
 */
function canManageAdmins() {
    return isSuperAdmin();
}
