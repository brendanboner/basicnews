#!/usr/bin/env php
<?php
// CLI only: rebuild manifest/index, migrate legacy flat files to bundles, regenerate artifacts.
// Usage: php bin/rebuild.php [--migrate] [--regenerate-html] [--all]
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
require_once __DIR__ . '/../includes/functions.php';

$opts = array_slice($argv, 1);
$doMigrate = in_array('--migrate', $opts, true) || in_array('--all', $opts, true) || empty($opts);
$doHtml = in_array('--regenerate-html', $opts, true) || in_array('--all', $opts, true);
$doCache = in_array('--cache', $opts, true) || in_array('--all', $opts, true) || empty($opts);

echo "Rebuild starting...\n";
$migrated = 0; $indexed = 0; $htmlCount = 0;

// Migrate legacy flat files: content/<fullId>.{md,html,meta.json}
if ($doMigrate) {
    $metas = glob(CONTENT_DIR . '*.meta.json');
    foreach ($metas as $mf) {
        $fullId = basename($mf, '.meta.json');
        if ($fullId === '' || $fullId[0] === '_') continue;
        $dir = articleDir($fullId);
        if (is_dir($dir) && file_exists($dir . 'meta.json')) continue; // already bundled
        ensureArticleDir($fullId);
        $srcMd = CONTENT_DIR . $fullId . '.md';
        $srcHtml = CONTENT_DIR . $fullId . '.html';
        $meta = json_decode(@file_get_contents($mf), true);
        if (!is_array($meta)) $meta = array();
        $meta['fullId'] = $fullId;
        $meta['owner'] = $meta['owner'] ?? $meta['author'] ?? articleOwner($fullId);
        $meta['author'] = $meta['author'] ?? $meta['owner'];
        $meta['slug'] = articleSlugId($fullId);
        $meta['status'] = $meta['status'] ?? (!empty($meta['published']) ? 'published' : 'draft');
        $meta['published'] = ($meta['status'] === 'published');
        writeJsonFile(articleMetaPath($fullId), $meta);
        if (file_exists($srcMd)) @copy($srcMd, articleMdPath($fullId));
        if (file_exists($srcHtml)) @copy($srcHtml, articleHtmlPath($fullId));
        // move legacy to a backup subdir? keep originals for safety; mark migrated by leaving them (operator can clean)
        $migrated++;
        echo " migrated: $fullId\n";
    }
}

// Rebuild manifest from bundles
$manifest = array('version' => 1, 'generated' => time(), 'articles' => array());
foreach (glob(CONTENT_DIR . '*/meta.json') as $mf) {
    $fullId = basename(dirname($mf));
    if ($fullId === '' || $fullId[0] === '_') continue;
    $meta = readArticleMeta($fullId);
    if ($meta) {
        $entry = manifestEntryFromMeta($meta);
        if ($entry) { $manifest['articles'][$fullId] = $entry; $indexed++; }
        if ($doHtml && ($meta['status'] === 'published' || !empty($meta['published'])) && file_exists(articleMdPath($fullId))) {
            $html = simpleMarkdown(@file_get_contents(articleMdPath($fullId)));
            if (atomicWriteFile(articleHtmlPath($fullId), $html)) $htmlCount++;
            else echo " warning: failed to write html for $fullId\n";
        }
    }
}
saveManifest($manifest);
echo " indexed: $indexed articles\n";
if ($doMigrate) echo " migrated bundles: $migrated\n";
if ($doHtml) echo " regenerated html: $htmlCount\n";

if ($doCache) {
    $pub = array();
    foreach ($manifest['articles'] as $e) {
        if (!empty($e['published']) || ($e['status'] ?? '') === 'published') $pub[] = $e;
    }
    usort($pub, function($a,$b){ return ($b['date']??0)-($a['date']??0); });
    writeJsonFile(CACHE_DIR . 'published.json', array('generated'=>time(),'articles'=>$pub));
    echo " wrote cache/published.json\n";
}

appendWorkflowEvent(array('action' => 'rebuild', 'migrated' => $migrated, 'indexed' => $indexed, 'html' => $htmlCount));
echo "Done.\n";
?>