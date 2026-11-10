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
                    @unlink(CACHE_DIR . 'published.json');
                    $message = 'Changes saved and submitted to the editor/subeditor queue.';
                    appendWorkflowEvent(array('action' => 'submit_revision', 'id' => $fullId, 'by' => $username, 'from' => $fromStatus, 'to' => $status));
                } else {
                    if ($status === 'published') {
                        ensureArticleDir($fullId);
                        atomicWriteFile(articleHtmlPath($fullId), simpleMarkdown($content));
                        @unlink(CACHE_DIR . 'published.json');
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
$canSubmit = (!$isNew && ($meta['owner'] ?? $username) === $username && $status === 'draft');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isNew ? 'New article' : 'Edit article'; ?> - <?php echo bw_h(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="<?php echo bw_asset('../css/style.css'); ?>">
</head>
<body class="<?php echo bw_h(bw_style_body_classes('')); ?>">
<?php bw_user_nav($isNew ? 'new' : 'articles'); ?>
<main class="bw-container">
    <?php
    $subtitle = $isNew ? 'Write in Markdown, save a draft, then submit it when you’re ready for review.' : 'Edit the story below. Your changes follow the review/publishing workflow for your role.';
    bw_page_header($isNew ? 'New article' : 'Edit article', $subtitle, '<a href="index.php" class="btn">Back to my articles</a>');
    ?>

    <?php if (!$isNew): ?><p style="margin:-8px 0 14px;"><?php echo bw_status_badge($status); ?><?php if ($fullId): ?> <span class="bw-muted" style="font-size:12px;">ID: <?php echo bw_h($fullId); ?></span><?php endif; ?></p><?php endif; ?>
    <?php if ($message): ?><div class="message success"><?php echo bw_h($message); ?></div><?php endif; ?>
    <?php if ($uploadMessage): ?><div class="message success"><?php echo bw_h($uploadMessage); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?php echo bw_h($error); ?></div><?php endif; ?>

    <section class="bw-card">
        <form method="POST" id="article-form">
            <?php echo bw_csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo bw_h($fullId); ?>">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo bw_h($title); ?>" required>
            </div>

            <?php if (hasRole('subeditor') && !$isNew): ?>
                <div class="form-group">
                    <label for="editor_notes">Editor notes (visible to writers in the workflow)</label>
                    <textarea id="editor_notes" name="editor_notes" style="min-height:80px;"><?php echo bw_h($notes); ?></textarea>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="content">Story (Markdown)</label>
                <div id="image-drop-zone" class="image-drop-zone" data-fullid="<?php echo bw_h($fullId); ?>" data-upload="upload_image.php" data-csrf="<?php echo bw_h(bw_csrf_token()); ?>">
                    <textarea id="content" name="content" class="markdown-editor" required><?php echo bw_h($content); ?></textarea>
                    <p class="drop-hint"><?php if ($fullId): ?>Drag &amp; drop or paste images here (with JavaScript). Without JavaScript, use “Add image” below.<?php else: ?>Save the article first to enable drag-and-drop/paste uploads (with JavaScript). You can still use image URLs in Markdown now.<?php endif; ?></p>
                </div>
                <p class="bw-help">Formatting: <code>#</code> headings, <code>**bold**</code>, <code>*italic*</code>, <code>[link](url)</code>, <code>![image](url)</code>, tables, blockquotes.</p>
            </div>

            <div class="bw-page-actions">
                <button type="submit" name="save_draft" class="btn<?php echo $canSubmit ? '' : ' btn-primary'; ?>">Save draft</button>
                <?php if ($canSubmit): ?>
                    <button type="submit" name="submit_review" class="btn btn-primary">Submit for review</button>
                <?php endif; ?>
                <a href="index.php" class="btn">Cancel</a>
            </div>
            <?php if (!$isNew && ($status === 'published') && !hasRole('editor')): ?>
                <p class="bw-help" style="margin-top:12px;"><strong>Note:</strong> if you save changes to a published story, it will be sent back for review and taken off the live site until an editor republishes it.</p>
            <?php endif; ?>
        </form>
    </section>

    <?php if (!$isNew && $fullId): ?>
    <section class="bw-card">
        <h2>Add image (works without JavaScript)</h2>
        <form method="POST" enctype="multipart/form-data">
            <?php echo bw_csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo bw_h($fullId); ?>">
            <input type="hidden" name="title" value="<?php echo bw_h($title); ?>">
            <textarea name="content" style="display:none;"><?php echo bw_h($content); ?></textarea>
            <div class="form-group">
                <label for="image_file">Upload image file</label>
                <input type="file" id="image_file" name="image_file" accept="image/*">
            </div>
            <button type="submit" name="upload_image" class="btn">Upload image</button>
        </form>
        <p class="bw-help">Or paste an image URL directly into Markdown: <code>![caption](https://example.com/image.jpg)</code></p>
    </section>
    <?php endif; ?>
</main>
<script src="../js/editor.js" defer></script>
</body>
</html>
