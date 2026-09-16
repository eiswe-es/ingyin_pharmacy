<?php
require_once __DIR__ . '/functions.php';
requirePermission('manage_stock');

$products = getProducts();
$categories = getCategories();
$categoryLookup = [];
$categoryProductCounts = [];
foreach ($products as $product) {
    $categorySlug = getCategorySlug($product['category'] ?? '');
    if ($categorySlug === '') {
        continue;
    }
    $categoryProductCounts[$categorySlug] = ($categoryProductCounts[$categorySlug] ?? 0) + 1;
}
foreach ($categories as $category) {
    $categoryLookup[$category['name']] = true;
}
$success = '';
$error = '';
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_product'])) {
        $productId = strtolower(trim($_POST['product_id'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $sellingPrice = (float)($_POST['selling_price'] ?? $_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $supplier = trim($_POST['supplier'] ?? '');
        $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
        $expiryDate = trim($_POST['expiry_date'] ?? '');
        $unitType = trim($_POST['unit_type'] ?? 'each');

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
                'unit_type' => $unitType
            ];
            saveProducts($products);
            logActivity('create_product', ['product_id' => $productId, 'name' => $name]);
            $success = 'Product created successfully.';
            $products = getProducts();
        }
    } elseif (isset($_POST['update_product'])) {
        $productId = trim($_POST['current_product_id'] ?? '');
        if ($productId !== '' && isset($products[$productId])) {
            $product = $products[$productId];
            $product['name'] = trim($_POST['name'] ?? $product['name']);
            $product['price'] = (float)($_POST['selling_price'] ?? $product['price']);
            $product['selling_price'] = $product['price'];
            $product['stock'] = (int)($_POST['stock'] ?? $product['stock']);
            $product['category'] = trim($_POST['category'] ?? $product['category']);
            $product['supplier'] = trim($_POST['supplier'] ?? ($product['supplier'] ?? ''));
            $product['purchase_price'] = (float)($_POST['purchase_price'] ?? ($product['purchase_price'] ?? 0));
            $product['expiry_date'] = trim($_POST['expiry_date'] ?? ($product['expiry_date'] ?? ''));
            $product['unit_type'] = trim($_POST['unit_type'] ?? ($product['unit_type'] ?? 'each'));
            $products[$productId] = $product;
            saveProducts($products);
            logActivity('update_product', ['product_id' => $productId]);
            $success = 'Product updated successfully.';
            $products = getProducts();
        } else {
            $error = 'Unable to update the selected product.';
        }
    } elseif (isset($_POST['delete_product'])) {
        $deleteKey = trim($_POST['delete_key'] ?? '');
        if ($deleteKey !== '' && isset($products[$deleteKey])) {
            unset($products[$deleteKey]);
            saveProducts($products);
            logActivity('delete_product', ['product_id' => $deleteKey]);
            $success = 'Product deleted successfully.';
            $products = getProducts();
        } else {
            $error = 'Unable to delete the selected product.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Ingyin Pharmacy</title>
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
                <?= renderNavLinks('products.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Product Management</h2>
                    <p>Manage inventory products</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>

            <main class="container">
                <section class="card wide">
                    <div class="card-header">
                        <div>
                            <h2>Product Management</h2>
                            <p class="muted">View and maintain your pharmacy inventory.</p>
                        </div>
                        <button type="button" class="icon-button primary" id="openCreateProductModal" title="Add a new product" aria-label="Add a new product"><span aria-hidden="true">+</span></button>
                    </div>
                    <?php if ($success !== ''): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                    <?php if ($error !== ''): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                    <div class="table-wrap">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $key => $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($key) ?></td>
                                        <td><strong><?= htmlspecialchars($product['name']) ?></strong><small class="table-subtext"><?= htmlspecialchars($key) ?></small></td>
                                        <td><span class="tag"><?= htmlspecialchars($product['category'] ?? 'Uncategorized') ?></span></td>
                                        <td><?= formatCurrency((float)($product['selling_price'] ?? $product['price'] ?? 0)) ?></td>
                                        <td><?= htmlspecialchars(formatStockValue($product)) ?></td>
                                        <td>
                                            <div class="inline-actions">
                                                <button type="button" class="icon-button secondary edit-product-btn" title="Edit product" aria-label="Edit <?= htmlspecialchars($product['name']) ?>" data-id="<?= htmlspecialchars($key, ENT_QUOTES) ?>" data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>" data-category="<?= htmlspecialchars($product['category'] ?? '', ENT_QUOTES) ?>" data-selling-price="<?= htmlspecialchars((string)($product['selling_price'] ?? $product['price'] ?? 0), ENT_QUOTES) ?>" data-purchase-price="<?= htmlspecialchars((string)($product['purchase_price'] ?? 0), ENT_QUOTES) ?>" data-supplier="<?= htmlspecialchars($product['supplier'] ?? '', ENT_QUOTES) ?>" data-expiry-date="<?= htmlspecialchars($product['expiry_date'] ?? '', ENT_QUOTES) ?>" data-unit-type="<?= htmlspecialchars($product['unit_type'] ?? 'each', ENT_QUOTES) ?>" data-stock="<?= (int)$product['stock'] ?>"><span aria-hidden="true">&#9998;</span></button>
                                                <form method="post" onsubmit="return confirm('Delete this product?');"><input type="hidden" name="delete_key" value="<?= htmlspecialchars($key) ?>"><button type="submit" name="delete_product" class="icon-button danger" title="Delete product" aria-label="Delete <?= htmlspecialchars($product['name']) ?>"><span aria-hidden="true">&#128465;</span></button></form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-overlay" id="productModal" style="display: none;">
                        <div class="modal-card">
                            <div class="modal-header"><h3 id="productModalTitle">Add New Product</h3><button type="button" class="modal-close" id="closeProductModal" aria-label="Close">&times;</button></div>
                            <form method="post" class="checkout-form">
                                <input type="hidden" name="current_product_id" id="currentProductId">
                        <label>
                            Product ID
                            <input type="text" name="product_id" id="productIdInput" readonly>
                        </label>
                        <label>
                            Product Name
                            <input type="text" name="name" required>
                        </label>
                        <label>
                            Category
                            <select name="category">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['name']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            Supplier
                            <input type="text" name="supplier">
                        </label>
                        <label>
                            Purchase Price
                            <input type="number" step="0.01" name="purchase_price">
                        </label>
                        <label>
                            Selling Price
                            <input type="number" step="0.01" name="selling_price" required>
                        </label>
                        <label>
                            Unit Type
                            <select name="unit_type">
                                <option value="each">Each</option>
                                <option value="box">Box</option>
                                <option value="bottle">Bottle</option>
                            </select>
                        </label>
                        <label>
                            Expiry Date
                            <input type="date" name="expiry_date">
                        </label>
                        <label>
                            Stock
                            <input type="number" name="stock" required>
                        </label>
                                <div class="actions"><button type="button" class="secondary" id="cancelProductModal">Cancel</button><button type="submit" name="create_product" id="productSubmitBtn">Create Product</button></div>
                            </form>
                        </div>
                    </div>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
    <script>
        const categoryProductCounts = <?= json_encode($categoryProductCounts) ?>;
        const productIdInput = document.getElementById('productIdInput');
        const productModal = document.getElementById('productModal');
        const productModalTitle = document.getElementById('productModalTitle');
        const productSubmitBtn = document.getElementById('productSubmitBtn');
        const currentProductIdInput = document.getElementById('currentProductId');
        const categoryInput = document.querySelector('select[name="category"]');

        function generateProductId(category) {
            const slug = (category || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'product';
            const count = categoryProductCounts[slug] || 0;
            return `${slug}-${String(count + 1).padStart(3, '0')}`;
        }

        function updateGeneratedProductId() {
            if (productIdInput && categoryInput) {
                productIdInput.value = generateProductId(categoryInput.value);
            }
        }

        function openProductModal(mode, button) {
            productModalTitle.textContent = mode === 'edit' ? 'Edit Product' : 'Add New Product';
            productSubmitBtn.textContent = mode === 'edit' ? 'Save Changes' : 'Create Product';
            productSubmitBtn.name = mode === 'edit' ? 'update_product' : 'create_product';
            currentProductIdInput.value = mode === 'edit' ? button.dataset.id : '';
            productIdInput.value = mode === 'edit' ? button.dataset.id : generateProductId(categoryInput.value);
            document.querySelector('[name="name"]').value = mode === 'edit' ? button.dataset.name : '';
            categoryInput.value = mode === 'edit' ? button.dataset.category : categoryInput.options[0]?.value || '';
            document.querySelector('[name="supplier"]').value = mode === 'edit' ? button.dataset.supplier : '';
            document.querySelector('[name="purchase_price"]').value = mode === 'edit' ? button.dataset.purchasePrice : '';
            document.querySelector('[name="selling_price"]').value = mode === 'edit' ? button.dataset.sellingPrice : '';
            document.querySelector('[name="unit_type"]').value = mode === 'edit' ? button.dataset.unitType : 'each';
            document.querySelector('[name="expiry_date"]').value = mode === 'edit' ? button.dataset.expiryDate : '';
            document.querySelector('[name="stock"]').value = mode === 'edit' ? button.dataset.stock : '';
            productModal.style.display = 'flex';
        }

        document.getElementById('openCreateProductModal')?.addEventListener('click', () => openProductModal('create'));
        document.querySelectorAll('.edit-product-btn').forEach((button) => button.addEventListener('click', () => openProductModal('edit', button)));
        document.getElementById('closeProductModal')?.addEventListener('click', () => { productModal.style.display = 'none'; });
        document.getElementById('cancelProductModal')?.addEventListener('click', () => { productModal.style.display = 'none'; });
        productModal?.addEventListener('click', (event) => { if (event.target === productModal) productModal.style.display = 'none'; });

        updateGeneratedProductId();
    </script>
</body>
</html>
