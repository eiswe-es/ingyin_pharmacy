<?php
require_once __DIR__ . '/functions.php';
requirePermission('checkout');

$cartItems = getCartItems();
$cartTotal = getCartTotal();
$discount = 0;
$checkoutTotal = $cartTotal;
$cashReceived = 0;
$change = 0;
$paymentMethod = '';
$currentUser = getCurrentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? '');
    $staffName = trim($_POST['staff_name'] ?? '');
    $generateInvoice = !empty($_POST['generate_invoice']);
    $discount = max(0, (float)($_POST['discount'] ?? 0));
    $checkoutTotal = max(0, round($cartTotal - $discount, 2));
    $cashReceived = max(0, (float)($_POST['cash_received'] ?? 0));
    $change = $paymentMethod === 'Cash' ? round($cashReceived - $checkoutTotal, 2) : 0;

    if ($customerName === '' || $phone === '' || $paymentMethod === '' || $staffName === '') {
        $error = 'Please fill in customer name, phone, staff name and payment method.';
    } elseif ($discount > $cartTotal) {
        $error = 'Discount cannot be greater than the cart subtotal.';
    } elseif ($paymentMethod === 'Cash' && $cashReceived < $checkoutTotal) {
        $error = 'Cash received is less than the amount due.';
    } elseif (!$cartItems) {
        $error = 'Cart is empty.';
    } else {
        $products = getProducts();
        $stockError = '';

        foreach ($cartItems as $item) {
            $product = $products[$item['id']] ?? null;
            if (!$product) {
                $stockError = 'One or more products are no longer available.';
                break;
            }

            $availableStock = max(0, (int)($product['stock'] ?? 0));
            if ($availableStock < (int)$item['quantity']) {
                $stockError = htmlspecialchars($product['name']) . ' has only ' . $availableStock . ' ' . strtolower(getProductUnitLabel($product)) . ' left.';
                break;
            }
        }

        if ($stockError !== '') {
            $error = $stockError;
        } else {
            foreach ($cartItems as $item) {
                $productId = $item['id'];
                $products[$productId]['stock'] = max(0, (int)($products[$productId]['stock'] ?? 0) - (int)$item['quantity']);
            }
            saveProducts($products);

            $orders = loadOrders();
            $orderId = time();
            $invoiceNumber = $generateInvoice ? 'INV-' . date('Ymd') . '-' . str_pad((string)(count($orders) + 1), 4, '0', STR_PAD_LEFT) : null;
            $orders[] = [
                'id' => $orderId,
                'customer_name' => $customerName,
                'phone' => $phone,
                'payment_method' => $paymentMethod,
                'staff_name' => $staffName,
                'sold_by' => $currentUser['full_name'] ?? $currentUser['username'] ?? 'System',
                'items' => $cartItems,
                'subtotal' => $cartTotal,
                'discount' => $discount,
                'total' => $checkoutTotal,
                'cash_received' => $cashReceived,
                'change_amount' => $change,
                'invoice_generated' => $generateInvoice,
                'invoice_number' => $invoiceNumber,
                'created_at' => date('Y-m-d H:i:s')
            ];
            saveOrders($orders);
            logActivity('checkout', ['customer_name' => $customerName, 'payment_method' => $paymentMethod, 'total' => $cartTotal]);
            clearCart();
            if ($generateInvoice) {
                header('Location: invoice.php?id=' . $orderId);
            } else {
                header('Location: orders.php?success=1');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Ingyin Pharmacy</title>
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
                <?= renderNavLinks('checkout.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Checkout</h2>
                    <p>Complete the sale</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>

            <main class="container">
                <section class="card wide">
                    <h2>Checkout</h2>
                    <?php if (!empty($error)): ?>
                        <div class="alert error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post" class="checkout-form">
                        <label>
                            Customer Name
                            <input type="text" name="customer_name" required>
                        </label>
                        <label>
                            Phone Number
                            <input type="text" name="phone" required>
                        </label>
                        <label>
                            Staff Name
                            <input type="text" name="staff_name" value="<?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username'] ?? '') ?>" required>
                        </label>
                        <label>
                            Payment Method
                            <select name="payment_method" id="paymentMethodInput" required>
                                <option value="">Select</option>
                                <option value="Cash" <?= $paymentMethod === 'Cash' ? 'selected' : '' ?>>Cash</option>
                                <option value="Card" <?= $paymentMethod === 'Card' ? 'selected' : '' ?>>Card</option>
                                <option value="Mobile Wallet" <?= $paymentMethod === 'Mobile Wallet' ? 'selected' : '' ?>>Mobile Wallet</option>
                            </select>
                        </label>
                        <label>
                            Discount
                            <input type="number" name="discount" min="0" step="0.01" value="<?= htmlspecialchars((string)$discount) ?>">
                        </label>
                        <label>
                            Cash Received
                            <input type="number" name="cash_received" min="0" step="0.01" value="<?= htmlspecialchars((string)$cashReceived) ?>">
                        </label>
                        <label>
                            <input type="checkbox" name="generate_invoice" value="1">
                            Generate printable invoice
                        </label>

                        <div class="summary">
                            <h3>Subtotal: <?= formatCurrency($cartTotal) ?><br>Amount Due: <?= formatCurrency($checkoutTotal) ?><br>Change: <?= formatCurrency(max(0, $change)) ?></h3>
                            <button type="submit">Save Sale</button>
                        </div>
                    </form>

                    <h3>Items to be sold</h3>
                    <ul>
                        <?php foreach ($cartItems as $item): ?>
                            <li><?= htmlspecialchars($item['quantity']) ?> × <?= htmlspecialchars($item['name']) ?> (<?= htmlspecialchars(strtoupper(getProductUnitLabel(['unit_type' => $item['unit_type'] ?? 'each']))) ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
</body>
</html>
