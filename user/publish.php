<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireLogin();

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

$mdPath = articleMdPath($fullId);
$legacyMd = CONTENT_DIR . $fullId . '.md';
if (file_exists($mdPath) || file_exists($legacyMd)) {
    saveVersion($fullId);
    $mdContent = @file_get_contents(file_exists($mdPath) ? $mdPath : $legacyMd);
    $htmlContent = simpleMarkdown($mdContent);
    ensureArticleDir($fullId);
    atomicWriteFile(articleHtmlPath($fullId), $htmlContent);

    $meta = readArticleMeta($fullId);
    if (!$meta) {
        $lm = CONTENT_DIR . $fullId . '.meta.json';
        $meta = file_exists($lm) ? json_decode(@file_get_contents($lm), true) : array();
        if (!is_array($meta)) $meta = array();
    }
    $from = $meta['status'] ?? 'draft';
    $meta['title'] = $meta['title'] ?? articleSlugId($fullId);
    $meta['author'] = $meta['author'] ?? articleOwner($fullId);
    $meta['owner'] = $meta['author'];
    $meta['date'] = $meta['date'] ?? time();
    $meta['updated'] = time();
    $meta['published'] = true;
    $meta['status'] = 'published';
    $meta['slug'] = articleSlugId($fullId);
    writeJsonFile(articleMetaPath($fullId), $meta);
    updateManifestForArticle($fullId, $meta);
    appendWorkflowEvent(array('action' => 'publish', 'id' => $fullId, 'by' => $_SESSION['user']['username'] ?? '', 'from' => $from, 'to' => 'published'));
}

header('Location: index.php?msg=' . urlencode('Published'));
exit;
?>