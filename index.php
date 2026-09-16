<?php
require_once __DIR__ . '/functions.php';
requirePermission('view_products');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        requirePermission('manage_cart');
        if (addToCart($_POST['product_id'], (int)($_POST['quantity'] ?? 1))) {
            header('Location: index.php?added=1');
            exit;
        }
        $error = 'Unable to add this product. Check stock, expiry date and cart quantity.';
    }

    if (isset($_POST['create_product'])) {
        requirePermission('manage_stock');
        $name = trim($_POST['name'] ?? '');
        $sellingPrice = (float)($_POST['selling_price'] ?? $_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $supplier = trim($_POST['supplier'] ?? '');
        $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
        $expiryDate = trim($_POST['expiry_date'] ?? '');
        $unitType = trim($_POST['unit_type'] ?? 'each');
        $reorderLevel = max(0, (int)($_POST['reorder_level'] ?? 10));
        $barcode = trim($_POST['barcode'] ?? '');

        $products = getProducts();
        $productId = trim($_POST['product_id'] ?? '');
        if ($productId === '') {
            $productId = generateProductId($category, $products);
        }

        if ($name === '' || $category === '') {
            $error = 'Please fill in product name and category.';
        } elseif (isset($products[$productId])) {
            $error = 'This product ID already exists.';
        } else {
            $products[$productId] = [
                'id' => $productId,
                'name' => $name,
                'price' => $sellingPrice,
                'selling_price' => $sellingPrice,
                'stock' => $stock,
                'category' => $category,
                'supplier' => $supplier,
                'purchase_price' => $purchasePrice,
                'expiry_date' => $expiryDate,
                'unit_type' => $unitType,
                'reorder_level' => $reorderLevel,
                'barcode' => $barcode,
            ];
            saveProducts($products);
            logActivity('create_product', ['product_id' => $productId, 'name' => $name]);
            header('Location: index.php?created=1');
            exit;
        }
    }

    if (isset($_POST['update_product'])) {
        requirePermission('manage_stock');
        $currentProductId = trim($_POST['current_product_id'] ?? '');
        $products = getProducts();
        if ($currentProductId === '' || !isset($products[$currentProductId])) {
            $error = 'Unable to update the selected product.';
        } else {
            $products[$currentProductId]['name'] = trim($_POST['name'] ?? $products[$currentProductId]['name']);
            $products[$currentProductId]['price'] = (float)($_POST['selling_price'] ?? $_POST['price'] ?? $products[$currentProductId]['price']);
            $products[$currentProductId]['selling_price'] = (float)($_POST['selling_price'] ?? $_POST['price'] ?? $products[$currentProductId]['selling_price'] ?? $products[$currentProductId]['price']);
            $products[$currentProductId]['stock'] = (int)($_POST['stock'] ?? $products[$currentProductId]['stock']);
            $products[$currentProductId]['category'] = trim($_POST['category'] ?? $products[$currentProductId]['category']);
            $products[$currentProductId]['supplier'] = trim($_POST['supplier'] ?? $products[$currentProductId]['supplier'] ?? '');
            $products[$currentProductId]['purchase_price'] = (float)($_POST['purchase_price'] ?? $products[$currentProductId]['purchase_price'] ?? 0);
            $products[$currentProductId]['expiry_date'] = trim($_POST['expiry_date'] ?? $products[$currentProductId]['expiry_date'] ?? '');
            $products[$currentProductId]['unit_type'] = trim($_POST['unit_type'] ?? $products[$currentProductId]['unit_type'] ?? 'each');
            $products[$currentProductId]['reorder_level'] = max(0, (int)($_POST['reorder_level'] ?? getProductReorderLevel($products[$currentProductId])));
            $products[$currentProductId]['barcode'] = trim($_POST['barcode'] ?? $products[$currentProductId]['barcode'] ?? '');
            saveProducts($products);
            logActivity('update_product', ['product_id' => $currentProductId]);
            header('Location: index.php?updated=1');
            exit;
        }
    }

    if (isset($_POST['delete_product'])) {
        requirePermission('manage_stock');
        $productId = trim($_POST['product_id'] ?? '');
        if ($productId !== '' && softDeleteProduct($productId)) {
            logActivity('soft_delete_product', ['product_id' => $productId]);
            header('Location: index.php?deleted=1');
            exit;
        }
        $error = 'Unable to delete the selected product.';
    }
}

cleanupDeletedProducts();
$products = getProducts();
$categories = getCategories();
$categoryProductCounts = [];
foreach ($products as $product) {
    $categorySlug = getCategorySlug($product['category'] ?? '');
    if ($categorySlug === '') {
        continue;
    }
    $categoryProductCounts[$categorySlug] = ($categoryProductCounts[$categorySlug] ?? 0) + 1;
}
$cartCount = count(getCart());
$currentUser = getCurrentUser();
$search = trim($_GET['search'] ?? '');
$displayProducts = array_filter($products, static function (array $product) use ($search): bool {
    if ($search === '') {
        return true;
    }

    $haystack = strtolower(implode(' ', [
        $product['id'] ?? '',
        $product['name'] ?? '',
        $product['category'] ?? '',
        $product['barcode'] ?? '',
    ]));
    return str_contains($haystack, strtolower($search));
});
$inventoryAlerts = getInventoryAlerts($products);
$today = date('Y-m-d');
$todayOrders = 0;
$todaySales = 0;
foreach (loadOrders() as $order) {
    if (substr((string)($order['created_at'] ?? ''), 0, 10) === $today) {
        $todayOrders++;
        $todaySales += (float)($order['total'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingyin Pharmacy</title>
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
                <?= renderNavLinks('index.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Pharmacy Control Center</h2>
                    <p>Manage sales, stock and staff from one place</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>
            <section class="hero">
                <h2>Welcome to the pharmacy counter</h2>
                <p>Hello <?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?>, you are logged in as <?= htmlspecialchars($currentUser['role']) ?>.</p>
                <?php if (isset($_GET['added'])): ?>
                    <div class="alert success">Item added to cart.</div>
                <?php endif; ?>
                <?php if (isset($_GET['created'])): ?>
                    <div class="alert success">Product added successfully.</div>
                <?php endif; ?>
                <?php if (isset($_GET['updated'])): ?>
                    <div class="alert success">Product updated successfully.</div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert success">Product moved to temporary delete and will be removed after 30 days.</div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="alert error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
            </section>

            <section class="dashboard-stats" aria-label="Daily pharmacy summary">
                <div class="stat-card">
                    <span>Products</span>
                    <strong><?= count($products) ?></strong>
                </div>
                <div class="stat-card">
                    <span>Low Stock</span>
                    <strong><?= count($inventoryAlerts['low_stock']) ?></strong>
                </div>
                <div class="stat-card">
                    <span>Today's Sales</span>
                    <strong><?= formatCurrency($todaySales) ?></strong>
                </div>
                <div class="stat-card">
                    <span>Today's Orders</span>
                    <strong><?= $todayOrders ?></strong>
                </div>
            </section>

            <?php if ($inventoryAlerts['low_stock'] || $inventoryAlerts['expired'] || $inventoryAlerts['expiring_soon']): ?>
                <section class="inventory-alerts" aria-label="Inventory alerts">
                    <h2>Inventory Alerts</h2>
                    <?php if ($inventoryAlerts['expired']): ?>
                        <div class="alert error">
                            <strong>Expired products:</strong>
                            <?= htmlspecialchars(implode(', ', array_column($inventoryAlerts['expired'], 'name'))) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($inventoryAlerts['low_stock']): ?>
                        <div class="alert warning">
                            <strong>Low stock:</strong>
                            <?= htmlspecialchars(implode(', ', array_column($inventoryAlerts['low_stock'], 'name'))) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($inventoryAlerts['expiring_soon']): ?>
                        <div class="alert warning">
                            <strong>Expiring within 30 days:</strong>
                            <?= htmlspecialchars(implode(', ', array_column($inventoryAlerts['expiring_soon'], 'name'))) ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="card wide">
                <div class="card-header product-list-header">
                    <h2>Products</h2>
                    <div class="inline-actions">
                        <form method="get" class="product-search">
                            <input type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, ID or barcode" aria-label="Search products">
                            <button type="submit" class="secondary">Search</button>
                        </form>
                        <?php if ($search !== ''): ?><a class="btn secondary" href="index.php">Clear</a><?php endif; ?>
                        <?php if (userHasPermission('manage_stock')): ?>
                            <button type="button" class="icon-button primary" id="openCreateModalBtn" title="Add a new product" aria-label="Add a new product"><span aria-hidden="true">+</span></button>
                        <?php endif; ?>
                    </div>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayProducts as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['category']) ?></td>
                                <td><?= formatCurrency($product['price']) ?></td>
                                <td><?= htmlspecialchars(formatStockValue($product)) ?></td>
                                <td>
                                    <div class="inline-actions product-actions">
                                        <?php if (userHasPermission('manage_cart')): ?>
                                            <form method="post" class="inline-actions">
                                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                                <input type="number" name="quantity" value="1" min="1" max="<?= max(1, (int)($product['stock'] ?? 0)) ?>" aria-label="Quantity for <?= htmlspecialchars($product['name']) ?>">
                                                <button type="submit" name="add_to_cart" class="icon-button primary" title="Add product to cart" aria-label="Add <?= htmlspecialchars($product['name']) ?> to cart"><span aria-hidden="true">+</span></button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (userHasPermission('manage_stock')): ?>
                                                <button type="button" class="icon-button secondary edit-product-btn" title="Edit product" aria-label="Edit <?= htmlspecialchars($product['name']) ?>"
                                                data-id="<?= htmlspecialchars($product['id'], ENT_QUOTES) ?>"
                                                data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>"
                                                data-category="<?= htmlspecialchars($product['category'], ENT_QUOTES) ?>"
                                                data-selling-price="<?= htmlspecialchars((string)($product['selling_price'] ?? $product['price'] ?? 0), ENT_QUOTES) ?>"
                                                data-purchase-price="<?= htmlspecialchars((string)($product['purchase_price'] ?? 0), ENT_QUOTES) ?>"
                                                data-supplier="<?= htmlspecialchars($product['supplier'] ?? '', ENT_QUOTES) ?>"
                                                data-expiry-date="<?= htmlspecialchars($product['expiry_date'] ?? '', ENT_QUOTES) ?>"
                                                data-unit-type="<?= htmlspecialchars($product['unit_type'] ?? 'each', ENT_QUOTES) ?>"
                                                data-stock="<?= htmlspecialchars((string)$product['stock'], ENT_QUOTES) ?>"
                                                data-reorder-level="<?= htmlspecialchars((string)getProductReorderLevel($product), ENT_QUOTES) ?>"
                                                data-barcode="<?= htmlspecialchars($product['barcode'] ?? '', ENT_QUOTES) ?>">
                                                <span aria-hidden="true">&#9998;</span>
                                            </button>
                                            <form method="post" onsubmit="return confirm('Move this product to temporary delete?');" class="inline-actions">
                                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                                <button type="submit" name="delete_product" class="icon-button danger" title="Delete product" aria-label="Delete <?= htmlspecialchars($product['name']) ?>"><span aria-hidden="true">&#128465;</span></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <?php if (userHasPermission('manage_stock')): ?>
        <div class="modal-overlay" id="productModal" style="display: none;">
            <div class="modal-card">
                <div class="modal-header">
                    <h3 id="productModalTitle">Add Product</h3>
                    <button type="button" class="modal-close" id="closeProductModal" aria-label="Close">×</button>
                </div>
                <form method="post" class="product-form">
                    <input type="hidden" name="current_product_id" id="currentProductId" value="">
                    <label>
                        Product ID
                        <input type="text" name="product_id" id="productIdInput" readonly>
                    </label>
                    <label>
                        Product Name
                        <input type="text" name="name" id="productNameInput" required>
                    </label>
                    <label>
                        Barcode
                        <input type="text" name="barcode" id="productBarcodeInput" inputmode="numeric">
                    </label>
                    <label>
                        Category
                        <select name="category" id="productCategoryInput">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['name']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Supplier
                        <input type="text" name="supplier" id="productSupplierInput">
                    </label>
                    <label>
                        Purchase Price
                        <input type="number" step="0.01" name="purchase_price" id="productPurchasePriceInput">
                    </label>
                    <label>
                        Selling Price
                        <input type="number" step="0.01" name="selling_price" id="productPriceInput" required>
                    </label>
                    <label>
                        Unit Type
                        <select name="unit_type" id="productUnitTypeInput">
                            <option value="each">Each</option>
                            <option value="box">Box</option>
                            <option value="bottle">Bottle</option>
                        </select>
                    </label>
                    <label>
                        Expiry Date
                        <input type="date" name="expiry_date" id="productExpiryInput">
                    </label>
                    <label>
                        Stock
                        <input type="number" name="stock" id="productStockInput" required>
                    </label>
                    <label>
                        Reorder Level
                        <input type="number" name="reorder_level" id="productReorderLevelInput" min="0" value="10" required>
                    </label>
                    <div class="actions">
                        <button type="button" class="secondary" id="cancelProductModal">Cancel</button>
                        <button type="submit" name="create_product" id="productSubmitBtn">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script src="sidebar.js"></script>
    <script>
        const productModal = document.getElementById('productModal');
        const productModalTitle = document.getElementById('productModalTitle');
        const categoryProductCounts = <?= json_encode($categoryProductCounts) ?>;
        const closeProductModal = document.getElementById('closeProductModal');
        const cancelProductModal = document.getElementById('cancelProductModal');
        const productSubmitBtn = document.getElementById('productSubmitBtn');
        const currentProductIdInput = document.getElementById('currentProductId');
        const productIdInput = document.getElementById('productIdInput');
        const productNameInput = document.getElementById('productNameInput');
        const productCategoryInput = document.getElementById('productCategoryInput');
        const productPriceInput = document.getElementById('productPriceInput');
        const productPurchasePriceInput = document.getElementById('productPurchasePriceInput');
        const productSupplierInput = document.getElementById('productSupplierInput');
        const productExpiryInput = document.getElementById('productExpiryInput');
        const productUnitTypeInput = document.getElementById('productUnitTypeInput');
        const productStockInput = document.getElementById('productStockInput');
        const productReorderLevelInput = document.getElementById('productReorderLevelInput');
        const productBarcodeInput = document.getElementById('productBarcodeInput');

        function generateProductId(category) {
            const slug = (category || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'product';
            const count = categoryProductCounts[slug] || 0;
            return `${slug}-${String(count + 1).padStart(3, '0')}`;
        }

        function openProductModal(mode, product = null) {
            if (!productModal) {
                return;
            }

            if (mode === 'edit' && product) {
                productModalTitle.textContent = 'Edit Product';
                productSubmitBtn.textContent = 'Save Changes';
                productSubmitBtn.name = 'update_product';
                currentProductIdInput.value = product.id;
                productIdInput.value = product.id;
                productIdInput.readOnly = true;
                productNameInput.value = product.name;
                productCategoryInput.value = product.category;
                productPriceInput.value = product.sellingPrice;
                productPurchasePriceInput.value = product.purchasePrice;
                productSupplierInput.value = product.supplier;
                productExpiryInput.value = product.expiryDate;
                productUnitTypeInput.value = product.unitType;
                productStockInput.value = product.stock;
                productReorderLevelInput.value = product.reorderLevel;
                productBarcodeInput.value = product.barcode;
            } else {
                productModalTitle.textContent = 'Add Product';
                productSubmitBtn.textContent = 'Save Product';
                productSubmitBtn.name = 'create_product';
                currentProductIdInput.value = '';
                productIdInput.value = generateProductId(productCategoryInput.value);
                productIdInput.readOnly = true;
                productNameInput.value = '';
                productCategoryInput.selectedIndex = 0;
                productPriceInput.value = '';
                productPurchasePriceInput.value = '';
                productSupplierInput.value = '';
                productExpiryInput.value = '';
                productUnitTypeInput.value = 'each';
                productStockInput.value = '';
                productReorderLevelInput.value = '10';
                productBarcodeInput.value = '';
            }

            productModal.style.display = 'flex';
        }

        function closeProductModalView() {
            if (productModal) {
                productModal.style.display = 'none';
            }
        }

        document.getElementById('openCreateModalBtn')?.addEventListener('click', () => openProductModal('create'));
        document.querySelectorAll('.edit-product-btn').forEach((button) => {
            button.addEventListener('click', () => {
                openProductModal('edit', {
                    id: button.dataset.id,
                    name: button.dataset.name,
                    category: button.dataset.category,
                    sellingPrice: button.dataset.sellingPrice,
                    purchasePrice: button.dataset.purchasePrice,
                    supplier: button.dataset.supplier,
                    expiryDate: button.dataset.expiryDate,
                    unitType: button.dataset.unitType,
                    stock: button.dataset.stock,
                    reorderLevel: button.dataset.reorderLevel,
                    barcode: button.dataset.barcode,
                });
            });
        });

        productCategoryInput?.addEventListener('change', () => {
            if (currentProductIdInput.value === '') {
                productIdInput.value = generateProductId(productCategoryInput.value);
            }
        });

        closeProductModal?.addEventListener('click', closeProductModalView);
        cancelProductModal?.addEventListener('click', closeProductModalView);
        productModal?.addEventListener('click', (event) => {
            if (event.target === productModal) {
                closeProductModalView();
            }
        });
    </script>
</body>
</html>
