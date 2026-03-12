<?php
/**
 * header.php — Global HTML Header & Navigation
 * Included at the top of every page.
 */
require_once __DIR__ . '/auth.php';

$isLoggedIn = !empty($_SESSION['user_id']) && !empty($_SESSION['authenticated']);
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>LuxCarry | Luxury Handbags</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- ── Security Research Banner ─────────────────────────────────── -->
<div class="research-banner text-center py-1">
    <small><i class="bi bi-shield-lock-fill"></i> <strong>CYBERSECURITY RESEARCH ENVIRONMENT</strong> — For educational purposes only</small>
</div>

<!-- ── Navigation ───────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/">
            <i class="bi bi-bag-heart-fill text-warning me-1"></i>LuxCarry
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/shop/products.php">
                        <i class="bi bi-grid3x3-gap"></i> Shop
                    </a>
                </li>
                <?php if ($isLoggedIn): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'cart.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/shop/cart.php">
                        <i class="bi bi-cart3"></i> Cart
                        <?php if (!empty($_SESSION['cart'])): ?>
                        <span class="badge bg-warning text-dark"><?= count($_SESSION['cart']) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?= e($_SESSION['username'] ?? 'Account') ?>
                            <?php if (!empty($_SESSION['mfa_enabled'])): ?>
                                <span class="badge bg-success ms-1" title="MFA Active">
                                    <i class="bi bi-shield-check"></i>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/profile.php">
                                <i class="bi bi-gear me-1"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/enable_mfa.php">
                                <i class="bi bi-shield-lock me-1"></i>MFA Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'login.php' ? 'active' : '' ?>"
                           href="<?= BASE_URL ?>/auth/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-warning btn-sm text-dark px-3 ms-2"
                           href="<?= BASE_URL ?>/auth/register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ── Flash Messages ────────────────────────────────────────────── -->
<?php $flash = getFlash(); if ($flash): ?>
<div class="container mt-3"><?= $flash ?></div>
<?php endif; ?>

<!-- ── Main Content Wrapper ─────────────────────────────────────── -->
<main class="main-content">
