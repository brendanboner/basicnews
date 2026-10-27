<?php
require_once '../config/config.php';
requireAdmin();

$message = '';
$error = '';

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password required';
    } elseif (preg_match('/[^a-zA-Z0-9_]/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores';
    } elseif ($username === 'admin') {
        $error = 'Cannot create user named admin';
    } else {
        $userFile = USERS_DIR . $username . '.json';
        if (file_exists($userFile)) {
            $error = 'User already exists';
        } else {
            $user = array(
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'type' => 'user'
            );
            if (file_put_contents($userFile, json_encode($user, JSON_PRETTY_PRINT))) {
                $message = 'User created successfully';
            } else {
                $error = 'Failed to create user';
            }
        }
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $username = $_GET['delete'];
    if ($username !== 'admin') {
        $userFile = USERS_DIR . $username . '.json';
        if (file_exists($userFile)) {
            unlink($userFile);
            $message = 'User deleted successfully';
        }
    }
}

// Get all users
$users = array();
foreach (glob(USERS_DIR . '*.json') as $file) {
    $user = json_decode(file_get_contents($file), true);
    if ($user['type'] === 'user') {
        $users[] = $user;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="nav">
        <a href="index.php">Admin Panel</a>
        <a href="users.php">Manage Users</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <div style="max-width: 700px; margin: 0 auto;">
        <h1 style="font-size: 18pt; margin-bottom: 30px; text-align: center; border-bottom: 1px solid #000000; padding-bottom: 10px;">Manage Users</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div style="border: 1px solid #000000; padding: 20px; margin-bottom: 30px;">
            <h2 style="font-size: 14pt; margin-bottom: 15px;">Create New User</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="create" class="btn">Create User</button>
            </form>
        </div>
        
        <h2 style="font-size: 14pt; margin-bottom: 15px;">Existing Users</h2>
        <?php if (empty($users)): ?>
            <p style="font-style: italic;">No users created yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>
                                <a href="users.php?delete=<?php echo urlencode($user['username']); ?>" 
                                   class="btn" 
                                   onclick="return confirm('Delete this user?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>