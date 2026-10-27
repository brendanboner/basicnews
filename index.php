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
                        <?php if ($article['image']): ?>
                            <div class="story-image">
                                <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
                            </div>
                        <?php endif; ?>
                        <h2 class="headline lead-headline"><?php echo htmlspecialchars($article['title']); ?></h2>
                        <?php if ($article['excerpt']): ?>
                            <p class="story-excerpt"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                        <?php endif; ?>
                        <?php if ($article['author'] || $article['date']): ?>
                            <div class="story-byline">
                                <?php if ($article['author']): ?>
                                    <span><?php echo htmlspecialchars($article['author']); ?></span>
                                <?php endif; ?>
                                <?php if ($article['date']): ?>
                                    <span><?php echo date('F j, Y', $article['date']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </a>
                </article>
            <?php endif; ?>
            
            <div class="secondary-column">
                <?php if ($count >= 2): 
                    $article = $articles[1];
                ?>
                    <article class="story secondary-story">
                        <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="story-link">
                            <?php if ($article['image']): ?>
                                <div class="story-image small-image">
                                    <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <h3 class="headline secondary-headline"><?php echo htmlspecialchars($article['title']); ?></h3>
                            <?php if ($article['excerpt']): ?>
                                <p class="story-excerpt"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                            <?php endif; ?>
                            <?php if ($article['author'] || $article['date']): ?>
                                <div class="story-byline">
                                    <?php if ($article['author']): ?>
                                        <span><?php echo htmlspecialchars($article['author']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($article['date']): ?>
                                        <span><?php echo date('F j, Y', $article['date']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    </article>
                    <div class="column-rule"></div>
                <?php endif; ?>
                
                <?php if ($count >= 3): 
                    $article = $articles[2];
                ?>
                    <article class="story secondary-story">
                        <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="story-link">
                            <?php if ($article['image']): ?>
                                <div class="story-image small-image">
                                    <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <h3 class="headline secondary-headline"><?php echo htmlspecialchars($article['title']); ?></h3>
                            <?php if ($article['excerpt']): ?>
                                <p class="story-excerpt"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                            <?php endif; ?>
                            <?php if ($article['author'] || $article['date']): ?>
                                <div class="story-byline">
                                    <?php if ($article['author']): ?>
                                        <span><?php echo htmlspecialchars($article['author']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($article['date']): ?>
                                        <span><?php echo date('F j, Y', $article['date']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    </article>
                <?php endif; ?>
            </div>
            
            <?php if ($count >= 4): ?>
                <div class="tertiary-column">
                    <?php for ($i = 3; $i < min($count, 7); $i++): 
                        $article = $articles[$i];
                    ?>
                        <article class="story tertiary-story">
                            <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="story-link">
                                <?php if ($article['image']): ?>
                                    <div class="story-image tiny-image">
                                        <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
                                    </div>
                                <?php endif; ?>
                                <h4 class="headline tertiary-headline"><?php echo htmlspecialchars($article['title']); ?></h4>
                                <?php if ($article['excerpt']): ?>
                                    <p class="story-excerpt small-excerpt"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                                <?php endif; ?>
                                <?php if ($article['author'] || $article['date']): ?>
                                    <div class="story-byline tiny-byline">
                                        <?php if ($article['author']): ?>
                                            <span><?php echo htmlspecialchars($article['author']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($article['date']): ?>
                                            <span><?php echo date('m/d/Y', $article['date']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </article>
                        <?php if ($i < min($count, 7) - 1): ?>
                            <div class="story-rule"></div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
        
        <?php if ($count > 7): ?>
            <section class="additional-section">
                <div class="section-rule-top"></div>
                <div class="additional-grid">
                    <?php for ($i = 7; $i < $count; $i++): 
                        $article = $articles[$i];
                    ?>
                        <article class="story additional-story">
                            <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="story-link">
                                <h4 class="headline additional-headline"><?php echo htmlspecialchars($article['title']); ?></h4>
                                <?php if ($article['excerpt']): ?>
                                    <p class="story-excerpt small-excerpt"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                                <?php endif; ?>
                                <?php if ($article['author'] || $article['date']): ?>
                                    <div class="story-byline tiny-byline">
                                        <?php if ($article['author']): ?>
                                            <span><?php echo htmlspecialchars($article['author']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($article['date']): ?>
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