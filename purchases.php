<?php
require_once __DIR__ . '/functions.php';
requirePermission('manage_stock');

$products = getProducts();
$purchases = array_reverse(loadPurchases());
$currentUser = getCurrentUser();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = trim($_POST['product_id'] ?? '');
    $supplier = trim($_POST['supplier'] ?? '');
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
    $sellingPrice = (float)($_POST['selling_price'] ?? 0);
    $expiryDate = trim($_POST['expiry_date'] ?? '');
    $unitType = trim($_POST['unit_type'] ?? 'each');
    $notes = trim($_POST['notes'] ?? '');

    if ($productId === '' || !isset($products[$productId])) {
        $error = 'Please select a valid product.';
    } else {
        $products[$productId]['supplier'] = $supplier;
        $products[$productId]['purchase_price'] = $purchasePrice;
        $products[$productId]['selling_price'] = $sellingPrice > 0 ? $sellingPrice : ($products[$productId]['selling_price'] ?? $products[$productId]['price'] ?? 0);
        $products[$productId]['price'] = $products[$productId]['selling_price'];
        $products[$productId]['unit_type'] = $unitType;
        $products[$productId]['expiry_date'] = $expiryDate;
        $products[$productId]['stock'] = (int)($products[$productId]['stock'] ?? 0) + $quantity;
        saveProducts($products);

        $purchases[] = [
            'id' => time(),
            'product_id' => $productId,
            'product_name' => $products[$productId]['name'],
            'supplier' => $supplier,
            'quantity' => $quantity,
            'unit_type' => $unitType,
            'purchase_price' => $purchasePrice,
            'selling_price' => $products[$productId]['selling_price'],
            'expiry_date' => $expiryDate,
            'notes' => $notes,
            'purchased_at' => date('Y-m-d H:i:s')
        ];
        savePurchases($purchases);
        logActivity('record_purchase', ['product_id' => $productId, 'quantity' => $quantity]);
        $message = 'Purchase recorded and stock updated.';
        $products = getProducts();
        $purchases = array_reverse(loadPurchases());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchases - Ingyin Pharmacy</title>
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
                <?= renderNavLinks('purchases.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Purchase Ledger</h2>
                    <p>Track supplier purchases, stock updates and expiry dates</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>

            <main class="container">
                <section class="card wide">
                    <h2>Record Purchase</h2>
                    <?php if ($message !== ''): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                    <?php if ($error !== ''): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                    <form method="post" class="checkout-form">
                        <label>
                            Product
                            <select name="product_id" required>
                                <option value="">Select product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= htmlspecialchars($product['id']) ?>"><?= htmlspecialchars($product['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            Supplier
                            <input type="text" name="supplier" required>
                        </label>
                        <label>
                            Quantity
                            <input type="number" name="quantity" min="1" value="1" required>
                        </label>
                        <label>
                            Unit Type
                            <select name="unit_type" required>
                                <option value="each">Each</option>
                                <option value="box">Box</option>
                                <option value="bottle">Bottle</option>
                            </select>
                        </label>
                        <label>
                            Purchase Price
                            <input type="number" step="0.01" name="purchase_price" required>
                        </label>
                        <label>
                            Selling Price
                            <input type="number" step="0.01" name="selling_price" required>
                        </label>
                        <label>
                            Expiry Date
                            <input type="date" name="expiry_date">
                        </label>
                        <label>
                            Notes
                            <textarea name="notes"></textarea>
                        </label>
                        <button type="submit">Save Purchase</button>
                    </form>
                </section>

                <section class="card wide">
                    <h2>Purchase History</h2>
                    <?php if ($purchases): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Supplier</th>
                                    <th>Qty</th>
                                    <th>Unit</th>
                                    <th>Buy Price</th>
                                    <th>Sell Price</th>
                                    <th>Expiry</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($purchase['purchased_at']) ?></td>
                                        <td><?= htmlspecialchars($purchase['product_name']) ?></td>
                                        <td><?= htmlspecialchars($purchase['supplier']) ?></td>
                                        <td><?= (int)$purchase['quantity'] ?></td>
                                        <td><?= htmlspecialchars(strtoupper($purchase['unit_type'] ?? 'each')) ?></td>
                                        <td><?= formatCurrency((float)$purchase['purchase_price']) ?></td>
                                        <td><?= formatCurrency((float)$purchase['selling_price']) ?></td>
                                        <td><?= htmlspecialchars($purchase['expiry_date'] ?: '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No purchases recorded yet.</p>
                    <?php endif; ?>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
</body>
</html>
