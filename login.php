<?php
require_once __DIR__ . '/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = loginUser(trim($_POST['username'] ?? ''), trim($_POST['password'] ?? ''));
    if ($user) {
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ingyin Pharmacy POS</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="container auth-container">
        <section class="card wide">
            <h2 class="card-title text-center">Ingyin Pharmacy</h2>
            <!-- <p>Use the demo accounts below to test role access.</p>
            <p><strong>Owner:</strong> owner / owner123</p>
            <p><strong>Stock Staff:</strong> stock / stock123</p> -->

            <?php if ($error !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="checkout-form">
                <label>
                    Username
                    <input type="text" name="username" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" required>
                </label>
                <button type="submit">Login</button>
            </form>
        </section>
    </main>
</body>
</html>
