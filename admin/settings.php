<?php
require_once '../config/config.php';
require_once '../includes/ui.php';
requireAdmin();

$message = '';
$error = '';
$settings = bw_site_settings();
$customCssFile = __DIR__ . '/../css/custom.css';
$customCss = is_file($customCssFile) ? (string)@file_get_contents($customCssFile) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    bw_require_csrf();
    $siteTitle = trim((string)($_POST['site_title'] ?? ''));
    $edition = trim((string)($_POST['edition'] ?? ''));
    $theme = (string)($_POST['theme'] ?? 'light');
    $fontScale = (string)($_POST['font_scale'] ?? 'normal');
    $homeDensity = (string)($_POST['home_density'] ?? 'full');
    $spacing = (string)($_POST['spacing'] ?? 'normal');
    $pageTurn = (string)($_POST['page_turn'] ?? 'on');
    $pageTurnPerspective = (string)($_POST['page_turn_perspective'] ?? 'normal');
    $pageTurnSpeed = (string)($_POST['page_turn_speed'] ?? 'normal');
    $leadDetail = (string)($_POST['lead_detail'] ?? 'extended');
    $customCss = (string)($_POST['custom_css'] ?? '');
    if ($siteTitle === '') $siteTitle = 'The Daily Fuckall';
    if ($edition === '') $edition = 'Late Edition';
    if (!in_array($theme, array('light', 'paper', 'dark'), true)) $theme = 'light';
    if (!in_array($fontScale, array('small', 'normal', 'large', 'xlarge'), true)) $fontScale = 'normal';
    if (!in_array($homeDensity, array('full', 'compact', 'headlines'), true)) $homeDensity = 'full';
    if (!in_array($spacing, array('tight', 'normal', 'relaxed'), true)) $spacing = 'normal';
    if (!in_array($pageTurn, array('on', 'off'), true)) $pageTurn = 'on';
    if (!in_array($pageTurnPerspective, array('subtle', 'normal', 'dramatic'), true)) $pageTurnPerspective = 'normal';
    if (!in_array($pageTurnSpeed, array('fast', 'normal', 'slow'), true)) $pageTurnSpeed = 'normal';
    if (!in_array($leadDetail, array('standard', 'extended'), true)) $leadDetail = 'extended';
    // Basic safety: limit size, no PHP tags (it's a .css file but be defensive)
    if (strlen($customCss) > 100000) $customCss = substr($customCss, 0, 100000);
    $customCss = str_replace(array('<?', '?>'), '', $customCss);

    $payload = array(
        'site_title' => $siteTitle,
        'edition' => $edition,
        'theme' => $theme,
        'font_scale' => $fontScale,
        'home_density' => $homeDensity,
        'spacing' => $spacing,
        'page_turn' => $pageTurn,
        'page_turn_perspective' => $pageTurnPerspective,
        'page_turn_speed' => $pageTurnSpeed,
        'lead_detail' => $leadDetail,
    );
    $ok1 = @file_put_contents(SITE_SETTINGS_FILE, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
    $ok2 = @file_put_contents($customCssFile, $customCss) !== false;
    @unlink(CACHE_DIR . 'published.json');

    if ($ok1 && $ok2) {
        $message = 'Settings saved. Preset styles are applied to the homepage and article pages.';
        $settings = bw_site_settings(true);
    } elseif ($ok1) {
        $message = 'Site settings saved, but custom CSS could not be written (check permissions on css/custom.css).';
        $settings = bw_site_settings(true);
    } else {
        $error = 'Could not save settings (check permissions on content/).';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site settings - <?php echo bw_h(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="<?php echo bw_asset('../css/style.css'); ?>">
</head>
<body class="<?php echo bw_h(bw_style_body_classes('')); ?>">
<?php bw_admin_nav('settings'); ?>
<main class="bw-container">
    <?php bw_page_header('Site settings', 'Control the masthead, choose built-in reading styles, and tune the page-turn effect between stories. Changes apply to the public homepage and article pages.'); ?>
    <?php if ($message): ?><div class="message success"><?php echo bw_h($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?php echo bw_h($error); ?></div><?php endif; ?>

    <section class="bw-card">
        <form method="POST">
            <?php echo bw_csrf_field(); ?>
            <div class="form-group">
                <label for="site_title">Site title (masthead)</label>
                <input type="text" id="site_title" name="site_title" value="<?php echo htmlspecialchars($settings['site_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="form-group">
                <label for="edition">Edition / subhead text</label>
                <input type="text" id="edition" name="edition" value="<?php echo htmlspecialchars($settings['edition'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <h2 style="font-size:14pt;margin:26px 0 10px;border-top:1px solid #000;padding-top:16px;">Style Presets (no CSS typing)</h2>
            <p style="margin-bottom:14px;font-style:italic;">Choose built-in BasicWeb styles. These are fast, file-based, and load after the main stylesheet; custom CSS (below) can still override them.</p>

            <div class="form-group">
                <label for="theme">Background / reading theme</label>
                <select id="theme" name="theme">
                    <option value="light" <?php echo ($settings['theme'] ?? '') === 'light' ? 'selected' : ''; ?>>Light (white, default)</option>
                    <option value="paper" <?php echo ($settings['theme'] ?? '') === 'paper' ? 'selected' : ''; ?>>Newspaper paper (warm/cream)</option>
                    <option value="dark" <?php echo ($settings['theme'] ?? '') === 'dark' ? 'selected' : ''; ?>>Dark</option>
                </select>
            </div>
            <div class="form-group">
                <label for="font_scale">Text size</label>
                <select id="font_scale" name="font_scale">
                    <option value="small" <?php echo ($settings['font_scale'] ?? '') === 'small' ? 'selected' : ''; ?>>Small</option>
                    <option value="normal" <?php echo ($settings['font_scale'] ?? '') === 'normal' ? 'selected' : ''; ?>>Normal (default)</option>
                    <option value="large" <?php echo ($settings['font_scale'] ?? '') === 'large' ? 'selected' : ''; ?>>Large</option>
                    <option value="xlarge" <?php echo ($settings['font_scale'] ?? '') === 'xlarge' ? 'selected' : ''; ?>>Extra large</option>
                </select>
            </div>
            <div class="form-group">
                <label for="home_density">Homepage information amount</label>
                <select id="home_density" name="home_density">
                    <option value="full" <?php echo ($settings['home_density'] ?? '') === 'full' ? 'selected' : ''; ?>>Full (default layout with excerpts/images)</option>
                    <option value="compact" <?php echo ($settings['home_density'] ?? '') === 'compact' ? 'selected' : ''; ?>>Compact (fewer items, less excerpt text)</option>
                    <option value="headlines" <?php echo ($settings['home_density'] ?? '') === 'headlines' ? 'selected' : ''; ?>>Headlines-focused (minimal excerpts on homepage)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="spacing">Spacing (homepage and article/subpages)</label>
                <select id="spacing" name="spacing">
                    <option value="tight" <?php echo ($settings['spacing'] ?? '') === 'tight' ? 'selected' : ''; ?>>Tight</option>
                    <option value="normal" <?php echo ($settings['spacing'] ?? '') === 'normal' ? 'selected' : ''; ?>>Normal (default)</option>
                    <option value="relaxed" <?php echo ($settings['spacing'] ?? '') === 'relaxed' ? 'selected' : ''; ?>>Relaxed</option>
                </select>
            </div>

            <h2 style="font-size:14pt;margin:26px 0 10px;border-top:1px solid #000;padding-top:16px;">Page-turn effect (article pages)</h2>
            <p style="margin-bottom:14px;font-style:italic;">Moving between stories turns the page: the links at the top of each article ("Turn back a page" / "Turn the page") gently curl the page over its spine, and the next story settles into place. Hovering a link teases the curl, and the arrow keys turn pages too. The homepage itself stays a classic broadsheet front page.</p>

            <div class="form-group">
                <label for="page_turn">Page-turn effect</label>
                <select id="page_turn" name="page_turn">
                    <option value="on" <?php echo ($settings['page_turn'] ?? 'on') === 'on' ? 'selected' : ''; ?>>On (default) — pages turn between stories</option>
                    <option value="off" <?php echo ($settings['page_turn'] ?? 'off') === 'off' ? 'selected' : ''; ?>>Off — plain Previous/Next story links</option>
                </select>
            </div>
            <div class="form-group">
                <label for="page_turn_perspective">3D perspective depth</label>
                <select id="page_turn_perspective" name="page_turn_perspective">
                    <option value="subtle" <?php echo ($settings['page_turn_perspective'] ?? '') === 'subtle' ? 'selected' : ''; ?>>Subtle (gentle depth)</option>
                    <option value="normal" <?php echo ($settings['page_turn_perspective'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal (default)</option>
                    <option value="dramatic" <?php echo ($settings['page_turn_perspective'] ?? '') === 'dramatic' ? 'selected' : ''; ?>>Dramatic (pronounced 3D)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="page_turn_speed">Page turn speed</label>
                <select id="page_turn_speed" name="page_turn_speed">
                    <option value="fast" <?php echo ($settings['page_turn_speed'] ?? '') === 'fast' ? 'selected' : ''; ?>>Fast (0.45s)</option>
                    <option value="normal" <?php echo ($settings['page_turn_speed'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal (0.7s, default)</option>
                    <option value="slow" <?php echo ($settings['page_turn_speed'] ?? '') === 'slow' ? 'selected' : ''; ?>>Slow (1.05s)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="lead_detail">Front-page lead story detail</label>
                <select id="lead_detail" name="lead_detail">
                    <option value="extended" <?php echo ($settings['lead_detail'] ?? 'extended') === 'extended' ? 'selected' : ''; ?>>Extended (default) — multi-paragraph newspaper lead</option>
                    <option value="standard" <?php echo ($settings['lead_detail'] ?? '') === 'standard' ? 'selected' : ''; ?>>Standard — short excerpt</option>
                </select>
                <small style="display:block;margin-top:5px;font-style:italic;">Applies to whichever story editors promote to the front page (Admin dashboard &rarr; "Front page &amp; lead story").</small>
            </div>

            <div class="form-group">
                <label for="custom_css">Custom CSS (optional, loads after presets/main stylesheet; fast, file-based)</label>
                <textarea id="custom_css" name="custom_css" class="markdown-editor" style="min-height:220px;font-family:'Courier New',monospace;font-size:12px;"><?php echo htmlspecialchars($customCss, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <small style="display:block;margin-top:5px;font-style:italic;">Use simple overrides (colors, fonts, spacing). Avoid external @import/dependencies to keep it fast.</small>
            </div>
            <button type="submit" class="btn btn-primary">Save settings</button>
        </form>
    </section>
</main>
</body>
</html>
