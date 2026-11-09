<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

define('CONTENT_DIR', __DIR__ . '/../content/');
define('USERS_DIR', __DIR__ . '/../users/');
define('TRASH_DIR', __DIR__ . '/../content/_trash/');
define('MEDIA_DIR', __DIR__ . '/../media/');
define('CACHE_DIR', __DIR__ . '/../cache/');
define('INDEX_FILE', CONTENT_DIR . '_index.json');
define('WORKFLOW_LOG', CONTENT_DIR . '_workflow.jsonl');
define('SITE_TITLE', 'The Daily Fuckall');

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
function requireLogin() { if (!isLoggedIn()) { header('Location: /user/login.php'); exit; } }
function requireAdmin() { if (!isAdmin()) { header('Location: /admin/login.php'); exit; } }
function requireRole($role) { if (!hasRole($role)) { header('Location: /user/index.php'); exit; } }
function userBasePath() {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script, '/user/') !== false) return '';
    if (strpos($script, '/admin/') !== false) return '../user/';
    return 'user/';
}
