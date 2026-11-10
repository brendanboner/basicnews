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

/* User profiles (stored alongside credentials in users/*.json) */
function bw_user_file_path($username) {
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$username);
    if ($username === '') return '';
    return USERS_DIR . $username . '.json';
}

function bw_read_user($username, $reload = false) {
    static $cache = array();
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$username);
    if ($username === '') return null;
    if (!$reload && array_key_exists($username, $cache)) return $cache[$username];
    $file = bw_user_file_path($username);
    $user = ($file !== '' && is_file($file)) ? json_decode(@file_get_contents($file), true) : null;
    if (!is_array($user)) $user = null;
    $cache[$username] = $user;
    return $user;
}

function bw_user_profile($username, $reload = false) {
    $user = bw_read_user($username, $reload);
    $fullName = trim((string)($user['full_name'] ?? $user['display_name'] ?? ''));
    $title = trim((string)($user['title'] ?? ''));
    $bio = trim((string)($user['bio'] ?? ''));
    return array(
        'username' => (string)$username,
        'full_name' => $fullName,
        'display_name' => $fullName !== '' ? $fullName : (string)$username,
        'title' => $title,
        'bio' => $bio,
    );
}

function bw_user_display_name($username) {
    $profile = bw_user_profile($username);
    return $profile['display_name'];
}

function bw_user_title($username) {
    $profile = bw_user_profile($username);
    return $profile['title'];
}

function bw_user_bio($username) {
    $profile = bw_user_profile($username);
    return $profile['bio'];
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
        'promoted' => !empty($meta['promoted']),
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

/* Pull a leading image out of its paragraph and into a newspaper figure so
   article pages get the same halftone treatment as the front page. */
function bw_style_content_images($html) {
    $html = (string)$html;
    $html = preg_replace_callback(
        '#<p>\s*(<img\b[^>]*>)(.*?)</p>#is',
        function ($m) {
            $rest = trim($m[2]);
            $out = '<figure class="nyt-figure">' . $m[1] . '</figure>';
            if ($rest !== '') $out .= '<p>' . $rest . '</p>';
            return $out;
        },
        $html
    );
    return $html;
}

function bw_build_public_article_entry($entry, $htmlContent = null) {
    $fullId = $entry['fullId'] ?? '';
    if ($fullId === '') return null;
    if ($htmlContent === null) {
        $htmlFile = articleHtmlPath($fullId);
        if (!file_exists($htmlFile)) return null;
        $htmlContent = @file_get_contents($htmlFile);
    }
    $htmlContent = normalizeMediaPaths((string)$htmlContent);
    $firstImage = '';
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $htmlContent, $imgMatch)) {
        $firstImage = $imgMatch[1];
    }
    $textContent = html_entity_decode(strip_tags($htmlContent), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $textContent = preg_replace('/\s+/u', ' ', $textContent);
    $textContent = trim($textContent);
    $excerptShort = (mb_strlen($textContent, 'UTF-8') > 200) ? mb_substr($textContent, 0, 197, 'UTF-8') . '...' : $textContent;
    $excerptLong = (mb_strlen($textContent, 'UTF-8') > 900) ? mb_substr($textContent, 0, 897, 'UTF-8') . '...' : $textContent;
    $author = $entry['author'] ?? $entry['owner'] ?? '';
    return array(
        'id' => $fullId,
        'title' => $entry['title'] ?? $fullId,
        'author' => $author,
        'author_name' => bw_user_display_name($author),
        'author_title' => bw_user_title($author),
        'date' => isset($entry['date']) ? (int)$entry['date'] : 0,
        'excerpt' => $excerptShort,
        'excerptLong' => $excerptLong,
        'excerptFull' => $textContent,
        'image' => $firstImage,
        'promoted' => !empty($entry['promoted']),
    );
}

/* Split an article's full text into newspaper-style paragraphs for the
   front-page lead, stopping near $maxChars at a sentence boundary.
   Returns an array of paragraph strings (never cuts mid-sentence). */
function bw_lead_paragraphs($entry, $maxChars = 1000) {
    $text = trim((string)($entry['excerptFull'] ?? ''));
    if ($text === '') return array();
    $sentences = preg_split('/(?<=[.!?])\s+/u', $text);
    if (!is_array($sentences)) $sentences = array($text);
    $paras = array();
    $cur = '';
    $used = 0;
    foreach ($sentences as $s) {
        $s = trim($s);
        if ($s === '') continue;
        $cur = ($cur === '') ? $s : $cur . ' ' . $s;
        $used += mb_strlen($s, 'UTF-8') + 1;
        $curLen = mb_strlen($cur, 'UTF-8');
        if ($curLen >= 220 || ($used >= $maxChars && $curLen >= 80)) {
            $paras[] = $cur;
            $cur = '';
        }
        if ($used >= $maxChars) break;
    }
    if ($cur !== '') $paras[] = $cur;
    return $paras;
}

function getAllPublishedArticles() {
    // Fast path: prebuilt cache (CLI: bin/basicweb rebuild --cache)
    $cacheFile = CACHE_DIR . 'published.json';
    if (is_file($cacheFile)) {
        $cache = readJsonFile($cacheFile);
        if (is_array($cache) && isset($cache['articles']) && is_array($cache['articles'])) {
            $cached = $cache['articles'];
            $ok = true;
            foreach ($cached as $c) { if (!is_array($c) || !array_key_exists('excerpt', $c) || !array_key_exists('author_name', $c) || !array_key_exists('promoted', $c)) { $ok = false; break; } }
            if ($ok) return $cached;
        }
    }
    $articles = array();
    $all = getAllArticles();
    foreach ($all as $entry) {
        $status = $entry['status'] ?? '';
        $published = !empty($entry['published']) || $status === 'published';
        if (!$published) continue;
        $a = bw_build_public_article_entry($entry);
        if ($a) $articles[] = $a;
    }
    usort($articles, function($a, $b) { return ($b['date'] ?? 0) - ($a['date'] ?? 0); });
    return $articles;
}

function getArticle($id) {
    $id = safeArticleId($id);
    $htmlFile = articleHtmlPath($id);
    $meta = readArticleMeta($id);
    if ($meta && file_exists($htmlFile)) {
        $status = $meta['status'] ?? (!empty($meta['published']) ? 'published' : 'draft');
        if ($status === 'published' || !empty($meta['published'])) {
            $html = bw_style_content_images(normalizeMediaPaths(@file_get_contents($htmlFile)));
            return array('html' => $html, 'meta' => $meta);
        }
    }
    // Legacy fallback (pre-bundle flat files)
    $legacyHtml = CONTENT_DIR . $id . '.html';
    $legacyMeta = CONTENT_DIR . $id . '.meta.json';
    if (file_exists($legacyHtml) && file_exists($legacyMeta)) {
        $meta = json_decode(@file_get_contents($legacyMeta), true);
        if (is_array($meta) && (!empty($meta['published']) || ($meta['status'] ?? '') === 'published')) {
            $html = bw_style_content_images(normalizeMediaPaths(@file_get_contents($legacyHtml)));
            return array('html' => $html, 'meta' => $meta);
        }
    }
    return false;
}

function bw_sanitize_markdown_url($url) {
    // $url is already HTML-escaped in our markdown flow; decode minimally for scheme checks.
    $u = html_entity_decode((string)$url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $u = trim($u);
    if ($u === '') return '#';
    if (preg_match('/[\x00-\x1f\x7f]/', $u)) return '#';
    if (preg_match('#^(https?:|mailto:)#i', $u)) return htmlspecialchars($u, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    if (preg_match('~^(/|\.{1,2}/|\?|\#|media/|\.\./media/)~', $u)) return htmlspecialchars($u, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    // Block javascript:, data:, vbscript:, and other schemes.
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $u)) return '#';
    return htmlspecialchars($u, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function simpleMarkdown($text) {
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // Tables and blockquotes (line-based; note: '>' is escaped to '&gt;')
    $lines = preg_split('/\R/', $text);
    $result = '';
    $i = 0;
    $count = count($lines);
    while ($i < $count) {
        $line = $lines[$i];
        // Blockquote: group consecutive &gt; lines
        if (preg_match('/^\s*&gt;\s?(.*)$/', $line, $bm)) {
            $bq = $bm[1] . "\n";
            $i++;
            while ($i < $count && preg_match('/^\s*&gt;\s?(.*)$/', $lines[$i], $bm2)) {
                $bq .= $bm2[1] . "\n";
                $i++;
            }
            $result .= "<blockquote>\n" . trim($bq) . "\n</blockquote>\n";
            continue;
        }
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
    $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/u', function($m) {
        return '<img src="' . bw_sanitize_markdown_url($m[2]) . '" alt="' . $m[1] . '">';
    }, $text);
    $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/u', function($m) {
        return '<a href="' . bw_sanitize_markdown_url($m[2]) . '"><strong>' . $m[1] . '</strong></a>';
    }, $text);

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