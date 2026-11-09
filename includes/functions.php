<?php
require_once __DIR__ . '/../config/config.php';

function safeArticleId($fullId) {
    $fullId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$fullId);
    return $fullId;
}

function articleOwner($fullId) {
    $parts = explode('_', $fullId, 2);
    return isset($parts[0]) ? $parts[0] : '';
}

function articleSlugId($fullId) {
    $parts = explode('_', $fullId, 2);
    return isset($parts[1]) ? $parts[1] : $fullId;
}

/* Bundle layout (dir-per-article) */
function articleDir($fullId) {
    $fullId = safeArticleId($fullId);
    if ($fullId === '') return '';
    return CONTENT_DIR . $fullId . '/';
}
function articleMdPath($fullId) { $d = articleDir($fullId); return $d ? $d . 'article.md' : ''; }
function articleMetaPath($fullId) { $d = articleDir($fullId); return $d ? $d . 'meta.json' : ''; }
function articleHtmlPath($fullId) { $d = articleDir($fullId); return $d ? $d . 'article.html' : ''; }
function articleRevisionsDir($fullId) { $d = articleDir($fullId); return $d ? $d . 'revisions/' : ''; }

function ensureArticleDir($fullId) {
    $d = articleDir($fullId);
    if ($d === '') return false;
    if (!is_dir($d)) @mkdir($d, 0755, true);
    $r = $d . 'revisions/';
    if (!is_dir($r)) @mkdir($r, 0755, true);
    return is_dir($d);
}

/* Atomic file helpers */
function atomicWriteFile($path, $data) {
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $tmp = $path . '.tmp.' . getmypid() . '.' . mt_rand(1000,9999);
    $fp = @fopen($tmp, 'wb');
    if ($fp) {
        $ok = true;
        if (!@flock($fp, LOCK_EX)) $ok = false;
        if ($ok && @fwrite($fp, $data) === false) $ok = false;
        @fflush($fp);
        @flock($fp, LOCK_UN);
        @fclose($fp);
        if ($ok && @rename($tmp, $path)) return true;
        @unlink($tmp);
    }
    return @file_put_contents($path, $data, LOCK_EX) !== false;
}

function readJsonFile($path) {
    if (!file_exists($path)) return null;
    $fp = @fopen($path, 'rb');
    if (!$fp) return null;
    @flock($fp, LOCK_SH);
    $data = stream_get_contents($fp);
    @flock($fp, LOCK_UN);
    @fclose($fp);
    $j = json_decode($data, true);
    return is_array($j) ? $j : null;
}

function writeJsonFile($path, $arr) {
    return atomicWriteFile($path, json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/* Append-only workflow log (JSONL) */
function appendWorkflowEvent($event) {
    if (!is_array($event)) return false;
    $event['ts'] = isset($event['ts']) ? $event['ts'] : time();
    $line = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    $dir = dirname(WORKFLOW_LOG);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $fp = @fopen(WORKFLOW_LOG, 'ab');
    if (!$fp) return false;
    @flock($fp, LOCK_EX);
    $ok = @fwrite($fp, $line) !== false;
    @fflush($fp);
    @flock($fp, LOCK_UN);
    @fclose($fp);
    return $ok;
}

/* Manifest / index */
function loadManifest() {
    $m = readJsonFile(INDEX_FILE);
    if (!is_array($m)) $m = array('version' => 1, 'generated' => 0, 'articles' => array());
    if (!isset($m['articles']) || !is_array($m['articles'])) $m['articles'] = array();
    return $m;
}

function manifestEntryFromMeta($meta) {
    if (!is_array($meta)) return null;
    $fullId = $meta['fullId'] ?? '';
    if ($fullId === '') return null;
    $status = $meta['status'] ?? (!empty($meta['published']) ? 'published' : 'draft');
    $published = ($status === 'published') || !empty($meta['published']);
    return array(
        'fullId' => $fullId,
        'id' => articleSlugId($fullId),
        'owner' => $meta['owner'] ?? $meta['author'] ?? articleOwner($fullId),
        'author' => $meta['author'] ?? $meta['owner'] ?? articleOwner($fullId),
        'title' => $meta['title'] ?? articleSlugId($fullId),
        'status' => $status,
        'published' => $published,
        'date' => isset($meta['date']) ? (int)$meta['date'] : 0,
        'updated' => isset($meta['updated']) ? (int)$meta['updated'] : (isset($meta['date']) ? (int)$meta['date'] : 0),
        'editor_notes' => $meta['editor_notes'] ?? '',
    );
}

function saveManifest($manifest) {
    $manifest['generated'] = time();
    $manifest['version'] = 1;
    return writeJsonFile(INDEX_FILE, $manifest);
}

function updateManifestForArticle($fullId, $meta = null) {
    $fullId = safeArticleId($fullId);
    if ($fullId === '') return false;
    $lockPath = INDEX_FILE . '.lock';
    $lfp = @fopen($lockPath, 'c+');
    if ($lfp) @flock($lfp, LOCK_EX);
    $manifest = loadManifest();
    $articles = $manifest['articles'];
    if ($meta === null) {
        $mp = articleMetaPath($fullId);
        $meta = readJsonFile($mp);
    }
    if (is_array($meta)) {
        $meta['fullId'] = $fullId;
        $entry = manifestEntryFromMeta($meta);
        if ($entry) $articles[$fullId] = $entry;
        else unset($articles[$fullId]);
    } else {
        unset($articles[$fullId]);
    }
    $manifest['articles'] = $articles;
    $ok = saveManifest($manifest);
    if ($lfp) { @flock($lfp, LOCK_UN); @fclose($lfp); }
    return $ok;
}

function removeFromManifest($fullId) {
    $fullId = safeArticleId($fullId);
    $lockPath = INDEX_FILE . '.lock';
    $lfp = @fopen($lockPath, 'c+');
    if ($lfp) @flock($lfp, LOCK_EX);
    $manifest = loadManifest();
    unset($manifest['articles'][$fullId]);
    $ok = saveManifest($manifest);
    if ($lfp) { @flock($lfp, LOCK_UN); @fclose($lfp); }
    return $ok;
}

function readArticleMeta($fullId) {
    $fullId = safeArticleId($fullId);
    $meta = readJsonFile(articleMetaPath($fullId));
    if (!is_array($meta)) return null;
    $meta['fullId'] = $fullId;
    $meta['id'] = articleSlugId($fullId);
    $meta['owner'] = $meta['owner'] ?? $meta['author'] ?? articleOwner($fullId);
    $meta['status'] = $meta['status'] ?? (!empty($meta['published']) ? 'published' : 'draft');
    return $meta;
}

function writeArticleFiles($fullId, $content, $meta) {
    $fullId = safeArticleId($fullId);
    if ($fullId === '' || !ensureArticleDir($fullId)) return false;
    if (!atomicWriteFile(articleMdPath($fullId), $content)) return false;
    $meta['fullId'] = $fullId;
    $meta['owner'] = $meta['owner'] ?? $meta['author'] ?? articleOwner($fullId);
    $meta['author'] = $meta['author'] ?? $meta['owner'];
    $meta['slug'] = articleSlugId($fullId);
    $meta['status'] = $meta['status'] ?? 'draft';
    $meta['published'] = ($meta['status'] === 'published');
    if (!writeJsonFile(articleMetaPath($fullId), $meta)) return false;
    updateManifestForArticle($fullId, $meta);
    return true;
}

function saveVersion($fullId) {
    $fullId = safeArticleId($fullId);
    if ($fullId === '' || !ensureArticleDir($fullId)) return;
    $dir = articleRevisionsDir($fullId);
    $ts = date('Ymd-His');
    foreach (array('article.md' => 'md', 'article.html' => 'html', 'meta.json' => 'meta.json') as $srcName => $dstExt) {
        $src = articleDir($fullId) . $srcName;
        if (file_exists($src)) @copy($src, $dir . $ts . '.' . $dstExt);
    }
}

function moveToTrash($fullId) {
    $fullId = safeArticleId($fullId);
    if ($fullId === '') return false;
    $srcDir = articleDir($fullId);
    $dst = TRASH_DIR . $fullId . '/' . date('Ymd-His') . '/';
    if (!is_dir($dst)) @mkdir($dst, 0755, true);
    if (is_dir($srcDir)) {
        // move bundle contents
        foreach (glob($srcDir . '*') as $item) {
            @rename($item, $dst . basename($item));
        }
        @rmdir($srcDir);
    }
    $media = MEDIA_DIR . $fullId . '/';
    if (is_dir($media)) @rename($media, $dst . 'media');
    removeFromManifest($fullId);
    appendWorkflowEvent(array('action' => 'trash', 'id' => $fullId));
    return true;
}

function getAllArticles() {
    $manifest = loadManifest();
    $articles = array_values($manifest['articles']);
    // Fallback: if manifest empty but bundles exist, do a lightweight scan (CLI rebuild is preferred)
    if (empty($articles)) {
        foreach (glob(CONTENT_DIR . '*/meta.json') as $metaFile) {
            $fullId = basename(dirname($metaFile));
            if ($fullId === '' || $fullId[0] === '_') continue;
            $meta = readArticleMeta($fullId);
            if ($meta) $articles[] = manifestEntryFromMeta($meta);
        }
    }
    $articles = array_filter($articles);
    usort($articles, function($a, $b) {
        $da = isset($a['updated']) ? $a['updated'] : (isset($a['date']) ? $a['date'] : 0);
        $db = isset($b['updated']) ? $b['updated'] : (isset($b['date']) ? $b['date'] : 0);
        return $db - $da;
    });
    return array_values($articles);
}

function getArticlesForUser($username) {
    $all = getAllArticles();
    $out = array();
    foreach ($all as $a) {
        if (($a['owner'] ?? '') === $username) $out[] = $a;
    }
    return $out;
}

function getArticlesByStatus($statuses) {
    if (!is_array($statuses)) $statuses = array($statuses);
    $all = getAllArticles();
    $out = array();
    foreach ($all as $a) {
        if (in_array($a['status'] ?? '', $statuses, true)) $out[] = $a;
    }
    return $out;
}

function canEditArticle($meta, $username, $role) {
    $owner = $meta['owner'] ?? '';
    $status = $meta['status'] ?? 'draft';
    if ($role === 'admin' || $role === 'editor') return true;
    if ($role === 'subeditor' && in_array($status, array('submitted', 'in_review', 'approved'), true)) return true;
    // Owners can edit drafts/submitted directly; published edits go back through review on save.
    if ($owner === $username && in_array($status, array('draft', 'submitted', 'published'), true)) return true;
    return false;
}

function normalizeMediaPaths($html) {
    $html = preg_replace('#(src|href)=([\'"])\.\./media/#', '$1=$2media/', $html);
    return $html;
}

function getAllPublishedArticles() {
    $articles = array();
    $all = getAllArticles();
    foreach ($all as $entry) {
        $status = $entry['status'] ?? '';
        $published = !empty($entry['published']) || $status === 'published';
        if (!$published) continue;
        $fullId = $entry['fullId'] ?? '';
        $htmlFile = articleHtmlPath($fullId);
        if (!file_exists($htmlFile)) continue;
        $htmlContent = @file_get_contents($htmlFile);
        $htmlContent = normalizeMediaPaths($htmlContent);
        $firstImage = '';
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $htmlContent, $imgMatch)) {
            $firstImage = $imgMatch[1];
        }
        $textContent = strip_tags($htmlContent);
        $textContent = preg_replace('/\s+/u', ' ', $textContent);
        $textContent = trim($textContent);
        $excerptShort = (mb_strlen($textContent, 'UTF-8') > 200) ? mb_substr($textContent, 0, 197, 'UTF-8') . '...' : $textContent;
        $excerptLong = (mb_strlen($textContent, 'UTF-8') > 900) ? mb_substr($textContent, 0, 897, 'UTF-8') . '...' : $textContent;
        $excerptFull = $textContent;
        $articles[] = array(
            'id' => $fullId,
            'title' => $entry['title'] ?? $fullId,
            'author' => $entry['author'] ?? $entry['owner'] ?? '',
            'date' => isset($entry['date']) ? $entry['date'] : 0,
            'excerpt' => $excerptShort,
            'excerptLong' => $excerptLong,
            'excerptFull' => $excerptFull,
            'image' => $firstImage,
            'html' => $htmlContent
        );
    }
    usort($articles, function($a, $b) { return $b['date'] - $a['date']; });
    return $articles;
}

function getArticle($id) {
    $id = safeArticleId($id);
    $htmlFile = articleHtmlPath($id);
    $meta = readArticleMeta($id);
    if ($meta && file_exists($htmlFile)) {
        $status = $meta['status'] ?? (!empty($meta['published']) ? 'published' : 'draft');
        if ($status === 'published' || !empty($meta['published'])) {
            $html = normalizeMediaPaths(@file_get_contents($htmlFile));
            return array('html' => $html, 'meta' => $meta);
        }
    }
    // Legacy fallback (pre-bundle flat files)
    $legacyHtml = CONTENT_DIR . $id . '.html';
    $legacyMeta = CONTENT_DIR . $id . '.meta.json';
    if (file_exists($legacyHtml) && file_exists($legacyMeta)) {
        $meta = json_decode(@file_get_contents($legacyMeta), true);
        if (is_array($meta) && (!empty($meta['published']) || ($meta['status'] ?? '') === 'published')) {
            $html = normalizeMediaPaths(@file_get_contents($legacyHtml));
            return array('html' => $html, 'meta' => $meta);
        }
    }
    return false;
}

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
            $headerLine = trim($line, " \t\r\n");
            if ($headerLine === '') { $i++; continue; }
            if (substr($headerLine, 0, 1) === '|') $headerLine = substr($headerLine, 1);
            if (substr($headerLine, -1) === '|') $headerLine = substr($headerLine, 0, -1);
            $headers = array_map('trim', explode('|', $headerLine));
            $i += 2;
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
    
    $text = preg_replace('/^### (.+)$/mu', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.+)$/mu', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.+)$/mu', '<h1>$1</h1>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/su', '<em>$1</em>', $text);
    $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/u', '<img src="$2" alt="$1">', $text);
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/u', '<a href="$2"><strong>$1</strong></a>', $text);
    
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
?>