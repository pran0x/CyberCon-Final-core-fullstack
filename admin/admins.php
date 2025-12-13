<?php
require_once 'auth.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get current admin info
$currentAdmin = null;
$stmt = $conn->prepare("SELECT * FROM admins WHERE username = :username");
$stmt->bindParam(':username', $_SESSION['admin_username']);
$stmt->execute();
$currentAdmin = $stmt->fetch();

// Get all admins
$admins = $conn->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();

$success = $error = '';

// Handle new admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'admin';
    
    if ($username && $password && $fullName && $email) {
        try {
            $insertQuery = "INSERT INTO admins (username, password, full_name, email, role) 
                          VALUES (:username, :password, :full_name, :email, :role)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bindParam(':username', $username);
            $insertStmt->bindParam(':password', $password);
            $insertStmt->bindParam(':full_name', $fullName);
            $insertStmt->bindParam(':email', $email);
            $insertStmt->bindParam(':role', $role);
            
            if ($insertStmt->execute()) {
                $success = "Admin created successfully!";
                $admins = $conn->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
            }
        } catch (PDOException $e) {
            $error = "Failed to create admin: " . $e->getMessage();
        }
    } else {
        $error = "All fields are required!";
    }
}

// Handle admin deletion
if (isset($_GET['delete']) && $currentAdmin['role'] === 'super_admin') {
    $deleteId = $_GET['delete'];
    if ($deleteId != $currentAdmin['id']) {
        $conn->prepare("DELETE FROM admins WHERE id = :id")->execute([':id' => $deleteId]);
        $success = "Admin deleted successfully!";
        $admins = $conn->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - CyberCon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="top-bar mb-4">
            <h3><i class="fas fa-users-cog"></i> Admin Management</h3>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
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
        
        <?php if ($currentAdmin['role'] === 'super_admin'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-user-plus"></i> Create New Admin</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Password *</label>
                            <input type="text" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-select">
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" name="create_admin" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Admin
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> All Admins (<?php echo count($admins); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Avatar</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td>
                                        <?php if ($admin['avatar']): ?>
                                            <img src="../uploads/avatars/<?php echo htmlspecialchars($admin['avatar']); ?>" 
                                                 class="rounded-circle" width="40" height="40" alt="Avatar">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <?php echo strtoupper(substr($admin['full_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $admin['role'] === 'super_admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $admin['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($admin['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $admin['last_login'] ? date('M j, Y H:i', strtotime($admin['last_login'])) : 'Never'; ?>
                                    </td>
                                    <td>
                                        <a href="profile.php?id=<?php echo $admin['id']; ?>" class="btn btn-sm btn-info" title="View Profile">
                                            <i class="fas fa-user"></i>
                                        </a>
                                        <?php if ($currentAdmin['role'] === 'super_admin' && $admin['id'] != $currentAdmin['id']): ?>
                                            <a href="?delete=<?php echo $admin['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this admin?')"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
