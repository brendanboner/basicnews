<?php
// Shared rebuild logic (CLI and protected web trigger). Requires includes/functions.php.

function bw_rebuild_site($doMigrate = true, $doHtml = true, $doCache = true) {
    $migrated = 0; $indexed = 0; $htmlCount = 0; $warnings = array();

    if ($doMigrate) {
        $metas = glob(CONTENT_DIR . '*.meta.json');
        if (is_array($metas)) foreach ($metas as $mf) {
            $fullId = basename($mf, '.meta.json');
            if ($fullId === '' || $fullId[0] === '_') continue;
            $dir = articleDir($fullId);
            if (is_dir($dir) && file_exists($dir . 'meta.json')) continue;
            if (!ensureArticleDir($fullId)) { $warnings[] = "migrate: could not create $fullId"; continue; }
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
            $migrated++;
        }
    }

    $manifest = array('version' => 1, 'generated' => time(), 'articles' => array());
    $bundleMetas = glob(CONTENT_DIR . '*/meta.json');
    if (is_array($bundleMetas)) foreach ($bundleMetas as $mf) {
        $fullId = basename(dirname($mf));
        if ($fullId === '' || $fullId[0] === '_') continue;
        $meta = readArticleMeta($fullId);
        if ($meta) {
            $entry = manifestEntryFromMeta($meta);
            if ($entry) { $manifest['articles'][$fullId] = $entry; $indexed++; }
            if ($doHtml && ($meta['status'] === 'published' || !empty($meta['published'])) && file_exists(articleMdPath($fullId))) {
                $html = simpleMarkdown((string)@file_get_contents(articleMdPath($fullId)));
                if (atomicWriteFile(articleHtmlPath($fullId), $html)) $htmlCount++;
                else $warnings[] = "html: failed to write $fullId (check permissions)";
            }
        }
    }
    saveManifest($manifest);

    if ($doCache) {
        $pub = array();
        foreach ($manifest['articles'] as $e) {
            if (!empty($e['published']) || ($e['status'] ?? '') === 'published') {
                $a = bw_build_public_article_entry($e);
                if ($a) $pub[] = $a;
            }
        }
        usort($pub, function($a,$b){ return ($b['date']??0)-($a['date']??0); });
        if (!writeJsonFile(CACHE_DIR . 'published.json', array('generated'=>time(),'articles'=>$pub))) {
            $warnings[] = 'cache: failed to write cache/published.json';
        }
    }

    appendWorkflowEvent(array('action' => 'rebuild', 'migrated' => $migrated, 'indexed' => $indexed, 'html' => $htmlCount));
    return array('migrated' => $migrated, 'indexed' => $indexed, 'html' => $htmlCount, 'warnings' => $warnings);
}
