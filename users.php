<?php
require_once __DIR__ . '/functions.php';
requirePermission('manage_roles');

$users = getUsersConfig();
$roles = getRolesConfig();
$success = '';
$error = '';
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $fullName = trim($_POST['full_name'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'stock');

        if ($username === '' || $password === '' || $fullName === '') {
            $error = 'Please fill in username, full name, and password.';
        } elseif (in_array($username, array_map('strtolower', array_column($users, 'username')), true)) {
            $error = 'That username is already taken.';
        } else {
            $users[] = [
                'id' => time(),
                'username' => $username,
                'password' => $password,
                'role' => $role,
                'full_name' => $fullName
            ];
            saveUsersConfig($users);
            logActivity('create_user', ['username' => $username, 'role' => $role]);
            $success = 'User created successfully.';
            $users = getUsersConfig();
        }
    } elseif (isset($_POST['update_users'])) {
        $updatedUsers = [];
        foreach ($users as $user) {
            $userId = (string)$user['id'];
            $submitted = $_POST['users'][$userId] ?? [];
            $updatedUser = $user;
            $updatedUser['full_name'] = trim($submitted['full_name'] ?? $user['full_name']);
            $updatedUser['role'] = trim($submitted['role'] ?? $user['role']);
            $newPassword = trim($submitted['password'] ?? '');
            if ($newPassword !== '') {
                $updatedUser['password'] = $newPassword;
            }
            $updatedUsers[] = $updatedUser;
        }

        saveUsersConfig($updatedUsers);
        logActivity('update_users', ['count' => count($updatedUsers)]);
        $success = 'Staff access updated.';
        $users = getUsersConfig();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Ingyin Pharmacy</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div>
                    <h1>Ingyin Pharmacy</h1>
                    <p>Admin Dashboard</p>
                </div>
                <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle menu">☰</button>
            </div>
            <nav class="sidebar-nav">
                <?= renderNavLinks('users.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Staff Accounts</h2>
                    <p>Manage staff users and roles</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>

            <main class="container">
                <section class="card wide">
                    <h2>Staff Accounts</h2>
                    <?php if ($success !== ''): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                    <?php if ($error !== ''): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                    <form method="post">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>New Password</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <input type="text" name="users[<?= (int)$user['id'] ?>][full_name]" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                                        </td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td>
                                            <select name="users[<?= (int)$user['id'] ?>][role]">
                                                <?php foreach ($roles as $roleKey => $role): ?>
                                                    <option value="<?= htmlspecialchars($roleKey) ?>" <?= ($user['role'] ?? '') === $roleKey ? 'selected' : '' ?>><?= htmlspecialchars($role['name'] ?? $roleKey) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="password" name="users[<?= (int)$user['id'] ?>][password]" placeholder="Leave blank to keep">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" name="update_users">Save Staff Access</button>
                    </form>

                    <hr>

                    <h3>Create New Staff User</h3>
                    <form method="post" class="checkout-form">
                        <label>
                            Full Name
                            <input type="text" name="full_name" required>
                        </label>
                        <label>
                            Username
                            <input type="text" name="username" required>
                        </label>
                        <label>
                            Password
                            <input type="password" name="password" required>
                        </label>
                        <label>
                            Role
                            <select name="role">
                                <?php foreach ($roles as $roleKey => $role): ?>
                                    <option value="<?= htmlspecialchars($roleKey) ?>"><?= htmlspecialchars($role['name'] ?? $roleKey) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit" name="create_user">Create User</button>
                    </form>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
</body>
</html>
