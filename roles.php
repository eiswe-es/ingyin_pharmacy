<?php
require_once __DIR__ . '/functions.php';
requirePermission('manage_roles');

$roles = getRolesConfig();
$permissions = getPermissionList();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedRoles = [];
    $submittedRoles = $_POST['roles'] ?? [];

    foreach ($submittedRoles as $roleKey => $roleData) {
        $cleanKey = preg_replace('/[^a-z0-9_\-]/i', '', strtolower(trim($roleKey)));
        if ($cleanKey === '') {
            continue;
        }

        $updatedRoles[$cleanKey] = [
            'name' => trim($roleData['name'] ?? $cleanKey),
            'permissions' => array_values(array_filter(array_map('trim', $roleData['permissions'] ?? [])))
        ];
    }

    $newRoleName = trim($_POST['new_role_name'] ?? '');
    if ($newRoleName !== '') {
        $newKey = preg_replace('/[^a-z0-9_\-]/i', '', strtolower(str_replace(' ', '_', $newRoleName)));
        if ($newKey !== '') {
            $updatedRoles[$newKey] = [
                'name' => $newRoleName,
                'permissions' => []
            ];
        }
    }

    saveRolesConfig($updatedRoles);
    logActivity('update_roles', ['roles' => array_keys($updatedRoles)]);
    $roles = $updatedRoles;
    $success = 'Role permissions were updated.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles - Ingyin Pharmacy</title>
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
                <?= renderNavLinks('roles.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Role Management</h2>
                    <p>Customize roles and permissions</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>

            <main class="container">
                <section class="card wide">
                    <h2>Role and Permission Management</h2>
                    <?php if (!empty($success)): ?>
                        <div class="alert success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="role-list">
                            <?php foreach ($roles as $roleKey => $role): ?>
                                <div class="role-card">
                                    <h3><?= htmlspecialchars($role['name'] ?? $roleKey) ?></h3>
                                    <input type="hidden" name="roles[<?= htmlspecialchars($roleKey) ?>][name]" value="<?= htmlspecialchars($role['name'] ?? $roleKey) ?>">
                                    <div class="permission-grid">
                                        <?php foreach ($permissions as $permissionKey => $permissionLabel): ?>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="roles[<?= htmlspecialchars($roleKey) ?>][permissions][]" value="<?= htmlspecialchars($permissionKey) ?>" <?= in_array($permissionKey, $role['permissions'] ?? [], true) ? 'checked' : '' ?>>
                                                <?= htmlspecialchars($permissionLabel) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" class="icon-button primary" title="Save role permissions" aria-label="Save role permissions"><span aria-hidden="true">&#10003;</span></button>
                    </form>
                    <button type="button" class="icon-button primary" id="openCreateRoleModal" title="Add a new role" aria-label="Add a new role"><span aria-hidden="true">+</span></button>

                    <div class="modal-overlay" id="roleModal" style="display: none;">
                        <div class="modal-card">
                            <div class="modal-header"><h3>Create New Role</h3><button type="button" class="modal-close" id="closeRoleModal" aria-label="Close">&times;</button></div>
                            <form method="post" class="checkout-form">
                                <?php foreach ($roles as $roleKey => $role): ?>
                                    <input type="hidden" name="roles[<?= htmlspecialchars($roleKey) ?>][name]" value="<?= htmlspecialchars($role['name'] ?? $roleKey) ?>">
                                    <?php foreach ($role['permissions'] ?? [] as $permissionKey): ?><input type="hidden" name="roles[<?= htmlspecialchars($roleKey) ?>][permissions][]" value="<?= htmlspecialchars($permissionKey) ?>"><?php endforeach; ?>
                                <?php endforeach; ?>
                                <label>Role Name<input type="text" name="new_role_name" placeholder="e.g. Finance Staff" required></label>
                                <div class="actions"><button type="button" class="secondary" id="cancelRoleModal">Cancel</button><button type="submit" class="icon-button primary" title="Create role" aria-label="Create role"><span aria-hidden="true">&#10003;</span></button></div>
                            </form>
                        </div>
                    </div>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
    <script>
        const roleModal = document.getElementById('roleModal');
        document.getElementById('openCreateRoleModal')?.addEventListener('click', () => { roleModal.style.display = 'flex'; });
        document.getElementById('closeRoleModal')?.addEventListener('click', () => { roleModal.style.display = 'none'; });
        document.getElementById('cancelRoleModal')?.addEventListener('click', () => { roleModal.style.display = 'none'; });
        roleModal?.addEventListener('click', (event) => { if (event.target === roleModal) roleModal.style.display = 'none'; });
    </script>
</body>
</html>
