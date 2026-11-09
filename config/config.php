<?php
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    if (PHP_VERSION_ID >= 70300) {
        @session_set_cookie_params(array(
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    } else {
        @session_set_cookie_params(0, '/; samesite=Lax', '', $secure, true);
    }
    @session_start();
}

function bw_csrf_token() {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function bw_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(bw_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
function bw_verify_csrf() {
    $token = '';
    if (isset($_POST['csrf_token'])) $token = (string)$_POST['csrf_token'];
    elseif (isset($_GET['csrf_token'])) $token = (string)$_GET['csrf_token'];
    $sess = isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '';
    return $token !== '' && $sess !== '' && hash_equals($sess, $token);
}
function bw_require_csrf() {
    if (!bw_verify_csrf()) {
        http_response_code(403);
        exit('Invalid security token. Please go back and try again.');
    }
}

define('CONTENT_DIR', __DIR__ . '/../content/');
define('USERS_DIR', __DIR__ . '/../users/');
define('TRASH_DIR', __DIR__ . '/../content/_trash/');
define('MEDIA_DIR', __DIR__ . '/../media/');
define('CACHE_DIR', __DIR__ . '/../cache/');
define('INDEX_FILE', CONTENT_DIR . '_index.json');
define('WORKFLOW_LOG', CONTENT_DIR . '_workflow.jsonl');
define('SITE_SETTINGS_FILE', CONTENT_DIR . '_site.json');
function bw_site_settings($reload = false) {
    static $s = null;
    if ($s !== null && !$reload) return $s;
    $defaults = array(
        'site_title' => 'The Daily Fuckall',
        'edition' => 'Late Edition',
        'theme' => 'light',
        'font_scale' => 'normal',
        'home_density' => 'full',
        'spacing' => 'normal',
    );
    $s = $defaults;
    $f = SITE_SETTINGS_FILE;
    if (is_file($f)) {
        $j = json_decode(@file_get_contents($f), true);
        if (is_array($j)) $s = array_merge($s, $j);
    }
    $s['site_title'] = trim((string)($s['site_title'] ?? '')) ?: $defaults['site_title'];
    $s['edition'] = trim((string)($s['edition'] ?? '')) ?: $defaults['edition'];
    $s['theme'] = in_array($s['theme'] ?? '', array('light', 'paper', 'dark'), true) ? $s['theme'] : $defaults['theme'];
    $s['font_scale'] = in_array($s['font_scale'] ?? '', array('small', 'normal', 'large', 'xlarge'), true) ? $s['font_scale'] : $defaults['font_scale'];
    $s['home_density'] = in_array($s['home_density'] ?? '', array('full', 'compact', 'headlines'), true) ? $s['home_density'] : $defaults['home_density'];
    $s['spacing'] = in_array($s['spacing'] ?? '', array('tight', 'normal', 'relaxed'), true) ? $s['spacing'] : $defaults['spacing'];
    return $s;
}
function bw_style_body_classes($base = '') {
    $s = bw_site_settings();
    $classes = array();
    if ($base !== '') $classes[] = $base;
    $classes[] = 'bw-theme-' . $s['theme'];
    $classes[] = 'bw-font-' . $s['font_scale'];
    $classes[] = 'bw-home-' . $s['home_density'];
    $classes[] = 'bw-space-' . $s['spacing'];
    return implode(' ', array_map(function($c) { return preg_replace('/[^a-z0-9_-]/', '', $c); }, $classes));
}
$_bwSite = bw_site_settings();
define('SITE_TITLE', $_bwSite['site_title']);
unset($_bwSite);

if (!is_dir(CONTENT_DIR)) { @mkdir(CONTENT_DIR, 0755, true); }
if (!is_dir(USERS_DIR)) { @mkdir(USERS_DIR, 0755, true); }
if (!is_dir(TRASH_DIR)) { @mkdir(TRASH_DIR, 0755, true); }
if (!is_dir(MEDIA_DIR)) { @mkdir(MEDIA_DIR, 0755, true); }
if (!is_dir(CACHE_DIR)) { @mkdir(CACHE_DIR, 0755, true); }

$adminFile = USERS_DIR . 'admin.json';
if (!file_exists($adminFile)) {
    $admin = array('username' => 'admin', 'password' => password_hash('admin', PASSWORD_DEFAULT), 'type' => 'admin', 'role' => 'admin');
    @file_put_contents($adminFile, json_encode($admin, JSON_PRETTY_PRINT));
} else {
    $admin = json_decode(@file_get_contents($adminFile), true);
    if (is_array($admin) && !isset($admin['role'])) {
        $admin['role'] = 'admin';
        @file_put_contents($adminFile, json_encode($admin, JSON_PRETTY_PRINT));
    }
}

function isLoggedIn() { return isset($_SESSION['user']); }
function isAdmin() { return isset($_SESSION['user']) && (($_SESSION['user']['type'] ?? '') === 'admin' || ($_SESSION['user']['role'] ?? '') === 'admin'); }
function getUserRole() {
    if (!isset($_SESSION['user'])) return '';
    if (isAdmin()) return 'admin';
    return $_SESSION['user']['role'] ?? 'contributor';
}
function hasRole($role) {
    $r = getUserRole();
    $levels = array('contributor' => 1, 'subeditor' => 2, 'editor' => 3, 'admin' => 4);
    $ur = isset($levels[$r]) ? $levels[$r] : 0;
    $nr = isset($levels[$role]) ? $levels[$role] : 99;
    return $ur >= $nr;
}
function bw_password_file_for_session() {
    if (!isLoggedIn()) return '';
    $u = $_SESSION['user'] ?? array();
    $name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($u['username'] ?? ''));
    if ($name === '') return '';
    if (isAdmin() || $name === 'admin') return USERS_DIR . 'admin.json';
    return USERS_DIR . $name . '.json';
}
function requirePasswordChanged() {
    if (!isLoggedIn()) return;
    if (empty($_SESSION['force_password_change'])) return;
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (in_array($script, array('password.php', 'logout.php', 'login.php'), true)) return;
    $base = isAdmin() ? '/admin/' : '/user/';
    if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) $base = '';
    elseif (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/user/') !== false) $base = '';
    header('Location: ' . $base . 'password.php?forced=1');
    exit;
}
function requireLogin() { if (!isLoggedIn()) { header('Location: /user/login.php'); exit; } requirePasswordChanged(); }
function requireAdmin() { if (!isAdmin()) { header('Location: /admin/login.php'); exit; } requirePasswordChanged(); }
function requireRole($role) { if (!hasRole($role)) { header('Location: /user/index.php'); exit; } requirePasswordChanged(); }
function userBasePath() {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script, '/user/') !== false) return '';
    if (strpos($script, '/admin/') !== false) return '../user/';
    return 'user/';
}
