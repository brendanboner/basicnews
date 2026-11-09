<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/ui.php';
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
    <title>Archive - <?php echo bw_h(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="<?php echo bw_h(bw_style_body_classes('')); ?>">
<?php bw_user_nav('archive'); ?>
<main class="bw-container">
    <?php bw_page_header('Archive', 'Archived stories stay on file (content and history preserved) but are removed from active queues and the live site. Unarchive restores a story to draft.'); ?>
    <?php if ($msg): ?><div class="message success"><?php echo bw_h($msg); ?></div><?php endif; ?>

    <?php if (empty($items)): ?>
        <section class="bw-card"><p class="bw-muted" style="font-style:italic;">No archived articles.</p></section>
    <?php else: ?>
        <section class="bw-card">
            <ul class="file-list">
                <?php foreach ($items as $a): $fid = $a['fullId'] ?? ''; ?>
                <li>
                    <div class="file-info">
                        <strong><?php echo bw_h($a['title'] ?? $fid); ?></strong>
                        <div style="margin:6px 0 4px;"><?php echo bw_status_badge('archived'); ?></div>
                        <span style="font-size:10pt;color:#444;">
                            By <?php echo bw_h($a['owner'] ?? ''); ?>
                            <?php if (!empty($a['updated'])): ?> · Updated <?php echo date('Y-m-d H:i', $a['updated']); ?><?php endif; ?>
                        </span>
                    </div>
                    <div class="file-actions">
                        <a class="btn btn-small" href="edit.php?id=<?php echo urlencode($fid); ?>">Edit</a>
                        <a class="btn btn-small btn-primary" href="actions.php?action=unarchive&id=<?php echo urlencode($fid); ?>&return=archive.php&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Unarchive to draft</a>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
