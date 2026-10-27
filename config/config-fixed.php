<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

define('CONTENT_DIR', __DIR__ . '/../content/');
define('USERS_DIR', __DIR__ . '/../users/');
define('SITE_TITLE', 'The Daily Bonner');

if (!is_dir(CONTENT_DIR)) {
    @mkdir(CONTENT_DIR, 0755, true);
}
if (!is_dir(USERS_DIR)) {
    @mkdir(USERS_DIR, 0755, true);
}

$adminFile = USERS_DIR . 'admin.json';
if (!file_exists($adminFile)) {
    $admin = array(
        'username' => 'admin',
        'password' => password_hash('admin', PASSWORD_DEFAULT),
        'type' => 'admin'
    );
    @file_put_contents($adminFile, json_encode($admin, JSON_PRETTY_PRINT));
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['type'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /user/login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /admin/login.php');
        exit;
    }
}
