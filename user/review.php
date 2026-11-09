<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireLogin();
if (!hasRole('subeditor')) { header('Location: index.php'); exit; }

$username = $_SESSION['user']['username'];
$role = getUserRole();
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$items = getArticlesByStatus(array('submitted', 'in_review'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Queue - <?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="nav">
        <a href="index.php">My Articles</a>
        <a href="edit.php">New Article</a>
        <a href="review.php">Review Queue</a>
        <?php if (hasRole('editor')): ?><a href="editor.php">Editor Queue</a><?php endif; ?>
        <a href="password.php">Change Password</a>
        <a href="logout.php">Logout</a>
    </div>
    <div style="max-width:900px;margin:0 auto;">
        <h1 style="font-size:18pt;margin-bottom:20px;text-align:center;border-bottom:1px solid #000;padding-bottom:10px;">Review Queue</h1>
        <?php if ($msg): ?><div class="message success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <?php if (empty($items)): ?>
            <p style="font-style:italic;">No articles awaiting review.</p>
        <?php else: ?>
            <ul class="file-list">
                <?php foreach ($items as $a): $fid = $a['fullId'] ?? ''; ?>
                <li>
                    <div class="file-info">
                        <strong><?php echo htmlspecialchars($a['title'] ?? $fid); ?></strong><br>
                        <span style="font-size:10pt;font-style:italic;">
                            By <?php echo htmlspecialchars($a['owner'] ?? ''); ?> | Status: <?php echo htmlspecialchars($a['status'] ?? ''); ?>
                            <?php if (!empty($a['updated'])): ?> | Updated: <?php echo date('Y-m-d H:i', $a['updated']); ?><?php endif; ?>
                            <?php if (!empty($a['editor_notes'])): ?><br>Note: <?php echo htmlspecialchars($a['editor_notes']); ?><?php endif; ?>
                        </span>
                    </div>
                    <div class="file-actions">
                        <a class="btn" href="edit.php?id=<?php echo urlencode($fid); ?>">Edit/Subedit</a>
                        <?php if (($a['status'] ?? '') === 'submitted'): ?>
                            <a class="btn" href="actions.php?action=start_review&id=<?php echo urlencode($fid); ?>&return=review.ph&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Start Review</a>
                        <?php endif; ?>
                        <form method="POST" action="actions.php?return=review.php" style="display:inline;"><?php echo bw_csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($fid); ?>">
                            <input type="hidden" name="action" value="send_back">
                            <input type="text" name="notes" placeholder="Note (optional)" style="width:140px;padding:6px;font-size:12px;">
                            <button type="submit" class="btn">Send Back</button>
                        </form>
                        <?php if (hasRole('editor')): ?>
                            <a class="btn" href="actions.php?action=approve&id=<?php echo urlencode($fid); ?>&return=review.ph&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Approve</a>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
