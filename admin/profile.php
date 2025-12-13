<?php
require_once 'auth.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$adminId = $_GET['id'] ?? $_SESSION['admin_id'] ?? null;

// Fetch admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = :id");
$stmt->bindParam(':id', $adminId);
$stmt->execute();
$admin = $stmt->fetch();

if (!$admin) {
    header('Location: admins.php');
    exit();
}

$success = $error = '';

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if ($file['error'] === 0) {
        if (in_array($file['type'], $allowedTypes)) {
            if ($file['size'] <= $maxSize) {
                $uploadDir = '../uploads/avatars/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $adminId . '_' . time() . '.' . $extension;
                $uploadPath = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Delete old avatar
                    if ($admin['avatar'] && file_exists($uploadDir . $admin['avatar'])) {
                        unlink($uploadDir . $admin['avatar']);
                    }
                    
                    // Update database
                    $updateQuery = "UPDATE admins SET avatar = :avatar WHERE id = :id";
                    $updateStmt = $conn->prepare($updateQuery);
                    $updateStmt->bindParam(':avatar', $filename);
                    $updateStmt->bindParam(':id', $adminId);
                    
                    if ($updateStmt->execute()) {
                        $success = "Avatar uploaded successfully!";
                        $admin['avatar'] = $filename;
                    }
                } else {
                    $error = "Failed to upload file.";
                }
            } else {
                $error = "File size must be less than 2MB.";
            }
        } else {
            $error = "Only JPG, JPEG, and PNG files are allowed.";
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    
    if ($fullName && $email) {
        try {
            if ($newPassword) {
                $updateQuery = "UPDATE admins SET full_name = :full_name, email = :email, password = :password WHERE id = :id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bindParam(':password', $newPassword);
            } else {
                $updateQuery = "UPDATE admins SET full_name = :full_name, email = :email WHERE id = :id";
                $updateStmt = $conn->prepare($updateQuery);
            }
            
            $updateStmt->bindParam(':full_name', $fullName);
            $updateStmt->bindParam(':email', $email);
            $updateStmt->bindParam(':id', $adminId);
            
            if ($updateStmt->execute()) {
                $success = "Profile updated successfully!";
                $admin['full_name'] = $fullName;
                $admin['email'] = $email;
            }
        } catch (PDOException $e) {
            $error = "Failed to update profile: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - CyberCon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar mb-4">
            <h3><i class="fas fa-user-circle"></i> Admin Profile</h3>
            <a href="admins.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Admins
            </a>
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
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-camera"></i> Avatar</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($admin['avatar']): ?>
                            <img src="../uploads/avatars/<?php echo htmlspecialchars($admin['avatar']); ?>" 
                                 class="img-fluid rounded-circle mb-3" 
                                 style="max-width: 200px; max-height: 200px; object-fit: cover;" 
                                 alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-placeholder-large mb-3">
                                <?php echo strtoupper(substr($admin['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" name="avatar" class="form-control" accept=".jpg,.jpeg,.png" required>
                                <small class="text-muted">JPG, JPEG, PNG only. Max 2MB</small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-upload"></i> Upload Avatar
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Account Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <div class="detail-label">Username:</div>
                            <div class="detail-value"><strong><?php echo htmlspecialchars($admin['username']); ?></strong></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Role:</div>
                            <div class="detail-value">
                                <span class="badge bg-<?php echo $admin['role'] === 'super_admin' ? 'danger' : 'primary'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value">
                                <span class="badge bg-<?php echo $admin['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($admin['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Created:</div>
                            <div class="detail-value">
                                <small><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Last Login:</div>
                            <div class="detail-value">
                                <small><?php echo $admin['last_login'] ? date('M j, Y H:i', strtotime($admin['last_login'])) : 'Never'; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-edit"></i> Edit Profile</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current password">
                                <small class="text-muted">Only enter if you want to change your password</small>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
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
