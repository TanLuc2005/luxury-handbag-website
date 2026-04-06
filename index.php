<?php
/**
 * index.php — LuxCarry Landing Page
 */
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-section text-center py-5">
    <div class="container py-5">
        <h1 class="display-4 fw-bold text-white mb-3">
            <i class="bi bi-bag-heart-fill text-warning me-2"></i>LuxCarry
        </h1>
        <p class="lead mb-4 mx-auto" style="max-width: 600px;">
            <?= $lang['hero_subtitle'] ?? 'Discover our curated collection of premium luxury handbags.' ?>
        </p>
        <div class="mb-4">
            <span class="badge bg-info text-dark px-3 py-2 rounded-pill">
                <?= $lang['mfa_demo_badge'] ?? 'MFA Research' ?>
            </span>
        </div>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?= BASE_URL ?>/shop/products.php" class="btn btn-warning btn-lg px-4 fw-bold">
                <?= $lang['btn_shop_now'] ?? 'Shop Now' ?>
            </a>
            <?php if (!empty($_SESSION['authenticated'])): ?>
                <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-speedometer2 me-2"></i><?= $lang['btn_dashboard'] ?? 'Dashboard' ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold"><i class="bi bi-shield-check text-warning me-2"></i><?= $lang['sec_features_title'] ?? 'Security Features' ?></h2>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm text-center p-3">
                    <div class="card-body">
                        <i class="bi bi-shield-lock-fill display-4 text-success mb-3"></i>
                        <h5 class="card-title fw-bold"><?= $lang['feat_mfa_title'] ?? 'Email MFA' ?></h5>
                        <p class="card-text text-muted small"><?= $lang['feat_mfa_desc'] ?? 'One-Time Passwords delivered securely via email for two-factor authentication.' ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm text-center p-3">
                    <div class="card-body">
                        <i class="bi bi-lock-fill display-4 text-primary mb-3"></i>
                        <h5 class="card-title fw-bold"><?= $lang['feat_hash_title'] ?? 'bcrypt Hashing' ?></h5>
                        <p class="card-text text-muted small"><?= $lang['feat_hash_desc'] ?? 'Passwords hashed with cost-12 bcrypt. Never stored in plain text.' ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm text-center p-3">
                    <div class="card-body">
                        <i class="bi bi-person-x-fill display-4 text-danger mb-3"></i>
                        <h5 class="card-title fw-bold"><?= $lang['feat_lock_title'] ?? 'Account Lockout' ?></h5>
                        <p class="card-text text-muted small"><?= $lang['feat_lock_desc'] ?? 'Accounts lock after 5 failed attempts for 5 minutes to block brute force.' ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm text-center p-3">
                    <div class="card-body">
                        <i class="bi bi-file-earmark-lock-fill display-4 text-warning mb-3"></i>
                        <h5 class="card-title fw-bold"><?= $lang['feat_csrf_title'] ?? 'CSRF Protection' ?></h5>
                        <p class="card-text text-muted small"><?= $lang['feat_csrf_desc'] ?? 'All forms protected by cryptographic CSRF tokens.' ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm text-center p-3">
                    <div class="card-body">
                        <i class="bi bi-key-fill display-4 text-info mb-3"></i>
                        <h5 class="card-title fw-bold"><?= $lang['feat_pdo_title'] ?? 'PDO Prepared' ?></h5>
                        <p class="card-text text-muted small"><?= $lang['feat_pdo_desc'] ?? 'All queries use PDO prepared statements to prevent SQL injection.' ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm text-center p-3">
                    <div class="card-body">
                        <i class="bi bi-card-list display-4 text-secondary mb-3"></i>
                        <h5 class="card-title fw-bold"><?= $lang['feat_log_title'] ?? 'Attack Logging' ?></h5>
                        <p class="card-text text-muted small"><?= $lang['feat_log_desc'] ?? 'All login events logged with timestamp, IP, username, and result.' ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-dark text-white text-center">
    <div class="container py-4">
        <h3 class="mb-3 fw-bold"><i class="bi bi-mortarboard-fill text-warning me-2"></i><?= $lang['res_platform_title'] ?? 'Cybersecurity Research Platform' ?></h3>
        <p class="lead fs-6 mx-auto text-light mb-4" style="max-width: 800px;">
            <?= $lang['res_platform_desc'] ?? 'This platform demonstrates MFA effectiveness against account takeover attacks. Use the Python simulators to observe how brute force and credential stuffing are defeated by Email-based MFA.' ?>
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <span class="badge bg-secondary px-3 py-2"><?= $lang['tag_brute_force'] ?? 'Brute Force Simulation' ?></span>
            <span class="badge bg-secondary px-3 py-2"><?= $lang['tag_cred_stuffing'] ?? 'Credential Stuffing' ?></span>
            <span class="badge bg-secondary px-3 py-2"><?= $lang['tag_mfa_bypass'] ?? 'MFA Bypass Resistance' ?></span>
            <span class="badge bg-secondary px-3 py-2"><?= $lang['tag_logging'] ?? 'Login Attempt Logging' ?></span>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>