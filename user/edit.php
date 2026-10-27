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
$error = '';

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
$isNew = empty($id);

$mdFile = '';
$htmlFile = '';
$metaFile = '';
$title = '';
$content = '';

if (!$isNew) {
    // Sanitize id
    if (preg_match('/[^a-zA-Z0-9_-]/', $id)) {
        $error = 'Invalid ID';
    } else {
        $mdFile = CONTENT_DIR . $username . '_' . $id . '.md';
        $metaFile = CONTENT_DIR . $username . '_' . $id . '.meta.json';
        
        if (file_exists($mdFile)) {
            $content = file_get_contents($mdFile);
        }
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            $title = isset($meta['title']) ? $meta['title'] : $id;
        } else {
            $title = $id;
        }
    }
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $articleId = trim($_POST['article_id'] ?? '');
    
    if (empty($title)) {
        $error = 'Title required';
    } elseif ($isNew && empty($articleId)) {
        $error = 'Article ID required';
    } elseif ($isNew && preg_match('/[^a-zA-Z0-9_-]/', $articleId)) {
        $error = 'Article ID can only contain letters, numbers, hyphens, and underscores';
    } else {
        if ($isNew) {
            $id = $articleId;
            $mdFile = CONTENT_DIR . $username . '_' . $id . '.md';
            if (file_exists($mdFile)) {
                $error = 'Article ID already exists';
            }
        }
        
        if (!$error) {
            $mdFile = CONTENT_DIR . $username . '_' . $id . '.md';
            $metaFile = CONTENT_DIR . $username . '_' . $id . '.meta.json';
            
            if (file_put_contents($mdFile, $content)) {
                $meta = array(
                    'title' => $title,
                    'author' => $username,
                    'date' => time(),
                    'published' => false
                );
                // Preserve published status if editing existing
                if (file_exists($metaFile)) {
                    $oldMeta = json_decode(file_get_contents($metaFile), true);
                    if (isset($oldMeta['published'])) {
                        $meta['published'] = $oldMeta['published'];
                    }
                    if (isset($oldMeta['date']) && $isNew === false) {
                        // Keep original date on edit
                    }
                }
                file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT));
                $message = 'Saved successfully';
                $isNew = false;
            } else {
                $error = 'Failed to save';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isNew ? 'New Article' : 'Edit Article'; ?> - <?php echo SITE_TITLE; ?></title>
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
            <?php echo $isNew ? 'New Article' : 'Edit Article'; ?>
        </h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?php if ($isNew): ?>
                <div class="form-group">
                    <label for="article_id">Article ID (URL identifier, no spaces)</label>
                    <input type="text" id="article_id" name="article_id" required>
                </div>
            <?php else: ?>
                <input type="hidden" name="article_id" value="<?php echo htmlspecialchars($id); ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="content">Markdown Content</label>
                <textarea id="content" name="content" class="markdown-editor" required><?php echo htmlspecialchars($content); ?></textarea>
                <small style="display: block; margin-top: 5px; font-style: italic;">
                    Supported: # ## ### **bold** *italic* [link](url) ![alt](image.jpg)
                </small>
            </div>
            
            <button type="submit" class="btn">Save</button>
            <a href="index.php" class="btn">Cancel</a>
        </form>
    </div>
</body>
</html>