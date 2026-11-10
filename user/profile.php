<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/ui.php';
requireLogin();

if (isAdmin()) {
    header('Location: ../admin/index.php');
    exit;
}

$username = $_SESSION['user']['username'];
$profile = bw_user_profile($username);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    bw_require_csrf();
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $title = trim((string)($_POST['title'] ?? ''));
    $bio = trim((string)($_POST['bio'] ?? ''));

    if (mb_strlen($fullName) > 100) {
        $error = 'Full name must be 100 characters or fewer.';
    } elseif (mb_strlen($title) > 100) {
        $error = 'Title must be 100 characters or fewer.';
    } elseif (mb_strlen($bio) > 1200) {
        $error = 'Biography must be 1200 characters or fewer.';
    } else {
        $user = bw_read_user($username, true);
        if (!is_array($user)) {
            $error = 'Could not load your account.';
        } else {
            $user['full_name'] = $fullName;
            $user['title'] = $title;
            $user['bio'] = $bio;
            $file = bw_user_file_path($username);
            if ($file !== '' && @file_put_contents($file, json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false) {
                $_SESSION['user'] = $user;
                $profile = bw_user_profile($username, true);
                $message = 'Profile saved. Your byline will use this name on your stories.';
            } else {
                $error = 'Could not save your profile. Check permissions on the users directory.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your profile - <?php echo bw_h(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="<?php echo bw_asset('../css/style.css'); ?>">
</head>
<body class="<?php echo bw_h(bw_style_body_classes('')); ?>">
<?php bw_user_nav('profile'); ?>
<main class="bw-container-narrow">
    <?php bw_page_header('Your profile', 'This is the name and description readers see with your stories.'); ?>

    <?php if ($message): ?><div class="message success"><?php echo bw_h($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?php echo bw_h($error); ?></div><?php endif; ?>

    <section class="bw-card">
        <form method="POST">
            <?php echo bw_csrf_field(); ?>
            <div class="form-group">
                <label for="full_name">Author full name</label>
                <input type="text" id="full_name" name="full_name" maxlength="100" value="<?php echo bw_h($profile['full_name']); ?>" placeholder="e.g. Jane Doe" autocomplete="name">
                <p class="bw-help">Used for the public byline. Leave blank to use your username.</p>
            </div>
            <div class="form-group">
                <label for="title">Title / role</label>
                <input type="text" id="title" name="title" maxlength="100" value="<?php echo bw_h($profile['title']); ?>" placeholder="e.g. Staff Writer">
            </div>
            <div class="form-group">
                <label for="bio">Short biography</label>
                <textarea id="bio" name="bio" maxlength="1200" style="min-height:140px;"><?php echo bw_h($profile['bio']); ?></textarea>
                <p class="bw-help">Appears at the end of your stories. Keep it brief and print-friendly.</p>
            </div>

            <p class="bw-kv" style="margin-bottom:16px;"><strong>Byline preview:</strong> By <?php echo bw_h($profile['display_name']); ?><?php if ($profile['title'] !== ''): ?>, <?php echo bw_h($profile['title']); ?><?php endif; ?></p>
            <button type="submit" class="btn btn-primary">Save profile</button>
            <a href="index.php" class="btn">Cancel</a>
        </form>
    </section>

    <section class="bw-card">
        <h2>Account</h2>
        <p class="bw-kv"><strong>Username:</strong> <?php echo bw_h($username); ?></p>
        <p class="bw-kv"><strong>Role:</strong> <?php echo bw_h(getUserRole()); ?></p>
        <p class="bw-help">Your username stays private. The full name above is used on the public site.</p>
        <p style="margin-top:12px;"><a class="btn" href="password.php">Change password</a></p>
    </section>
</main>
</body>
</html>
