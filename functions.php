<?php
session_start();

function getDefaultProducts(): array {
    return [
        'paracetamol' => [
            'id' => 'paracetamol',
            'name' => 'Paracetamol 500mg',
            'price' => 8.50,
            'stock' => 50,
            'category' => 'Pain Relief'
        ],
        'amoxicillin' => [
            'id' => 'amoxicillin',
            'name' => 'Amoxicillin 250mg',
            'price' => 15.00,
            'stock' => 30,
            'category' => 'Antibiotic'
        ],
        'cough-syrup' => [
            'id' => 'cough-syrup',
            'name' => 'Cough Syrup',
            'price' => 12.75,
            'stock' => 20,
            'category' => 'Cold & Flu'
        ],
        'vitamin-c' => [
            'id' => 'vitamin-c',
            'name' => 'Vitamin C',
            'price' => 9.25,
            'stock' => 40,
            'category' => 'Supplements'
        ],
        'antacid' => [
            'id' => 'antacid',
            'name' => 'Antacid Tablets',
            'price' => 6.50,
            'stock' => 25,
            'category' => 'Digestive'
        ],
    ];
}

function getDataDirectory(): string {
    return __DIR__ . '/data';
}

function getCatalogFilePath(): string {
    return getDataDirectory() . '/products.json';
}

function getRolesFilePath(): string {
    return getDataDirectory() . '/roles.json';
}

function getUsersFilePath(): string {
    return getDataDirectory() . '/users.json';
}

function getOrderFilePath(): string {
    return getDataDirectory() . '/orders.json';
}

function getPurchaseFilePath(): string {
    return getDataDirectory() . '/purchases.json';
}

function getAuditLogFilePath(): string {
    return getDataDirectory() . '/audit.json';
}

function getCategoryFilePath(): string {
    return getDataDirectory() . '/categories.json';
}

function getDeletedProductsFilePath(): string {
    return getDataDirectory() . '/deleted_products.json';
}

function getCategorySlug(string $name): string {
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

function getProductUnitLabel(array $product): string {
    $unitType = strtolower(trim($product['unit_type'] ?? 'each'));
    return match ($unitType) {
        'box' => 'box',
        'bottle' => 'bottle',
        default => 'each',
    };
}

function formatStockValue(array $product): string {
    $stock = max(0, (int)($product['stock'] ?? 0));
    return $stock . ' ' . getProductUnitLabel($product);
}

function generateProductId(string $category, array $existingProducts = []): string {
    $base = getCategorySlug($category);
    $prefix = $base !== '' ? $base : 'product';
    $counter = 1;
    do {
        $candidate = $prefix . '-' . str_pad((string)$counter, 3, '0', STR_PAD_LEFT);
        $counter++;
    } while (isset($existingProducts[$candidate]));

    return $candidate;
}

function getDefaultCategories(): array {
    $categories = [];
    foreach (getDefaultProducts() as $product) {
        $slug = getCategorySlug($product['category'] ?? '');
        if ($slug === '') {
            continue;
        }
        $categories[$slug] = [
            'id' => $slug,
            'name' => $product['category']
        ];
    }
    return $categories;
}

function getCategories(): array {
    $filePath = getCategoryFilePath();
    if (!file_exists($filePath)) {
        $defaultCategories = getDefaultCategories();
        saveCategories($defaultCategories);
        return $defaultCategories;
    }

    $contents = file_get_contents($filePath);
    if (trim($contents) === '') {
        return getDefaultCategories();
    }

    $categories = json_decode($contents, true);
    return is_array($categories) ? $categories : getDefaultCategories();
}

function saveCategories(array $categories): void {
    file_put_contents(getCategoryFilePath(), json_encode($categories, JSON_PRETTY_PRINT));
}

function saveDeletedProducts(array $products): void {
    file_put_contents(getDeletedProductsFilePath(), json_encode($products, JSON_PRETTY_PRINT));
}

function cleanupDeletedProducts(): void {
    $filePath = getDeletedProductsFilePath();
    if (!file_exists($filePath)) {
        return;
    }

    $contents = file_get_contents($filePath);
    if (trim($contents) === '') {
        return;
    }

    $deletedProducts = json_decode($contents, true);
    if (!is_array($deletedProducts)) {
        return;
    }

    $cutoff = time() - (30 * 24 * 60 * 60);
    $remaining = [];
    foreach ($deletedProducts as $product) {
        $deletedAt = (int)($product['deleted_at'] ?? 0);
        if ($deletedAt > 0 && $deletedAt <= $cutoff) {
            continue;
        }
        $remaining[] = $product;
    }

    if (count($remaining) !== count($deletedProducts)) {
        saveDeletedProducts($remaining);
    }
}

function getDeletedProducts(): array {
    cleanupDeletedProducts();

    $filePath = getDeletedProductsFilePath();
    if (!file_exists($filePath)) {
        return [];
    }

    $contents = file_get_contents($filePath);
    if (trim($contents) === '') {
        return [];
    }

    $deletedProducts = json_decode($contents, true);
    return is_array($deletedProducts) ? $deletedProducts : [];
}

function softDeleteProduct(string $productId): bool {
    $products = getProducts();
    if (!isset($products[$productId])) {
        return false;
    }

    $product = $products[$productId];
    unset($products[$productId]);
    saveProducts($products);

    $deletedProducts = getDeletedProducts();
    $deletedProducts[$productId] = [
        ...$product,
        'deleted_at' => time(),
    ];
    saveDeletedProducts($deletedProducts);
    return true;
}

function getCategoryById(string $categoryId): ?array {
    $categories = getCategories();
    return $categories[$categoryId] ?? null;
}

function createCategory(string $name): ?array {
    $name = trim($name);
    if ($name === '') {
        return null;
    }

    $categories = getCategories();
    $slug = getCategorySlug($name);
    if ($slug === '' || isset($categories[$slug])) {
        return null;
    }

    $categories[$slug] = [
        'id' => $slug,
        'name' => $name,
    ];
    saveCategories($categories);
    return $categories[$slug];
}

function updateCategory(string $categoryId, string $newName): bool {
    $newName = trim($newName);
    if ($newName === '') {
        return false;
    }

    $categories = getCategories();
    if (!isset($categories[$categoryId])) {
        return false;
    }

    $categories[$categoryId]['name'] = $newName;
    saveCategories($categories);
    return true;
}

function deleteCategory(string $categoryId): bool {
    $categories = getCategories();
    if (!isset($categories[$categoryId])) {
        return false;
    }

    $category = $categories[$categoryId];
    $products = getProducts();
    foreach ($products as $product) {
        if (($product['category'] ?? '') === $category['name']) {
            return false;
        }
    }

    unset($categories[$categoryId]);
    saveCategories($categories);
    return true;
}

function loadAuditLogs(): array {
    $filePath = getAuditLogFilePath();
    if (!file_exists($filePath)) {
        return [];
    }

    $contents = file_get_contents($filePath);
    if (trim($contents) === '') {
        return [];
    }

    $logs = json_decode($contents, true);
    return is_array($logs) ? $logs : [];
}

function saveAuditLogs(array $logs): void {
    file_put_contents(getAuditLogFilePath(), json_encode($logs, JSON_PRETTY_PRINT));
}

function logActivity(string $action, array $details = []): void {
    $user = getCurrentUser();
    $logs = loadAuditLogs();

    $logs[] = [
        'id' => time() . '-' . rand(1000, 9999),
        'user_id' => $user['id'] ?? null,
        'username' => $user['username'] ?? 'guest',
        'full_name' => $user['full_name'] ?? ($user['username'] ?? 'guest'),
        'role' => $user['role'] ?? 'guest',
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'created_at' => date('Y-m-d H:i:s')
    ];

    saveAuditLogs($logs);
}

function getUserDisplayName(array $user): string {
    return trim($user['full_name'] ?? '') !== '' ? $user['full_name'] : ($user['username'] ?? 'Unknown');
}

function getRoleLabel(string $role): string {
    $roles = getRolesConfig();
    return $roles[$role]['name'] ?? ucfirst(str_replace('_', ' ', $role));
}

function isAdminUser(): bool {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }

    return $user['role'] === 'owner' || $user['role'] === 'admin' || userHasPermission('manage_roles');
}

function ensureDataFiles(): void {
    if (!is_dir(getDataDirectory())) {
        mkdir(getDataDirectory(), 0777, true);
    }

    if (!file_exists(getCatalogFilePath())) {
        saveProducts(getDefaultProducts());
    }

    if (!file_exists(getRolesFilePath())) {
        $defaultRoles = [
            'owner' => [
                'name' => 'Owner',
                'permissions' => ['view_products', 'manage_cart', 'checkout', 'view_orders', 'view_profit_loss', 'manage_stock', 'manage_roles']
            ],
            'stock' => [
                'name' => 'Stock Staff',
                'permissions' => ['view_products', 'manage_stock']
            ]
        ];
        file_put_contents(getRolesFilePath(), json_encode($defaultRoles, JSON_PRETTY_PRINT));
    }

    if (!file_exists(getCategoryFilePath())) {
        saveCategories(getDefaultCategories());
    }

    if (!file_exists(getPurchaseFilePath())) {
        savePurchases([]);
    }

    if (!file_exists(getUsersFilePath())) {
        $defaultUsers = [
            [
                'id' => 1,
                'username' => 'owner',
                'password' => 'owner123',
                'role' => 'owner',
                'full_name' => 'Owner'
            ],
            [
                'id' => 2,
                'username' => 'stock',
                'password' => 'stock123',
                'role' => 'stock',
                'full_name' => 'Stock Staff'
            ]
        ];
        file_put_contents(getUsersFilePath(), json_encode($defaultUsers, JSON_PRETTY_PRINT));
    }
}

ensureDataFiles();

function saveProducts(array $products): void {
    file_put_contents(getCatalogFilePath(), json_encode($products, JSON_PRETTY_PRINT));
}

function getProducts(): array {
    cleanupDeletedProducts();

    $filePath = getCatalogFilePath();
    if (!file_exists($filePath)) {
        $defaultProducts = getDefaultProducts();
        saveProducts($defaultProducts);
        return $defaultProducts;
    }

    $contents = file_get_contents($filePath);
    if (trim($contents) === '') {
        return getDefaultProducts();
    }

    $products = json_decode($contents, true);
    return is_array($products) ? $products : getDefaultProducts();
}

function getProductById(string $productId): ?array {
    $products = getProducts();
    return $products[$productId] ?? null;
}

function getCart(): array {
    return $_SESSION['cart'] ?? [];
}

function saveCart(array $cart): void {
    $_SESSION['cart'] = $cart;
}

function addToCart(string $productId, int $quantity = 1): void {
    $product = getProductById($productId);
    if (!$product) {
        return;
    }

    $cart = getCart();
    $currentQty = isset($cart[$productId]) ? (int)$cart[$productId] : 0;
    $cart[$productId] = $currentQty + max(1, $quantity);
    saveCart($cart);
    logActivity('add_to_cart', ['product_id' => $productId, 'quantity' => max(1, $quantity)]);
}

function updateCartItem(string $productId, int $quantity): void {
    $cart = getCart();

    if ($quantity <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $quantity;
    }

    saveCart($cart);
    logActivity('update_cart', ['product_id' => $productId, 'quantity' => $quantity]);
}

function removeFromCart(string $productId): void {
    $cart = getCart();
    unset($cart[$productId]);
    saveCart($cart);
    logActivity('remove_from_cart', ['product_id' => $productId]);
}

function clearCart(): void {
    saveCart([]);
    logActivity('clear_cart');
}

function getCartItems(): array {
    $products = getProducts();
    $cart = getCart();
    $items = [];

    foreach ($cart as $productId => $quantity) {
        if (!isset($products[$productId])) {
            continue;
        }

        $product = $products[$productId];
        $sellingPrice = (float)($product['selling_price'] ?? $product['price'] ?? 0);
        $items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $sellingPrice,
            'quantity' => $quantity,
            'unit_type' => $product['unit_type'] ?? 'each',
            'line_total' => $sellingPrice * $quantity,
        ];
    }

    return $items;
}

function getCartTotal(): float {
    $total = 0;
    foreach (getCartItems() as $item) {
        $total += $item['line_total'];
    }

    return round($total, 2);
}

function formatCurrency(float $amount): string {
    return '₱' . number_format($amount, 2);
}

function getRolesConfig(): array {
    $filePath = getRolesFilePath();
    if (!file_exists($filePath)) {
        return [];
    }

    $contents = file_get_contents($filePath);
    if (trim($contents) === '') {
        return [];
    }

    $roles = json_decode($contents, true);
    return is_array($roles) ? $roles : [];
}

function saveRolesConfig(array $roles): void {
    file_put_contents(getRolesFilePath(), json_encode($roles, JSON_PRETTY_PRINT));
}

function getUsersConfig(): array {
    $filePath = getUsersFilePath();
    if (!file_exists($filePath)) {
        return [];
    }

    $contents = file_get_contents($filePath);
    if (trim($contents) === '') {
        return [];
    }

    $users = json_decode($contents, true);
    return is_array($users) ? $users : [];
}

function saveUsersConfig(array $users): void {
    file_put_contents(getUsersFilePath(), json_encode($users, JSON_PRETTY_PRINT));
}

function getPermissionList(): array {
    return [
        'view_products' => 'View Products',
        'manage_cart' => 'Manage Cart',
        'checkout' => 'Checkout Sales',
        'view_orders' => 'View Orders',
        'view_profit_loss' => 'View Profit & Loss',
        'manage_stock' => 'Manage Stock',
        'manage_roles' => 'Manage Roles'
    ];
}

function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool {
    return !empty(getCurrentUser());
}

function getCurrentRole(): string {
    return getCurrentUser()['role'] ?? '';
}

function getRolePermissions(string $role): array {
    $roles = getRolesConfig();
    return $roles[$role]['permissions'] ?? [];
}

function userHasPermission(string $permission): bool {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }

    return in_array($permission, getRolePermissions($user['role'] ?? ''), true);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requirePermission(string $permission): void {
    requireLogin();
    if (!userHasPermission($permission)) {
        header('Location: forbidden.php');
        exit;
    }
}

function verifyPassword(string $inputPassword, string $storedPassword): bool {
    return $storedPassword === $inputPassword || password_verify($inputPassword, $storedPassword);
}

function loginUser(string $username, string $password): ?array {
    foreach (getUsersConfig() as $user) {
        if (strtolower($user['username']) === strtolower($username) && verifyPassword($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'] ?? $user['username'],
                'role' => $user['role']
            ];
            logActivity('login_success', ['username' => $username]);
            return $_SESSION['user'];
        }
    }

    logActivity('login_failed', ['username' => $username]);
    return null;
}

function logoutUser(): void {
    unset($_SESSION['user']);
    session_destroy();
}

function renderNavLinks(string $activePage = ''): string {
    $links = [];
    if (userHasPermission('manage_cart')) {
        $links[] = ['url' => 'cart.php', 'label' => 'Cart'];
    }
    if (userHasPermission('checkout')) {
        $links[] = ['url' => 'checkout.php', 'label' => 'Checkout'];
    }
    if (userHasPermission('view_orders')) {
        $links[] = ['url' => 'orders.php', 'label' => 'Orders'];
    }
    if (userHasPermission('view_profit_loss')) {
        $links[] = ['url' => 'profit_loss.php', 'label' => 'Profit & Loss'];
    }
    if (userHasPermission('manage_roles')) {
        $links[] = ['url' => 'roles.php', 'label' => 'Roles'];
        $links[] = ['url' => 'users.php', 'label' => 'Users'];
        $links[] = ['url' => 'audit.php', 'label' => 'Audit'];
    }
    $links[] = ['url' => 'logout.php', 'label' => 'Logout'];

    $productLinks = [];
    if (userHasPermission('view_products')) {
        $productLinks[] = ['url' => 'index.php', 'label' => 'Products'];
    }
    if (userHasPermission('manage_stock')) {
        $productLinks[] = ['url' => 'categories.php', 'label' => 'Categories'];
        $productLinks[] = ['url' => 'stock.php', 'label' => 'Stock'];
        $productLinks[] = ['url' => 'purchases.php', 'label' => 'Purchases'];
    }

    $html = '';
    if ($productLinks) {
        $productActive = in_array($activePage, ['index.php', 'categories.php', 'stock.php', 'purchases.php'], true) ? ' active' : '';
        $html .= '<div class="nav-group' . $productActive . '">';
        $html .= '<button type="button" class="nav-group-title" data-nav-group="product">Product <span class="nav-arrow">▾</span></button>';
        $html .= '<div class="nav-submenu">';
        foreach ($productLinks as $link) {
            $active = $activePage === $link['url'] ? ' class="active"' : '';
            $html .= '<a' . $active . ' href="' . htmlspecialchars($link['url']) . '">' . htmlspecialchars($link['label']) . '</a>';
        }
        $html .= '</div></div>';
    }

    foreach ($links as $link) {
        $active = $activePage === $link['url'] ? ' class="active"' : '';
        $html .= '<a' . $active . ' href="' . htmlspecialchars($link['url']) . '">' . htmlspecialchars($link['label']) . '</a>';
    }

    return $html;
}

function loadOrders(): array {
    $filePath = getOrderFilePath();
    if (!file_exists($filePath)) {
        return [];
    }

    $contents = file_get_contents($filePath);
    if (trim($contents) === '') {
        return [];
    }

    $orders = json_decode($contents, true);
    return is_array($orders) ? $orders : [];
}

function saveOrders(array $orders): void {
    file_put_contents(getOrderFilePath(), json_encode($orders, JSON_PRETTY_PRINT));
}

function loadPurchases(): array {
    $filePath = getPurchaseFilePath();
    if (!file_exists($filePath)) {
        return [];
    }

    $contents = file_get_contents($filePath);
    if (trim($contents) === '') {
        return [];
    }

    $purchases = json_decode($contents, true);
    return is_array($purchases) ? $purchases : [];
}

function savePurchases(array $purchases): void {
    file_put_contents(getPurchaseFilePath(), json_encode($purchases, JSON_PRETTY_PRINT));
}
