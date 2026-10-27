<?php
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
$article = getArticle($id);

if (!$article) {
    header('HTTP/1.0 404 Not Found');
    exit('Article not found');
}

$articles = getAllPublishedArticles();
$prevId = '';
$nextId = '';
$currentIndex = -1;
for ($i = 0; $i < count($articles); $i++) {
    if ($articles[$i]['id'] === $id) {
        $currentIndex = $i;
        break;
    }
}
if ($currentIndex >= 0) {
    if (isset($articles[$currentIndex + 1])) {
        $prevId = $articles[$currentIndex + 1]['id'];
    }
    if (isset($articles[$currentIndex - 1])) {
        $nextId = $articles[$currentIndex - 1]['id'];
    }
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
    <nav class="article-nav">
        <?php if ($prevId): ?>
            <a href="article.php?id=<?php echo urlencode($prevId); ?>" class="nav-link" title="Previous article">
                <span class="page-turn">«</span>
            </a>
        <?php else: ?>
            <span class="nav-spacer"></span>
        <?php endif; ?>
        
        <span class="nav-spacer"></span>
        
        <?php if ($nextId): ?>
            <a href="article.php?id=<?php echo urlencode($nextId); ?>" class="nav-link" title="Next article">
                <span class="page-turn">»</span>
            </a>
        <?php else: ?>
            <span class="nav-spacer"></span>
        <?php endif; ?>
    </nav>
    
    <div class="article-header">
        <h1><a href="index.php"><?php echo htmlspecialchars($article['meta']['title']); ?></a></h1>
        <div class="article-meta">
            <?php if (isset($article['meta']['author']) && $article['meta']['author']): ?>
                <span><?php echo htmlspecialchars($article['meta']['author']); ?></span>
            <?php endif; ?>
            <?php if (isset($article['meta']['date'])): ?>
                <span><?php echo date('F j, Y', $article['meta']['date']); ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="article-content">
        <?php echo $article['html']; ?>
    </div>
</body>
</html>