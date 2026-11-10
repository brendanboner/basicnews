<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/ui.php';
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
    <title>Editor queue - <?php echo bw_h(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="<?php echo bw_asset('../css/style.css'); ?>">
</head>
<body class="<?php echo bw_h(bw_style_body_classes('')); ?>">
<?php bw_user_nav('editor'); ?>
<main class="bw-container">
    <?php bw_page_header('Editor queue', 'Approve, publish, archive, or send stories back. Approved stories still need “Publish” to go live.'); ?>
    <?php if ($msg): ?><div class="message success"><?php echo bw_h($msg); ?></div><?php endif; ?>

    <section class="bw-card">
        <h2>Site maintenance</h2>
        <p class="bw-help">If published pages or homepage previews look out of date, rebuild the site. This regenerates published HTML and the front-page cache (no SSH).</p>
        <form method="POST" style="margin-top:10px;">
            <?php echo bw_csrf_field(); ?>
            <button type="submit" name="rebuild_site" class="btn" onclick="return confirm('Regenerate published pages and update the cache now?');">Rebuild site (regenerate HTML + cache)</button>
        </form>
    </section>

    <?php if (empty($items)): ?>
        <section class="bw-card"><p class="bw-muted" style="font-style:italic;">No articles are in the editorial pipeline.</p></section>
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
                        <a class="btn btn-small" href="edit.php?id=<?php echo urlencode($fid); ?>">Edit</a>
                        <?php if (in_array($st, array('submitted','in_review'), true)): ?>
                            <a class="btn btn-small" href="actions.php?action=approve&id=<?php echo urlencode($fid); ?>&return=editor.php&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Approve</a>
                        <?php endif; ?>
                        <?php if ($st === 'approved'): ?>
                            <a class="btn btn-small" href="actions.php?action=unapprove&id=<?php echo urlencode($fid); ?>&return=editor.php&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Unapprove</a>
                        <?php endif; ?>
                        <a class="btn btn-small btn-primary" href="publish.php?id=<?php echo urlencode($fid); ?>&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Publish</a>
                        <a class="btn btn-small" href="actions.php?action=archive&id=<?php echo urlencode($fid); ?>&return=editor.php&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>">Archive</a>
                        <form method="POST" action="actions.php?return=editor.php" class="bw-inline-form"><?php echo bw_csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo bw_h($fid); ?>">
                            <input type="hidden" name="action" value="send_back">
                            <input type="text" name="notes" placeholder="Note" style="width:130px;padding:6px;font-size:12px;">
                            <button type="submit" class="btn btn-small">Send back</button>
                        </form>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
