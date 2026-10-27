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
    
    // Wrap in basic HTML structure
    $fullHtml = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Article</title>
</head>
<body>
' . $htmlContent . '
</body>
</html>';
    
    file_put_contents($htmlFile, $htmlContent); // Store just the content, article.php will wrap it
    
    // Update meta
    $meta = array(
        'title' => $id,
        'author' => $username,
        'date' => time(),
        'published' => true
    );
    if (file_exists($metaFile)) {
        $oldMeta = json_decode(file_get_contents($metaFile), true);
        if (isset($oldMeta['title'])) $meta['title'] = $oldMeta['title'];
        if (isset($oldMeta['date'])) $meta['date'] = $oldMeta['date'];
    }
    file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT));
}

header('Location: index.php');
exit;
?>