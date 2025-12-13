<?php
require_once 'auth.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get registration ID
$id = $_GET['id'] ?? null;

if (!$id || !$conn) {
    header('Location: dashboard.php');
    exit();
}

// Fetch registration details
$query = "SELECT * FROM registrations WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$registration = $stmt->fetch();

if (!$registration) {
    header('Location: dashboard.php');
    exit();
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_registration'])) {
    $deleteQuery = "DELETE FROM registrations WHERE id = :id";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bindParam(':id', $id);
    
    if ($deleteStmt->execute()) {
        header('Location: dashboard.php?deleted=1');
        exit();
    } else {
        $error = "Failed to delete registration.";
    }
}

// Handle full registration update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_registration'])) {
    $updateQuery = "UPDATE registrations SET 
        full_name = :full_name,
        student_id = :student_id,
        email = :email,
        phone = :phone,
        university = :university,
        department = :department,
        batch = :batch,
        section = :section,
        ticket_type = :ticket_type,
        payment_method = :payment_method,
        payment_number = :payment_number,
        transaction_id = :transaction_id,
        status = :status
        WHERE id = :id";
    
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':full_name', $_POST['full_name']);
    $updateStmt->bindParam(':student_id', $_POST['student_id']);
    $updateStmt->bindParam(':email', $_POST['email']);
    $updateStmt->bindParam(':phone', $_POST['phone']);
    $updateStmt->bindParam(':university', $_POST['university']);
    $updateStmt->bindParam(':department', $_POST['department']);
    $updateStmt->bindParam(':batch', $_POST['batch']);
    $updateStmt->bindParam(':section', $_POST['section']);
    $updateStmt->bindParam(':ticket_type', $_POST['ticket_type']);
    $updateStmt->bindParam(':payment_method', $_POST['payment_method']);
    $updateStmt->bindParam(':payment_number', $_POST['payment_number']);
    $updateStmt->bindParam(':transaction_id', $_POST['transaction_id']);
    $updateStmt->bindParam(':status', $_POST['status']);
    $updateStmt->bindParam(':id', $id);
    
    if ($updateStmt->execute()) {
        // Log update
        require_once '../config/logger.php';
        $logger = new ActivityLogger($conn);
        $logger->log(
            'Update Registration',
            'Updated registration details for ' . $_POST['full_name'] . ' (' . $registration['ticket_id'] . ')',
            'registration',
            $id
        );
        
        $success = "Registration updated successfully!";
        // Refresh registration data
        $stmt->execute();
        $registration = $stmt->fetch();
    } else {
        $error = "Failed to update registration.";
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    $oldStatus = $registration['status'];
    $updateQuery = "UPDATE registrations SET status = :status WHERE id = :id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':status', $newStatus);
    $updateStmt->bindParam(':id', $id);
    
    if ($updateStmt->execute()) {
        // Log status update
        require_once '../config/logger.php';
        $logger = new ActivityLogger($conn);
        $logger->log(
            'Update Registration Status',
            'Changed status from ' . $oldStatus . ' to ' . $newStatus . ' for ' . $registration['ticket_id'],
            'registration',
            $id
        );
        
        $success = "Status updated successfully!";
        $registration['status'] = $newStatus;
    } else {
        $error = "Failed to update status.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Registration - CyberCon Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }
            
            .sidebar, .top-bar, .btn, button, form .card-footer, 
            .card-header .badge, .alert, .no-print, i, .fas, .fa {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                max-width: 100%;
            }
            
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 8px;
                padding-bottom: 8px;
                border-bottom: 2px solid #333;
            }
            
            .print-header img {
                max-width: 70px;
                height: auto;
                margin-bottom: 5px;
            }
            
            .print-header h2 {
                font-size: 18px;
                margin: 5px 0;
                font-weight: 700;
                color: #333;
                line-height: 1.2;
            }
            
            .print-header p {
                font-size: 10px;
                margin: 2px 0;
                color: #666;
                line-height: 1.3;
            }
            
            .row {
                margin: 0 !important;
            }
            
            .col-md-8, .col-md-4, .col-md-6, .col-md-12 {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
            }
            
            .card {
                border: 1px solid #ddd !important;
                page-break-inside: avoid;
                box-shadow: none !important;
                margin-bottom: 8px !important;
            }
            
            .card-header {
                background: #f5f5f5 !important;
                color: #333 !important;
                border-bottom: 1px solid #ddd !important;
                padding: 6px 10px !important;
                font-weight: 600;
            }
            
            .card-header h5, .card-header h6 {
                font-size: 12px !important;
                margin: 0 !important;
                font-weight: 600;
                line-height: 1.3;
            }
            
            .card-body {
                padding: 8px 10px !important;
                font-size: 10px !important;
            }
            
            .row.mb-3 {
                margin-bottom: 6px !important;
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 8px !important;
            }
            
            .row.mb-3 > .col-md-12 {
                grid-column: 1 / -1 !important;
            }
            
            .row.mb-3 > .col-md-4 {
                grid-column: span 1 !important;
            }
            
            .form-label {
                font-size: 9px !important;
                margin-bottom: 3px !important;
                font-weight: 700;
                color: #555;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                line-height: 1.2;
                display: block;
            }
            
            .form-control, .form-select {
                font-size: 11px !important;
                padding: 4px 6px !important;
                border: 1px solid #ccc !important;
                background: #fafafa !important;
                color: #333;
                line-height: 1.4;
                height: auto !important;
                min-height: 24px !important;
                font-weight: 500;
            }
            
            .alert {
                display: none !important;
            }
            
            .print-logo {
                display: block !important;
                text-align: center;
                margin: 10px 0 0 0;
                padding-top: 8px;
                border-top: 1px solid #ddd;
            }
            
            .print-logo img {
                max-width: 50px;
                height: auto;
            }
            
            .print-logo p {
                margin: 5px 0 0 0;
                font-size: 9px;
                font-weight: 600;
                color: #333;
                line-height: 1.2;
            }
            
            footer {
                position: fixed;
                bottom: 8mm;
                left: 0;
                right: 0;
                text-align: center;
                padding: 5px;
                border-top: 1px solid #ddd;
                background: white;
                font-size: 8px !important;
            }
            
            footer p {
                margin: 0 !important;
                font-size: 8px !important;
                line-height: 1.2;
            }
            
            body {
                background: white !important;
                font-size: 10px !important;
                font-family: 'Arial', 'Helvetica', sans-serif;
                line-height: 1.4;
            }
            
            h5, h6 {
                font-size: 12px !important;
                line-height: 1.3;
            }
            
            small {
                font-size: 8px !important;
            }
            
            .card.mt-4 {
                margin-top: 8px !important;
            }
            
            * {
                box-sizing: border-box !important;
            }
            
            .print-footer-info {
                display: block !important;
                margin-top: 15px;
                padding: 10px;
                border: 1px solid #ddd;
                page-break-before: always;
            }
            
            .print-footer-info h3 {
                font-size: 14px !important;
                text-align: center;
                margin-bottom: 10px;
                padding-bottom: 5px;
                border-bottom: 2px solid #333;
                font-weight: 700;
                color: #333;
            }
            
            .print-footer-info h4 {
                font-size: 11px !important;
                margin-top: 8px;
                margin-bottom: 4px;
                font-weight: 700;
                color: #444;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .print-footer-info .policy-section {
                margin-bottom: 8px;
            }
            
            .print-footer-info p {
                font-size: 9px !important;
                margin: 3px 0;
                line-height: 1.4;
                color: #333;
            }
            
            .print-footer-info ul {
                margin: 3px 0 3px 15px;
                padding: 0;
            }
            
            .print-footer-info ul li {
                font-size: 9px !important;
                margin: 2px 0;
                line-height: 1.4;
                color: #333;
            }
            
            .print-footer-info strong {
                font-weight: 700;
                color: #000;
            }
            
            .print-footer-info em {
                font-style: italic;
                color: #555;
            }
        }
        
        .print-logo, .print-header, .print-footer-info {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar mb-4">
            <h3><i class="fas fa-eye"></i> Registration Details</h3>
            <div>
                <button onclick="window.print()" class="btn btn-info me-2">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
        <div class="row">
            <!-- Registration Information -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Registration Information</h5>
                        <div>
                            <span class="badge bg-secondary">ID: <?php echo htmlspecialchars($registration['ticket_id']); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-user"></i> Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($registration['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-id-card"></i> Student ID</label>
                                <input type="text" name="student_id" class="form-control" value="<?php echo htmlspecialchars($registration['student_id']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($registration['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-phone"></i> Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($registration['phone']); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label"><i class="fas fa-university"></i> University</label>
                                <input type="text" name="university" class="form-control" value="<?php echo htmlspecialchars($registration['university']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-building"></i> Department</label>
                                <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($registration['department'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-graduation-cap"></i> Batch</label>
                                <input type="text" name="batch" class="form-control" value="<?php echo htmlspecialchars($registration['batch'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-users"></i> Section</label>
                                <input type="text" name="section" class="form-control" value="<?php echo htmlspecialchars($registration['section'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-ticket-alt"></i> Ticket Type</label>
                                <select name="ticket_type" class="form-select" required>
                                    <option value="Early Bird" <?php echo $registration['ticket_type'] === 'Early Bird' ? 'selected' : ''; ?>>Early Bird</option>
                                    <option value="Regular" <?php echo $registration['ticket_type'] === 'Regular' ? 'selected' : ''; ?>>Regular</option>
                                    <option value="VIP" <?php echo $registration['ticket_type'] === 'VIP' ? 'selected' : ''; ?>>VIP</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-flag"></i> Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="pending" <?php echo $registration['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $registration['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo $registration['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="duplicate" <?php echo $registration['status'] === 'duplicate' ? 'selected' : ''; ?>>Duplicate</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-credit-card"></i> Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-money-check"></i> Payment Method</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="Bkash" <?php echo $registration['payment_method'] === 'Bkash' ? 'selected' : ''; ?>>Bkash</option>
                                    <option value="Nagad" <?php echo $registration['payment_method'] === 'Nagad' ? 'selected' : ''; ?>>Nagad</option>
                                    <option value="Rocket" <?php echo $registration['payment_method'] === 'Rocket' ? 'selected' : ''; ?>>Rocket</option>
                                    <option value="Bank Transfer" <?php echo $registration['payment_method'] === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="fas fa-phone-square"></i> Payment Number</label>
                                <input type="text" name="payment_number" class="form-control" value="<?php echo htmlspecialchars($registration['payment_number']); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label"><i class="fas fa-receipt"></i> Transaction ID</label>
                                <input type="text" name="transaction_id" class="form-control" value="<?php echo htmlspecialchars($registration['transaction_id']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Registration Date:</strong> 
                            <?php echo date('F j, Y \a\t g:i A', strtotime($registration['registration_date'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4 border-primary">
                    <div class="card-body">
                        <button type="submit" name="update_registration" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="registrations.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card no-print">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Permanently delete this registration. This action cannot be undone.</p>
                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash"></i> Delete Registration
                        </button>
                    </div>
                </div>
                
                <div class="card mt-4 no-print">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Current Status</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php
                        $statusClass = [
                            'pending' => 'warning',
                            'confirmed' => 'success',
                            'cancelled' => 'danger',
                            'duplicate' => 'info'
                        ];
                        $class = $statusClass[$registration['status']] ?? 'secondary';
                        ?>
                        <h3><span class="badge bg-<?php echo $class; ?>">
                            <?php echo ucfirst($registration['status']); ?>
                        </span></h3>
                        <p class="text-muted small mt-2">Change status using the form above</p>
                    </div>
                </div>
                
                <div class="card mt-4 no-print">
                    <div class="card-header">
                        <h5><i class="fas fa-clock"></i> Timestamps</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Registration:</strong><br>
                        <small class="text-muted"><?php echo date('F j, Y \a\t g:i:s A', strtotime($registration['registration_date'])); ?></small></p>
                        
                        <p class="mb-2"><strong>Created:</strong><br>
                        <small class="text-muted"><?php echo date('F j, Y \a\t g:i:s A', strtotime($registration['created_at'])); ?></small></p>
                        
                        <p class="mb-0"><strong>Last Updated:</strong><br>
                        <small class="text-muted"><?php echo date('F j, Y \a\t g:i:s A', strtotime($registration['updated_at'])); ?></small></p>
                    </div>
                </div>
            </div>
        </div>
        </form>
        
        <!-- Print Header (Only visible when printing) -->
        <div class="print-header">
            <img src="../assets/images/logo/cyber-security-club-logo.png" alt="Cyber Security Club Logo" onerror="this.src='../assets/images/logo/CyberCon.png'">
            <h2>CYBERCON 2025</h2>
            <p>Registration Details</p>
            <p>Cyber Security Club, Uttara University</p>
        </div>
        
        <!-- Print Footer Logo (Only visible when printing) -->
        <div class="print-logo">
            <img src="../assets/images/logo/cyber-security-club-logo.png" alt="Cyber Security Club Logo" onerror="this.src='../assets/images/logo/CyberCon.png'">
            <p>Cyber Security Club, Uttara University</p>
        </div>
        
        <!-- Print Footer Information (Only visible when printing) -->
        <div class="print-footer-info">
            <h3>CyberCon 2025 - Registration & Ticket Policy</h3>
            
            <div class="policy-section">
                <h4>Registration Details</h4>
                <p><strong>Event Date:</strong> To be announced | <strong>Venue:</strong> Uttara University Campus</p>
                <p>This registration confirms your participation in CyberCon 2025, an exclusive cybersecurity conference organized by the Cyber Security Club of Uttara University. Your ticket grants you access to all keynote sessions, workshops, and networking events.</p>
            </div>
            
            <div class="policy-section">
                <h4>Ticket Types & Benefits</h4>
                <ul>
                    <li><strong>Student Pass (Early Bird):</strong> Special discounted rate for early registrations. First 200 registrants receive an exclusive CyberCon 2025 T-shirt! Includes full conference access, workshop materials, refreshments, and networking opportunities. Perfect for students looking to explore cybersecurity at an affordable price.</li>
                    <li><strong>Regular Pass:</strong> Standard admission ticket with full access to all conference sessions, hands-on workshops, and networking events. Includes workshop materials, lunch, refreshments, and certificate of attendance. Ideal for students who want comprehensive conference experience.</li>
                    <li><strong>VIP Pass:</strong> Premium all-inclusive experience with priority seating at all sessions, exclusive VIP lounge access, dedicated networking session with industry speakers and sponsors, certificate of participation, CyberCon 2025 merchandise kit (T-shirt, notebook, pen), complimentary lunch and premium refreshments, and early access to workshop registrations.</li>
                </ul>
            </div>
            
            <div class="policy-section">
                <h4>Important Policies</h4>
                <ul>
                    <li><strong>Non-Transferable:</strong> This ticket is registered under your name and student ID. It cannot be transferred to another person.</li>
                    <li><strong>Entry Requirements:</strong> You must present your student ID card along with this registration confirmation at the venue entrance.</li>
                    <li><strong>Refund Policy:</strong> Refund requests must be submitted at least 7 days before the event. Processing fee may apply. Contact us at cyberclub@uttarauniversity.edu.bd</li>
                    <li><strong>Code of Conduct:</strong> All attendees must adhere to professional conduct standards. Harassment of any kind will not be tolerated.</li>
                    <li><strong>Photography & Recording:</strong> Event photography and videography will be conducted. By attending, you consent to be photographed/recorded for promotional purposes.</li>
                </ul>
            </div>
            
            <div class="policy-section">
                <h4>Payment Verification</h4>
                <p>Your payment has been recorded with Transaction ID: <strong><?php echo htmlspecialchars($registration['transaction_id']); ?></strong> via <?php echo htmlspecialchars($registration['payment_method']); ?>. If your status shows "Pending", our team is verifying your payment. You will receive a confirmation email once verified.</p>
            </div>
            
            <div class="policy-section">
                <h4>Contact Information</h4>
                <p><strong>Email:</strong> cyberclub@uttarauniversity.edu.bd | <strong>Phone:</strong> +880 1XXX-XXXXXX</p>
                <p><strong>Website:</strong> www.cybercon.uttarauniversity.edu.bd | <strong>Social Media:</strong> @CyberClubUU</p>
                <p class="text-center mt-3"><em>Thank you for registering for CyberCon 2025. We look forward to seeing you!</em></p>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Are you sure you want to delete this registration?</strong></p>
                    <p class="text-muted">This action cannot be undone.</p>
                    <div class="alert alert-warning">
                        <strong>Ticket ID:</strong> <?php echo htmlspecialchars($registration['ticket_id']); ?><br>
                        <strong>Name:</strong> <?php echo htmlspecialchars($registration['full_name']); ?><br>
                        <strong>Student ID:</strong> <?php echo htmlspecialchars($registration['student_id']); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="delete_registration" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Yes, Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printDetails() {
            window.print();
        }
    </script>
    
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
