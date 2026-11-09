<?php
require_once '../config/config.php';
requireAdmin();

$message = '';
$error = '';
$settings = bw_site_settings();
$customCssFile = __DIR__ . '/../css/custom.css';
$customCss = is_file($customCssFile) ? (string)@file_get_contents($customCssFile) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    bw_require_csrf();
    $siteTitle = trim((string)($_POST['site_title'] ?? ''));
    $edition = trim((string)($_POST['edition'] ?? ''));
    $customCss = (string)($_POST['custom_css'] ?? '');
    if ($siteTitle === '') $siteTitle = 'The Daily Fuckall';
    if ($edition === '') $edition = 'Late Edition';
    // Basic safety: limit size, no PHP tags (it's a .css file but be defensive)
    if (strlen($customCss) > 100000) $customCss = substr($customCss, 0, 100000);
    $customCss = str_replace(array('<?', '?>'), '', $customCss);

    $ok1 = @file_put_contents(SITE_SETTINGS_FILE, json_encode(array('site_title' => $siteTitle, 'edition' => $edition), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
    $ok2 = @file_put_contents($customCssFile, $customCss) !== false;
    @unlink(CACHE_DIR . 'published.json');

    if ($ok1 && $ok2) {
        $message = 'Settings saved.';
        $settings = bw_site_settings(); // reload cache-busted? static in request
    } elseif ($ok1) {
        $message = 'Site settings saved, but custom CSS could not be written (check permissions on css/custom.css).';
    } else {
        $error = 'Could not save settings (check permissions on content/).';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - <?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="nav">
        <a href="index.php">Admin Panel</a>
        <a href="users.php">Manage Users</a>
        <a href="settings.php">Site Settings</a>
        <a href="password.php">Change Password</a>
        <a href="logout.php">Logout</a>
    </div>

    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="font-size: 18pt; margin-bottom: 20px; text-align: center; border-bottom: 1px solid #000; padding-bottom: 10px;">Site Settings</h1>
        <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST">
            <?php echo bw_csrf_field(); ?>
            <div class="form-group">
                <label for="site_title">Site title (masthead)</label>
                <input type="text" id="site_title" name="site_title" value="<?php echo htmlspecialchars($settings['site_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="form-group">
                <label for="edition">Edition / subhead text</label>
                <input type="text" id="edition" name="edition" value="<?php echo htmlspecialchars($settings['edition'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-group">
                <label for="custom_css">Custom CSS (optional, loads after main stylesheet; fast, file-based)</label>
                <textarea id="custom_css" name="custom_css" class="markdown-editor" style="min-height:220px;font-family:'Courier New',monospace;font-size:12px;"><?php echo htmlspecialchars($customCss, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <small style="display:block;margin-top:5px;font-style:italic;">Use simple overrides (colors, fonts, spacing). Avoid external @import/dependencies to keep it fast.</small>
            </div>
            <button type="submit" class="btn">Save Settings</button>
        </form>
    </div>
</body>
</html>
