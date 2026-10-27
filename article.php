<?php
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
$article = getArticle($id);

if (!$article) {
    header('HTTP/1.0 404 Not Found');
    exit('Article not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['meta']['title']); ?> - <?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="article-page">
    <a href="index.php" class="back-button">← Back</a>
    
    <div class="article-header">
        <h1><?php echo htmlspecialchars($article['meta']['title']); ?></h1>
        <div class="article-meta">
            <?php if (isset($article['meta']['author']) && $article['meta']['author']): ?>
                By <?php echo htmlspecialchars($article['meta']['author']); ?> | 
            <?php endif; ?>
            <?php echo isset($article['meta']['date']) ? date('F j, Y', $article['meta']['date']) : ''; ?>
        </div>
    </div>
    
    <div class="article-content">
        <?php echo $article['html']; ?>
    </div>
</body>
</html>