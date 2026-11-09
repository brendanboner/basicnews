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
$message = '';
$error = '';
$uploadMessage = '';

$idParam = isset($_GET['id']) ? trim($_GET['id']) : '';
if (isset($_POST['id'])) $idParam = trim($_POST['id']);
$isNew = ($idParam === '');

function generateSlug($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    if ($slug === '') $slug = 'article';
    return $slug;
}

function resolveFullId($idParam, $username) {
    $safe = safeArticleId($idParam);
    if ($safe === '') return '';
    if (strpos($safe, '_') !== false) return $safe;
    $own = $username . '_' . $safe;
    if (file_exists(articleMetaPath($own)) || file_exists(articleMdPath($own)) || file_exists(CONTENT_DIR . $own . '.meta.json')) return $own;
    $bundleMatches = glob(CONTENT_DIR . '*/meta.json');
    $cands = array();
    foreach ($bundleMatches as $mf) {
        $fid = basename(dirname($mf));
        if ($fid !== '' && $fid[0] !== '_' && articleSlugId($fid) === $safe) $cands[] = $fid;
    }
    if (count($cands) === 1) return $cands[0];
    foreach (glob(CONTENT_DIR . '*_' . $safe . '.meta.json') as $mf) $cands[] = basename($mf, '.meta.json');
    if (count($cands) >= 1) return $cands[0];
    return $own;
}

$title = '';
$content = '';
$meta = array();
$fullId = '';

if (!$isNew) {
    $fullId = resolveFullId($idParam, $username);
    $meta = readArticleMeta($fullId);
    $mdPath = articleMdPath($fullId);
    $legacyMd = CONTENT_DIR . $fullId . '.md';
    if (!$meta && file_exists($legacyMd)) {
        $lm = CONTENT_DIR . $fullId . '.meta.json';
        $meta = file_exists($lm) ? json_decode(@file_get_contents($lm), true) : array();
        if (!is_array($meta)) $meta = array();
        $meta['fullId'] = $fullId;
        $meta['owner'] = $meta['owner'] ?? $meta['author'] ?? articleOwner($fullId);
        $meta['status'] = $meta['status'] ?? (!empty($meta['published']) ? 'published' : 'draft');
    }
    if (!$meta && !file_exists($mdPath) && !file_exists($legacyMd)) {
        $error = 'Article not found.';
        $fullId = '';
    } elseif ($fullId !== '' && !canEditArticle($meta ?: array('owner'=>articleOwner($fullId),'status'=>'draft'), $username, $role)) {
        $error = 'You do not have permission to edit this article.';
    } elseif ($fullId !== '') {
        $title = $meta['title'] ?? articleSlugId($fullId);
        if (file_exists($mdPath)) $content = @file_get_contents($mdPath);
        elseif (file_exists($legacyMd)) $content = @file_get_contents($legacyMd);
    }
}

// Handle image upload (no-JS fallback)
if (!$isNew && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image']) && isset($_FILES['image_file'])) {
    bw_require_csrf();
    if ($fullId === '') { $error = 'Save the article before uploading images.'; }
    else {
        $mediaDir = MEDIA_DIR . $fullId . '/';
        if (!is_dir($mediaDir)) @mkdir($mediaDir, 0755, true);
        $f = $_FILES['image_file'];
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $name = basename($f['name'] ?? 'image');
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
            if ($name === '' || $name === '_' || $name === '.' || $name === '..') $name = 'image_' . time() . '.jpg';
            $target = $mediaDir . $name;
            $allowed = array('image/jpeg','image/png','image/gif','image/webp');
            $type = @mime_content_type($f['tmp_name']);
            if (in_array($type, $allowed, true) && @move_uploaded_file($f['tmp_name'], $target)) {
                $web = '../media/' . rawurlencode($fullId) . '/' . rawurlencode($name);
                $uploadMessage = 'Image uploaded. Use: ![alt](' . $web . ')';
            } else {
                $error = 'Failed to upload image (check type/permissions).';
            }
        } elseif (($f['error'] ?? 0) !== UPLOAD_ERR_NO_FILE) {
            $error = 'Image upload error.';
        }
    }
    $title = trim($_POST['title'] ?? $title);
    $content = $_POST['content'] ?? $content;
}

// Save article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_draft']) || isset($_POST['submit_review']) || isset($_POST['save']))) {
    bw_require_csrf();
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $action = isset($_POST['submit_review']) ? 'submit' : 'save';
    $notes = trim($_POST['editor_notes'] ?? '');

    if (empty($title)) {
        $error = 'Title required';
    } elseif ($fullId !== '' && !canEditArticle($meta ?: array(), $username, $role)) {
        $error = 'You do not have permission to edit this article.';
    } else {
        if ($isNew) {
            $datePrefix = date('Ymd');
            $slug = generateSlug($title);
            $baseId = $datePrefix . '-' . $slug;
            $slugId = $baseId;
            $counter = 1;
            while (file_exists(articleMdPath($username . '_' . $slugId)) || file_exists(CONTENT_DIR . $username . '_' . $slugId . '.md') || is_dir(articleDir($username . '_' . $slugId))) {
                $slugId = $baseId . '-' . $counter;
                $counter++;
            }
            $fullId = $username . '_' . $slugId;
            $meta = array();
        }
        if ($fullId !== '') {
            if (file_exists(articleMdPath($fullId)) || file_exists(CONTENT_DIR . $fullId . '.md')) saveVersion($fullId);
            $oldMeta = readArticleMeta($fullId);
            if (!$oldMeta) {
                $lm = CONTENT_DIR . $fullId . '.meta.json';
                $oldMeta = file_exists($lm) ? json_decode(@file_get_contents($lm), true) : array();
                if (!is_array($oldMeta)) $oldMeta = array();
            }
            $status = $oldMeta['status'] ?? (!empty($oldMeta['published']) ? 'published' : 'draft');
            $fromStatus = $status;
            $wasPublished = ($status === 'published') || !empty($oldMeta['published']);
            // Post-publication edits by non-editors return to the subeditor/editor queue.
            if ($wasPublished && !hasRole('editor')) {
                $status = 'submitted';
            } elseif ($action === 'submit' && $status === 'draft') {
                $status = 'submitted';
            }
            $newMeta = $oldMeta;
            $newMeta['title'] = $title;
            $newMeta['author'] = $oldMeta['author'] ?? articleOwner($fullId);
            $newMeta['owner'] = $newMeta['author'];
            $newMeta['date'] = $oldMeta['date'] ?? time();
            $newMeta['updated'] = time();
            $newMeta['slug'] = articleSlugId($fullId);
            $newMeta['status'] = $status;
            $newMeta['published'] = ($status === 'published');
            if ($notes !== '') $newMeta['editor_notes'] = $notes;
            if (writeArticleFiles($fullId, $content, $newMeta)) {
                if ($wasPublished && !hasRole('editor')) {
                    $htmlFile = articleHtmlPath($fullId);
                    if (file_exists($htmlFile)) @unlink($htmlFile);
                    $message = 'Changes saved and submitted to the editor/subeditor queue.';
                    appendWorkflowEvent(array('action' => 'submit_revision', 'id' => $fullId, 'by' => $username, 'from' => $fromStatus, 'to' => $status));
                } else {
                    if ($status === 'published') {
                        ensureArticleDir($fullId);
                        atomicWriteFile(articleHtmlPath($fullId), simpleMarkdown($content));
                    }
                    appendWorkflowEvent(array('action' => ($action === 'submit' ? 'submit' : 'save'), 'id' => $fullId, 'by' => $username, 'from' => $fromStatus, 'to' => $status));
                    $message = ($action === 'submit') ? 'Article saved and submitted for review.' : 'Saved successfully.';
                }
                $meta = readArticleMeta($fullId) ?: $newMeta;
                $isNew = false;
            } else {
                $error = 'Failed to save.';
            }
        }
    }
}

$status = $meta['status'] ?? 'draft';
$notes = $meta['editor_notes'] ?? '';
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
        <?php if (hasRole('subeditor')): ?><a href="review.php">Review Queue</a><?php endif; ?>
        <?php if (hasRole('editor')): ?><a href="editor.php">Editor Queue</a><?php endif; ?>
        <a href="password.php">Change Password</a>
        <a href="logout.php">Logout</a>
    </div>

    <div style="max-width: 900px; margin: 0 auto;">
        <h1 style="font-size: 18pt; margin-bottom: 20px; text-align: center; border-bottom: 1px solid #000; padding-bottom: 10px;">
            <?php echo $isNew ? 'New Article' : 'Edit Article'; ?>
            <?php if (!$isNew): ?><span style="font-size:11pt;font-weight:normal;">(<?php echo htmlspecialchars($status); ?>)</span><?php endif; ?>
        </h1>

        <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($uploadMessage): ?><div class="message success"><?php echo htmlspecialchars($uploadMessage); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST" id="article-form">
            <?php echo bw_csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($fullId); ?>">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
            </div>

            <?php if (!$isNew && $fullId): ?>
                <div class="form-group">
                    <label>Article ID</label>
                    <input type="text" value="<?php echo htmlspecialchars($fullId); ?>" disabled style="background: #f0f0f0;">
                </div>
            <?php endif; ?>

            <?php if (hasRole('subeditor') && !$isNew): ?>
                <div class="form-group">
                    <label for="editor_notes">Editor Notes</label>
                    <textarea id="editor_notes" name="editor_notes" style="min-height:80px;"><?php echo htmlspecialchars($notes); ?></textarea>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="content">Markdown Content</label>
                <div id="image-drop-zone" class="image-drop-zone" data-fullid="<?php echo htmlspecialchars($fullId); ?>" data-upload="upload_image.php" data-csrf="<?php echo htmlspecialchars(bw_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <textarea id="content" name="content" class="markdown-editor" required><?php echo htmlspecialchars($content); ?></textarea>
                    <p class="drop-hint"><?php if ($fullId): ?>Drag &amp; drop images here or paste an image (requires JavaScript). Without JavaScript, use the upload form below.<?php else: ?>Save the article first to enable drag-and-drop image uploads (requires JavaScript). You can still paste image URLs in Markdown, and the upload form appears after saving.<?php endif; ?></p>
                </div>
                <small style="display: block; margin-top: 5px; font-style: italic;">
                    Supported: # ## ### **bold** *italic* [link](url) ![alt](image) | Tables | Images
                </small>
            </div>

            <button type="submit" name="save_draft" class="btn">Save Draft</button>
            <?php if (($meta['owner'] ?? $username) === $username && ($status === 'draft')): ?>
                <button type="submit" name="submit_review" class="btn">Submit for Review</button>
            <?php endif; ?>
            <a href="index.php" class="btn">Cancel</a>
        </form>

        <?php if (!$isNew && $fullId): ?>
        <div style="margin-top:24px; border:1px solid #000; padding:14px;">
            <h2 style="font-size:13pt;margin-bottom:10px;">Add Image (works without JavaScript)</h2>
            <form method="POST" enctype="multipart/form-data">
                <?php echo bw_csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($fullId); ?>">
                <input type="hidden" name="title" value="<?php echo htmlspecialchars($title); ?>">
                <textarea name="content" style="display:none;"><?php echo htmlspecialchars($content); ?></textarea>
                <div class="form-group">
                    <label for="image_file">Upload image file</label>
                    <input type="file" id="image_file" name="image_file" accept="image/*">
                </div>
                <button type="submit" name="upload_image" class="btn">Upload Image</button>
            </form>
            <p style="margin-top:10px;font-size:11pt;">You can also paste an image URL directly into Markdown: <code>![caption](https://example.com/image.jpg)</code></p>
        </div>
        <?php endif; ?>
    </div>
    <script src="../js/editor.js" defer></script>
</body>
</html>
