<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireLogin();

if (isAdmin()) {
    header('Location: ../admin/index.php');
    exit;
}

$username = $_SESSION['user']['username'];
$role = getUserRole();
$message = isset($_GET['msg']) ? $_GET['msg'] : '';

$userArticles = getArticlesForUser($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Panel - <?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="nav">
        <a href="index.php">My Articles</a>
        <a href="edit.php">New Article</a>
        <?php if (hasRole('subeditor')): ?><a href="review.php">Review Queue</a><?php endif; ?>
        <?php if (hasRole('editor')): ?><a href="editor.php">Editor Queue</a> <a href="archive.php">Archive</a><?php endif; ?>
        <a href="password.php">Change Password</a>
        <a href="logout.php">Logout (<?php echo htmlspecialchars($username . ' / ' . $role); ?>)</a>
    </div>
    
    <div style="max-width: 900px; margin: 0 auto;">
        <h1 style="font-size: 18pt; margin-bottom: 20px; text-align: center; border-bottom: 1px solid #000; padding-bottom: 10px;">
            My Articles
        </h1>
        
        <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        
        <p style="margin-bottom: 20px;">
            <a href="edit.php" class="btn">Create New Article</a>
        </p>
        
        <?php if (empty($userArticles)): ?>
            <p style="font-style: italic;">No articles yet. Create your first article.</p>
        <?php else: ?>
            <ul class="file-list">
                <?php foreach ($userArticles as $a): 
                    $status = $a['status'] ?? (!empty($a['published']) ? 'published' : 'draft');
                    $id = $a['id'] ?? articleSlugId($a['fullId']);
                ?>
                    <li>
                        <div class="file-info">
                            <strong><?php echo htmlspecialchars($a['title'] ?? $id); ?></strong><br>
                            <span style="font-size: 10pt; font-style: italic;">
                                Status: <?php echo htmlspecialchars($status); ?>
                                <?php if (!empty($a['date'])): ?> | <?php echo date('Y-m-d', $a['date']); ?><?php endif; ?>
                                <?php if (!empty($a['editor_notes'])): ?><br>Note: <?php echo htmlspecialchars($a['editor_notes']); ?><?php endif; ?>
                            </span>
                        </div>
                        <div class="file-actions">
                            <?php if (canEditArticle($a, $username, $role)): ?>
                                <a href="edit.php?id=<?php echo urlencode($a['fullId']); ?>" class="btn">Edit</a>
                            <?php endif; ?>
                            <?php if ($status === 'draft'): ?>
                                <a href="actions.php?action=submit&id=<?php echo urlencode($a['fullId']); ?>&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>" class="btn">Submit</a>
                            <?php endif; ?>
                            <?php if (hasRole('editor') && $status !== 'published'): ?>
                                <a href="publish.php?id=<?php echo urlencode($a['fullId']); ?>&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>" class="btn">Publish</a>
                            <?php endif; ?>
                            <?php if (hasRole('editor') && $status === 'published'): ?>
                                <a href="unpublish.php?id=<?php echo urlencode($a['fullId']); ?>&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>" class="btn">Unpublish</a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>