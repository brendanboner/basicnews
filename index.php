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
        <main class="newspaper-grid newspaper-grid-center">
            <?php 
            $count = count($articles);
            // Lead story is most recent (first)
            if ($count >= 1): 
                $article = $articles[0];
            ?>
                <article class="story lead-story center-lead lead-article">
                    <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="story-link">
                        <?php if (isset($article['image']) && $article['image']): ?>
                            <div class="story-image lead-image-small">
                                <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
                            </div>
                        <?php endif; ?>
                        <h2 class="headline lead-headline"><?php echo htmlspecialchars($article['title']); ?></h2>
                        <?php if ((isset($article['author']) && $article['author']) || isset($article['date'])): ?>
                            <div class="story-byline lead-byline">
                                <?php if (isset($article['author']) && $article['author']): ?>
                                    <span><?php echo htmlspecialchars($article['author']); ?></span>
                                <?php endif; ?>
                                <?php if (isset($article['date'])): ?>
                                    <span><?php echo date('F j, Y', $article['date']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($article['html']) && $article['html']): ?>
                            <div class="lead-article-body">
                                <?php echo $article['html']; ?>
                            </div>
                        <?php endif; ?>
                    </a>
                </article>
            <?php endif; ?>
            
            <div class="secondary-column left-column">
                <?php if ($count >= 2): 
                    $article = $articles[1];
                ?>
                    <article class="story secondary-story">
                        <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="story-link">
                            <?php if (isset($article['image']) && $article['image']): ?>
                                <div class="story-image small-image">
                                    <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <h3 class="headline secondary-headline"><?php echo htmlspecialchars($article['title']); ?></h3>
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
                    <?php if ($count >= 3): ?><div class="column-rule"></div><?php endif; ?>
                <?php endif; ?>
                
                <?php if ($count >= 3): 
                    $article = $articles[2];
                ?>
                    <article class="story secondary-story">
                        <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="story-link">
                            <?php if (isset($article['image']) && $article['image']): ?>
                                <div class="story-image small-image">
                                    <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <h3 class="headline secondary-headline"><?php echo htmlspecialchars($article['title']); ?></h3>
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
            </div>
            
            <?php if ($count >= 4): ?>
                <div class="tertiary-column right-column">
                    <?php for ($i = 3; $i < min($count, 8); $i++): 
                        $article = $articles[$i];
                    ?>
                        <article class="story tertiary-story">
                            <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="story-link">
                                <?php if (isset($article['image']) && $article['image']): ?>
                                    <div class="story-image tiny-image">
                                        <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
                                    </div>
                                <?php endif; ?>
                                <h4 class="headline tertiary-headline"><?php echo htmlspecialchars($article['title']); ?></h4>
                                <?php if (isset($article['excerpt']) && $article['excerpt']): ?>
                                    <p class="story-excerpt small-excerpt"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                                <?php endif; ?>
                                <?php if ((isset($article['author']) && $article['author']) || isset($article['date'])): ?>
                                    <div class="story-byline tiny-byline">
                                        <?php if (isset($article['author']) && $article['author']): ?>
                                            <span><?php echo htmlspecialchars($article['author']); ?></span>
                                        <?php endif; ?>
                                        <?php if (isset($article['date'])): ?>
                                            <span><?php echo date('m/d/Y', $article['date']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </article>
                        <?php if ($i < min($count, 8) - 1): ?><div class="story-rule"></div><?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
        
        <?php if ($count > 8): ?>
            <section class="additional-section">
                <div class="section-rule-top"></div>
                <div class="additional-grid">
                    <?php for ($i = 8; $i < $count; $i++): 
                        $article = $articles[$i];
                    ?>
                        <article class="story additional-story">
                            <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="story-link">
                                <h4 class="headline additional-headline"><?php echo htmlspecialchars($article['title']); ?></h4>
                                <?php if (isset($article['excerpt']) && $article['excerpt']): ?>
                                    <p class="story-excerpt small-excerpt"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                                <?php endif; ?>
                                <?php if ((isset($article['author']) && $article['author']) || isset($article['date'])): ?>
                                    <div class="story-byline tiny-byline">
                                        <?php if (isset($article['author']) && $article['author']): ?>
                                            <span><?php echo htmlspecialchars($article['author']); ?></span>
                                        <?php endif; ?>
                                        <?php if (isset($article['date'])): ?>
                                            <span><?php echo date('m/d/Y', $article['date']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </article>
                    <?php endfor; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
