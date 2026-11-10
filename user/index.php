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
$role = getUserRole();
$message = isset($_GET['msg']) ? $_GET['msg'] : '';

$userArticles = getArticlesForUser($username);
$drafts = $submitted = 0;
foreach ($userArticles as $a) { $s = $a['status'] ?? 'draft'; if ($s==='draft') $drafts++; if (in_array($s,array('submitted','in_review','approved'),true)) $submitted++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My articles - <?php echo bw_h(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="<?php echo bw_asset('../css/style.css'); ?>">
</head>
<body class="<?php echo bw_h(bw_style_body_classes('')); ?>">
<?php bw_user_nav('articles'); ?>
<main class="bw-container">
    <?php bw_page_header('My articles', 'Write drafts, submit them for review, and track what happens next.', '<a href="edit.php" class="btn btn-primary">New article</a>'); ?>

    <?php if ($message): ?><div class="message success"><?php echo bw_h($message); ?></div><?php endif; ?>

    <section class="bw-card">
        <p class="bw-kv"><strong>Your work:</strong> <?php echo (int)$drafts; ?> draft(s), <?php echo (int)$submitted; ?> in review/pending. <?php if (hasRole('subeditor')): ?>You can also check the <a href="review.php">Review queue</a>.<?php endif; ?> <?php if (hasRole('editor')): ?>Use the <a href="editor.php">Editor queue</a> to approve, publish, archive, or rebuild the site.<?php endif; ?></p>
    </section>

    <?php if (empty($userArticles)): ?>
        <section class="bw-card">
            <p><strong>No articles yet.</strong></p>
            <p class="bw-help">Start a draft, save your work, then submit it for review when it’s ready.</p>
            <p style="margin-top:12px;"><a href="edit.php" class="btn btn-primary">Create your first article</a></p>
        </section>
    <?php else: ?>
        <section class="bw-card">
            <ul class="file-list">
                <?php foreach ($userArticles as $a):
                    $status = $a['status'] ?? (!empty($a['published']) ? 'published' : 'draft');
                    $id = $a['id'] ?? articleSlugId($a['fullId']);
                ?>
                    <li>
                        <div class="file-info">
                            <strong><?php echo bw_h($a['title'] ?? $id); ?></strong>
                            <div style="margin:6px 0 4px;"><?php echo bw_status_badge($status); ?> <?php if (!empty($a['promoted'])): ?><span class="bw-badge bw-badge-published">Front page</span><?php endif; ?></div>
                            <span style="font-size: 10pt; color:#444;">
                                <?php if (!empty($a['date'])): ?>Created <?php echo date('Y-m-d', $a['date']); ?><?php endif; ?>
                                <?php if (!empty($a['updated'])): ?> · Updated <?php echo date('Y-m-d H:i', $a['updated']); ?><?php endif; ?>
                                <?php if (!empty($a['editor_notes'])): ?><br><strong>Editor note:</strong> <?php echo bw_h($a['editor_notes']); ?><?php endif; ?>
                            </span>
                        </div>
                        <div class="file-actions">
                            <?php if (canEditArticle($a, $username, $role)): ?>
                                <a href="edit.php?id=<?php echo urlencode($a['fullId']); ?>" class="btn btn-small">Edit</a>
                            <?php endif; ?>
                            <?php if ($status === 'draft'): ?>
                                <a href="actions.php?action=submit&id=<?php echo urlencode($a['fullId']); ?>&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>" class="btn btn-small btn-primary">Submit for review</a>
                            <?php endif; ?>
                            <?php if (hasRole('editor') && $status !== 'published'): ?>
                                <a href="publish.php?id=<?php echo urlencode($a['fullId']); ?>&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>" class="btn btn-small">Publish</a>
                            <?php endif; ?>
                            <?php if (hasRole('editor') && $status === 'published'): ?>
                                <a href="unpublish.php?id=<?php echo urlencode($a['fullId']); ?>&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>" class="btn btn-small">Unpublish</a>
                                <?php if (!empty($a['promoted'])): ?>
                                <a href="promote.php?id=<?php echo urlencode($a['fullId']); ?>&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>" class="btn btn-small">Unpromote</a>
                                <?php else: ?>
                                <a href="promote.php?id=<?php echo urlencode($a['fullId']); ?>&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>" class="btn btn-small">Promote</a>
                                <?php endif; ?>
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