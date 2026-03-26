<?php
/**
 * footer.php — Global HTML Footer
 */
?>
</main><!-- /main-content -->

<footer class="footer bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h6 class="text-warning"><i class="bi bi-bag-heart-fill me-1"></i>LuxCarry</h6>
                <p class="text-muted small">Premium handbag destination.<br>This is a cybersecurity research.</p>
            </div>
            <div class="col-md-4">
                <h6>Quick Links</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= BASE_URL ?>/shop/products.php" class="text-muted text-decoration-none">Shop</a></li>
                    <li><a href="<?= BASE_URL ?>/auth/login.php" class="text-muted text-decoration-none">Login</a></li>
                    <li><a href="<?= BASE_URL ?>/auth/register.php" class="text-muted text-decoration-none">Register</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6><i class="bi bi-shield-fill-check text-success me-1"></i>Security Research</h6>
                <p class="text-muted small">
                    MFA Demo &bull; Brute Force Simulation<br>
                    Credential Stuffing Analysis<br>
                    <span class="badge bg-danger">Research Only</span>
                </p>
            </div>
        </div>
        <hr class="border-secondary">
        <p class="text-center text-muted small mb-0">
            &copy; <?= date('Y') ?> LuxCarry | Cybersecurity Research Project |
            <strong>Not for commercial use</strong>
        </p>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
