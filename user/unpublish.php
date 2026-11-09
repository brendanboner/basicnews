<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireLogin();
bw_require_csrf();

if (!hasRole('editor')) {
    header('Location: index.php?msg=' . urlencode('Permission denied'));
    exit;
}

$idParam = isset($_GET['id']) ? trim($_GET['id']) : '';
$fullId = safeArticleId($idParam);
if ($fullId === '') { header('Location: index.php'); exit; }
if (strpos($fullId, '_') === false) {
    foreach (glob(CONTENT_DIR . '*/meta.json') as $mf) {
        $fid = basename(dirname($mf));
        if ($fid !== '' && $fid[0] !== '_' && articleSlugId($fid) === $fullId) { $fullId = $fid; break; }
    }
    if (strpos($fullId, '_') === false) {
        $matches = glob(CONTENT_DIR . '*_' . $fullId . '.md');
        if (count($matches) === 1) $fullId = basename($matches[0], '.md');
    }
}

$meta = readArticleMeta($fullId);
if (!$meta) {
    $lm = CONTENT_DIR . $fullId . '.meta.json';
    $meta = file_exists($lm) ? json_decode(@file_get_contents($lm), true) : null;
}
if (is_array($meta) || file_exists(articleMetaPath($fullId)) || file_exists(CONTENT_DIR . $fullId . '.meta.json')) {
    if (!is_array($meta)) $meta = readArticleMeta($fullId) ?: array();
    saveVersion($fullId);
    $htmlFile = articleHtmlPath($fullId);
    if (file_exists($htmlFile)) @unlink($htmlFile);
    $legacyHtml = CONTENT_DIR . $fullId . '.html';
    if (file_exists($legacyHtml)) @unlink($legacyHtml);
    $from = $meta['status'] ?? 'published';
    $meta['fullId'] = $fullId;
    $meta['published'] = false;
    $meta['status'] = 'draft';
    $meta['updated'] = time();
    ensureArticleDir($fullId);
    writeJsonFile(articleMetaPath($fullId), $meta);
    updateManifestForArticle($fullId, $meta);
    appendWorkflowEvent(array('action' => 'unpublish', 'id' => $fullId, 'by' => $_SESSION['user']['username'] ?? '', 'from' => $from, 'to' => 'draft'));
}

header('Location: index.php?msg=' . urlencode('Unpublished'));
exit;
?>