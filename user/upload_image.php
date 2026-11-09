<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || isAdmin()) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'Not authorized'));
    exit;
}
if (!bw_verify_csrf()) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'Invalid security token'));
    exit;
}

$username = $_SESSION['user']['username'];
$role = getUserRole();
$idParam = isset($_POST['id']) ? trim($_POST['id']) : '';
$fullId = safeArticleId($idParam);

if ($fullId === '' || strpos($fullId, '_') === false) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'Save the article before uploading images.'));
    exit;
}

$meta = readArticleMeta($fullId);
if (!$meta) {
    $lm = CONTENT_DIR . $fullId . '.meta.json';
    $meta = file_exists($lm) ? json_decode(@file_get_contents($lm), true) : array();
    if (!is_array($meta)) $meta = array();
    $meta['fullId'] = $fullId;
    $meta['owner'] = $meta['owner'] ?? $meta['author'] ?? articleOwner($fullId);
    $meta['status'] = $meta['status'] ?? 'draft';
}
if (!canEditArticle($meta, $username, $role) && ($meta['owner'] ?? '') !== $username) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'Permission denied'));
    exit;
}

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'No image received'));
    exit;
}

$f = $_FILES['image'];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'Upload failed'));
    exit;
}

$maxBytes = 5 * 1024 * 1024;
if (($f['size'] ?? 0) > $maxBytes) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'Image too large (max 5 MB)'));
    exit;
}

$tmp = $f['tmp_name'];
$type = @mime_content_type($tmp);
$allowed = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp');
if (!isset($allowed[$type])) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'Unsupported image type'));
    exit;
}

$orig = basename($f['name'] ?? '');
$orig = preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig);
$ext = $allowed[$type];
$base = preg_replace('/\.[^.]+$/', '', $orig);
if ($base === '' || $base === '_' || $base === '.') $base = 'image';
$name = $base . '-' . substr(sha1(uniqid('', true)), 0, 8) . '.' . $ext;

$dir = MEDIA_DIR . $fullId . '/';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
$target = $dir . $name;

if (!@move_uploaded_file($tmp, $target)) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'Failed to store image'));
    exit;
}

appendWorkflowEvent(array('action' => 'upload_image', 'id' => $fullId, 'by' => $username, 'file' => $name));
$publicPath = 'media/' . rawurlencode($fullId) . '/' . rawurlencode($name);
$editorPath = '../' . $publicPath;
echo json_encode(array(
    'ok' => true,
    'path' => $publicPath,
    'editorPath' => $editorPath,
    'markdown' => '![' . $base . '](' . $editorPath . ')',
    'name' => $name
));
?>