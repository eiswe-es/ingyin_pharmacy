<?php
require_once __DIR__ . '/functions.php';
requirePermission('manage_stock');

$categories = getCategories();
$success = '';
$error = '';
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_category'])) {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $error = 'Please enter a category name.';
        } else {
            $created = createCategory($name);
            if ($created) {
                $success = 'Category created successfully.';
                $categories = getCategories();
                logActivity('create_category', ['category_name' => $name]);
            } else {
                $error = 'This category already exists or the name is invalid.';
            }
        }
    } elseif (isset($_POST['update_categories'])) {
        $updatedCategories = [];
        foreach ($categories as $key => $category) {
            $submitted = $_POST['categories'][$key] ?? [];
            $name = trim($submitted['name'] ?? $category['name']);
            if ($name === '') {
                $error = 'Category names cannot be empty.';
                break;
            }
            $updatedCategories[$key] = [
                'id' => $key,
                'name' => $name,
            ];
        }

        if ($error === '') {
            saveCategories($updatedCategories);
            logActivity('update_categories', ['count' => count($updatedCategories)]);
            $success = 'Categories updated successfully.';
            $categories = getCategories();
        }
    } elseif (isset($_POST['delete_category'])) {
        $deleteId = trim($_POST['delete_id'] ?? '');
        if ($deleteId !== '' && deleteCategory($deleteId)) {
            $success = 'Category deleted successfully.';
            $categories = getCategories();
            logActivity('delete_category', ['category_id' => $deleteId]);
        } else {
            $error = 'Unable to delete the selected category.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Ingyin Pharmacy</title>
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
                <?= renderNavLinks('categories.php') ?>
            </nav>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <h2>Category Management</h2>
                    <p>Manage product categories</p>
                </div>
                <div class="topbar-user">
                    <span><?= htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']) ?></span>
                    <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
                </div>
            </header>

            <main class="container">
                <section class="card wide">
                    <h2>Product Categories</h2>
                    <?php if ($success !== ''): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                    <?php if ($error !== ''): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                    <form method="post">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $key => $category): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($key) ?></td>
                                        <td>
                                            <input type="text" name="categories[<?= htmlspecialchars($key) ?>][name]" value="<?= htmlspecialchars($category['name']) ?>">
                                        </td>
                                        <td>
                                            <button type="submit" name="delete_category" class="danger">Delete</button>
                                            <input type="hidden" name="delete_id" value="<?= htmlspecialchars($key) ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" name="update_categories">Save Categories</button>
                    </form>

                    <hr>

                    <h3>Create New Category</h3>
                    <form method="post" class="checkout-form">
                        <label>
                            Category Name
                            <input type="text" name="name" required>
                        </label>
                        <button type="submit" name="create_category">Create Category</button>
                    </form>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
</body>
</html>
