<?php
/**
 * footer.php — Global HTML Footer
 * Included at the bottom of every page.
 */
?>
</main> <footer class="footer py-5 mt-auto">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-4">
                <h5 class="fw-bold text-warning mb-3"><i class="bi bi-bag-heart-fill me-2"></i>LuxCarry</h5>
                <p class="text-muted small">
                    <?= $lang['footer_desc'] ?? 'Premium handbag destination. This is a cybersecurity research environment.' ?>
                </p>
            </div>
            
            <div class="col-md-4">
                <h6 class="fw-bold mb-3"><?= $lang['footer_quick_links'] ?? 'Quick Links' ?></h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/shop/products.php" class="text-muted text-decoration-none"><?= $lang['nav_shop'] ?? 'Shop' ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/auth/login.php" class="text-muted text-decoration-none"><?= $lang['nav_login'] ?? 'Login' ?></a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/auth/register.php" class="text-muted text-decoration-none"><?= $lang['nav_register'] ?? 'Register' ?></a></li>
                </ul>
            </div>
            
            <div class="col-md-4">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-shield-check text-success me-2"></i><?= $lang['footer_sec_research'] ?? 'Security Research' ?>
                </h6>
                <ul class="list-unstyled text-muted small">
                    <li class="mb-2"><?= $lang['footer_mfa_demo'] ?? 'MFA Demo • Brute Force Simulation' ?></li>
                    <li class="mb-2"><?= $lang['footer_cred_analysis'] ?? 'Credential Stuffing Analysis' ?></li>
                    <li><span class="badge bg-danger mt-1"><?= $lang['footer_research_only'] ?? 'Research Only' ?></span></li>
                </ul>
            </div>
        </div>
        
        <hr class="border-secondary my-4">
        
        <div class="text-center text-muted small">
            &copy; <?= date('Y') ?> LuxCarry | <?= $lang['footer_text'] ?? 'Cybersecurity Research Project | Not for commercial use' ?>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>