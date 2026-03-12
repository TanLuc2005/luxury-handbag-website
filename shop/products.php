<?php
/**
 * products.php — Product Listing Page
 */
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Shop';
$db        = getDB();

$stmt = $db->query('SELECT * FROM products ORDER BY ProductName');
$products = $stmt->fetchAll();

// Add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    requireLogin();
    validateCSRF();

    $productId = (int)$_POST['product_id'];
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]++;
    } else {
        $_SESSION['cart'][$productId] = 1;
    }
    setFlash('success', 'Item added to cart!');
    header('Location: ' . BASE_URL . '/shop/products.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-grid3x3-gap text-warning me-2"></i>Luxury Handbags</h2>
        <span class="text-muted small"><?= count($products) ?> products</span>
    </div>

    <?php if (empty($products)): ?>
    <div class="alert alert-info text-center py-5">
        <i class="bi bi-bag-x display-4 mb-3 d-block"></i>
        No products yet. Add some via phpMyAdmin!
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($products as $p): ?>
        <div class="col-md-4 col-lg-3">
            <div class="card product-card border-0 shadow-sm h-100">
                <?php if (!empty($p['Image'])): ?>
                <img src="<?= e($p['Image']) ?>" class="card-img-top product-img" alt="<?= e($p['ProductName']) ?>">
                <?php else: ?>
                <div class="product-img-placeholder d-flex align-items-center justify-content-center bg-light">
                    <i class="bi bi-bag display-3 text-muted"></i>
                </div>
                <?php endif; ?>
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title"><?= e($p['ProductName']) ?></h6>
                    <p class="text-warning fw-bold mt-auto mb-2">$<?= number_format($p['Price'], 2) ?></p>
                    <?php if (!empty($_SESSION['authenticated'])): ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="product_id" value="<?= $p['ProductID'] ?>">
                        <button type="submit" name="add_to_cart" class="btn btn-warning btn-sm w-100">
                            <i class="bi bi-cart-plus me-1"></i>Add to Cart
                        </button>
                    </form>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-warning btn-sm w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login to Buy
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
