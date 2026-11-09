<?php
require_once '../config/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    bw_require_csrf();
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $userFile = ($username === 'admin') ? USERS_DIR . 'admin.json' : USERS_DIR . $username . '.json';
    if ($username !== '' && file_exists($userFile)) {
        $user = json_decode(@file_get_contents($userFile), true);
        if (is_array($user) && password_verify($password, $user['password'] ?? '')) {
            session_regenerate_id(true);
            $_SESSION['user'] = $user;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: index.php');
            exit;
        }
    }
    $error = 'Invalid credentials';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="form-container">
        <h1>Admin Login</h1>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?php echo bw_csrf_field(); ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn">Login</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; font-style: italic;">
            <a href="../index.php" style="color: #000000;">← Back to site</a>
        </p>
    </div>
</body>
</html>