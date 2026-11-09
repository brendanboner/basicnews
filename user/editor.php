<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireLogin();
if (!hasRole('editor')) { header('Location: index.php'); exit; }

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
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
        <a href="logout.php">Logout</a>
    </div>
    <div style="max-width:950px;margin:0 auto;">
        <h1 style="font-size:18pt;margin-bottom:20px;text-align:center;border-bottom:1px solid #000;padding-bottom:10px;">Editor Queue</h1>
        <?php if ($msg): ?><div class="message success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
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
                            <a class="btn" href="actions.php?action=approve&id=<?php echo urlencode($fid); ?>&return=editor.php">Approve</a>
                        <?php endif; ?>
                        <?php if ($st === 'approved'): ?>
                            <a class="btn" href="actions.php?action=unapprove&id=<?php echo urlencode($fid); ?>&return=editor.php">Unapprove</a>
                        <?php endif; ?>
                        <a class="btn" href="publish.php?id=<?php echo urlencode($fid); ?>">Publish</a>
                        <form method="POST" action="actions.php?return=editor.php" style="display:inline;">
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
