<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    if (!empty($_SESSION['force_password_change'])) {
        header('Location: ' . (isAdmin() ? 'admin/password.php?forced=1' : 'user/password.php?forced=1'));
    } else {
        header('Location: ' . (isAdmin() ? 'admin/index.php' : 'user/index.php'));
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    bw_require_csrf();
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '') {
        $error = 'Invalid credentials';
    } else {
        // Check admin account (admin.json) and regular user accounts.
        $candidates = array();
        $candidates[] = USERS_DIR . $username . '.json';
        if ($username === 'admin') $candidates[] = USERS_DIR . 'admin.json';

        $authed = false;
        foreach (array_unique($candidates) as $userFile) {
            if (!file_exists($userFile)) continue;
            $user = json_decode(@file_get_contents($userFile), true);
            if (!is_array($user)) continue;
            if (!password_verify($password, $user['password'] ?? '')) continue;

            $isAdminUser = (($user['type'] ?? '') === 'admin') || (($user['role'] ?? '') === 'admin') || (($user['username'] ?? '') === 'admin' && basename($userFile) === 'admin.json');
            if (!$isAdminUser && !isset($user['role']) && ($user['type'] ?? '') === '') {
                // Unknown account shape; skip.
                continue;
            }
            if (!$isAdminUser && !isset($user['role'])) $user['role'] = 'contributor';

            session_regenerate_id(true);
            $_SESSION['user'] = $user;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if ($isAdminUser) {
                $_SESSION['force_password_change'] = ($password === 'admin' || password_verify('admin', $user['password'] ?? ''));
                header('Location: ' . (!empty($_SESSION['force_password_change']) ? 'admin/password.php?forced=1' : 'admin/index.php'));
            } else {
                unset($_SESSION['force_password_change']);
                header('Location: user/index.php');
            }
            exit;
        }
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars(SITE_TITLE, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <?php if (is_file(__DIR__ . '/css/custom.css')): ?><link rel="stylesheet" href="css/custom.css"><?php endif; ?>
</head>
<body class="<?php echo htmlspecialchars(bw_style_body_classes(''), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="form-container">
        <h1>Login</h1>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php echo bw_csrf_field(); ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username" autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <p style="text-align: center; margin-top: 20px; font-style: italic;">
            <a href="index.php" style="color: inherit;">← Back to site</a>
        </p>
    </div>
</body>
</html>
