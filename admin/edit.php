<?php
require_once 'auth.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$id = $_GET['id'] ?? null;

if (!$id || !$conn) {
    header('Location: dashboard.php');
    exit();
}

// Fetch registration
$query = "SELECT * FROM registrations WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$registration = $stmt->fetch();

if (!$registration) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'] ?? '';
    $studentId = $_POST['student_id'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $university = $_POST['university'] ?? '';
    $ticketType = $_POST['ticket_type'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? '';
    $paymentNumber = $_POST['payment_number'] ?? '';
    $transactionId = $_POST['transaction_id'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    
    try {
        $updateQuery = "UPDATE registrations SET 
            full_name = :full_name,
            student_id = :student_id,
            email = :email,
            phone = :phone,
            university = :university,
            ticket_type = :ticket_type,
            payment_method = :payment_method,
            payment_number = :payment_number,
            transaction_id = :transaction_id,
            status = :status
            WHERE id = :id";
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':full_name', $fullName);
        $updateStmt->bindParam(':student_id', $studentId);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->bindParam(':phone', $phone);
        $updateStmt->bindParam(':university', $university);
        $updateStmt->bindParam(':ticket_type', $ticketType);
        $updateStmt->bindParam(':payment_method', $paymentMethod);
        $updateStmt->bindParam(':payment_number', $paymentNumber);
        $updateStmt->bindParam(':transaction_id', $transactionId);
        $updateStmt->bindParam(':status', $status);
        $updateStmt->bindParam(':id', $id);
        
        if ($updateStmt->execute()) {
            $success = "Registration updated successfully!";
            // Refresh data
            $stmt->execute();
            $registration = $stmt->fetch();
        } else {
            $error = "Failed to update registration.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Registration - CyberCon Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar mb-4">
            <h3><i class="fas fa-edit"></i> Edit Registration</h3>
            <div>
                <a href="view.php?id=<?php echo $id; ?>" class="btn btn-outline-info me-2">
                    <i class="fas fa-eye"></i> View Details
                </a>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-user-edit"></i> Edit Registration - <?php echo htmlspecialchars($registration['ticket_id']); ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($registration['full_name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student ID *</label>
                            <input type="text" name="student_id" class="form-control" value="<?php echo htmlspecialchars($registration['student_id']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($registration['email']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone *</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($registration['phone']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">University *</label>
                            <input type="text" name="university" class="form-control" value="<?php echo htmlspecialchars($registration['university']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ticket Type *</label>
                            <select name="ticket_type" class="form-select" required>
                                <option value="Early Bird" <?php echo $registration['ticket_type'] === 'Early Bird' ? 'selected' : ''; ?>>Early Bird</option>
                                <option value="Regular" <?php echo $registration['ticket_type'] === 'Regular' ? 'selected' : ''; ?>>Regular</option>
                                <option value="VIP" <?php echo $registration['ticket_type'] === 'VIP' ? 'selected' : ''; ?>>VIP</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Bkash" <?php echo $registration['payment_method'] === 'Bkash' ? 'selected' : ''; ?>>Bkash</option>
                                <option value="Nagad" <?php echo $registration['payment_method'] === 'Nagad' ? 'selected' : ''; ?>>Nagad</option>
                                <option value="Rocket" <?php echo $registration['payment_method'] === 'Rocket' ? 'selected' : ''; ?>>Rocket</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Number *</label>
                            <input type="text" name="payment_number" class="form-control" value="<?php echo htmlspecialchars($registration['payment_number']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction ID *</label>
                            <input type="text" name="transaction_id" class="form-control" value="<?php echo htmlspecialchars($registration['transaction_id']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?php echo $registration['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $registration['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="cancelled" <?php echo $registration['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Footer -->
    <footer class="text-center py-3 mt-4" style="background: #f8f9fa; border-top: 1px solid #dee2e6;">
        <div class="container">
            <p class="mb-0 text-muted small">
                Developed by <a href="https://linkedin.com/in/pran0x" target="_blank" class="text-decoration-none"><strong>pran0x</strong></a> | 
                All rights reserved by <strong>Cyber Security Club, Uttara University</strong>
            </p>
        </div>
    </footer>
</body>
</html>
