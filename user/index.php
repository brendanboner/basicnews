<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireLogin();

if ($_SESSION['user']['type'] !== 'user') {
    header('Location: ../admin/index.php');
    exit;
}

$username = $_SESSION['user']['username'];
$message = '';

// Get user's files
$userFiles = array();
$files = glob(CONTENT_DIR . $username . '_*.md');
foreach ($files as $file) {
    $basename = basename($file, '.md');
    $id = str_replace($username . '_', '', $basename);
    $htmlFile = CONTENT_DIR . $username . '_' . $id . '.html';
    $metaFile = CONTENT_DIR . $username . '_' . $id . '.meta.json';
    
    $meta = array('title' => $id, 'published' => false);
    if (file_exists($metaFile)) {
        $meta = json_decode(file_get_contents($metaFile), true);
    }
    
    $userFiles[] = array(
        'id' => $id,
        'fullId' => $username . '_' . $id,
        'title' => $meta['title'],
        'published' => isset($meta['published']) && $meta['published'],
        'date' => isset($meta['date']) ? $meta['date'] : filemtime($file)
    );
}
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
        <a href="logout.php">Logout</a>
    </div>
    
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="font-size: 18pt; margin-bottom: 30px; text-align: center; border-bottom: 1px solid #000000; padding-bottom: 10px;">
            My Articles - <?php echo htmlspecialchars($username); ?>
        </h1>
        
        <p style="margin-bottom: 20px;">
            <a href="edit.php" class="btn">Create New Article</a>
        </p>
        
        <?php if (empty($userFiles)): ?>
            <p style="font-style: italic;">No articles yet. Create your first article.</p>
        <?php else: ?>
            <ul class="file-list">
                <?php foreach ($userFiles as $file): ?>
                    <li>
                        <div class="file-info">
                            <strong><?php echo htmlspecialchars($file['title']); ?></strong><br>
                            <span style="font-size: 10pt; font-style: italic;">
                                Status: <?php echo $file['published'] ? 'Published' : 'Draft'; ?> | 
                                Created: <?php echo date('Y-m-d', $file['date']); ?>
                            </span>
                        </div>
                        <div class="file-actions">
                            <a href="edit.php?id=<?php echo urlencode($file['id']); ?>" class="btn">Edit</a>
                            <?php if (!$file['published']): ?>
                                <a href="publish.php?id=<?php echo urlencode($file['id']); ?>" class="btn">Publish</a>
                            <?php else: ?>
                                <a href="unpublish.php?id=<?php echo urlencode($file['id']); ?>" class="btn">Unpublish</a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>