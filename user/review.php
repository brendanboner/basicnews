<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/ui.php';
requireLogin();
if (!hasRole('subeditor')) { header('Location: index.php'); exit; }

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$items = getArticlesByStatus(array('submitted', 'in_review'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review queue - <?php echo bw_h(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="<?php echo bw_asset('../css/style.css'); ?>">
</head>
<body class="<?php echo bw_h(bw_style_body_classes('')); ?>">
<?php bw_user_nav('review'); ?>
<main class="bw-container">
    <?php bw_page_header('Review queue', 'Stories submitted by writers. Open a story to edit/subedit, leave a note, send it back, or move it forward.'); ?>
    <?php if ($msg): ?><div class="message success"><?php echo bw_h($msg); ?></div><?php endif; ?>

    <?php if (empty($items)): ?>
        <section class="bw-card"><p class="bw-muted" style="font-style:italic;">No articles are waiting for review right now.</p></section>
    <?php else: ?>
        <section class="bw-card">
            <ul class="file-list">
                <?php foreach ($items as $a): $fid = $a['fullId'] ?? ''; $st = $a['status'] ?? ''; ?>
                <li>
                    <div class="file-info">
                        <strong><?php echo bw_h($a['title'] ?? $fid); ?></strong>
                        <div style="margin:6px 0 4px;"><?php echo bw_status_badge($st); ?></div>
                        <span style="font-size:10pt;color:#444;">
                            By <?php echo bw_h($a['owner'] ?? ''); ?>
                            <?php if (!empty($a['updated'])): ?> · Updated <?php echo date('Y-m-d H:i', $a['updated']); ?><?php endif; ?>
                            <?php if (!empty($a['editor_notes'])): ?><br><strong>Note:</strong> <?php echo bw_h($a['editor_notes']); ?><?php endif; ?>
                        </span>
                    </div>
                    <div class="file-actions">
                        <a class="btn btn-small" href="edit.php?id=<?php echo urlencode($fid); ?>">Edit / subedit</a>
                        <?php if ($st === 'submitted'): ?>
                            <a class="btn btn-small" href="actions.php?action=start_review&id=<?php echo urlencode($fid); ?>&return=review.php&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Start review</a>
                        <?php endif; ?>
                        <form method="POST" action="actions.php?return=review.php" class="bw-inline-form"><?php echo bw_csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo bw_h($fid); ?>">
                            <input type="hidden" name="action" value="send_back">
                            <input type="text" name="notes" placeholder="Note for writer (optional)" style="width:190px;padding:6px;font-size:12px;">
                            <button type="submit" class="btn btn-small">Send back to draft</button>
                        </form>
                        <?php if (hasRole('editor')): ?>
                            <a class="btn btn-small btn-primary" href="actions.php?action=approve&id=<?php echo urlencode($fid); ?>&return=review.php&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Approve</a>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
