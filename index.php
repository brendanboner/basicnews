<?php
require_once 'includes/functions.php';
$articles = getAllPublishedArticles();
$_bw = bw_site_settings();
$_home = $_bw['home_density'] ?? 'full';
$_railMax = ($_home === 'full') ? 10 : (($_home === 'compact') ? 7 : 8);
$_bottom = ($_home === 'full');
$_showMidExcerpts = ($_home !== 'headlines');
$_showRailExcerpts = ($_home === 'full');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_TITLE, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <?php if (is_file(__DIR__ . '/css/custom.css')): ?><link rel="stylesheet" href="css/custom.css"><?php endif; ?>
</head>
<body class="<?php echo htmlspecialchars(bw_style_body_classes('nyt'), ENT_QUOTES, 'UTF-8'); ?>">
    <header class="nyt-masthead">
        <div class="nyt-rule-top"></div>
        <h1 class="nyt-title"><?php echo SITE_TITLE; ?></h1>
        <div class="nyt-rule-thin"></div>
        <div class="nyt-subhead">
            <span class="nyt-date"><?php echo date('l, F j, Y'); ?></span>
            <span class="nyt-edition"><?php echo htmlspecialchars(bw_site_settings()['edition'] ?? 'Late Edition', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="nyt-rule-thick"></div>
    </header>

    <?php if (empty($articles)): ?>
        <p class="nyt-empty">No articles published yet.</p>
    <?php else: ?>
        <main class="nyt-grid">
            <?php $count = count($articles); ?>
            
            <?php if ($count >= 1): $article = $articles[0]; ?>
            <section class="nyt-col nyt-col-lead">
                <article class="nyt-story nyt-story-lead">
                    <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="nyt-link">
                        <?php if (isset($article['image']) && $article['image']): ?>
                        <figure class="nyt-figure">
                            <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
                        </figure>
                        <?php endif; ?>
                        <h2 class="nyt-headline nyt-headline-xl"><?php echo htmlspecialchars($article['title']); ?></h2>
                        <?php if (isset($article['excerpt']) && $article['excerpt']): ?>
                        <p class="nyt-summary"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                        <?php endif; ?>
                        <div class="nyt-byline">
                            <?php if (isset($article['author']) && $article['author']): ?>
                                <span class="nyt-by">By <?php echo htmlspecialchars($article['author']); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </article>
            </section>
            <?php endif; ?>

            <section class="nyt-col nyt-col-mid">
                <?php if ($count >= 2): $article = $articles[1]; ?>
                <article class="nyt-story">
                    <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="nyt-link">
                        <?php if (isset($article['image']) && $article['image']): ?>
                        <figure class="nyt-figure nyt-figure-sm">
                            <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="">
                        </figure>
                        <?php endif; ?>
                        <h3 class="nyt-headline nyt-headline-lg"><?php echo htmlspecialchars($article['title']); ?></h3>
                        <?php if ($_showMidExcerpts && isset($article['excerpt']) && $article['excerpt']): ?>
                        <p class="nyt-summary"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                        <?php endif; ?>
                    </a>
                </article>
                <div class="nyt-divider"></div>
                <?php endif; ?>

                <?php if ($count >= 3): $article = $articles[2]; ?>
                <article class="nyt-story">
                    <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="nyt-link">
                        <h3 class="nyt-headline nyt-headline-md"><?php echo htmlspecialchars($article['title']); ?></h3>
                        <?php if ($_showMidExcerpts && isset($article['excerpt']) && $article['excerpt']): ?>
                        <p class="nyt-summary"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                        <?php endif; ?>
                    </a>
                </article>
                <?php if ($count >= 4): ?><div class="nyt-divider"></div><?php endif; ?>
                <?php endif; ?>

                <?php if ($count >= 4): $article = $articles[3]; ?>
                <article class="nyt-story">
                    <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="nyt-link">
                        <h3 class="nyt-headline nyt-headline-md"><?php echo htmlspecialchars($article['title']); ?></h3>
                        <?php if ($_showMidExcerpts && isset($article['excerpt']) && $article['excerpt']): ?>
                        <p class="nyt-summary"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                        <?php endif; ?>
                    </a>
                </article>
                <?php endif; ?>
            </section>

            <aside class="nyt-col nyt-col-rail">
                <?php for ($i = 4; $i < min($count, $_railMax); $i++): $article = $articles[$i]; ?>
                <article class="nyt-story nyt-story-rail">
                    <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="nyt-link">
                        <h4 class="nyt-headline nyt-headline-sm"><?php echo htmlspecialchars($article['title']); ?></h4>
                        <?php if ($_showRailExcerpts && isset($article['excerpt']) && $article['excerpt']): ?>
                        <p class="nyt-summary nyt-summary-sm"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                        <?php endif; ?>
                    </a>
                </article>
                <?php if ($i < min($count, $_railMax) - 1): ?>
                <div class="nyt-divider-thin"></div>
                <?php endif; ?>
                <?php endfor; ?>
            </aside>
        </main>

        <?php if ($_bottom && $count > $_railMax): ?>
        <section class="nyt-bottom">
            <div class="nyt-rule-section"></div>
            <div class="nyt-bottom-grid">
                <?php for ($i = $_railMax; $i < $count; $i++): $article = $articles[$i]; ?>
                <article class="nyt-story">
                    <a href="article.php?id=<?php echo urlencode($article['id']); ?>" class="nyt-link">
                        <h4 class="nyt-headline nyt-headline-sm"><?php echo htmlspecialchars($article['title']); ?></h4>
                        <?php if (isset($article['excerpt']) && $article['excerpt']): ?>
                        <p class="nyt-summary nyt-summary-sm"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                        <?php endif; ?>
                    </a>
                </article>
                <?php endfor; ?>
            </div>
        </section>
        <?php elseif (!$_bottom && $count > $_railMax): ?>
        <section class="nyt-bottom-more">
            <div class="nyt-rule-section"></div>
            <p style="font-style:italic;font-size:13px;"><a href="article.php?id=<?php echo urlencode($articles[$_railMax]['id']); ?>" style="color:inherit;">More articles…</a></p>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
