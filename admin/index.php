<?php
require_once '../config/config.php';
require_once '../includes/ui.php';
require_once '../includes/functions.php';
requireAdmin();

$userCount = 0;
$roleCounts = array('contributor' => 0, 'subeditor' => 0, 'editor' => 0);
foreach (glob(USERS_DIR . '*.json') as $file) {
    $u = json_decode(@file_get_contents($file), true);
    if (!is_array($u) || ($u['type'] ?? '') !== 'user') continue;
    $userCount++;
    $r = $u['role'] ?? 'contributor';
    if (!isset($roleCounts[$r])) $roleCounts[$r] = 0;
    $roleCounts[$r]++;
}
$manifest = loadManifest();
$articles = is_array($manifest['articles'] ?? null) ? $manifest['articles'] : array();
$statusCounts = array('draft'=>0,'submitted'=>0,'in_review'=>0,'approved'=>0,'published'=>0,'archived'=>0);
foreach ($articles as $a) { $s = $a['status'] ?? 'draft'; if (!isset($statusCounts[$s])) $statusCounts[$s]=0; $statusCounts[$s]++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo bw_h(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="<?php echo bw_h(bw_style_body_classes('')); ?>">
<?php bw_admin_nav('dashboard'); ?>
<main class="bw-container">
    <?php bw_page_header('Dashboard', 'Manage people, site settings, and the publishing system from one place.'); ?>

    <div class="bw-grid" style="margin-bottom:16px;">
        <div class="bw-stat"><div class="bw-stat-num"><?php echo (int)$userCount; ?></div><div class="bw-stat-label">User accounts</div></div>
        <div class="bw-stat"><div class="bw-stat-num"><?php echo (int)($statusCounts['published'] ?? 0); ?></div><div class="bw-stat-label">Published articles</div></div>
        <div class="bw-stat"><div class="bw-stat-num"><?php echo (int)(($statusCounts['submitted'] ?? 0) + ($statusCounts['in_review'] ?? 0) + ($statusCounts['approved'] ?? 0)); ?></div><div class="bw-stat-label">In editorial pipeline</div></div>
        <div class="bw-stat"><div class="bw-stat-num"><?php echo (int)($statusCounts['draft'] ?? 0); ?></div><div class="bw-stat-label">Drafts</div></div>
    </div>

    <div class="bw-grid">
        <section class="bw-card">
            <h2>Users &amp; roles</h2>
            <p class="bw-kv">Contributors: <strong><?php echo (int)$roleCounts['contributor']; ?></strong> · Subeditors: <strong><?php echo (int)$roleCounts['subeditor']; ?></strong> · Editors: <strong><?php echo (int)$roleCounts['editor']; ?></strong></p>
            <p class="bw-help">Create accounts and assign roles: contributor (writes), subeditor (reviews), editor (approves/publishes).</p>
            <p style="margin-top:12px;"><a href="users.php" class="btn btn-primary">Manage users</a></p>
        </section>
        <section class="bw-card">
            <h2>Site settings &amp; style</h2>
            <p class="bw-help">Set the masthead/edition and choose preset styles (theme, text size, homepage info, spacing) without writing CSS.</p>
            <p style="margin-top:12px;"><a href="settings.php" class="btn btn-primary">Open site settings</a></p>
        </section>
        <section class="bw-card">
            <h2>Security</h2>
            <p class="bw-help">Keep the admin password strong and ensure your web server blocks sensitive paths (<code>/config</code>, <code>/includes</code>, <code>/users</code>, <code>/content/_*</code>, etc.).</p>
            <p style="margin-top:12px;"><a href="password.php" class="btn">Change admin password</a> <a href="../login.php" class="btn">Login page</a></p>
        </section>
    </div>
</main>
</body>
</html>
