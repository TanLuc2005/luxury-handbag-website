<?php
/**
 * index.php — Landing Page
 */
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section text-white text-center py-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="bi bi-bag-heart-fill text-warning me-2"></i>LuxCarry
                </h1>
                <p class="lead mb-4">
                    Discover our curated collection of premium luxury handbags.<br>
                    <span class="badge bg-info ms-1">MFA Research Demo</span>
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="<?= BASE_URL ?>/shop/products.php" class="btn btn-warning btn-lg fw-bold">
                        <i class="bi bi-grid3x3-gap me-1"></i>Shop Now
                    </a>
                    <?php if (empty($_SESSION['authenticated'])): ?>
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-person-plus me-1"></i>Create Account
                    </a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Security Features Section -->
<div class="container py-5">
    <h3 class="text-center mb-4 fw-bold">
        <i class="bi bi-shield-check text-warning me-2"></i>Security Features
    </h3>
    <div class="row g-4 text-center">
        <?php
        $features = [
            ['icon' => 'bi-shield-lock-fill text-success', 'title' => 'TOTP MFA', 'desc' => 'Google Authenticator compatible Time-Based OTP for two-factor authentication.'],
            ['icon' => 'bi-lock-fill text-primary', 'title' => 'bcrypt Hashing', 'desc' => 'Passwords hashed with cost-12 bcrypt. Never stored in plain text.'],
            ['icon' => 'bi-person-slash text-danger', 'title' => 'Account Lockout', 'desc' => 'Accounts lock after 5 failed attempts for 10 minutes to block brute force.'],
            ['icon' => 'bi-file-earmark-lock text-warning', 'title' => 'CSRF Protection', 'desc' => 'All forms protected by cryptographic CSRF tokens.'],
            ['icon' => 'bi-key-fill text-info', 'title' => 'PDO Prepared', 'desc' => 'All queries use PDO prepared statements to prevent SQL injection.'],
            ['icon' => 'bi-journal-text text-secondary', 'title' => 'Attack Logging', 'desc' => 'All login events logged with timestamp, IP, username, and result.'],
        ];
        foreach ($features as $f):
        ?>
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 py-3 px-2">
                <i class="bi <?= $f['icon'] ?> display-5 mb-2"></i>
                <h6 class="fw-bold"><?= $f['title'] ?></h6>
                <p class="small text-muted mb-0"><?= $f['desc'] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Research section -->
<div class="bg-dark text-white py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h4><i class="bi bi-mortarboard-fill text-warning me-2"></i>Cybersecurity Research Platform</h4>
                <p class="text-muted mt-2">
                    This platform demonstrates MFA effectiveness against account takeover attacks.
                    Use the Python simulators in <code>/attacks/</code> to observe how
                    brute force and credential stuffing are defeated by TOTP-based MFA.
                </p>
                <div class="d-flex gap-2 justify-content-center flex-wrap mt-3">
                    <span class="badge bg-secondary fs-6">Brute Force Simulation</span>
                    <span class="badge bg-secondary fs-6">Credential Stuffing</span>
                    <span class="badge bg-secondary fs-6">MFA Bypass Resistance</span>
                    <span class="badge bg-secondary fs-6">Login Attempt Logging</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
