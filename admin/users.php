<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/ui.php';
requireAdmin();

$message = '';
$error = '';

$validRoles = array('contributor', 'subeditor', 'editor');

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    bw_require_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'contributor';
    $fullName = trim($_POST['full_name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    if (!in_array($role, $validRoles, true)) $role = 'contributor';
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password required';
    } elseif (preg_match('/[^a-zA-Z0-9_]/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores';
    } elseif ($username === 'admin') {
        $error = 'Cannot create user named admin';
    } elseif (mb_strlen($fullName) > 100 || mb_strlen($title) > 100) {
        $error = 'Full name and title must be 100 characters or fewer';
    } else {
        $userFile = USERS_DIR . $username . '.json';
        if (file_exists($userFile)) {
            $error = 'User already exists';
        } else {
            $user = array(
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'type' => 'user',
                'role' => $role,
                'full_name' => $fullName,
                'title' => $title
            );
            if (file_put_contents($userFile, json_encode($user, JSON_PRETTY_PRINT))) {
                $message = 'User created successfully';
            } else {
                $error = 'Failed to create user';
            }
        }
    }
}

// Update role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    bw_require_csrf();
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'contributor';
    if (!in_array($role, $validRoles, true)) $role = 'contributor';
    if ($username !== '' && $username !== 'admin') {
        $userFile = USERS_DIR . $username . '.json';
        if (file_exists($userFile)) {
            $user = json_decode(file_get_contents($userFile), true);
            if (is_array($user)) {
                $user['role'] = $role;
                $user['type'] = 'user';
                file_put_contents($userFile, json_encode($user, JSON_PRETTY_PRINT));
                $message = 'Role updated for ' . $username;
            }
        }
    }
}

// Update public author profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    bw_require_csrf();
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', (string)($_POST['username'] ?? ''));
    $fullName = trim($_POST['full_name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $role = $_POST['role'] ?? 'contributor';
    if (!in_array($role, $validRoles, true)) $role = 'contributor';
    if ($username === '' || $username === 'admin') {
        $error = 'Invalid user';
    } elseif (mb_strlen($fullName) > 100 || mb_strlen($title) > 100) {
        $error = 'Full name and title must be 100 characters or fewer';
    } elseif (mb_strlen($bio) > 1200) {
        $error = 'Biography must be 1200 characters or fewer';
    } else {
        $userFile = USERS_DIR . $username . '.json';
        if (file_exists($userFile)) {
            $user = json_decode(file_get_contents($userFile), true);
            if (is_array($user)) {
                $user['full_name'] = $fullName;
                $user['title'] = $title;
                $user['bio'] = $bio;
                $user['role'] = $role;
                $user['type'] = 'user';
                if (file_put_contents($userFile, json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                    $message = 'Profile updated for ' . $username;
                } else {
                    $error = 'Could not save the profile';
                }
            }
        }
    }
}

// Delete user
if (isset($_GET['delete'])) {
    bw_require_csrf();
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$_GET['delete']);
    if ($username !== 'admin') {
        $userFile = USERS_DIR . $username . '.json';
        if (file_exists($userFile)) {
            unlink($userFile);
            $message = 'User deleted successfully';
        }
    }
}

// Get all users
$users = array();
foreach (glob(USERS_DIR . '*.json') as $file) {
    $user = json_decode(file_get_contents($file), true);
    if (is_array($user) && ($user['type'] ?? '') === 'user') {
        if (!isset($user['role'])) $user['role'] = 'contributor';
        $users[] = $user;
    }
}

$editUsername = isset($_GET['edit']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string)$_GET['edit']) : '';
$editingUser = null;
if ($editUsername !== '' && $editUsername !== 'admin') {
    $editFile = USERS_DIR . $editUsername . '.json';
    $editData = file_exists($editFile) ? json_decode(file_get_contents($editFile), true) : null;
    if (is_array($editData)) {
        $editingUser = array(
            'username' => $editUsername,
            'full_name' => trim((string)($editData['full_name'] ?? '')),
            'display_name' => bw_user_display_name($editUsername),
            'title' => trim((string)($editData['title'] ?? '')),
            'bio' => trim((string)($editData['bio'] ?? '')),
            'role' => $editData['role'] ?? 'contributor',
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - <?php echo bw_h(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="<?php echo bw_asset('../css/style.css'); ?>">
</head>
<body class="<?php echo bw_h(bw_style_body_classes('')); ?>">
<?php bw_admin_nav('users'); ?>
<main class="bw-container">
    <?php bw_page_header('Users & roles', 'Create accounts and control who can write, review, and publish stories.'); ?>

    <?php if ($message): ?><div class="message success"><?php echo bw_h($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?php echo bw_h($error); ?></div><?php endif; ?>

    <?php if ($editingUser): ?>
    <section class="bw-card">
        <h2>Edit author profile: <?php echo bw_h($editingUser['display_name']); ?></h2>
        <p class="bw-help" style="margin-bottom:14px;">This name, title, and biography appear with the author's published stories.</p>
        <form method="POST">
            <?php echo bw_csrf_field(); ?>
            <input type="hidden" name="username" value="<?php echo bw_h($editingUser['username']); ?>">
            <div class="bw-grid">
                <div class="form-group">
                    <label for="edit_full_name">Author full name</label>
                    <input type="text" id="edit_full_name" name="full_name" maxlength="100" value="<?php echo bw_h($editingUser['full_name']); ?>" placeholder="e.g. Jane Doe">
                </div>
                <div class="form-group">
                    <label for="edit_title">Title / role</label>
                    <input type="text" id="edit_title" name="title" maxlength="100" value="<?php echo bw_h($editingUser['title']); ?>" placeholder="e.g. Staff Writer">
                </div>
                <div class="form-group">
                    <label for="edit_role">Role</label>
                    <select id="edit_role" name="role">
                        <option value="contributor" <?php if ($editingUser['role'] === 'contributor') echo 'selected'; ?>>Contributor</option>
                        <option value="subeditor" <?php if ($editingUser['role'] === 'subeditor') echo 'selected'; ?>>Subeditor</option>
                        <option value="editor" <?php if ($editingUser['role'] === 'editor') echo 'selected'; ?>>Editor</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="edit_bio">Short biography</label>
                <textarea id="edit_bio" name="bio" maxlength="1200" style="min-height:120px;"><?php echo bw_h($editingUser['bio']); ?></textarea>
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary">Save profile</button>
            <a href="users.php" class="btn">Cancel</a>
        </form>
    </section>
    <?php endif; ?>

    <section class="bw-card">
        <h2>Create a new user</h2>
        <p class="bw-help" style="margin-bottom:14px;"><strong>Contributor</strong> writes and submits drafts. <strong>Subeditor</strong> reviews submissions. <strong>Editor</strong> approves, publishes, archives, and can rebuild the site.</p>
        <form method="POST" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;">
            <?php echo bw_csrf_field(); ?>
            <div class="form-group" style="margin:0;">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="off">
            </div>
            <div class="form-group" style="margin:0;">
                <label for="full_name">Author full name</label>
                <input type="text" id="full_name" name="full_name" maxlength="100" placeholder="e.g. Jane Doe" autocomplete="off">
            </div>
            <div class="form-group" style="margin:0;">
                <label for="title">Title / role</label>
                <input type="text" id="title" name="title" maxlength="100" placeholder="e.g. Staff Writer">
            </div>
            <div class="form-group" style="margin:0;">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            <div class="form-group" style="margin:0;">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="contributor">Contributor (writer)</option>
                    <option value="subeditor">Subeditor (reviewer)</option>
                    <option value="editor">Editor (publisher)</option>
                </select>
            </div>
            <div><button type="submit" name="create" class="btn btn-primary">Create user</button></div>
        </form>
    </section>

    <section class="bw-card">
        <h2>Existing users</h2>
        <?php if (empty($users)): ?>
            <p class="bw-muted" style="font-style:italic;">No user accounts yet. Create one above.</p>
        <?php else: ?>
            <div class="bw-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Author name</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): $profile = bw_user_profile($user['username']); ?>
                        <tr>
                            <td><strong><?php echo bw_h($user['username']); ?></strong></td>
                            <td>
                                <strong><?php echo bw_h($profile['display_name']); ?></strong>
                                <?php if ($profile['title'] !== ''): ?><br><span class="bw-muted"><?php echo bw_h($profile['title']); ?></span><?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="bw-inline-form">
                                    <?php echo bw_csrf_field(); ?>
                                    <input type="hidden" name="username" value="<?php echo bw_h($user['username']); ?>">
                                    <select name="role" aria-label="Role for <?php echo bw_h($user['username']); ?>">
                                        <option value="contributor" <?php if (($user['role'] ?? '') === 'contributor') echo 'selected'; ?>>Contributor</option>
                                        <option value="subeditor" <?php if (($user['role'] ?? '') === 'subeditor') echo 'selected'; ?>>Subeditor</option>
                                        <option value="editor" <?php if (($user['role'] ?? '') === 'editor') echo 'selected'; ?>>Editor</option>
                                    </select>
                                    <button type="submit" name="update_role" class="btn btn-small">Save role</button>
                                </form>
                            </td>
                            <td>
                                <a href="users.php?edit=<?php echo urlencode($user['username']); ?>" class="btn btn-small">Edit profile</a>
                                <a href="users.php?delete=<?php echo urlencode($user['username']); ?>&csrf_token=<?php echo urlencode(bw_csrf_token()); ?>"
                                   class="btn btn-small btn-danger"
                                   onclick="return confirm('Delete user <?php echo bw_h($user['username']); ?>? Their articles will remain.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>