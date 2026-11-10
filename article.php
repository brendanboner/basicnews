<?php
require_once 'includes/functions.php';
require_once 'includes/ui.php';

$id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
$article = getArticle($id);

if (!$article) {
    header('HTTP/1.0 404 Not Found');
    exit('Article not found');
}

$articles = getAllPublishedArticles();
/* The promoted story leads the paper; everything else follows by date. */
usort($articles, function ($a, $b) {
    $pa = !empty($a['promoted']) ? 1 : 0;
    $pb = !empty($b['promoted']) ? 1 : 0;
    if ($pa !== $pb) return $pb - $pa;
    return ($b['date'] ?? 0) - ($a['date'] ?? 0);
});
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

$authorUsername = $article['meta']['author'] ?? $article['meta']['owner'] ?? '';
$authorProfile = $authorUsername !== '' ? bw_user_profile($authorUsername) : array('display_name' => '', 'title' => '', 'bio' => '');
$authorName = $authorProfile['display_name'];
$authorTitle = $authorProfile['title'];
$authorBio = $authorProfile['bio'];
$articleDate = isset($article['meta']['date']) ? (int)$article['meta']['date'] : 0;

/* Subtle page-turn between stories: tuned in Admin -> Site settings. */
$_bw = bw_site_settings();
$_turn = ($_bw['page_turn'] ?? 'on') === 'on';
$_perspMap = array('subtle' => '2400px', 'normal' => '1600px', 'dramatic' => '1200px');
$_persp = $_perspMap[$_bw['page_turn_perspective'] ?? 'normal'] ?? '1600px';
$_speedMap = array('fast' => '450ms', 'normal' => '650ms', 'slow' => '900ms');
$_speed = $_speedMap[$_bw['page_turn_speed'] ?? 'normal'] ?? '650ms';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['meta']['title']); ?> - <?php echo htmlspecialchars(SITE_TITLE, ENT_QUOTES, 'UTF-8'); ?></title>
    <?php if ($authorName !== ''): ?><meta name="author" content="<?php echo htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?php echo bw_asset('css/style.css'); ?>">
    <?php if ($_turn): ?><link rel="stylesheet" href="<?php echo bw_asset('css/page-turn.css'); ?>"><?php endif; ?>
    <?php if (is_file(__DIR__ . '/css/custom.css')): ?><link rel="stylesheet" href="<?php echo bw_asset('css/custom.css'); ?>"><?php endif; ?>
</head>
<body class="<?php echo htmlspecialchars(bw_style_body_classes('article-page'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php bw_site_masthead('article'); ?>

    <main class="article-main"<?php if ($_turn): ?> style="--pt-perspective:<?php echo bw_h($_persp); ?>;--pt-speed:<?php echo bw_h($_speed); ?>;"<?php endif; ?>>
        <div class="bw-leaf" id="bw-leaf">
            <nav class="article-nav" aria-label="Story navigation">
                <?php if ($prevId): ?>
                    <a href="article.php?id=<?php echo urlencode($prevId); ?>" class="nav-link bw-turn-link"<?php if ($_turn): ?> data-turn="prev"<?php endif; ?> rel="prev"><?php echo $_turn ? '&larr; Turn back a page' : '&larr; Previous story'; ?></a>
                <?php else: ?>
                    <span class="nav-spacer"></span>
                <?php endif; ?>

                <span class="nav-spacer"></span>

                <?php if ($nextId): ?>
                    <a href="article.php?id=<?php echo urlencode($nextId); ?>" class="nav-link bw-turn-link"<?php if ($_turn): ?> data-turn="next"<?php endif; ?> rel="next"><?php echo $_turn ? 'Turn the page &rarr;' : 'Next story &rarr;'; ?></a>
                <?php else: ?>
                    <span class="nav-spacer"></span>
                <?php endif; ?>
            </nav>

            <article class="article">
                <header class="article-header">
                    <h1><a href="index.php"><?php echo htmlspecialchars($article['meta']['title']); ?></a></h1>
                    <div class="article-meta">
                        <?php if ($authorName !== ''): ?>
                            <span class="article-byline">By <?php echo htmlspecialchars($authorName); ?><?php if ($authorTitle !== ''): ?>, <?php echo htmlspecialchars($authorTitle); ?><?php endif; ?></span>
                        <?php endif; ?>
                        <?php if ($articleDate): ?>
                            <span><time datetime="<?php echo date('c', $articleDate); ?>"><?php echo date('F j, Y', $articleDate); ?></time></span>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="article-content">
                    <?php echo $article['html']; ?>
                </div>

                <?php if ($authorName !== '' && ($authorTitle !== '' || $authorBio !== '')): ?>
                <footer class="article-author">
                    <p class="article-author-name"><strong><?php echo htmlspecialchars($authorName); ?></strong><?php if ($authorTitle !== ''): ?> <span class="article-author-title"><?php echo htmlspecialchars($authorTitle); ?></span><?php endif; ?></p>
                    <?php if ($authorBio !== ''): ?>
                        <p><?php echo nl2br(htmlspecialchars($authorBio)); ?></p>
                    <?php endif; ?>
                </footer>
                <?php endif; ?>
            </article>
        </div>
    </main>
    <?php if ($_turn): ?><script src="<?php echo bw_asset('js/page-turn.js'); ?>" defer></script><?php endif; ?>
</body>
</html>
