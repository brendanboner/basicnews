<?php
require_once 'includes/functions.php';
$articles = getAllPublishedArticles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="broadsheet">
    <header class="masthead">
        <div class="masthead-rule-top"></div>
        <h1 class="masthead-title"><?php echo SITE_TITLE; ?></h1>
        <div class="masthead-rule-bottom"></div>
        <div class="masthead-dateline">
            <span><?php echo date('l, F j, Y'); ?></span>
            <?php if (count($articles) > 0): ?>
                <span class="edition-label">Edition</span>
            <?php endif; ?>
        </div>
    </header>
    
    <?php if (empty($articles)): ?>
        <p style="text-align: center; font-style: italic; margin-top: 40px;">No articles published yet.</p>
    <?php else: ?>
        <main class="newspaper-grid">
            <?php 
            $count = count($articles);
            if ($count >= 1): 
                $article = $articles[0];
            ?>
                <article class="story lead-story">
                    <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="story-link">
                        <?php if (isset($article['image']) && $article['image']): ?>
                            <div class="story-image">
                                <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
                            </div>
                        <?php endif; ?>
                        <h2 class="headline lead-headline"><?php echo htmlspecialchars($article['title']); ?></h2>
                        <?php if (isset($article['excerpt']) && $article['excerpt']): ?>
                            <p class="story-excerpt"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                        <?php endif; ?>
                        <?php if ((isset($article['author']) && $article['author']) || isset($article['date'])): ?>
                            <div class="story-byline">
                                <?php if (isset($article['author']) && $article['author']): ?>
                                    <span><?php echo htmlspecialchars($article['author']); ?></span>
                                <?php endif; ?>
                                <?php if (isset($article['date'])): ?>
                                    <span><?php echo date('F j, Y', $article['date']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </a>
                </article>
            <?php endif; ?>
        </main>
    <?php endif; ?>
</body>
</html>
