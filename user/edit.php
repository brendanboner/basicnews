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

function generateSlug($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    if (empty($slug)) {
        $slug = 'article';
    }
    return $slug;
}

if (!$isNew) {
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
    
    if (empty($title)) {
        $error = 'Title required';
    } else {
        if ($isNew) {
            $datePrefix = date('Ymd');
            $slug = generateSlug($title);
            $baseId = $datePrefix . '-' . $slug;
            $id = $baseId;
            $counter = 1;
            while (file_exists(CONTENT_DIR . $username . '_' . $id . '.md')) {
                $id = $baseId . '-' . $counter;
                $counter++;
            }
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
                    'published' => false,
                    'slug' => $id
                );
                if (file_exists($metaFile)) {
                    $oldMeta = json_decode(file_get_contents($metaFile), true);
                    if (isset($oldMeta['published'])) {
                        $meta['published'] = $oldMeta['published'];
                    }
                    if (isset($oldMeta['date']) && !$isNew) {
                        $meta['date'] = $oldMeta['date'];
                    }
                    if (isset($oldMeta['slug'])) {
                        $meta['slug'] = $oldMeta['slug'];
                    } else {
                        $meta['slug'] = $id;
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
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
            </div>
            
            <?php if (!$isNew && isset($id)): ?>
                <div class="form-group">
                    <label>Article ID (auto-generated)</label>
                    <input type="text" value="<?php echo htmlspecialchars($id); ?>" disabled style="background: #f0f0f0;">
                </div>
            <?php endif; ?>
            
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