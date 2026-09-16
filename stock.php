<?php
require_once __DIR__ . '/functions.php';
requirePermission('manage_stock');

$products = getProducts();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedProducts = $products;
    foreach ($_POST['stock'] ?? [] as $productId => $stock) {
        if (isset($updatedProducts[$productId])) {
            $updatedProducts[$productId]['stock'] = max(0, (int)$stock);
        }
    }
    saveProducts($updatedProducts);
    logActivity('update_stock', ['products' => array_keys($_POST['stock'] ?? [])]);
    $products = $updatedProducts;
    $success = 'Stock updated successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock - Ingyin Pharmacy</title>
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
                <?= renderNavLinks('stock.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Stock Entry</h2>
                    <p>Update stock for pharmacy products</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>

            <main class="container">
                <section class="card wide">
                    <h2>Stock Entry</h2>
                    <?php if (!empty($success)): ?>
                        <div class="alert success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Unit</th>
                                    <th>Expiry</th>
                                    <th>Supplier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td><?= htmlspecialchars($product['category']) ?></td>
                                        <td>
                                            <input type="number" name="stock[<?= htmlspecialchars($product['id']) ?>]" value="<?= (int)$product['stock'] ?>" min="0">
                                        </td>
                                        <td><?= htmlspecialchars(strtoupper(getProductUnitLabel($product))) ?></td>
                                        <td><?= htmlspecialchars($product['expiry_date'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($product['supplier'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="icon-button primary" title="Save stock changes" aria-label="Save stock changes"><span aria-hidden="true">&#10003;</span></button>
                    </form>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
</body>
</html>
