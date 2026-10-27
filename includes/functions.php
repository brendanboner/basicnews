<?php
require_once __DIR__ . '/../config/config.php';

function simpleMarkdown($text) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // Headers
    $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);
    
    // Bold
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    
    // Italic
    $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);
    
    // Images
    $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $text);
    
    // Links - bold and not colored
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2"><strong>$1</strong></a>', $text);
    
    // Paragraphs
    $text = preg_replace('/\n\n+/', "\n\n", $text);
    $paragraphs = preg_split('/\n\s*\n/', trim($text));
    $result = '';
    foreach ($paragraphs as $p) {
        if (trim($p) === '') continue;
        // Don't wrap headers in p tags
        if (preg_match('/^<h[1-6]>/', trim($p))) {
            $result .= $p . "\n";
        } else {
            // Convert single newlines to spaces in paragraphs
            $p = str_replace("\n", ' ', $p);
            $result .= '<p>' . $p . '</p>' . "\n";
        }
    }
    
    return $result;
}

function getAllPublishedArticles() {
    $articles = array();
    $files = glob(CONTENT_DIR . '*.html');
    foreach ($files as $file) {
        $filename = basename($file, '.html');
        // Skip if no corresponding markdown info or if it's not published
        $metaFile = CONTENT_DIR . $filename . '.meta.json';
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            if (isset($meta['published']) && $meta['published']) {
                $htmlContent = file_get_contents($file);
                // Extract first image
                $firstImage = '';
                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $htmlContent, $imgMatch)) {
                    $firstImage = $imgMatch[1];
                }
                // Extract excerpt (first paragraph text)
                $excerpt = '';
                // Remove all HTML tags except we want text content
                $textContent = strip_tags($htmlContent);
                // Clean up whitespace
                $textContent = preg_replace('/\s+/', ' ', $textContent);
                $textContent = trim($textContent);
                if (strlen($textContent) > 200) {
                    $excerpt = substr($textContent, 0, 197) . '...';
                } else {
                    $excerpt = $textContent;
                }
                $articles[] = array(
                    'id' => $filename,
                    'title' => isset($meta['title']) ? $meta['title'] : $filename,
                    'author' => isset($meta['author']) ? $meta['author'] : '',
                    'date' => isset($meta['date']) ? $meta['date'] : filemtime($file),
                    'excerpt' => $excerpt,
                    'image' => $firstImage,
                    'html' => $htmlContent
                );
            }
        }
    }
    // Sort by date descending (newest first)
    usort($articles, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    return $articles;
}

function getArticle($id) {
    $htmlFile = CONTENT_DIR . $id . '.html';
    $metaFile = CONTENT_DIR . $id . '.meta.json';
    if (file_exists($htmlFile) && file_exists($metaFile)) {
        $meta = json_decode(file_get_contents($metaFile), true);
        if (isset($meta['published']) && $meta['published']) {
            return array(
                'html' => file_get_contents($htmlFile),
                'meta' => $meta
            );
        }
    }
    return false;
}
?>