<?php
require_once '../config/config.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="nav">
        <a href="index.php">Admin Panel</a>
        <a href="users.php">Manage Users</a>
        <a href="password.php">Change Password</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="form-container" style="max-width: 600px;">
        <h1>Admin Panel</h1>
        <p style="text-align: center;">Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></p>
        <p style="text-align: center; margin-top: 20px;">
            <a href="users.php" class="btn">Manage Users</a>
        </p>
    </div>
</body>
</html>