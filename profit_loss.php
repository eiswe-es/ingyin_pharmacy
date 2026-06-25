<?php
require_once __DIR__ . '/functions.php';
requirePermission('view_profit_loss');

$orders = loadOrders();
$revenue = 0;
foreach ($orders as $order) {
    $revenue += (float)($order['total'] ?? 0);
}
$estimatedCost = $revenue * 0.65;
$estimatedProfit = $revenue - $estimatedCost;
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss - Mini Pharmacy POS</title>
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
                <?= renderNavLinks('profit_loss.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Profit & Loss</h2>
                    <p>Owner-only financial overview</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>

            <main class="container">
                <section class="card wide">
                    <h2>Profit & Loss Summary</h2>
                    <div class="summary-box">
                        <div>
                            <h3>Total Revenue</h3>
                            <p><?= formatCurrency($revenue) ?></p>
                        </div>
                        <div>
                            <h3>Estimated Cost</h3>
                            <p><?= formatCurrency($estimatedCost) ?></p>
                        </div>
                        <div>
                            <h3>Estimated Profit</h3>
                            <p><?= formatCurrency($estimatedProfit) ?></p>
                        </div>
                    </div>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
</body>
</html>
