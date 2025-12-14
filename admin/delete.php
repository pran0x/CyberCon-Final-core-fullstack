<?php
require_once 'auth.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get registration ID
$id = $_POST['id'] ?? null;

if (!$id || !$conn) {
    header('Location: dashboard.php?error=invalid');
    exit();
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_registration'])) {
    // Check if user has permission to delete
    if (!canDelete()) {
        header('Location: dashboard.php?error=access_denied');
        exit();
    }
    
    // Get registration details before deleting
    $getQuery = "SELECT ticket_id, full_name FROM registrations WHERE id = :id";
    $getStmt = $conn->prepare($getQuery);
    $getStmt->bindParam(':id', $id);
    $getStmt->execute();
    $registration = $getStmt->fetch();
    
    $deleteQuery = "DELETE FROM registrations WHERE id = :id";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bindParam(':id', $id);
    
    if ($deleteStmt->execute()) {
        // Log deletion
        require_once '../config/logger.php';
        $logger = new ActivityLogger($conn);
        $logger->log(
            'Delete Registration', 
            'Deleted registration: ' . $registration['ticket_id'] . ' - ' . $registration['full_name'],
            'registration',
            $id
        );
        
        header('Location: dashboard.php?deleted=1');
        exit();
    } else {
        header('Location: dashboard.php?error=delete_failed');
        exit();
    }
}

// If not POST request, redirect back
header('Location: dashboard.php');
exit();
