<?php
require_once __DIR__ . '/functions.php';
requirePermission('view_orders');

$orderId = trim($_GET['id'] ?? '');
$order = null;
if ($orderId !== '') {
    foreach (loadOrders() as $candidate) {
        if ((string)$candidate['id'] === $orderId) {
            $order = $candidate;
            break;
        }
    }
}

if (!$order) {
    header('Location: orders.php');
    exit;
}

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= htmlspecialchars((string)$order['id']) ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { background: #f5f7fb; }
        .invoice-card { max-width: 760px; margin: 30px auto; background: #fff; padding: 24px; border-radius: 16px; box-shadow: 0 12px 24px rgba(0,0,0,.08); }
        .invoice-head { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .invoice-table th, .invoice-table td { padding: 10px; border-bottom: 1px solid #e9eef4; text-align: left; }
        .invoice-total { text-align: right; margin-top: 16px; font-size: 18px; font-weight: 700; }
        @media print { .no-print { display: none; } body { background: #fff; } .invoice-card { box-shadow: none; margin: 0; } }
    </style>
</head>
<body>
    <div class="invoice-card">
        <div class="no-print" style="text-align:right; margin-bottom:12px;">
            <button type="button" onclick="window.print()" class="btn">Print Invoice</button>
        </div>
        <div class="invoice-head">
            <div>
                <h1>Mini Pharmacy POS</h1>
                <p>Pharmacy sales invoice</p>
            </div>
            <div>
                <strong>Invoice #<?= htmlspecialchars((string)$order['id']) ?></strong>
                <p><?= htmlspecialchars($order['created_at']) ?></p>
            </div>
        </div>
        <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
        <p><strong>Staff:</strong> <?= htmlspecialchars($order['staff_name'] ?? $order['sold_by'] ?? 'Unknown') ?></p>
        <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td><?= htmlspecialchars(strtoupper(getProductUnitLabel(['unit_type' => $item['unit_type'] ?? 'each']))) ?></td>
                        <td><?= formatCurrency((float)$item['price']) ?></td>
                        <td><?= formatCurrency((float)$item['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="invoice-total">Total: <?= formatCurrency((float)$order['total']) ?></div>
        <p class="no-print"><a href="orders.php">Back to sales history</a></p>
    </div>
</body>
</html>
