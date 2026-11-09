#!/usr/bin/env php
<?php
// CLI: rebuild manifest/index, optionally regenerate published HTML and cache.
// Usage: php bin/rebuild.php [--migrate] [--regenerate-html] [--cache] [--all]
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/rebuild.php';

$opts = array_slice($argv, 1);
$all = in_array('--all', $opts, true) || empty($opts);
$doMigrate = $all || in_array('--migrate', $opts, true);
$doHtml = $all || in_array('--regenerate-html', $opts, true);
$doCache = $all || in_array('--cache', $opts, true);

echo "Rebuild starting...\n";
$r = bw_rebuild_site($doMigrate, $doHtml, $doCache);
if ($doMigrate) echo " migrated bundles: {$r['migrated']}\n";
echo " indexed: {$r['indexed']} articles\n";
if ($doHtml) echo " regenerated html: {$r['html']}\n";
if ($doCache) echo " wrote cache/published.json\n";
foreach (($r['warnings'] ?? array()) as $w) echo " warning: $w\n";
echo "Done.\n";
?>