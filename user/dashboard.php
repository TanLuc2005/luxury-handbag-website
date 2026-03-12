<?php
/**
 * dashboard.php — User Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'Dashboard';
$db        = getDB();

// Recent orders
$orders = $db->prepare(
    'SELECT o.*, COUNT(oi.OrderItemID) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON o.OrderID = oi.OrderID
     WHERE o.UserID = ?
     GROUP BY o.OrderID
     ORDER BY o.CreatedAt DESC LIMIT 5'
);
$orders->execute([$_SESSION['user_id']]);
$recentOrders = $orders->fetchAll();

// Account info
$stmt = $db->prepare('SELECT * FROM users WHERE UserID = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">

        <!-- Welcome card -->
        <div class="col-12">
            <div class="card border-0 bg-dark text-white shadow">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="avatar-circle bg-warning text-dark">
                        <?= strtoupper(substr($_SESSION['username'], 0, 2)) ?>
                    </div>
                    <div>
                        <h4 class="mb-0">Welcome back, <?= e($_SESSION['username']) ?>!</h4>
                        <p class="mb-0 text-muted small">
                            <?= $_SESSION['mfa_enabled'] ? '🛡️ MFA Active' : '⚠️ MFA Disabled — account less secure' ?>
                        </p>
                    </div>
                    <?php if (!$_SESSION['mfa_enabled']): ?>
                    <a href="<?= BASE_URL ?>/user/enable_mfa.php" class="btn btn-warning ms-auto btn-sm">
                        <i class="bi bi-shield-plus me-1"></i>Enable MFA
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stat cards -->
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm text-center p-3">
                <i class="bi bi-bag-check-fill display-6 text-warning mb-2"></i>
                <h3><?= count($recentOrders) ?></h3>
                <p class="text-muted small mb-0">Orders</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm text-center p-3">
                <i class="bi bi-shield-<?= $_SESSION['mfa_enabled'] ? 'check-fill text-success' : 'x text-danger' ?> display-6 mb-2"></i>
                <h3><?= $_SESSION['mfa_enabled'] ? 'ON' : 'OFF' ?></h3>
                <p class="text-muted small mb-0">MFA Status</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm text-center p-3">
                <i class="bi bi-cart3 display-6 text-info mb-2"></i>
                <h3><?= count($_SESSION['cart'] ?? []) ?></h3>
                <p class="text-muted small mb-0">Cart Items</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm text-center p-3">
                <i class="bi bi-person-check-fill display-6 text-primary mb-2"></i>
                <h3><?= $user['LoginAttempts'] ?></h3>
                <p class="text-muted small mb-0">Failed Logins</p>
            </div>
        </div>

        <!-- MFA security banner -->
        <div class="col-12">
            <?php if ($_SESSION['mfa_enabled']): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-shield-check-fill fs-4"></i>
                <div>
                    <strong>Your account is MFA-protected.</strong>
                    Even if attackers steal your password, they need your authenticator app OTP to log in.
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <div>
                    <strong>MFA is not enabled.</strong>
                    Your account is vulnerable to brute force and credential stuffing attacks.
                    <a href="<?= BASE_URL ?>/user/enable_mfa.php" class="alert-link">Enable MFA now →</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick links -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5><i class="bi bi-lightning-charge text-warning me-1"></i>Quick Actions</h5>
                    <div class="list-group list-group-flush mt-2">
                        <a href="<?= BASE_URL ?>/shop/products.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-grid3x3-gap me-2"></i>Browse Products
                        </a>
                        <a href="<?= BASE_URL ?>/shop/cart.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-cart3 me-2"></i>View Cart
                        </a>
                        <a href="<?= BASE_URL ?>/user/profile.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-gear me-2"></i>Account Settings
                        </a>
                        <a href="<?= BASE_URL ?>/user/enable_mfa.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-shield-lock me-2"></i>MFA Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account info -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5><i class="bi bi-person-lines-fill text-warning me-1"></i>Account Info</h5>
                    <table class="table table-sm mt-2">
                        <tr><th>Username</th><td><?= e($user['Username']) ?></td></tr>
                        <tr><th>Email</th><td><?= e($user['Email']) ?></td></tr>
                        <tr><th>Member since</th><td><?= date('M j, Y', strtotime($user['CreatedAt'])) ?></td></tr>
                        <tr>
                            <th>MFA</th>
                            <td>
                                <?php if ($user['IsMFAEnabled']): ?>
                                <span class="badge bg-success"><i class="bi bi-shield-check"></i> Enabled</span>
                                <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-shield-x"></i> Disabled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
