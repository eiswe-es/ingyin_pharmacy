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
    } elseif (isset($_POST['update_category'])) {
        $categoryId = trim($_POST['category_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if ($categoryId === '' || $name === '' || !isset($categories[$categoryId])) {
            $error = 'Please provide a valid category name.';
        } else {
            $oldName = $categories[$categoryId]['name'];
            if (updateCategory($categoryId, $name)) {
                logActivity('update_category', ['category_id' => $categoryId, 'old_name' => $oldName]);
                $success = 'Category updated successfully.';
                $categories = getCategories();
            } else {
                $error = 'Unable to update the selected category.';
            }
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
                    <div class="card-header">
                        <div>
                            <h2>Product Categories</h2>
                            <p class="muted">Organize products with reusable categories.</p>
                        </div>
                        <button type="button" class="icon-button primary" id="openCreateCategoryModal" title="Add a new category" aria-label="Add a new category"><span aria-hidden="true">+</span></button>
                    </div>
                    <?php if ($success !== ''): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                    <?php if ($error !== ''): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                    <div class="table-wrap">
                        <table class="table data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $key => $category): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($key) ?></td>
                                        <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                                        <td>
                                            <div class="inline-actions">
                                                <button type="button" class="icon-button secondary edit-category-btn" title="Edit category" aria-label="Edit <?= htmlspecialchars($category['name']) ?>" data-id="<?= htmlspecialchars($key, ENT_QUOTES) ?>" data-name="<?= htmlspecialchars($category['name'], ENT_QUOTES) ?>"><span aria-hidden="true">&#9998;</span></button>
                                                <form method="post" onsubmit="return confirm('Delete this category?');"><input type="hidden" name="delete_id" value="<?= htmlspecialchars($key) ?>"><button type="submit" name="delete_category" class="icon-button danger" title="Delete category" aria-label="Delete <?= htmlspecialchars($category['name']) ?>"><span aria-hidden="true">&#128465;</span></button></form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-overlay" id="categoryModal" style="display: none;">
                        <div class="modal-card">
                            <div class="modal-header"><h3 id="categoryModalTitle">Create New Category</h3><button type="button" class="modal-close" id="closeCategoryModal" aria-label="Close">&times;</button></div>
                            <form method="post" class="checkout-form">
                                <input type="hidden" name="category_id" id="categoryIdInput">
                        <label>
                            Category Name
                            <input type="text" name="name" id="categoryNameInput" required>
                        </label>
                                <div class="actions"><button type="button" class="secondary" id="cancelCategoryModal">Cancel</button><button type="submit" name="create_category" id="categorySubmitBtn">Create Category</button></div>
                            </form>
                        </div>
                    </div>
                </section>
            </main>
        </main>
    </div>

    <script src="sidebar.js"></script>
    <script>
        const categoryModal = document.getElementById('categoryModal');
        const categoryModalTitle = document.getElementById('categoryModalTitle');
        const categorySubmitBtn = document.getElementById('categorySubmitBtn');
        const categoryIdInput = document.getElementById('categoryIdInput');
        const categoryNameInput = document.getElementById('categoryNameInput');

        function openCategoryModal(mode, button) {
            const editing = mode === 'edit';
            categoryModalTitle.textContent = editing ? 'Edit Category' : 'Create New Category';
            categorySubmitBtn.textContent = editing ? 'Save Changes' : 'Create Category';
            categorySubmitBtn.name = editing ? 'update_category' : 'create_category';
            categoryIdInput.value = editing ? button.dataset.id : '';
            categoryNameInput.value = editing ? button.dataset.name : '';
            categoryModal.style.display = 'flex';
        }

        document.getElementById('openCreateCategoryModal')?.addEventListener('click', () => openCategoryModal('create'));
        document.querySelectorAll('.edit-category-btn').forEach((button) => button.addEventListener('click', () => openCategoryModal('edit', button)));
        document.getElementById('closeCategoryModal')?.addEventListener('click', () => { categoryModal.style.display = 'none'; });
        document.getElementById('cancelCategoryModal')?.addEventListener('click', () => { categoryModal.style.display = 'none'; });
        categoryModal?.addEventListener('click', (event) => { if (event.target === categoryModal) categoryModal.style.display = 'none'; });
    </script>
</body>
</html>
