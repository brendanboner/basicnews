<?php
require_once '../config/config.php';
if (!isLoggedIn() || isAdmin()) {
    header('Location: login.php');
    exit;
}
requirePasswordChanged(); // allows this page itself

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    bw_require_csrf();
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $file = bw_password_file_for_session();
    $user = $file && file_exists($file) ? json_decode(@file_get_contents($file), true) : null;

    if (!is_array($user) || !password_verify($current, $user['password'] ?? '')) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $user['password'] = password_hash($new, PASSWORD_DEFAULT);
        if (@file_put_contents($file, json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false) {
            $_SESSION['user'] = $user;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $message = 'Password changed successfully.';
        } else {
            $error = 'Could not save password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - <?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="nav">
        <a href="index.php">My Articles</a>
        <a href="edit.php">New Article</a>
        <?php if (hasRole('subeditor')): ?><a href="review.php">Review Queue</a><?php endif; ?>
        <?php if (hasRole('editor')): ?><a href="editor.php">Editor Queue</a><?php endif; ?>
        <a href="password.php">Change Password</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="form-container">
        <h1>Change Password</h1>
        <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST">
            <?php echo bw_csrf_field(); ?>
            <div class="form-group">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label for="new_password">New password</label>
                <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm new password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn">Change Password</button>
        </form>
    </div>
</body>
</html>
