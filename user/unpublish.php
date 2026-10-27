<?php
require_once '../config/config.php';
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

$htmlFile = CONTENT_DIR . $username . '_' . $id . '.html';
$metaFile = CONTENT_DIR . $username . '_' . $id . '.meta.json';

// Remove published HTML
if (file_exists($htmlFile)) {
    unlink($htmlFile);
}

// Update meta
if (file_exists($metaFile)) {
    $meta = json_decode(file_get_contents($metaFile), true);
    $meta['published'] = false;
    file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT));
}

header('Location: index.php');
exit;
?>