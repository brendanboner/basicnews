<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireLogin();
if (!hasRole('editor')) { header('Location: index.php'); exit; }

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$items = getArticlesByStatus(array('archived'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - <?php echo SITE_TITLE; ?></title>
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
        <h1 style="font-size:18pt;margin-bottom:20px;text-align:center;border-bottom:1px solid #000;padding-bottom:10px;">Archive</h1>
        <?php if ($msg): ?><div class="message success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <p style="margin-bottom:15px;font-style:italic;">Archived stories are kept on file but not in active queues or on the live site.</p>
        <?php if (empty($items)): ?>
            <p style="font-style:italic;">No archived articles.</p>
        <?php else: ?>
            <ul class="file-list">
                <?php foreach ($items as $a): $fid = $a['fullId'] ?? ''; ?>
                <li>
                    <div class="file-info">
                        <strong><?php echo htmlspecialchars($a['title'] ?? $fid); ?></strong><br>
                        <span style="font-size:10pt;font-style:italic;">
                            By <?php echo htmlspecialchars($a['owner'] ?? ''); ?> | Archived
                            <?php if (!empty($a['updated'])): ?> | Updated: <?php echo date('Y-m-d H:i', $a['updated']); ?><?php endif; ?>
                        </span>
                    </div>
                    <div class="file-actions">
                        <a class="btn" href="edit.php?id=<?php echo urlencode($fid); ?>">Edit</a>
                        <a class="btn" href="actions.php?action=unarchive&id=<?php echo urlencode($fid); ?>&return=archive.php&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Unarchive</a>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
