<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/rebuild.php';
requireLogin();
if (!hasRole('editor')) { header('Location: index.php'); exit; }

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rebuild_site'])) {
    bw_require_csrf();
    @set_time_limit(120);
    $r = bw_rebuild_site(true, true, true);
    $msg = 'Site rebuilt: indexed ' . (int)$r['indexed'] . ', regenerated HTML ' . (int)$r['html'] . ', cache updated.';
    if (!empty($r['warnings'])) $msg .= ' Warnings: ' . implode('; ', $r['warnings']);
}
$items = getArticlesByStatus(array('submitted', 'in_review', 'approved'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor Queue - <?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="nav">
        <a href="index.php">My Articles</a>
        <a href="edit.php">New Article</a>
        <a href="review.php">Review Queue</a>
        <a href="editor.php">Editor Queue</a>
        <a href="archive.php">Archive</a>
        <a href="password.php">Change Password</a>
        <a href="logout.php">Logout</a>
    </div>
    <div style="max-width:950px;margin:0 auto;">
        <h1 style="font-size:18pt;margin-bottom:20px;text-align:center;border-bottom:1px solid #000;padding-bottom:10px;">Editor Queue</h1>
        <?php if ($msg): ?><div class="message success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <form method="POST" style="margin-bottom:18px;padding:10px;border:1px solid #000;background:#fafafa;">
            <?php echo bw_csrf_field(); ?>
            <strong>Maintenance:</strong>
            <button type="submit" name="rebuild_site" class="btn" onclick="return confirm('Regenerate published pages and update the cache now?');">Rebuild site (regenerate HTML + cache)</button>
            <span style="font-size:10pt;font-style:italic;margin-left:8px;">Safe to run if pages or previews look out of date. No SSH needed.</span>
        </form>
        <?php if (empty($items)): ?>
            <p style="font-style:italic;">No articles in the editorial pipeline.</p>
        <?php else: ?>
            <ul class="file-list">
                <?php foreach ($items as $a): $fid = $a['fullId'] ?? ''; $st = $a['status'] ?? ''; ?>
                <li>
                    <div class="file-info">
                        <strong><?php echo htmlspecialchars($a['title'] ?? $fid); ?></strong><br>
                        <span style="font-size:10pt;font-style:italic;">
                            By <?php echo htmlspecialchars($a['owner'] ?? ''); ?> | Status: <?php echo htmlspecialchars($st); ?>
                            <?php if (!empty($a['updated'])): ?> | Updated: <?php echo date('Y-m-d H:i', $a['updated']); ?><?php endif; ?>
                            <?php if (!empty($a['editor_notes'])): ?><br>Note: <?php echo htmlspecialchars($a['editor_notes']); ?><?php endif; ?>
                        </span>
                    </div>
                    <div class="file-actions">
                        <a class="btn" href="edit.php?id=<?php echo urlencode($fid); ?>">Edit</a>
                        <?php if (in_array($st, array('submitted','in_review'), true)): ?>
                            <a class="btn" href="actions.php?action=approve&id=<?php echo urlencode($fid); ?>&return=editor.php&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Approve</a>
                        <?php endif; ?>
                        <?php if ($st === 'approved'): ?>
                            <a class="btn" href="actions.php?action=unapprove&id=<?php echo urlencode($fid); ?>&return=editor.php&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Unapprove</a>
                        <?php endif; ?>
                        <a class="btn" href="publish.php?id=<?php echo urlencode($fid); ?>&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Publish</a>
                        <a class="btn" href="actions.php?action=archive&id=<?php echo urlencode($fid); ?>&return=editor.php&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Archive</a>
                        <form method="POST" action="actions.php?return=editor.php" style="display:inline;"><?php echo bw_csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($fid); ?>">
                            <input type="hidden" name="action" value="send_back">
                            <input type="text" name="notes" placeholder="Note" style="width:120px;padding:6px;font-size:12px;">
                            <button type="submit" class="btn">Send Back</button>
                        </form>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
