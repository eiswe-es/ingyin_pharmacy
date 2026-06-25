<?php
require_once __DIR__ . '/functions.php';
requirePermission('view_orders');

$orders = array_reverse(loadOrders());
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Mini Pharmacy POS</title>
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
                <?= renderNavLinks('orders.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Sales History</h2>
                    <p>Recent pharmacy sales</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>

            <main class="container">
                <section class="card wide">
                    <h2>Sales History</h2>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert success">Sale saved successfully.</div>
                    <?php endif; ?>

                    <?php if ($orders): ?>
                        <?php foreach ($orders as $order): ?>
                            <article class="order-card">
                                <div class="order-head">
                                    <strong>#<?= (int)$order['id'] ?></strong>
                                    <span><?= htmlspecialchars($order['created_at']) ?></span>
                                </div>
                                <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                                <p><strong>Staff:</strong> <?= htmlspecialchars($order['staff_name'] ?? $order['sold_by'] ?? 'Unknown') ?></p>
                                <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                                <p><strong>Invoice:</strong> <?= !empty($order['invoice_generated']) ? (htmlspecialchars($order['invoice_number'] ?? 'Generated')) : 'Not generated' ?></p>
                                <ul>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <li><?= (int)$item['quantity'] ?> × <?= htmlspecialchars($item['name']) ?> <?= htmlspecialchars(strtoupper(getProductUnitLabel(['unit_type' => $item['unit_type'] ?? 'each']))) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p class="total"><strong>Total:</strong> <?= formatCurrency((float)$order['total']) ?></p>
                                <?php if (!empty($order['invoice_generated'])): ?>
                                    <p><a class="btn" href="invoice.php?id=<?= (int)$order['id'] ?>">Print Invoice</a></p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No sales recorded yet.</p>
                    <?php endif; ?>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
</body>
</html>
