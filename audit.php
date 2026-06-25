<?php
require_once __DIR__ . '/functions.php';
requirePermission('manage_roles');

$logs = array_reverse(loadAuditLogs());
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - Mini Pharmacy POS</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div>
                    <h1>Mini Pharmacy POS</h1>
                    <p>Admin Dashboard</p>
                </div>
                <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle menu">☰</button>
            </div>
            <nav class="sidebar-nav">
                <?= renderNavLinks('audit.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Action History</h2>
                    <p>Track staff actions and changes</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>

            <main class="container">
                <section class="card wide">
                    <h2>Action History</h2>
                    <?php if ($logs): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($log['full_name'] ?? $log['username'] ?? 'Unknown') ?></td>
                                        <td><?= htmlspecialchars($log['role'] ?? 'guest') ?></td>
                                        <td><?= htmlspecialchars($log['action'] ?? '') ?></td>
                                        <td>
                                            <?php if (!empty($log['details'])): ?>
                                                <?= htmlspecialchars(json_encode($log['details'])) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No actions have been recorded yet.</p>
                    <?php endif; ?>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
</body>
</html>
