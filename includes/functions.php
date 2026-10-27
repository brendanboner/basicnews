<?php
require_once __DIR__ . '/../config/config.php';

function simpleMarkdown($text) {
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    
    // Tables
    $lines = preg_split('/\R/', $text);
    $result = '';
    $i = 0;
    $count = count($lines);
    while ($i < $count) {
        $line = $lines[$i];
        if (strpos($line, '|') !== false && isset($lines[$i + 1]) && preg_match('/^\s*\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)+\|?\s*$/', $lines[$i + 1])) {
            // Header row
            $headerLine = trim($line, " \t\r\n");
            if ($headerLine === '') { $i++; continue; }
            if (substr($headerLine, 0, 1) === '|') $headerLine = substr($headerLine, 1);
            if (substr($headerLine, -1) === '|') $headerLine = substr($headerLine, 0, -1);
            $headers = array_map('trim', explode('|', $headerLine));
            $i += 2; // skip header and separator
            $table = "<table>\n<thead>\n<tr>\n";
            foreach ($headers as $h) {
                $table .= "  <th>" . $h . "</th>\n";
            }
            $table .= "</tr>\n</thead>\n<tbody>\n";
            while ($i < $count) {
                $rowLine = trim($lines[$i], " \t\r\n");
                if ($rowLine === '' || strpos($rowLine, '|') === false) break;
                if (substr($rowLine, 0, 1) === '|') $rowLine = substr($rowLine, 1);
                if (substr($rowLine, -1) === '|') $rowLine = substr($rowLine, 0, -1);
                $cells = array_map('trim', explode('|', $rowLine));
                $table .= "<tr>\n";
                foreach ($cells as $c) {
                    $table .= "  <td>" . $c . "</td>\n";
                }
                $table .= "</tr>\n";
                $i++;
            }
            $table .= "</tbody>\n</table>\n";
            $result .= $table;
            continue;
        }
        $result .= $line . "\n";
        $i++;
    }
    $text = $result;
    
    // Headers
    $text = preg_replace('/^### (.+)$/mu', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.+)$/mu', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.+)$/mu', '<h1>$1</h1>', $text);
    
    // Bold
    $text = preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $text);
    
    // Italic
    $text = preg_replace('/\*(.+?)\*/su', '<em>$1</em>', $text);
    
    // Images
    $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/u', '<img src="$2" alt="$1">', $text);
    
    // Links - bold and not colored
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/u', '<a href="$2"><strong>$1</strong></a>', $text);
    
    // Paragraphs
    $text = preg_replace('/\n\n+/u', "\n\n", $text);
    $paragraphs = preg_split('/\n\s*\n/u', trim($text));
    $result = '';
    foreach ($paragraphs as $p) {
        if (trim($p) === '') continue;
        if (preg_match('/^<(h[1-6]|table|ul|ol|blockquote)/', trim($p))) {
            $result .= $p . "\n";
        } else {
            $p2 = str_replace("\n", ' ', $p);
            $result .= '<p>' . $p2 . '</p>' . "\n";
        }
    }
    
    return $result;
}

function getAllPublishedArticles() {
    $articles = array();
    $files = glob(CONTENT_DIR . '*.html');
    foreach ($files as $file) {
        $filename = basename($file, '.html');
        $metaFile = CONTENT_DIR . $filename . '.meta.json';
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            if (isset($meta['published']) && $meta['published']) {
                $htmlContent = @file_get_contents($file);
                $firstImage = '';
                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $htmlContent, $imgMatch)) {
                    $firstImage = $imgMatch[1];
                }
                $excerpt = '';
                $textContent = strip_tags($htmlContent);
                $textContent = preg_replace('/\s+/u', ' ', $textContent);
                $textContent = trim($textContent);
                if (mb_strlen($textContent, 'UTF-8') > 220) {
                    $excerpt = mb_substr($textContent, 0, 217, 'UTF-8') . '...';
                } else {
                    $excerpt = $textContent;
                }
                $articles[] = array(
                    'id' => $filename,
                    'title' => isset($meta['title']) ? $meta['title'] : $filename,
                    'author' => isset($meta['author']) ? $meta['author'] : '',
                    'date' => isset($meta['date']) ? $meta['date'] : @filemtime($file),
                    'excerpt' => $excerpt,
                    'image' => $firstImage,
                    'html' => $htmlContent
                );
            }
        }
    }
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