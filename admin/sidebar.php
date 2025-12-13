<div class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/logo/cyber-security-club-logo.png" 
             alt="Cyber Security Club Logo" 
             style="width: 120px; height: 120px; object-fit: contain; margin-bottom: 15px; display: block; margin-left: auto; margin-right: auto;"
             onerror="this.src='../assets/images/logo/CyberCon.png'">
        <h4>CyberCon Portal</h4>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="registrations.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'registrations.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Registrations</span>
        </a>
        <a href="admins.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'admins.php' ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i>
            <span>Admins</span>
        </a>
        <a href="logs.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Activity Logs</span>
        </a>
        <a href="export.php" class="menu-item">
            <i class="fas fa-file-export"></i>
            <span>Export Data</span>
        </a>
    </div>
    
    <div class="sidebar-footer">
        <?php
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = :username");
        $stmt->bindParam(':username', $_SESSION['admin_username']);
        $stmt->execute();
        $currentAdmin = $stmt->fetch();
        ?>
        
        <div class="admin-info" style="cursor: pointer;" onclick="window.location.href='profile.php?id=<?php echo $currentAdmin['id']; ?>'">
            <?php if ($currentAdmin['avatar']): ?>
                <img src="../uploads/avatars/<?php echo htmlspecialchars($currentAdmin['avatar']); ?>" 
                     class="rounded-circle" width="35" height="35" alt="Avatar"
                     style="object-fit: cover; margin-right: 10px;">
            <?php else: ?>
                <div class="avatar-small" style="display: inline-block; margin-right: 10px;">
                    <?php echo strtoupper(substr($currentAdmin['full_name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($currentAdmin['full_name']); ?></span>
        </div>
        <a href="logout.php" class="btn btn-danger btn-sm w-100 mt-2">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
