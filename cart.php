<?php
require_once __DIR__ . '/functions.php';
requirePermission('manage_cart');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] ?? [] as $productId => $quantity) {
            updateCartItem($productId, (int)$quantity);
        }
        header('Location: cart.php?updated=1');
        exit;
    }

    if (isset($_POST['remove_item'])) {
        removeFromCart($_POST['product_id']);
        header('Location: cart.php?removed=1');
        exit;
    }

    if (isset($_POST['clear_cart'])) {
        clearCart();
        header('Location: cart.php?cleared=1');
        exit;
    }
}

$cartItems = getCartItems();
$cartTotal = getCartTotal();
$cartCount = count(getCart());
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Ingyin Pharmacy</title>
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
                <?= renderNavLinks('cart.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Sales Cart</h2>
                    <p>Review the current order before checkout</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>

            <main class="container">
                <section class="card wide">
                    <h2>Current Cart</h2>
                    <?php if (isset($_GET['updated'])): ?><div class="alert success">Cart updated.</div><?php endif; ?>
                    <?php if (isset($_GET['removed'])): ?><div class="alert success">Item removed.</div><?php endif; ?>
                    <?php if (isset($_GET['cleared'])): ?><div class="alert success">Cart cleared.</div><?php endif; ?>

                    <?php if ($cartItems): ?>
                        <form method="post">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Line Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['name']) ?> <small>(<?= htmlspecialchars(strtoupper(getProductUnitLabel(['unit_type' => $item['unit_type'] ?? 'each']))) ?>)</small></td>
                                            <td><?= formatCurrency($item['price']) ?></td>
                                            <td>
                                                <input type="number" name="quantities[<?= htmlspecialchars($item['id']) ?>]" value="<?= (int)$item['quantity'] ?>" min="1">
                                            </td>
                                            <td><?= formatCurrency($item['line_total']) ?></td>
                                            <td>
                                                <div class="inline-actions">
                                                    <button type="submit" name="remove_item" value="1">Remove</button>
                                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['id']) ?>">
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <div class="actions">
                                <button type="submit" name="update_cart" class="secondary">Update Cart</button>
                                <button type="submit" name="clear_cart" class="danger">Clear Cart</button>
                            </div>
                        </form>

                        <div class="summary">
                            <h3>Total: <?= formatCurrency($cartTotal) ?></h3>
                            <a class="btn" href="checkout.php">Proceed to Checkout</a>
                        </div>
                    <?php else: ?>
                        <p>Your cart is empty. Add some pharmacy products first.</p>
                        <a class="btn" href="index.php">Browse products</a>
                    <?php endif; ?>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
</body>
</html>
