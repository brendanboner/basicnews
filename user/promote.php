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
    $meta = file_exists($lm) ? json_decode(@file_get_contents($lm), true) : array();
    if (!is_array($meta)) $meta = array();
}

if (!empty($meta['title']) || !empty($meta['status']) || file_exists(articleMdPath($fullId))) {
    if (($meta['status'] ?? '') !== 'published' && empty($meta['published'])) {
        header('Location: index.php?msg=' . urlencode('Only published stories can be promoted to the front page'));
        exit;
    }

    $was = !empty($meta['promoted']);
    if ($was) {
        unset($meta['promoted']);
    } else {
        // Only one lead story at a time: demote any other promoted article.
        foreach (glob(CONTENT_DIR . '*/meta.json') as $mf) {
            $fid = basename(dirname($mf));
            if ($fid === '' || $fid[0] === '_' || $fid === $fullId) continue;
            $m = readArticleMeta($fid);
            if ($m && !empty($m['promoted'])) {
                unset($m['promoted']);
                writeJsonFile(articleMetaPath($fid), $m);
                updateManifestForArticle($fid, $m);
            }
        }
        $meta['promoted'] = true;
    }
    $meta['updated'] = time();
    writeJsonFile(articleMetaPath($fullId), $meta);
    updateManifestForArticle($fullId, $meta);
    appendWorkflowEvent(array('action' => $was ? 'unpromote' : 'promote', 'id' => $fullId, 'by' => $_SESSION['user']['username'] ?? ''));
    @unlink(CACHE_DIR . 'published.json');
    header('Location: index.php?msg=' . urlencode($was ? 'Removed from the front page' : 'Promoted to the front page'));
    exit;
}

header('Location: index.php');
exit;
