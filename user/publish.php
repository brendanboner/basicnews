<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
requireLogin();

if ($_SESSION['user']['type'] !== 'user') {
    header('Location: ../admin/index.php');
    exit;
}

$username = $_SESSION['user']['username'];
$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (preg_match('/[^a-zA-Z0-9_-]/', $id)) {
    header('Location: index.php');
    exit;
}

$mdFile = CONTENT_DIR . $username . '_' . $id . '.md';
$htmlFile = CONTENT_DIR . $username . '_' . $id . '.html';
$metaFile = CONTENT_DIR . $username . '_' . $id . '.meta.json';

if (file_exists($mdFile)) {
    $mdContent = file_get_contents($mdFile);
    $htmlContent = simpleMarkdown($mdContent);
    
    file_put_contents($htmlFile, $htmlContent);
    
    $meta = array(
        'title' => $id,
        'author' => $username,
        'date' => time(),
        'published' => true,
        'slug' => $id
    );
    if (file_exists($metaFile)) {
        $oldMeta = json_decode(file_get_contents($metaFile), true);
        if (isset($oldMeta['title'])) $meta['title'] = $oldMeta['title'];
        if (isset($oldMeta['date'])) $meta['date'] = $oldMeta['date'];
        if (isset($oldMeta['slug'])) $meta['slug'] = $oldMeta['slug']; else $meta['slug'] = $id;
    }
    file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT));
}

header('Location: index.php');
exit;
?>