<?php
session_start();

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check credentials from database
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = :username AND password = :password AND status = 'active'");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Update last login time
        $updateStmt = $conn->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
        $updateStmt->bindParam(':id', $admin['id']);
        $updateStmt->execute();
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['login_time'] = time();
        
        // Log login activity
        require_once '../config/logger.php';
        $logger = new ActivityLogger($conn);
        $logger->log('Admin Login', 'Admin ' . $username . ' logged in', 'admin', $admin['id']);
        
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CyberCon</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        
        .login-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-login:hover {
            background: #2563eb;
        }
        
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fdd;
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-site a {
            color: #666;
            text-decoration: none;
            font-size: 13px;
        }
        
        .back-to-site a:hover {
            color: #3b82f6;
        }
        
        .login-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
        }
        
        .login-footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .login-footer strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../assets/images/logo/cyber-security-club-logo.png" 
                 alt="Cyber Security Club Logo" 
                 onerror="this.src='../assets/images/logo/CyberCon.png'">
            <h2>Admin Login</h2>
            <p>CyberCon Portal</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            
            <button type="submit" class="btn-login">
                Login
            </button>
        </form>
        
        <div class="back-to-site">
            <a href="../index.php">← Back to Website</a>
        </div>
    </div>
    
    <footer class="login-footer">
        <p style="margin: 0;">
            Developed by <a href="https://linkedin.com/in/pran0x" target="_blank"><strong>pran0x</strong></a> | 
            All rights reserved by <strong>Cyber Security Club, Uttara University</strong>
        </p>
    </footer>
</body>
</html>
