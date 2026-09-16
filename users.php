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
    } elseif (isset($_POST['update_user'])) {
        $userId = (string)($_POST['user_id'] ?? '');
        foreach ($users as $index => $user) {
            if ((string)$user['id'] !== $userId) {
                continue;
            }
            $users[$index]['full_name'] = trim($_POST['full_name'] ?? $user['full_name']);
            $users[$index]['role'] = trim($_POST['role'] ?? $user['role']);
            $newPassword = trim($_POST['password'] ?? '');
            if ($newPassword !== '') {
                $users[$index]['password'] = $newPassword;
            }
            saveUsersConfig($users);
            logActivity('update_user', ['username' => $user['username']]);
            $success = 'Staff account updated.';
            break;
        }
        $users = getUsersConfig();
    } elseif (isset($_POST['delete_user'])) {
        $userId = (string)($_POST['user_id'] ?? '');
        $currentUserId = (string)($currentUser['id'] ?? '');
        $remainingUsers = array_values(array_filter($users, static fn (array $user): bool => (string)$user['id'] !== $userId));
        if ($userId === '' || count($remainingUsers) === count($users) || $userId === $currentUserId) {
            $error = 'You cannot delete the current account or an invalid user.';
        } else {
            saveUsersConfig($remainingUsers);
            logActivity('delete_user', ['user_id' => $userId]);
            $success = 'Staff account deleted.';
            $users = getUsersConfig();
        }
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
                    <div class="card-header">
                        <div>
                            <h2>Staff Accounts</h2>
                            <p class="muted">Manage staff access and account details.</p>
                        </div>
                        <button type="button" class="icon-button primary" id="openCreateUserModal" title="Add a new staff user" aria-label="Add a new staff user"><span aria-hidden="true">+</span></button>
                    </div>
                    <?php if ($success !== ''): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                    <?php if ($error !== ''): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                    <div class="table-wrap">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($user['full_name'] ?? '') ?></strong><small class="table-subtext">ID <?= (int)$user['id'] ?></small></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td>
                                            <span class="tag"><?= htmlspecialchars($roles[$user['role']]['name'] ?? $user['role']) ?></span>
                                        </td>
                                        <td>
                                            <div class="inline-actions">
                                                <button type="button" class="icon-button secondary edit-user-btn" title="Edit staff account" aria-label="Edit <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?>" data-id="<?= (int)$user['id'] ?>" data-name="<?= htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES) ?>" data-username="<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>" data-role="<?= htmlspecialchars($user['role'] ?? '', ENT_QUOTES) ?>"><span aria-hidden="true">&#9998;</span></button>
                                                <?php if ((string)($user['id'] ?? '') !== (string)($currentUser['id'] ?? '')): ?>
                                                    <form method="post" onsubmit="return confirm('Delete this staff account?');"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><button type="submit" name="delete_user" class="icon-button danger" title="Delete staff account" aria-label="Delete <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?>"><span aria-hidden="true">&#128465;</span></button></form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-overlay" id="userModal" style="display: none;">
                        <div class="modal-card">
                            <div class="modal-header"><h3 id="userModalTitle">Create New Staff User</h3><button type="button" class="modal-close" id="closeUserModal" aria-label="Close">&times;</button></div>
                            <form method="post" class="checkout-form">
                                <input type="hidden" name="user_id" id="userIdInput">
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
                                <div class="actions"><button type="button" class="secondary" id="cancelUserModal">Cancel</button><button type="submit" name="create_user" id="userSubmitBtn">Create User</button></div>
                            </form>
                        </div>
                    </div>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
    <script>
        const userModal = document.getElementById('userModal');
        const userModalTitle = document.getElementById('userModalTitle');
        const userSubmitBtn = document.getElementById('userSubmitBtn');
        const userIdInput = document.getElementById('userIdInput');
        const userNameInput = document.querySelector('#userModal [name="full_name"]');
        const userUsernameInput = document.querySelector('#userModal [name="username"]');
        const userPasswordInput = document.querySelector('#userModal [name="password"]');
        const userRoleInput = document.querySelector('#userModal [name="role"]');

        function openUserModal(mode, button) {
            const editing = mode === 'edit';
            userModalTitle.textContent = editing ? 'Edit Staff Account' : 'Create New Staff User';
            userSubmitBtn.textContent = editing ? 'Save Changes' : 'Create User';
            userSubmitBtn.name = editing ? 'update_user' : 'create_user';
            userIdInput.value = editing ? button.dataset.id : '';
            userNameInput.value = editing ? button.dataset.name : '';
            userUsernameInput.value = editing ? button.dataset.username : '';
            userUsernameInput.readOnly = editing;
            userPasswordInput.value = '';
            userPasswordInput.required = !editing;
            userRoleInput.value = editing ? button.dataset.role : userRoleInput.options[0]?.value || '';
            userModal.style.display = 'flex';
        }

        document.getElementById('openCreateUserModal')?.addEventListener('click', () => openUserModal('create'));
        document.querySelectorAll('.edit-user-btn').forEach((button) => button.addEventListener('click', () => openUserModal('edit', button)));
        document.getElementById('closeUserModal')?.addEventListener('click', () => { userModal.style.display = 'none'; });
        document.getElementById('cancelUserModal')?.addEventListener('click', () => { userModal.style.display = 'none'; });
        userModal?.addEventListener('click', (event) => { if (event.target === userModal) userModal.style.display = 'none'; });
    </script>
</body>
</html>
