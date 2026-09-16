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
    } elseif (isset($_POST['update_products'])) {
        $updatedProducts = [];
        foreach ($products as $key => $product) {
            $submitted = $_POST['products'][$key] ?? [];
            $updatedProduct = $product;
            $updatedProduct['name'] = trim($submitted['name'] ?? $product['name']);
            $updatedProduct['price'] = (float)($submitted['price'] ?? $product['price']);
            $updatedProduct['stock'] = (int)($submitted['stock'] ?? $product['stock']);
            $updatedProduct['category'] = trim($submitted['category'] ?? $product['category']);
            $updatedProducts[$key] = $updatedProduct;
        }

        saveProducts($updatedProducts);
        logActivity('update_products', ['count' => count($updatedProducts)]);
        $success = 'Products updated successfully.';
        $products = getProducts();
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
                    <h2>Product Management</h2>
                    <?php if ($success !== ''): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                    <?php if ($error !== ''): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                    <form method="post">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $key => $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($key) ?></td>
                                        <td>
                                            <input type="text" name="products[<?= htmlspecialchars($key) ?>][name]" value="<?= htmlspecialchars($product['name']) ?>">
                                        </td>
                                        <td>
                                            <div class="custom-select">
                                                <button type="button" class="select-trigger">
                                                    <span class="select-label"><?= htmlspecialchars($product['category'] ?? 'Select category') ?></span>
                                                    <span class="select-arrow">▾</span>
                                                </button>
                                                <div class="select-options">
                                                    <?php foreach ($categories as $category): ?>
                                                        <button type="button" class="select-option" data-value="<?= htmlspecialchars($category['name']) ?>"><?= htmlspecialchars($category['name']) ?></button>
                                                    <?php endforeach; ?>
                                                    <?php if (!empty($product['category']) && !isset($categoryLookup[$product['category']])): ?>
                                                        <button type="button" class="select-option" data-value="<?= htmlspecialchars($product['category']) ?>"><?= htmlspecialchars($product['category']) ?></button>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="hidden" name="products[<?= htmlspecialchars($key) ?>][category]" value="<?= htmlspecialchars($product['category'] ?? '') ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="products[<?= htmlspecialchars($key) ?>][price]" value="<?= htmlspecialchars((string)$product['price']) ?>">
                                        </td>
                                        <td>
                                            <input type="number" name="products[<?= htmlspecialchars($key) ?>][stock]" value="<?= (int)$product['stock'] ?>">
                                        </td>
                                        <td>
                                            <button type="submit" name="delete_product" class="danger">Delete</button>
                                            <input type="hidden" name="delete_key" value="<?= htmlspecialchars($key) ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" name="update_products">Save Changes</button>
                    </form>

                    <hr>

                    <h3>Add New Product</h3>
                    <form method="post" class="checkout-form">
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
                            <div class="custom-select">
                                <button type="button" class="select-trigger">
                                    <span class="select-label"><?= htmlspecialchars($categories[0]['name'] ?? 'Select category') ?></span>
                                    <span class="select-arrow">▾</span>
                                </button>
                                <div class="select-options">
                                    <?php foreach ($categories as $category): ?>
                                        <button type="button" class="select-option" data-value="<?= htmlspecialchars($category['name']) ?>"><?= htmlspecialchars($category['name']) ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="category" value="<?= htmlspecialchars($categories[0]['name'] ?? '') ?>">
                            </div>
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
                        <button type="submit" name="create_product">Create Product</button>
                    </form>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
    <script>
        const categoryProductCounts = <?= json_encode($categoryProductCounts) ?>;
        const productIdInput = document.getElementById('productIdInput');
        const categoryHiddenInput = document.querySelector('input[name="category"]');

        function generateProductId(category) {
            const slug = (category || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'product';
            const count = categoryProductCounts[slug] || 0;
            return `${slug}-${String(count + 1).padStart(3, '0')}`;
        }

        function updateGeneratedProductId() {
            if (productIdInput && categoryHiddenInput) {
                productIdInput.value = generateProductId(categoryHiddenInput.value);
            }
        }

        document.querySelectorAll('.custom-select').forEach((select) => {
            const trigger = select.querySelector('.select-trigger');
            const hiddenInput = select.querySelector('input[type="hidden"]');
            const label = select.querySelector('.select-label');

            trigger.addEventListener('click', (event) => {
                event.stopPropagation();
                document.querySelectorAll('.custom-select').forEach((other) => {
                    if (other !== select) {
                        other.classList.remove('open');
                    }
                });
                select.classList.toggle('open');
            });

            select.querySelectorAll('.select-option').forEach((option) => {
                option.addEventListener('click', () => {
                    const value = option.getAttribute('data-value');
                    if (label) {
                        label.textContent = value;
                    }
                    if (hiddenInput) {
                        hiddenInput.value = value;
                    }
                    if (hiddenInput && hiddenInput.name === 'category') {
                        updateGeneratedProductId();
                    }
                    select.classList.remove('open');
                });
            });
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-select').forEach((select) => {
                select.classList.remove('open');
            });
        });

        updateGeneratedProductId();
    </script>
</body>
</html>
