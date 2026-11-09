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
$action = isset($_GET['action']) ? trim($_GET['action']) : (isset($_POST['action']) ? trim($_POST['action']) : '');
$idParam = isset($_GET['id']) ? trim($_GET['id']) : (isset($_POST['id']) ? trim($_POST['id']) : '');
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : (isset($_GET['notes']) ? trim($_GET['notes']) : '');
$return = isset($_GET['return']) ? $_GET['return'] : 'index.php';

$fullId = safeArticleId($idParam);
if ($fullId === '' || strpos($fullId, '_') === false) {
    header('Location: index.php?msg=' . urlencode('Invalid article'));
    exit;
}

$meta = readArticleMeta($fullId);
if (!$meta) {
    $lm = CONTENT_DIR . $fullId . '.meta.json';
    if (file_exists($lm)) {
        $meta = json_decode(@file_get_contents($lm), true);
        if (is_array($meta)) { $meta['fullId']=$fullId; $meta['owner']=$meta['owner']??$meta['author']??articleOwner($fullId); $meta['status']=$meta['status']??(!empty($meta['published'])?'published':'draft'); }
    }
}
if (!is_array($meta) || (!file_exists(articleMdPath($fullId)) && !file_exists(CONTENT_DIR . $fullId . '.md'))) {
    header('Location: index.php?msg=' . urlencode('Article not found'));
    exit;
}

$status = $meta['status'];
$owner = $meta['owner'];
$ok = false;
$msg = 'No changes';

saveVersion($fullId);

switch ($action) {
    case 'submit':
        if ($owner === $username && $status === 'draft') {
            $meta['status'] = 'submitted'; $meta['published'] = false; $ok = true; $msg = 'Submitted for review';
        } else { $msg = 'Cannot submit this article'; }
        break;
    case 'start_review':
        if (hasRole('subeditor') && ($status === 'submitted' || $status === 'in_review')) {
            $meta['status'] = 'in_review'; $ok = true; $msg = 'Moved to in review';
        } else { $msg = 'Permission denied'; }
        break;
    case 'send_back':
        if (hasRole('subeditor') && in_array($status, array('submitted','in_review','approved'), true)) {
            $meta['status'] = 'draft'; $meta['published'] = false;
            if ($notes !== '') $meta['editor_notes'] = $notes;
            $ok = true; $msg = 'Sent back to contributor';
        } else { $msg = 'Permission denied'; }
        break;
    case 'approve':
        if (hasRole('editor') && in_array($status, array('submitted','in_review'), true)) {
            $meta['status'] = 'approved'; $ok = true; $msg = 'Approved';
        } else { $msg = 'Permission denied'; }
        break;
    case 'unapprove':
        if (hasRole('editor') && $status === 'approved') {
            $meta['status'] = 'in_review'; $ok = true; $msg = 'Approval removed';
        } else { $msg = 'Permission denied'; }
        break;
    case 'trash':
        $canTrash = hasRole('editor') || ($owner === $username && $status !== 'published');
        if ($canTrash) {
            moveToTrash($fullId);
            appendWorkflowEvent(array('action' => 'trash', 'id' => $fullId, 'by' => $username, 'from' => $status, 'to' => 'trash'));
            header('Location: index.php?msg=' . urlencode('Moved to trash'));
            exit;
        }
        $msg = 'Permission denied';
        break;
    default:
        $msg = 'Unknown action';
}

if ($ok) {
    if ($notes !== '' && $action !== 'send_back') $meta['editor_notes'] = $notes;
    $meta['updated'] = time();
    ensureArticleDir($fullId);
    writeJsonFile(articleMetaPath($fullId), $meta);
    updateManifestForArticle($fullId, $meta);
    appendWorkflowEvent(array('action' => $action, 'id' => $fullId, 'by' => $username, 'from' => $status, 'to' => $meta['status'], 'notes' => $notes));
}

$safeReturn = 'index.php';
if (in_array($return, array('index.php','review.php','editor.php'), true)) $safeReturn = $return;
header('Location: ' . $safeReturn . '?msg=' . urlencode($msg));
exit;
?>