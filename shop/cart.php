<?php
/**
 * cart.php — Shopping Cart
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'Shopping Cart';
$db        = getDB();

// ── Cart Actions ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    $action    = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'remove' && isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        setFlash('info', 'Item removed from cart.');
    }
    if ($action === 'update' && isset($_SESSION['cart'][$productId])) {
        $qty = max(0, (int)$_POST['qty']);
        if ($qty === 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = $qty;
        }
    }
    if ($action === 'checkout' && !empty($_SESSION['cart'])) {
        // Build order
        $total = 0;
        foreach ($_SESSION['cart'] as $pid => $qty) {
            $p = $db->prepare('SELECT Price FROM products WHERE ProductID = ?');
            $p->execute([$pid]);
            $row = $p->fetch();
            if ($row) $total += $row['Price'] * $qty;
        }

        $db->prepare('INSERT INTO orders (UserID, Total) VALUES (?, ?)')
           ->execute([$_SESSION['user_id'], $total]);
        $orderId = $db->lastInsertId();

        // Insert order items
        foreach ($_SESSION['cart'] as $pid => $qty) {
            $p = $db->prepare('SELECT Price FROM products WHERE ProductID = ?');
            $p->execute([$pid]);
            $row = $p->fetch();
            if ($row) {
                $db->prepare(
                    'INSERT INTO order_items (OrderID, ProductID, Quantity, UnitPrice) VALUES (?,?,?,?)'
                )->execute([$orderId, $pid, $qty, $row['Price']]);
            }
        }

        $_SESSION['cart'] = [];
        setFlash('success', "Order #$orderId placed successfully! Total: \$" . number_format($total, 2));
        
        // Chuyển hướng về lại trang giỏ hàng thay vì dashboard
        header('Location: ' . BASE_URL . '/shop/cart.php');
        exit;
    }

    header('Location: ' . BASE_URL . '/shop/cart.php');
    exit;
}

// ── Load cart items ───────────────────────────────────────────────────────────
$cartItems = [];
$cartTotal = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $productId => $qty) {
        $stmt = $db->prepare('SELECT * FROM products WHERE ProductID = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if ($product) {
            $product['qty']      = $qty;
            $product['subtotal'] = $product['Price'] * $qty;
            $cartTotal          += $product['subtotal'];
            $cartItems[]         = $product;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4"><i class="bi bi-cart3 text-warning me-2"></i>Your Cart</h2>

    <?php if (empty($cartItems)): ?>
    <div class="text-center py-5">
        <i class="bi bi-cart-x display-1 text-muted mb-3 d-block"></i>
        <h5 class="text-muted">Your cart is empty</h5>
        <a href="<?= BASE_URL ?>/shop/products.php" class="btn btn-warning mt-2">
            <i class="bi bi-grid3x3-gap me-1"></i>Browse Products
        </a>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td class="align-middle">
                                <strong><?= e($item['ProductName']) ?></strong>
                            </td>
                            <td class="align-middle">$<?= number_format($item['Price'], 2) ?></td>
                            <td class="align-middle" style="width:100px">
                                <form method="POST" class="d-flex gap-1">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= $item['ProductID'] ?>">
                                    <input type="number" name="qty" value="<?= $item['qty'] ?>"
                                           min="0" max="99" class="form-control form-control-sm"
                                           onchange="this.form.submit()">
                                </form>
                            </td>
                            <td class="align-middle">$<?= number_format($item['subtotal'], 2) ?></td>
                            <td class="align-middle">
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= $item['ProductID'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">Order Summary</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Items (<?= count($cartItems) ?>)</span>
                        <span>$<?= number_format($cartTotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Shipping</span>
                        <span class="text-success">Free</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                        <span>Total</span>
                        <span class="text-warning">$<?= number_format($cartTotal, 2) ?></span>
                    </div>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-warning w-100 fw-bold py-2">
                            <i class="bi bi-credit-card me-1"></i>Place Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>