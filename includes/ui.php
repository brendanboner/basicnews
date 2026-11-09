<?php
// Shared admin/user UI helpers. Assumes config is loaded.

function bw_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function bw_status_label($status) {
    $map = array(
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'in_review' => 'In review',
        'approved' => 'Approved',
        'published' => 'Published',
        'archived' => 'Archived',
    );
    return $map[$status] ?? ucfirst(str_replace('_', ' ', (string)$status));
}

function bw_status_badge($status) {
    $s = preg_replace('/[^a-z_]/', '', (string)$status);
    $label = bw_h(bw_status_label($status));
    return '<span class="bw-badge bw-badge-' . $s . '">' . $label . '</span>';
}

function bw_admin_nav($active = '') {
    $items = array(
        'dashboard' => array('label' => 'Dashboard', 'href' => 'index.php'),
        'users' => array('label' => 'Users', 'href' => 'users.php'),
        'settings' => array('label' => 'Site settings', 'href' => 'settings.php'),
        'password' => array('label' => 'Password', 'href' => 'password.php'),
    );
    $user = $_SESSION['user']['username'] ?? 'admin';
    echo '<header class="bw-topbar"><div class="bw-topbar-inner">';
    echo '<div class="bw-topbar-brand"><a href="index.php">' . bw_h(SITE_TITLE) . '</a> <span class="bw-topbar-sub">Admin</span></div>';
    echo '<nav class="bw-nav" aria-label="Admin">';
    foreach ($items as $key => $it) {
        $cls = 'bw-nav-link' . ($active === $key ? ' is-active' : '');
        echo '<a class="' . $cls . '" href="' . bw_h($it['href']) . '">' . bw_h($it['label']) . '</a>';
    }
    echo '</nav>';
    echo '<div class="bw-topbar-user"><a class="bw-nav-link" href="../index.php">View site</a> <span class="bw-muted">' . bw_h($user) . '</span> <a class="bw-nav-link" href="logout.php">Log out</a></div>';
    echo '</div></header>';
}

function bw_user_nav($active = '') {
    $username = $_SESSION['user']['username'] ?? '';
    $role = function_exists('getUserRole') ? getUserRole() : ($username ? 'user' : '');
    $items = array(
        'articles' => array('label' => 'My articles', 'href' => 'index.php', 'show' => true),
        'new' => array('label' => 'New article', 'href' => 'edit.php', 'show' => true),
        'review' => array('label' => 'Review queue', 'href' => 'review.php', 'show' => hasRole('subeditor')),
        'editor' => array('label' => 'Editor queue', 'href' => 'editor.php', 'show' => hasRole('editor')),
        'archive' => array('label' => 'Archive', 'href' => 'archive.php', 'show' => hasRole('editor')),
        'password' => array('label' => 'Password', 'href' => 'password.php', 'show' => true),
    );
    echo '<header class="bw-topbar"><div class="bw-topbar-inner">';
    echo '<div class="bw-topbar-brand"><a href="index.php">' . bw_h(SITE_TITLE) . '</a> <span class="bw-topbar-sub">Workspace</span></div>';
    echo '<nav class="bw-nav" aria-label="Workspace">';
    foreach ($items as $key => $it) {
        if (empty($it['show'])) continue;
        $cls = 'bw-nav-link' . ($active === $key ? ' is-active' : '');
        echo '<a class="' . $cls . '" href="' . bw_h($it['href']) . '">' . bw_h($it['label']) . '</a>';
    }
    echo '</nav>';
    echo '<div class="bw-topbar-user"><a class="bw-nav-link" href="../index.php">View site</a> <span class="bw-muted">' . bw_h($username) . ($role ? ' · ' . bw_h($role) : '') . '</span> <a class="bw-nav-link" href="logout.php">Log out</a></div>';
    echo '</div></header>';
}

function bw_page_header($title, $subtitle = '', $actionsHtml = '') {
    echo '<div class="bw-page-header">';
    echo '<div><h1 class="bw-page-title">' . bw_h($title) . '</h1>';
    if ($subtitle !== '') echo '<p class="bw-page-subtitle">' . bw_h($subtitle) . '</p>';
    echo '</div>';
    if ($actionsHtml !== '') echo '<div class="bw-page-actions">' . $actionsHtml . '</div>';
    echo '</div>';
}
